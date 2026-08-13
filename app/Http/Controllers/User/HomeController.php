<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * ログイン後のユーザーホーム画面を表示するコントローラー。
 */
class HomeController extends Controller
{
    /**
     * ユーザーホーム画面を表示する(お知らせ枠は現時点では静的なプレースホルダー)。
     *
     * @return View ユーザーホーム画面のビュー
     */
    public function index(): View
    {
        return view('user.home');
    }
}
