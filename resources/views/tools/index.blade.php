{{-- resources/views/tools/index.blade.php --}}
@extends('layouts.tool')

@section('title', 'ツール一覧')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-8">ツール一覧</h1>

        @if (!empty($availableTools))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($availableTools as $tool)
                    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden flex flex-col">
                        <div class="p-6 flex-grow">
                            <div class="flex items-center mb-4">
                                <div class="p-3 rounded-full text-white mr-4 {{ $tool['icon_bg_color_class'] ?? 'bg-gray-500' }}">
                                    <i class="fas {{ $tool['icon_class'] }} fa-lg"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300">{{ $tool['name'] }}</h2>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 mb-4 text-sm">
                                {{ $tool['description'] }}
                            </p>
                        </div>
                        <div class="px-6 pb-6 pt-0">
                            <a href="{{ $tool['route'] }}" class="inline-flex items-center justify-center w-full px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest
                                                          {{ $tool['button_bg_color_class'] ?? 'bg-gray-600' }}
                                                          {{ $tool['button_hover_bg_color_class'] ?? 'hover:bg-gray-700' }}
                                                          {{ $tool['button_active_bg_color_class'] ?? 'active:bg-gray-800' }}
                                                          focus:outline-none focus:border-gray-900 focus:ring ring-gray-300
                                                          disabled:opacity-25 transition ease-in-out duration-150">
                                {{ $tool['name'] }}を開く <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-tools fa-3x text-gray-400 dark:text-gray-500 mb-4"></i>
                <p class="text-xl text-gray-500 dark:text-gray-400">利用可能なツールがありません。</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">管理者にご確認ください。</p>
            </div>
        @endif
    </div>
@endsection