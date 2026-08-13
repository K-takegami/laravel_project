<?php

namespace App\Repositories;

use App\Models\User;

/**
 * 一般ユーザー(`users`テーブル)への検索クエリを集約するRepository。
 *
 * コントローラーやServiceから直接Eloquentクエリを呼び出さず、
 * 必ずこのクラスを経由してDBアクセスを行う。
 */
class UserRepository
{
    /**
     * メールアドレスからユーザーを検索する(ログイン時の資格情報確認に使用)。
     *
     * @param  string  $email  検索対象のメールアドレス
     * @return User|null  該当するユーザー(存在しない場合はnull)
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * IDからユーザーを検索する(2段階認証コード検証時に使用)。
     *
     * @param  int  $id  検索対象のユーザーID
     * @return User|null  該当するユーザー(存在しない場合はnull)
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }
}
