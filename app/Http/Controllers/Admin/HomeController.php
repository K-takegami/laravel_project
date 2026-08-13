<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * ログイン後の管理者ホーム画面を表示するコントローラー。
 */
class HomeController extends Controller
{
    /**
     * 管理者ホーム画面を表示する(お知らせ枠は現時点では静的なプレースホルダー)。
     *
     * @return View 管理者ホーム画面のビュー
     */
    public function index(): View
    {
        return view('admin.home');
    }
}
