<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Notifications\TwoFactorCodeNotification;
use App\Repositories\TwoFactorCodeRepository;
use App\Repositories\UserRepository;
use App\Services\TwoFactorCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Name of the cookie used to remember a device as two-factor verified.
     */
    private const REMEMBER_COOKIE = 'user_2fa_remember';

    /**
     * Number of minutes the "remember this device" cookie remains valid.
     */
    private const REMEMBER_TTL_MINUTES = 60 * 24;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TwoFactorCodeRepository $twoFactorCodeRepository,
        private readonly TwoFactorCodeService $twoFactorCodeService,
    ) {
    }

    public function showLogin(): View
    {
        return view('user.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = $this->userRepository->findByEmail($request->string('email')->toString());

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。']);
        }

        $rememberToken = $request->cookie(self::REMEMBER_COOKIE);

        if ($rememberToken !== null && $this->twoFactorCodeService->isValid($user->two_factor_remember_token, $user->two_factor_remember_expires_at, $rememberToken)) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return redirect()->route('user.home');
        }

        $code = $this->twoFactorCodeService->generate();
        $this->twoFactorCodeRepository->assignCode($user, $code['hashed'], $code['expiresAt']);
        $user->notify(new TwoFactorCodeNotification($code['plain']));

        session(['pending_2fa' => ['guard' => 'web', 'id' => $user->id]]);

        return redirect()->route('user.login.verify');
    }

    public function showVerify(): View|RedirectResponse
    {
        if (! $this->hasPendingTwoFactor()) {
            return redirect()->route('user.login');
        }

        return view('user.verify');
    }

    public function verify(VerifyCodeRequest $request): RedirectResponse
    {
        if (! $this->hasPendingTwoFactor()) {
            return redirect()->route('user.login');
        }

        $user = $this->userRepository->findById(session('pending_2fa.id'));

        if (! $user) {
            session()->forget('pending_2fa');

            return redirect()->route('user.login');
        }

        if (! $this->twoFactorCodeService->isValid($user->two_factor_code, $user->two_factor_expires_at, $request->string('code')->toString())) {
            return back()->withErrors(['code' => '認証コードが正しくないか、有効期限が切れています。']);
        }

        $this->twoFactorCodeRepository->clearCode($user);

        $remember = $this->twoFactorCodeService->generateRememberToken();
        $this->twoFactorCodeRepository->assignRememberToken($user, $remember['hashed'], $remember['expiresAt']);

        session()->forget('pending_2fa');

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('user.home')
            ->withCookie(cookie(self::REMEMBER_COOKIE, $remember['plain'], self::REMEMBER_TTL_MINUTES, httpOnly: true));
    }

    public function logout(): RedirectResponse
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('user.login');
    }

    private function hasPendingTwoFactor(): bool
    {
        return session('pending_2fa.guard') === 'web';
    }
}
