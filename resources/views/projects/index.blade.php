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
        @if($activeProjects->isEmpty() && $deliveredUnpaidProjects->isEmpty() && $archivedProjects->isEmpty())
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md shadow-sm dark:bg-blue-700 dark:text-blue-100 dark:border-blue-300"
                role="alert">
                <p class="font-bold">情報</p>
                <p>案件がありません。新規案件を作成してください。</p>
            </div>
        @else
            {{-- 進行中の案件 --}}
            <div class="mb-12" x-data="{ open: true }">
                <h2 @click="open = !open"
                    class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-4 border-b pb-2 cursor-pointer hover:text-blue-600 dark:hover:text-blue-400 transition">
                    <i class="fas fa-fw text-sm" :class="{'fa-chevron-right': !open, 'fa-chevron-down': open}"></i>
                    進行中の案件 ({{ $activeProjects->count() }})
                </h2>
                <div x-show="open" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform -translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform translate-y-0"
                    x-transition:leave-end="opacity-0 transform -translate-y-4">
                    @if($activeProjects->isEmpty())
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>進行中の案件はありません。</p>
                        </div>
                    @else
                        {{-- 月別表示 --}}
                        @foreach($activeProjectsByMonth as $monthKey => $projects)
                            @php
                                $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $monthKey);
                                $monthName = $monthDate->format('Y年m月');
                                $isPast = $monthDate->endOfMonth()->isPast();
                            @endphp
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-4 flex items-center">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $isPast ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200' : 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200' }} mr-2">
                                        <i class="fas fa-calendar mr-1.5"></i>
                                        {{ $monthName }} 納期
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
            </div>

            {{-- 納品済み・未払いの案件 --}}
            @if($deliveredUnpaidProjects->isNotEmpty())
                <div class="mb-12" x-data="{ open: true }">
                    <h2 @click="open = !open"
                        class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-4 border-b pb-2 cursor-pointer hover:text-blue-600 dark:hover:text-blue-400 transition">
                        <i class="fas fa-fw text-sm" :class="{'fa-chevron-right': !open, 'fa-chevron-down': open}"></i>
                        納品済み・未払いの案件 ({{ $deliveredUnpaidProjects->count() }})
                    </h2>
                    <div x-show="open" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform -translate-y-4"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 transform translate-y-0"
                        x-transition:leave-end="opacity-0 transform -translate-y-4">
                        {{-- 月別表示 --}}
                        @foreach($deliveredUnpaidProjectsByMonth as $monthKey => $projects)
                            @php
                                $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $monthKey);
                                $monthName = $monthDate->format('Y年m月');
                                $isPast = $monthDate->endOfMonth()->isPast();
                            @endphp
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-4 flex items-center">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $isPast ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200' : 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-200' }} mr-2">
                                        <i class="fas fa-calendar mr-1.5"></i>
                                        {{ $monthName }} 納期
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
                        {{-- アーカイブも月別表示 --}}
                        @foreach($archivedProjectsByMonth as $monthKey => $projects)
                            @php
                                $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $monthKey);
                                $monthName = $monthDate->format('Y年m月');
                            @endphp
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-4 flex items-center">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 mr-2">
                                        <i class="fas fa-calendar mr-1.5"></i>
                                        {{ $monthName }} 納期
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