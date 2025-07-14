@extends('layouts.app')

@section('title', '案件一覧')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">案件一覧</h1>
            @can('create', App\Models\Project::class)
                <x-primary-button class="ml-2" onclick="location.href='{{ route('projects.create') }}'"><i
                        class="fas fa-plus mr-1"></i>新規案件</x-primary-button>

            @endcan
        </div>

        {{-- 案件表示セクション --}}
        @if($activeProjects->isEmpty() && $archivedProjects->isEmpty())
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md shadow-sm dark:bg-blue-700 dark:text-blue-100 dark:border-blue-300"
                role="alert">
                <p class="font-bold">情報</p>
                <p>案件がありません。新規案件を作成してください。</p>
            </div>
        @else
            {{-- 進行中の案件 --}}
            <div class="mb-12">
                <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-4 border-b pb-2">進行中の案件</h2>
                @if($activeProjects->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>進行中の案件はありません。</p>
                    </div>
                @else
                    {{-- カテゴリ別表示 --}}
                    @foreach($activeProjectsByCategory as $categoryKey => $projects)
                        @php
                            $categoryName = $categoryKey === 'uncategorized' ? '未分類' : ($categories->where('name', $categoryKey)->first()->display_name ?? $categoryKey);
                        @endphp
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-4 flex items-center">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200 mr-2">
                                    {{ $categoryName }}
                                </span>
                                <span class="text-sm text-gray-500">({{ $projects->count() }}件)</span>
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach($projects as $project)
                                    @include('projects.partials.project-card', ['project' => $project])
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- 完了・キャンセルした案件 --}}
            @if($archivedProjects->isNotEmpty())
                <div x-data="{ open: false }">
                    <h2 @click="open = !open"
                        class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-4 border-b pb-2 cursor-pointer hover:text-blue-600 dark:hover:text-blue-400 transition">
                        <i class="fas fa-fw text-sm" :class="{'fa-chevron-right': !open, 'fa-chevron-down': open}"></i>
                        完了・キャンセルした案件 ({{ $archivedProjects->count() }})
                    </h2>

                    <div x-show="open" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform -translate-y-4"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 transform translate-y-0"
                        x-transition:leave-end="opacity-0 transform -translate-y-4">
                        {{-- アーカイブもカテゴリ別表示 --}}
                        @foreach($archivedProjectsByCategory as $categoryKey => $projects)
                            @php
                                $categoryName = $categoryKey === 'uncategorized' ? '未分類' : ($categories->where('name', $categoryKey)->first()->display_name ?? $categoryKey);
                            @endphp
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-4 flex items-center">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 mr-2">
                                        {{ $categoryName }}
                                    </span>
                                    <span class="text-sm text-gray-500">({{ $projects->count() }}件)</span>
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                    @foreach($projects as $project)
                                        @include('projects.partials.project-card', ['project' => $project])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection
