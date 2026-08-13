<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Notifications\TwoFactorCodeNotification;
use App\Repositories\TwoFactorCodeRepository;
use App\Services\TwoFactorCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_sends_code_and_redirects_to_verify(): void
    {
        Notification::fake();

        $admin = Admin::factory()->create(['password' => 'password']);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.login.verify'));
        Notification::assertSentTo($admin, TwoFactorCodeNotification::class);
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_login_with_invalid_credentials_is_rejected(): void
    {
        $admin = Admin::factory()->create(['password' => 'password']);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_verify_with_correct_code_logs_in_and_redirects_home(): void
    {
        $admin = Admin::factory()->create();
        $code = app(TwoFactorCodeService::class)->generate();
        app(TwoFactorCodeRepository::class)->assignCode($admin, $code['hashed'], $code['expiresAt']);

        $this->withSession(['pending_2fa' => ['admin' => $admin->id]]);

        $response = $this->post('/admin/login/verify', [
            'code' => $code['plain'],
        ]);

        $response->assertRedirect(route('admin.home'));
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_verify_with_incorrect_code_does_not_log_in(): void
    {
        $admin = Admin::factory()->create();
        $code = app(TwoFactorCodeService::class)->generate();
        app(TwoFactorCodeRepository::class)->assignCode($admin, $code['hashed'], $code['expiresAt']);

        $this->withSession(['pending_2fa' => ['admin' => $admin->id]]);

        $response = $this->post('/admin/login/verify', [
            'code' => '000000',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_home_requires_authentication(): void
    {
        $response = $this->get('/admin/home');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_login_skips_two_factor_when_valid_remember_cookie_present(): void
    {
        Notification::fake();

        $admin = Admin::factory()->create(['password' => 'password']);
        $remember = app(TwoFactorCodeService::class)->generateRememberToken();
        app(TwoFactorCodeRepository::class)->assignRememberToken($admin, $remember['hashed'], $remember['expiresAt']);

        $loginResponse = $this->withCookie('admin_2fa_remember', $remember['plain'])
            ->post('/admin/login', [
                'email' => $admin->email,
                'password' => 'password',
            ]);

        $loginResponse->assertRedirect(route('admin.home'));
        $this->assertAuthenticatedAs($admin, 'admin');
        Notification::assertNothingSent();
    }
}
