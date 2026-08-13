<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 2段階認証コード・「端末を記憶するトークン」の生成/検証ロジックを担当するService。
 *
 * DBへのアクセスは一切行わない(検索・保存は必ず Repository を経由する)。
 * ここでは平文/ハッシュ値の生成と、ハッシュ+有効期限の一致判定のみを行う。
 */
class TwoFactorCodeService
{
    /**
     * 生成した認証コードが有効な分数。
     */
    private const CODE_TTL_MINUTES = 10;

    /**
     * 「端末を記憶するトークン」が有効な時間数。
     */
    private const REMEMBER_TTL_HOURS = 24;

    /**
     * 6桁の数字コードと、そのハッシュ値・有効期限を生成する。
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
     * 「端末を記憶するトークン」(64文字のランダム文字列)と、そのハッシュ値・有効期限を生成する。
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
     * 入力値がDBに保存されたハッシュ値と一致し、かつ有効期限内かどうかを判定する。
     *
     * 認証コード・記憶トークンのどちらの検証にも共通して使用する
     * (どちらも「ハッシュ値との一致 + 有効期限」という同じ検証ロジックのため)。
     *
     * @param  string|null  $hashedValue  DBに保存されているハッシュ値(未発行の場合はnull)
     * @param  \Illuminate\Support\Carbon|null  $expiresAt  有効期限(未発行の場合はnull)
     * @param  string  $inputValue  ユーザーが入力した平文の値(コードまたはCookieのトークン)
     * @return bool  ハッシュが一致し、かつ有効期限内であれば true
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
