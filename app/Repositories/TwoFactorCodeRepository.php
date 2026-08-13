<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * 2段階認証コード・「端末を記憶するトークン」に関するDB更新を集約するRepository。
 *
 * `User`・`Admin` のどちらのモデルも同じカラム構成を持つため、
 * `Model` 型で受け取り共通の実装で両者に対応する。
 * ここでのみ `save()`(SQLのUPDATE発行)を行い、他クラスからは直接呼び出さない。
 */
class TwoFactorCodeRepository
{
    /**
     * 新しく生成した2段階認証コード(ハッシュ値)と有効期限を保存する。
     *
     * @param  Model  $authenticatable  対象のUser/Adminモデルインスタンス
     * @param  string  $hashedCode  ハッシュ化済みの認証コード
     * @param  Carbon  $expiresAt  認証コードの有効期限
     * @return void
     */
    public function assignCode(Model $authenticatable, string $hashedCode, Carbon $expiresAt): void
    {
        $authenticatable->forceFill([
            'two_factor_code' => $hashedCode,
            'two_factor_expires_at' => $expiresAt,
        ])->save();
    }

    /**
     * 使用済み・不要になった2段階認証コードをクリアする。
     *
     * @param  Model  $authenticatable  対象のUser/Adminモデルインスタンス
     * @return void
     */
    public function clearCode(Model $authenticatable): void
    {
        $authenticatable->forceFill([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();
    }

    /**
     * 「端末を記憶するトークン」(ハッシュ値)と有効期限を保存する。
     *
     * 保存後は、有効期限内であれば次回ログイン時に2段階認証を省略できるようになる。
     *
     * @param  Model  $authenticatable  対象のUser/Adminモデルインスタンス
     * @param  string  $hashedToken  ハッシュ化済みの記憶トークン
     * @param  Carbon  $expiresAt  記憶トークンの有効期限
     * @return void
     */
    public function assignRememberToken(Model $authenticatable, string $hashedToken, Carbon $expiresAt): void
    {
        $authenticatable->forceFill([
            'two_factor_remember_token' => $hashedToken,
            'two_factor_remember_expires_at' => $expiresAt,
        ])->save();
    }
}
