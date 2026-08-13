<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorCodeService
{
    /**
     * Number of minutes a generated code remains valid.
     */
    private const CODE_TTL_MINUTES = 10;

    /**
     * Number of hours a "remember this device" token remains valid.
     */
    private const REMEMBER_TTL_HOURS = 24;

    /**
     * Generate a new plaintext/hashed two-factor code pair with its expiry.
     *
     * @return array{plain: string, hashed: string, expiresAt: Carbon}
     */
    public function generate(): array
    {
        $plain = (string) random_int(100000, 999999);

        return [
            'plain' => $plain,
            'hashed' => Hash::make($plain),
            'expiresAt' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ];
    }

    /**
     * Generate a new plaintext/hashed "remember this device" token pair with its expiry.
     *
     * @return array{plain: string, hashed: string, expiresAt: Carbon}
     */
    public function generateRememberToken(): array
    {
        $plain = Str::random(64);

        return [
            'plain' => $plain,
            'hashed' => Hash::make($plain),
            'expiresAt' => now()->addHours(self::REMEMBER_TTL_HOURS),
        ];
    }

    /**
     * Determine whether the given input value matches the stored hashed value
     * and has not yet expired. Used for both two-factor codes and
     * "remember this device" tokens.
     */
    public function isValid(?string $hashedValue, ?Carbon $expiresAt, string $inputValue): bool
    {
        if ($hashedValue === null || $expiresAt === null) {
            return false;
        }

        if ($expiresAt->isPast()) {
            return false;
        }

        return Hash::check($inputValue, $hashedValue);
    }
}
