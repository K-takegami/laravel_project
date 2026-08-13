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

class LoginController extends Controller
{
    /**
     * Name of the cookie used to remember a device as two-factor verified.
     */
    private const REMEMBER_COOKIE = 'admin_2fa_remember';

    /**
     * Number of minutes the "remember this device" cookie remains valid.
     */
    private const REMEMBER_TTL_MINUTES = 60 * 24;

    public function __construct(
        private readonly AdminRepository $adminRepository,
        private readonly TwoFactorCodeRepository $twoFactorCodeRepository,
        private readonly TwoFactorCodeService $twoFactorCodeService,
    ) {
    }

    public function showLogin(): View
    {
        return view('admin.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $admin = $this->adminRepository->findByEmail($request->string('email')->toString());

        if (! $admin || ! Hash::check($request->string('password')->toString(), $admin->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。']);
        }

        $rememberToken = $request->cookie(self::REMEMBER_COOKIE);

        if ($rememberToken !== null && $this->twoFactorCodeService->isValid($admin->two_factor_remember_token, $admin->two_factor_remember_expires_at, $rememberToken)) {
            Auth::guard('admin')->login($admin);
            $request->session()->regenerate();

            return redirect()->route('admin.home');
        }

        $code = $this->twoFactorCodeService->generate();
        $this->twoFactorCodeRepository->assignCode($admin, $code['hashed'], $code['expiresAt']);
        $admin->notify(new TwoFactorCodeNotification($code['plain']));

        session(['pending_2fa' => ['guard' => 'admin', 'id' => $admin->id]]);

        return redirect()->route('admin.login.verify');
    }

    public function showVerify(): View|RedirectResponse
    {
        if (! $this->hasPendingTwoFactor()) {
            return redirect()->route('admin.login');
        }

        return view('admin.verify');
    }

    public function verify(VerifyCodeRequest $request): RedirectResponse
    {
        if (! $this->hasPendingTwoFactor()) {
            return redirect()->route('admin.login');
        }

        $admin = $this->adminRepository->findById(session('pending_2fa.id'));

        if (! $admin) {
            session()->forget('pending_2fa');

            return redirect()->route('admin.login');
        }

        if (! $this->twoFactorCodeService->isValid($admin->two_factor_code, $admin->two_factor_expires_at, $request->string('code')->toString())) {
            return back()->withErrors(['code' => '認証コードが正しくないか、有効期限が切れています。']);
        }

        $this->twoFactorCodeRepository->clearCode($admin);

        $remember = $this->twoFactorCodeService->generateRememberToken();
        $this->twoFactorCodeRepository->assignRememberToken($admin, $remember['hashed'], $remember['expiresAt']);

        session()->forget('pending_2fa');

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.home')
            ->withCookie(cookie(self::REMEMBER_COOKIE, $remember['plain'], self::REMEMBER_TTL_MINUTES, httpOnly: true));
    }

    public function logout(): RedirectResponse
    {
        Auth::guard('admin')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function hasPendingTwoFactor(): bool
    {
        return session('pending_2fa.guard') === 'admin';
    }
}
