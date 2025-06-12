@extends('layouts.app')

@section('title', '案件一覧')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">案件一覧</h1>
            @can('create', App\Models\Project::class)
                <x-primary-button class="ml-2" onclick="location.href='{{ route('projects.create') }}'"><i
                        class="fas fa-plus mr-1"></i>新規衣装案件</x-primary-button>

            @endcan
        </div>

        @if($projects->isEmpty())
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md shadow-sm dark:bg-blue-700 dark:text-blue-100 dark:border-blue-300"
                role="alert">
                <p class="font-bold">情報</p>
                <p>衣装案件がありません。新規衣装案件を作成してください。</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($projects as $project)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden flex flex-col">
                        <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center text-white"
                            style="background-color: {{ $project->color ?? '#6c757d' }};">
                            <h5 class="text-lg font-semibold truncate" title="{{ $project->title }}">{{ $project->title }}</h5>
                            @if($project->is_favorite)
                                <i class="fas fa-star text-yellow-400"></i>
                            @endif
                        </div>
                        <div class="p-5 flex-grow">
                            {{-- <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 h-20 overflow-hidden">
                                {{ Str::limit($project->description ?: ($project->getAttributeValue('description', '説明はありません')),
                                120) }}
                            </p> --}}

                            <div class="mb-3">
                                <small class="text-gray-500 dark:text-gray-400">期間:</small>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mb-0">{{ $project->start_date->format('Y/m/d') }}
                                    〜 {{ $project->end_date->format('Y/m/d') }}</p>
                            </div>

                            {{-- 期間表示の下に追加するコード --}}
                            <div class="mt-3 flex flex-wrap gap-2 items-center text-xs">
                                @php
                                    // 案件ステータスの定義
                                    $statusOptions = \App\Models\Project::PROJECT_STATUS_OPTIONS ?? [];
                                    $statusLabel = $statusOptions[$project->status] ?? '未設定';
                                    $statusColorClasses = [
                                        'not_started' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                        'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-200',
                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200',
                                        'on_hold' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200',
                                        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-200',
                                        '' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                    ];
                                    $statusColor = $statusColorClasses[$project->status ?? ''] ?? $statusColorClasses[''];

                                    // 納品状況の定義
                                    $deliveryLabel = $project->delivery_flag == '1' ? '納品済み' : '未納品';
                                    $deliveryColor = $project->delivery_flag == '1'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200'
                                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200';

                                    // 支払い状況の定義
                                    $paymentOptions = \App\Models\Project::PAYMENT_FLAG_OPTIONS ?? [];
                                    $paymentLabel = $paymentOptions[$project->payment_flag] ?? '未設定';
                                    $paymentColorClasses = [
                                        'Pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200',
                                        'Processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-200',
                                        'Completed' => 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200',
                                        'Partially Paid' => 'bg-orange-100 text-orange-800 dark:bg-orange-700 dark:text-orange-200',
                                        'Overdue' => 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-200',
                                        'Cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                        'Refunded' => 'bg-purple-100 text-purple-800 dark:bg-purple-700 dark:text-purple-200',
                                        'On Hold' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-200',
                                        '' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                    ];
                                    $paymentColor = $paymentColorClasses[$project->payment_flag ?? ''] ?? $paymentColorClasses[''];
                                @endphp

                                {{-- 納品状況バッジ --}}
                                <span title="納品状況"
                                    class="inline-flex items-center font-medium px-2 py-1 rounded-md {{ $deliveryColor }}">
                                    <i class="fas fa-truck mr-1.5"></i>
                                    {{ $deliveryLabel }}
                                </span>

                                {{-- 支払い状況バッジ --}}
                                @if($project->payment_flag)
                                    <span title="支払い状況"
                                        class="inline-flex items-center font-medium px-2 py-1 rounded-md {{ $paymentColor }}">
                                        <i class="fas fa-yen-sign mr-1.5"></i>
                                        {{ $paymentLabel }}
                                    </span>
                                @endif

                                {{-- 案件ステータスバッジ --}}
                                @if($project->status)
                                    <span title="案件ステータス"
                                        class="inline-flex items-center font-medium px-2 py-1 rounded-md {{ $statusColor }}">
                                        <i class="fas fa-tasks mr-1.5"></i>
                                        {{ $statusLabel }}
                                    </span>
                                @endif
                            </div>

                            <div>
                                <small class="text-gray-500 dark:text-gray-400">工程:</small>
                                <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                    <span>全 {{ $project->tasks->count() }} 工程</span>
                                    <span>完了: {{ $project->tasks->where('status', 'completed')->count() }}</span>
                                </div>
                                @php
                                    $totalTasks = $project->tasks->count();
                                    $completedTasks = $project->tasks->where('status', 'completed')->count();
                                    $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                                @endphp
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mt-1">
                                    <div class="h-2.5 rounded-full"
                                        style="width: {{ $progress }}%; background-color: {{ $project->color ?? '#6c757d' }};"
                                        title="{{ $progress }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                            <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-2">
                                <a href="{{ route('projects.show', $project) }}"
                                    class="w-full sm:flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-600 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    <i class="fas fa-eye mr-1"></i> 詳細
                                </a>
                                <a href="{{ route('gantt.index', ['project_id' => $project->id]) }}"
                                    class="w-full sm:flex-1 inline-flex items-center justify-center px-3 py-2 bg-teal-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-600 active:bg-teal-700 focus:outline-none focus:border-teal-700 focus:ring ring-teal-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    <i class="fas fa-chart-gantt mr-1"></i> ガント
                                </a>
                                @can('update', $project)
                                    <a href="{{ route('projects.edit', $project) }}"
                                        class="w-full sm:flex-1 inline-flex items-center justify-center px-3 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600 active:bg-yellow-700 focus:outline-none focus:border-yellow-700 focus:ring ring-yellow-300 disabled:opacity-25 transition ease-in-out duration-150">
                                        <i class="fas fa-edit mr-1"></i> 編集
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection