<?php

use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\User\HomeController as UserHomeController;
use App\Http\Controllers\User\LoginController as UserLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('user')->name('user.')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('login', [UserLoginController::class, 'showLogin'])->name('login');
        Route::get('login/verify', [UserLoginController::class, 'showVerify'])->name('login.verify');

        // ブルートフォース対策: パスワード・認証コードの総当たりを防ぐため試行回数を制限する(1分間に5回まで)
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('login', [UserLoginController::class, 'login']);
            Route::post('login/verify', [UserLoginController::class, 'verify']);
        });
    });

    Route::middleware('auth:web')->group(function () {
        Route::get('home', [UserHomeController::class, 'index'])->name('home');
        Route::post('logout', [UserLoginController::class, 'logout'])->name('logout');
    });
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminLoginController::class, 'showLogin'])->name('login');
        Route::get('login/verify', [AdminLoginController::class, 'showVerify'])->name('login.verify');

        // ブルートフォース対策: パスワード・認証コードの総当たりを防ぐため試行回数を制限する(1分間に5回まで)
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('login', [AdminLoginController::class, 'login']);
            Route::post('login/verify', [AdminLoginController::class, 'verify']);
        });
    });

    Route::middleware('auth:admin')->group(function () {
        Route::get('home', [AdminHomeController::class, 'index'])->name('home');
        Route::post('logout', [AdminLoginController::class, 'logout'])->name('logout');
    });
});
