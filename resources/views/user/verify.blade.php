@extends('layouts.app')

@section('title', '認証コード入力')

@section('content')
    <div class="mx-auto flex min-h-[80vh] max-w-md flex-col justify-center">
        <div class="rounded-lg border border-gray-300 bg-white p-8 shadow-sm">
            <h1 class="mb-2 text-center text-xl font-semibold">認証コード入力</h1>
            <p class="mb-6 text-center text-sm text-gray-500">
                ご登録のメールアドレスに送信された6桁の認証コードを入力してください。
            </p>

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-300 bg-[#fff2f2] p-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('user.login.verify') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="code" class="block text-sm font-medium">認証コード</label>
                    <input
                        id="code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        required
                        autofocus
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-center text-lg tracking-widest focus:border-blue-300 focus:outline-none focus:ring"
                    >
                </div>
                <button
                    type="submit"
                    class="w-full cursor-pointer rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black"
                >
                    認証する
                </button>
            </form>
        </div>
    </div>
@endsection
