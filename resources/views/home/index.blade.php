{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'ホーム')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">ホーム</h1>
        <div class="flex-shrink-0">
            @can('create', App\Models\Project::class)
            <x-primary-button class="ml-2" onclick="location.href='{{ route('projects.create') }}'"><i class="fas fa-plus mr-1"></i>新規衣装案件</x-primary-button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300">最近の工程</h5>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="pl-4 pr-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12"></th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[250px] sm:min-w-[300px]">工程名</th>
                                <th scope="col" class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">キャラクター</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">担当者</th>
                                <th scope="col" class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">期限</th>
                                <th scope="col" class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-36 min-w-[140px]">ステータス</th>
                                <th scope="col" class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @if($recentTasks->isEmpty())
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        表示する工程がありません
                                    </td>
                                </tr>
                            @else
                                @foreach($recentTasks as $task)
                                    @php
                                        $hoverClass = !empty($task->description) ? 'task-row-hoverable' : '';
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $hoverClass }}"
                                        @if(!empty($task->description))
                                            data-task-description="{{ htmlspecialchars($task->description) }}"
                                        @endif
                                        data-task-id="{{ $task->id }}"
                                        data-project-id="{{ $task->project->id }}"
                                        data-progress="{{ $task->progress ?? 0 }}"
                                        >
                                        <td class="pl-4 pr-2 py-3 whitespace-nowrap align-top">
                                            <a href="{{ route('projects.show', $task->project) }}" class="flex items-center group">
                                                <span class="w-6 h-6 flex items-center justify-center rounded text-white text-xs font-bold mr-2 flex-shrink-0" style="background-color: {{ $task->project->color }};">
                                                    {{ mb_substr($task->project->title, 0, 1) }}
                                                </span>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 align-top">
                                            <div class="flex items-center gap-x-3"> {{-- Adjusted gap-x-3 --}}
                                                @if(!$task->is_milestone && !$task->is_folder)
                                                    <div class="flex flex-col items-center">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 mb-1" style="font-size: 0.5rem;">進行中</span>
                                                        <input type="checkbox"
                                                               class="task-status-checkbox task-status-in-progress form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                                                               data-action="set-in-progress"
                                                               title="進行中にする"
                                                               @if($task->status == 'in_progress') checked @endif>
                                                    </div>
                                                @endif

                                                <div class="flex items-start flex-grow min-w-0">
                                                    <span class="task-status-icon-wrapper mr-2 mt-1 flex-shrink-0">
                                                        @if($task->is_milestone) <i class="fas fa-flag text-red-500" title="重要納期"></i>
                                                        @elseif($task->is_folder) <i class="fas fa-folder text-blue-500" title="フォルダ"></i>
                                                        @else
                                                            @switch($task->status)
                                                                @case('completed') <i class="fas fa-check-circle text-green-500" title="完了"></i> @break
                                                                @case('in_progress') <i class="fas fa-play-circle text-blue-500" title="進行中"></i> @break
                                                                @case('on_hold') <i class="fas fa-pause-circle text-yellow-500" title="保留中"></i> @break
                                                                @case('cancelled') <i class="fas fa-times-circle text-red-500" title="キャンセル"></i> @break
                                                                @default <i class="far fa-circle text-gray-400" title="未着手"></i>
                                                            @endswitch
                                                        @endif
                                                    </span>
                                                    <div class="min-w-0">
                                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="hover:text-blue-600 whitespace-normal break-words inline-block">
                                                            {{ $task->name }}
                                                        </a>
                                                        @if (!empty($task->description))
                                                            <i class="far fa-comment-alt ml-1 text-gray-400 dark:text-gray-500 fa-xs" title="メモあり"></i>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if(!$task->is_milestone && !$task->is_folder)
                                                    <div class="flex flex-col items-center">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 mb-1" style="font-size: 0.5rem;">完了</span>
                                                        <input type="checkbox"
                                                               class="task-status-checkbox task-status-completed form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600"
                                                               data-action="set-completed"
                                                               title="完了にする"
                                                               @if($task->status == 'completed') checked @endif>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ $task->character->name ?? '-' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ $task->assignee ?? '-' }}</td>
                                        <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">
                                            {{ optional($task->end_date)->format('n/j') }}
                                        </td>
                                        <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm align-top">
                                            @if(!$task->is_folder && !$task->is_milestone)
                                            <select class="task-status-select form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-300"
                                                    data-task-id="{{ $task->id }}"
                                                    data-project-id="{{ $task->project->id }}">
                                                <option value="not_started" {{ $task->status === 'not_started' ? 'selected' : '' }}>未着手</option>
                                                <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>進行中</option>
                                                <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>完了</option>
                                                <option value="on_hold" {{ $task->status === 'on_hold' ? 'selected' : '' }}>保留中</option>
                                                <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                                            </select>
                                            @else
                                            <span class="text-gray-500 dark:text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-right text-sm font-medium align-top">
                                            @can('update', $task)
                                            <x-icon-button
                                            :href="route('projects.tasks.edit', [$task->project, $task])"
                                            icon="fas fa-edit"
                                            title="編集"
                                            color="blue"
                                            />
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 右カラム（衣装案件概要、期限間近の工程） --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">衣装案件概要</h5>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">全衣装案件数:</span>
                        <span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full dark:bg-blue-700 dark:text-blue-200">{{ $projectCount }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">進行中の衣装案件:</span>
                        <span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-200">{{ $activeProjectCount }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">全工程数:</span>
                        <span class="px-2 py-1 text-xs font-semibold text-indigo-800 bg-indigo-100 rounded-full dark:bg-indigo-700 dark:text-indigo-200">{{ $taskCount }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300">期限間近の工程</h5>
                </div>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @if($upcomingTasks->isEmpty())
                        <li class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">表示する工程がありません</li>
                    @else
                        @foreach($upcomingTasks as $task)
                        <li class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 {{ !empty($task->description) ? 'task-row-hoverable' : '' }}"
                            @if(!empty($task->description))
                                data-task-description="{{ htmlspecialchars($task->description) }}"
                            @endif
                             data-task-id="{{ $task->id }}"
                             data-project-id="{{ $task->project->id }}"
                             data-progress="{{ $task->progress ?? 0 }}"
                            >
                            <div class="flex items-center justify-between">
                                <div class="min-w-0 flex-1 flex items-center gap-x-3"> {{-- Adjusted gap-x-3 --}}
                                    @if(!$task->is_milestone && !$task->is_folder)
                                        <div class="flex flex-col items-center self-start mt-0.5">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-1" style="font-size: 0.5rem;">進行中</span>
                                            <input type="checkbox"
                                                   class="task-status-checkbox task-status-in-progress form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                                                   data-action="set-in-progress"
                                                   title="進行中にする"
                                                   @if($task->status == 'in_progress') checked @endif>
                                        </div>
                                    @endif
                                    {{-- ステータスアイコンを追加 --}}
                                    <span class="task-status-icon-wrapper mr-1 mt-0.5 flex-shrink-0 self-start">
                                        @if($task->is_milestone) <i class="fas fa-flag text-red-500" title="重要納期"></i>
                                        @elseif($task->is_folder) <i class="fas fa-folder text-blue-500" title="フォルダ"></i>
                                        @else
                                            @switch($task->status)
                                                @case('completed') <i class="fas fa-check-circle text-green-500" title="完了"></i> @break
                                                @case('in_progress') <i class="fas fa-play-circle text-blue-500" title="進行中"></i> @break
                                                @case('on_hold') <i class="fas fa-pause-circle text-yellow-500" title="保留中"></i> @break
                                                @case('cancelled') <i class="fas fa-times-circle text-red-500" title="キャンセル"></i> @break
                                                @default <i class="far fa-circle text-gray-400" title="未着手"></i>
                                            @endswitch
                                        @endif
                                    </span>

                                    <div class="flex-grow min-w-0">
                                        <div>
                                            {{ $task->name }}
                                            @if (!empty($task->description))
                                                <i class="far fa-comment-alt ml-1 text-gray-400 dark:text-gray-500 fa-xs align-middle" title="メモあり"></i>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ $task->character->name ?? '' }}
                                            @if(!empty($task->assignee))
                                                担当: {{ $task->assignee }}
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            期限: {{ optional($task->end_date)->format('n/j') }} ({{ $task->end_date ? $task->end_date->diffForHumans() : '' }})
                                        </p>
                                    </div>
                                    @if(!$task->is_milestone && !$task->is_folder)
                                        <div class="flex flex-col items-center self-start mt-0.5">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-1" style="font-size: 0.5rem;">完了</span>
                                            <input type="checkbox"
                                                   class="task-status-checkbox task-status-completed form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600"
                                                   data-action="set-completed"
                                                   title="完了にする"
                                                   @if($task->status == 'completed') checked @endif>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-2 flex-shrink-0">
                                    {{-- Actions can be placed here if needed --}}
                                </div>
                            </div>
                        </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">ToDoリスト (期限なしタスク)</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php
                $todoColumns = [
                    'todoTasks' => ['label' => '未着手', 'status_class' => 'not_started', 'icon' => 'far fa-circle', 'color' => 'gray'],
                    'inProgressTasks' => ['label' => '進行中', 'status_class' => 'in_progress', 'icon' => 'fas fa-play-circle', 'color' => 'blue'],
                    'onHoldTasks' => ['label' => '保留中', 'status_class' => 'on_hold', 'icon' => 'fas fa-pause-circle', 'color' => 'yellow'],
                ];
            @endphp

            @foreach($todoColumns as $varName => $columnData)
                @php
                    $tasksInStatus = $$varName; // e.g., $todoTasks, $inProgressTasks, $onHoldTasks
                    // Filter tasks again in the view to ensure they match the column's expected status
                    if (is_iterable($tasksInStatus)) {
                        $filteredTasks = collect($tasksInStatus)->filter(function($task) use ($columnData) {
                            return isset($task->status) && $task->status == $columnData['status_class'];
                        });
                    } else {
                        $filteredTasks = collect(); // Assign an empty collection if $tasksInStatus is not iterable
                    }
                @endphp
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center
                                {{ 'bg-'.$columnData['color'].'-500' }} {{ $columnData['color'] === 'yellow' ? 'text-black' : 'text-white' }}
                                dark:{{ 'bg-'.$columnData['color'].'-700' }} dark:text-{{$columnData['color']}}-100 rounded-t-lg">
                        <h6 class="font-semibold">{{ $columnData['label'] }}</h6>
                        <span class="px-2 py-0.5 text-xs font-semibold {{ $columnData['color'] === 'yellow' ? 'bg-gray-200 text-gray-700' : 'bg-white text-'.$columnData['color'].'-600' }} dark:bg-gray-600 dark:text-gray-200 rounded-full">{{ $filteredTasks->count() }}</span>
                    </div>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                        @if($filteredTasks->isEmpty())
                            <li class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">工程がありません</li>
                        @else
                            @foreach($filteredTasks as $task)
                            <li class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-start {{ !empty($task->description) ? 'task-row-hoverable' : '' }} border-l-4"
                                style="border-left-color: {{ $task->project->color }};"
                                @if(!empty($task->description))
                                    data-task-description="{{ htmlspecialchars($task->description) }}"
                                @endif
                                data-task-id="{{ $task->id }}"
                                data-project-id="{{ $task->project->id }}"
                                data-progress="{{ $task->progress ?? 0 }}"
                                >
                                {{-- <span class="task-status-icon-wrapper w-5 h-5 flex items-center justify-center mr-3 mt-1 flex-shrink-0">
                                     @if($task->is_milestone) <i class="fas fa-flag text-red-500" title="重要納期"></i>
                                     @elseif($task->is_folder) <i class="fas fa-folder text-blue-500" title="フォルダ"></i>
                                     @else
                                         @switch($task->status)
                                             @case('completed') <i class="fas fa-check-circle text-green-500" title="完了"></i> @break
                                             @case('in_progress') <i class="fas fa-play-circle text-blue-500" title="進行中"></i> @break
                                             @case('on_hold') <i class="fas fa-pause-circle text-yellow-500" title="保留中"></i> @break
                                             @case('cancelled') <i class="fas fa-times-circle text-red-500" title="キャンセル"></i> @break
                                             @default <i class="far fa-circle text-gray-400" title="未着手"></i>
                                         @endswitch
                                     @endif
                                </span> --}}
                                <div class="flex-grow min-w-0 flex items-center gap-x-4">
                                    @if(!$task->is_milestone && !$task->is_folder)
                                        <div class="flex flex-col items-center self-start mt-0.5 ">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-1" style="font-size: 0.5rem;">進行中</span>
                                            <input type="checkbox"
                                                   class="task-status-checkbox task-status-in-progress form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                                                   data-action="set-in-progress"
                                                   title="進行中にする"
                                                   @if($task->status == 'in_progress') checked @endif>
                                        </div>
                                    @endif
                                    <div class="flex-grow min-w-0">
                                        <div>
                                            <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="text-sm font-medium text-gray-800 dark:text-gray-200 hover:text-blue-600 whitespace-normal break-words inline-block">
                                                {{ $task->name }}
                                            </a>
                                            @if (!empty($task->description))
                                                <i class="far fa-comment-alt ml-1 text-gray-400 dark:text-gray-500 fa-xs align-middle" title="メモあり"></i>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            <span class="font-semibold" style="color: {{ $task->project->color }}">{{ $task->project->title }}</span>
                                            <span class="mx-1">&bull;</span>
                                            {{ $task->character->name ?? '案件全体' }}
                                            <span class="mx-1">&bull;</span>
                                            担当: {{ $task->assignee ?? '-' }}
                                            <span class="hidden sm:inline mx-1">&bull;</span>
                                            <span class="hidden sm:inline">作成: {{ $task->created_at ? $task->created_at->format('n/j') : '-'}}</span>
                                        </p>
                                    </div>
                                    @if(!$task->is_milestone && !$task->is_folder)
                                        <div class="flex flex-col items-center self-start mt-0.5">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mb-1" style="font-size: 0.5rem;">完了</span>
                                            <input type="checkbox"
                                                   class="task-status-checkbox task-status-completed form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600"
                                                   data-action="set-completed"
                                                   title="完了にする"
                                                   @if($task->status == 'completed') checked @endif>
                                        </div>
                                    @endif
                                </div>
                                {{-- <div class="ml-2 flex-shrink-0">
                                     @can('update', $task)
                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="text-gray-400 hover:text-blue-500 dark:text-gray-500 dark:hover:text-blue-400" title="編集">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan
                                </div> --}}
                            </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection