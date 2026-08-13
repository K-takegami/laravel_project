<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TwoFactorCodeRepository
{
    /**
     * Persist a newly generated two-factor code on the given authenticatable model.
     */
    public function assignCode(Model $authenticatable, string $hashedCode, Carbon $expiresAt): void
    {
        $authenticatable->forceFill([
            'two_factor_code' => $hashedCode,
            'two_factor_expires_at' => $expiresAt,
        ])->save();
    }

    /**
     * Clear a previously issued two-factor code from the given authenticatable model.
     */
    public function clearCode(Model $authenticatable): void
    {
        $authenticatable->forceFill([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();
    }

    /**
     * Persist a "remember this device" token on the given authenticatable model,
     * allowing two-factor authentication to be skipped until it expires.
     */
    public function assignRememberToken(Model $authenticatable, string $hashedToken, Carbon $expiresAt): void
    {
        $authenticatable->forceFill([
            'two_factor_remember_token' => $hashedToken,
            'two_factor_remember_expires_at' => $expiresAt,
        ])->save();
    }
}
