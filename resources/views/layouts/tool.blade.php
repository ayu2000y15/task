<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel')) - ツール</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')
    @stack('styles')
</head>

<body class="font-sans antialiased text-gray-900 bg-gray-100 dark:text-gray-100 dark:bg-gray-900">

    {{-- ツール画面用のヘッダー (これは全幅、または広めのコンテナ) --}}
    <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-40">
        {{-- ヘッダーコンテンツは中央寄せで最大幅を指定 (例: max-w-7xl) --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div>
                <a href="{{ route('tools.index') }}"
                    class="text-lg font-semibold text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">
                    <i class="fas fa-tools mr-2"></i>ツール一覧
                </a>
                @yield('breadcrumbs')
            </div>
            <div>
                @auth
                    <div x-data="{ userMenuOpen: false }" class="relative">
                        <button @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center text-sm font-medium text-gray-700 rounded-md dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none p-2">
                            <span>{{ Auth::user()->name }}</span>
                            <i class="fas fa-chevron-down fa-xs ml-1"></i>
                        </button>
                        <div x-show="userMenuOpen" @click.away="userMenuOpen = false" x-transition
                            class="absolute right-0 mt-2 w-48 py-1 bg-white rounded-md shadow-lg dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50"
                            style="display: none;">
                            <a href="{{ route('home.index') }}"
                                class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                <i class="fas fa-home fa-fw mr-2"></i>ホームへ戻る
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <i class="fas fa-sign-out-alt fa-fw mr-2"></i>ログアウト
                                </a>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    {{-- ▼▼▼ 全幅で表示したいタブやページヘッダーのためのオプショナルなセクション ▼▼▼ --}}
    @hasSection('page_header_tabs')
        <div class="bg-white dark:bg-gray-800 shadow-sm {{-- 必要に応じてmb-Xを追加 --}}">
            {{-- タブ等のコンテンツは中央寄せで最大幅を指定 (例: max-w-7xl) --}}
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @yield('page_header_tabs')
            </div>
        </div>
    @endif
    {{-- ▲▲▲ ここまでタブ用セクション ▲▲▲ --}}

    {{-- メインコンテンツエリア (ここを画面の約90%幅に) --}}
    <main class="flex-1 py-4 md:py-6">
        <div class="max-w-[90vw] mx-auto px-4 sm:px-6 lg:px-8"> {{-- ★ メインコンテンツのラッパー ★ --}}
            @if(session('success'))
                <div class="mb-4">
                    <div class="p-4 bg-green-100 border border-green-400 text-green-700 rounded-md dark:bg-green-700 dark:text-green-100 dark:border-green-600"
                        role="alert">
                        {{ session('success') }}
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4">
                    <div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600"
                        role="alert">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            @yield('content') {{-- ここに各ビューのコンテンツが挿入される --}}
        </div>
    </main>

    @yield('scripts')
    @stack('scripts')
</body>

</html>