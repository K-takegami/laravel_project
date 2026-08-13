@extends('layouts.app')

@section('title', '管理者ホーム')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold">管理者ホーム</h1>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="cursor-pointer rounded-md border border-gray-300 px-4 py-1.5 text-sm hover:bg-gray-100">
                    ログアウト
                </button>
            </form>
        </div>

        <div class="rounded-lg border border-gray-300 bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-lg font-semibold">お知らせ</h2>
            <p class="text-sm text-gray-500">お知らせはありません。</p>
        </div>
    </div>
@endsection
