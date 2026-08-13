<?php

namespace App\Repositories;

use App\Models\Admin;

/**
 * 管理者(`admins`テーブル)への検索クエリを集約するRepository。
 *
 * コントローラーやServiceから直接Eloquentクエリを呼び出さず、
 * 必ずこのクラスを経由してDBアクセスを行う。
 */
class AdminRepository
{
    /**
     * メールアドレスから管理者を検索する(ログイン時の資格情報確認に使用)。
     *
     * @param  string  $email  検索対象のメールアドレス
     * @return Admin|null  該当する管理者(存在しない場合はnull)
     */
    public function findByEmail(string $email): ?Admin
    {
        return Admin::where('email', $email)->first();
    }

    /**
     * IDから管理者を検索する(2段階認証コード検証時に使用)。
     *
     * @param  int  $id  検索対象の管理者ID
     * @return Admin|null  該当する管理者(存在しない場合はnull)
     */
    public function findById(int $id): ?Admin
    {
        return Admin::find($id);
    }
}
