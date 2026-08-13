@extends('layouts.app')

@section('title', 'ユーザーログイン')

@section('content')
    <div class="mx-auto flex min-h-[80vh] max-w-md flex-col justify-center">
        <div class="rounded-lg border border-gray-300 bg-white p-8 shadow-sm">
            <h1 class="mb-6 text-center text-xl font-semibold">ユーザーログイン</h1>

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-300 bg-[#fff2f2] p-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('user.login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium">メールアドレス</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-300 focus:outline-none focus:ring"
                    >
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium">パスワード</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-300 focus:outline-none focus:ring"
                    >
                </div>
                <button
                    type="submit"
                    class="w-full cursor-pointer rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black"
                >
                    ログイン
                </button>
            </form>
        </div>
    </div>
@endsection
