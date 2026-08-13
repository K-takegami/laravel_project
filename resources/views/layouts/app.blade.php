{{-- ユーザー/管理者ログイン関連画面が共通で使う最小レイアウト。 --}}
{{-- 各ページは @section('title') / @section('content') で中身を差し込む。 --}}
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', config('app.name', 'Laravel'))</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-100 text-gray-900 antialiased">
        <div class="min-h-screen px-4 py-10">
            @yield('content')
        </div>
    </body>
</html>
