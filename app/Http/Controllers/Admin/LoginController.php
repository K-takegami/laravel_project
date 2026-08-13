<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Notifications\TwoFactorCodeNotification;
use App\Repositories\AdminRepository;
use App\Repositories\TwoFactorCodeRepository;
use App\Services\TwoFactorCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * 管理者向けのログイン・メール2段階認証・ログアウトを担当するコントローラー。
 *
 * ユーザー向け({@see \App\Http\Controllers\User\LoginController})と同じ流れだが、
 * `admin` ガード・`Admin` モデルに対して動作する点のみが異なる。
 */
class LoginController extends Controller
{
    /**
     * 「この端末では2段階認証を省略する」ことを記憶するCookie名。
     */
    private const REMEMBER_COOKIE = 'admin_2fa_remember';

    /**
     * 上記Cookieの有効期間(分)。24時間。
     */
    private const REMEMBER_TTL_MINUTES = 60 * 24;

    /**
     * タイミング攻撃対策用のダミーハッシュ値(実在のパスワードではない)。
     *
     * 管理者が存在しない場合でもこの値でHash::checkを実行し、
     * 「存在しないメールアドレスは即座に、存在するメールアドレスは
     * bcryptの検証時間だけ遅れて」応答が返る、という応答速度の差を無くす。
     */
    private const DUMMY_PASSWORD_HASH = '$2y$10$HLWBPuTLbffDbjaiui.Nnuq6aduZk8RvqJSJIoC9HRzO/V8VPpxY2';

    /**
     * コンストラクタ。DBアクセスを担当するRepositoryと、生成/検証ロジックを担当するServiceを注入する。
     *
     * @param  AdminRepository  $adminRepository  管理者の検索を担当するRepository
     * @param  TwoFactorCodeRepository  $twoFactorCodeRepository  2段階認証コード・記憶トークンの保存を担当するRepository
     * @param  TwoFactorCodeService  $twoFactorCodeService  コード・トークンの生成/検証ロジックを担当するService(DBアクセスなし)
     * @return void
     */
    public function __construct(
        private readonly AdminRepository $adminRepository,
        private readonly TwoFactorCodeRepository $twoFactorCodeRepository,
        private readonly TwoFactorCodeService $twoFactorCodeService,
    ) {
    }

    /**
     * ログインフォームを表示する。
     *
     * @return View ログインフォームのビュー
     */
    public function showLogin(): View
    {
        return view('admin.login');
    }

    /**
     * メールアドレス・パスワードを検証し、2段階認証の開始(またはスキップ)を行う。
     *
     * 「端末を記憶するCookie」が有効な場合は2段階認証を省略してそのままログインさせ、
     * それ以外の場合は認証コードを生成してメール送信し、コード入力画面へ遷移させる。
     *
     * @param  LoginRequest  $request  メールアドレス・パスワードを含むバリデーション済みリクエスト
     * @return RedirectResponse ホーム画面・コード入力画面・ログイン画面(エラー時)のいずれかへのリダイレクト
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $admin = $this->adminRepository->findByEmail($request->string('email')->toString());

        // タイミング攻撃対策: 管理者が存在しない場合もダミーハッシュと比較し、応答時間を揃える
        $passwordMatches = Hash::check(
            $request->string('password')->toString(),
            $admin->password ?? self::DUMMY_PASSWORD_HASH,
        );

        if (! $admin || ! $passwordMatches) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。']);
        }

        // 有効な「端末を記憶するCookie」があれば2段階認証をスキップして即ログインする
        $rememberToken = $request->cookie(self::REMEMBER_COOKIE);

        if ($rememberToken !== null && $this->twoFactorCodeService->isValid($admin->two_factor_remember_token, $admin->two_factor_remember_expires_at, $rememberToken)) {
            Auth::guard('admin')->login($admin);
            $request->session()->regenerate();

            return redirect()->route('admin.home');
        }

        // 通常の2段階認証フロー: コードを生成してDBに保存し、メールで送信する
        $code = $this->twoFactorCodeService->generate();
        $this->twoFactorCodeRepository->assignCode($admin, $code['hashed'], $code['expiresAt']);
        $admin->notify(new TwoFactorCodeNotification($code['plain']));

        // まだ本ログインは確立せず、認証待ち状態をセッションに保持する
        // (キーをガードごとに分けることで、同じブラウザでuser/adminのログインを
        //  同時に進めても互いの認証待ち状態を上書きしないようにする)
        session(['pending_2fa.admin' => $admin->id]);

        return redirect()->route('admin.login.verify');
    }

    /**
     * 認証コード入力フォームを表示する。
     *
     * パスワード認証を経ていない場合(pending_2faが無い場合)はログイン画面へ戻す。
     *
     * @return View|RedirectResponse コード入力フォーム、またはログイン画面へのリダイレクト
     */
    public function showVerify(): View|RedirectResponse
    {
        if (! $this->hasPendingTwoFactor()) {
            return redirect()->route('admin.login');
        }

        return view('admin.verify');
    }

    /**
     * 入力された認証コードを検証し、成功時に本ログインを確立する。
     *
     * 成功時は「端末を記憶するCookie」も新たに発行し、次回以降24時間は
     * 2段階認証を省略できるようにする。
     *
     * @param  VerifyCodeRequest  $request  認証コードを含むバリデーション済みリクエスト
     * @return RedirectResponse ホーム画面へのリダイレクト、またはエラー時はコード入力画面への差し戻し
     */
    public function verify(VerifyCodeRequest $request): RedirectResponse
    {
        if (! $this->hasPendingTwoFactor()) {
            return redirect()->route('admin.login');
        }

        $admin = $this->adminRepository->findById(session('pending_2fa.admin'));

        if (! $admin) {
            session()->forget('pending_2fa.admin');

            return redirect()->route('admin.login');
        }

        if (! $this->twoFactorCodeService->isValid($admin->two_factor_code, $admin->two_factor_expires_at, $request->string('code')->toString())) {
            return back()->withErrors(['code' => '認証コードが正しくないか、有効期限が切れています。']);
        }

        // 使用済みの認証コードをクリアする
        $this->twoFactorCodeRepository->clearCode($admin);

        // 「端末を記憶するトークン」を新たに発行し、DBには常にハッシュ化した値のみ保存する
        $remember = $this->twoFactorCodeService->generateRememberToken();
        $this->twoFactorCodeRepository->assignRememberToken($admin, $remember['hashed'], $remember['expiresAt']);

        session()->forget('pending_2fa.admin');

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        // Cookieには平文トークンを乗せる(JSから読めないようhttpOnly、Laravelの暗号化ミドルウェアで自動的に暗号化される)
        return redirect()->route('admin.home')
            ->withCookie(cookie(self::REMEMBER_COOKIE, $remember['plain'], self::REMEMBER_TTL_MINUTES, httpOnly: true));
    }

    /**
     * ログアウトし、セッションを完全に破棄する。
     *
     * 「端末を記憶するCookie」はここではクリアしない(端末の信頼状態はログアウトとは別に24時間維持する仕様のため)。
     *
     * @return RedirectResponse ログイン画面へのリダイレクト
     */
    public function logout(): RedirectResponse
    {
        Auth::guard('admin')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * パスワード認証済み・2段階認証待ちの状態かどうかを判定する。
     *
     * @return bool 認証待ち状態であれば true
     */
    private function hasPendingTwoFactor(): bool
    {
        return session()->has('pending_2fa.admin');
    }
}
