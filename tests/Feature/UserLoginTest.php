<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Repositories\TwoFactorCodeRepository;
use App\Services\TwoFactorCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_sends_code_and_redirects_to_verify(): void
    {
        Notification::fake();

        $user = User::factory()->create(['password' => 'password']);

        $response = $this->post('/user/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('user.login.verify'));
        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_login_with_invalid_credentials_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->post('/user/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_verify_with_correct_code_logs_in_and_redirects_home(): void
    {
        $user = User::factory()->create();
        $code = app(TwoFactorCodeService::class)->generate();
        app(TwoFactorCodeRepository::class)->assignCode($user, $code['hashed'], $code['expiresAt']);

        $this->withSession(['pending_2fa' => ['guard' => 'web', 'id' => $user->id]]);

        $response = $this->post('/user/login/verify', [
            'code' => $code['plain'],
        ]);

        $response->assertRedirect(route('user.home'));
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_verify_with_incorrect_code_does_not_log_in(): void
    {
        $user = User::factory()->create();
        $code = app(TwoFactorCodeService::class)->generate();
        app(TwoFactorCodeRepository::class)->assignCode($user, $code['hashed'], $code['expiresAt']);

        $this->withSession(['pending_2fa' => ['guard' => 'web', 'id' => $user->id]]);

        $response = $this->post('/user/login/verify', [
            'code' => '000000',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_home_requires_authentication(): void
    {
        $response = $this->get('/user/home');

        $response->assertRedirect(route('user.login'));
    }

    public function test_login_skips_two_factor_when_valid_remember_cookie_present(): void
    {
        Notification::fake();

        $user = User::factory()->create(['password' => 'password']);
        $remember = app(TwoFactorCodeService::class)->generateRememberToken();
        app(TwoFactorCodeRepository::class)->assignRememberToken($user, $remember['hashed'], $remember['expiresAt']);

        $loginResponse = $this->withCookie('user_2fa_remember', $remember['plain'])
            ->post('/user/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $loginResponse->assertRedirect(route('user.home'));
        $this->assertAuthenticatedAs($user, 'web');
        Notification::assertNothingSent();
    }
}
