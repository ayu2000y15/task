<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="{{ $tableId ?? '' }}">
    <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
            <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 pl-4 pr-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12"></th>
            <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[200px]">{{ ($isFolderView ?? false) ? 'フォルダ名' : (($isMilestoneView ?? false) ? '重要納期名' : '工程名') }}</th>

            @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">担当者</th>
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">開始日</th>
            @endif

            @if($isMilestoneView ?? false)
                 <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">日付</th>
            @elseif(!($isFolderView ?? false)) {{-- フォルダビューでない場合に終了日を表示 --}}
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">終了日</th>
            @endif

            @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">工数</th>
            @endif

             @if($isFolderView ?? false)
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">親工程</th>
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ファイル数</th>
            @endif

            @if(!($isFolderView ?? false))
            <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-36 min-w-[140px]">ステータス</th>
            @endif
            <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">操作</th>
        </tr>
    </thead>
    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($tasksToList as $task)
            @php
                $rowClass = ''; $now = \Carbon\Carbon::now()->startOfDay(); $daysUntilDue = $task->end_date ? $now->diffInDays($task->end_date, false) : null;
                if (!($isFolderView ?? false) && $task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled'])) $rowClass = 'bg-red-50 dark:bg-red-900/50';
                elseif (!($isFolderView ?? false) && !($isMilestoneView ?? false) && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled'])) $rowClass = 'bg-yellow-50 dark:bg-yellow-700/50';
                $hoverClass = !empty($task->description) ? 'task-row-hoverable' : '';
            @endphp

            @if(!($isFolderView ?? false)) {{-- 通常の工程または重要納期の場合 --}}
                @if($task->is_folder)
                @can('fileView', $task)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $rowClass }} {{ $hoverClass }}"
                        @if(!empty($task->description)) data-task-description="{{ htmlspecialchars($task->description) }}" @endif
                        data-progress="{{ $task->progress ?? 0 }}">
                        <td class="pl-4 pr-2 py-3 whitespace-nowrap align-top">
                            <a href="{{ route('projects.show', $task->project) }}" class="flex items-center group">
                                <span class="w-6 h-6 flex items-center justify-center rounded text-white text-xs font-bold mr-2 flex-shrink-0" style="background-color: {{ $task->project->color }};">
                                    {{ mb_substr($task->project->title, 0, 1) }}
                                </span>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 align-top">
                            <div class="flex items-start">
                                <span class="task-status-icon-wrapper mr-2 mt-1 flex-shrink-0">
                                    {{-- 動的アイコンロジック --}}
                                    @if($task->is_milestone) <i class="fas fa-flag text-red-500" title="重要納期"></i>
                                    @elseif($task->is_folder) <i class="fas fa-folder text-blue-500" title="フォルダ"></i> {{-- この分岐は $isFolderView が false の場合は通常通らないが、念のため --}}
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
                                        @if(!($isMilestoneView ?? false) && !($isFolderView ?? false) && !empty($task->parent->name) && $task->parent->is_folder) <span class="text-xs text-gray-400 dark:text-gray-500 block"> ({{ $task->parent->name }})</span> @endif
                                    </a>
                                    @if (!empty($task->description))
                                        <i class="far fa-comment-alt ml-1 text-gray-400 dark:text-gray-500 fa-xs" title="メモあり"></i>
                                    @endif
                                </div>
                            </div>
                            @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && $task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled'])) <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">期限切れ</span> @endif
                            @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >=0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                                <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100">あと{{ $daysUntilDue }}日</span>
                            @endif
                        </td>
                        {{-- 通常工程・重要納期で表示される列 --}}

                        @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">
                            <div class="editable-cell" data-task-id="{{ $task->id }}" data-project-id="{{ $task->project_id }}" data-field="assignee" data-current-value="{{ $task->assignee }}">{{ $task->assignee ?? '-' }}</div>
                        </td>
                        <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ optional($task->start_date)->format('n/j') }}</td>
                        @endif

                        @if($isMilestoneView ?? false)
                        <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ optional($task->start_date)->format('n/j') }}</td>
                        @elseif(!($isFolderView ?? false))
                        <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">
                            @if($task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled']))
                                <span class="text-red-600 dark:text-red-400">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $task->end_date->format('n/j') }}
                                </span>
                            @elseif(!$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                                <span class="text-yellow-600 dark:text-yellow-400">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    {{ $task->end_date->format('n/j') }}
                                </span>
                            @else
                                {{ optional($task->end_date)->format('n/j') }}
                            @endif
                        </td>
                        @endif

                        @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                        <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ $task->duration ? $task->duration . '日' : '-'}}</td>
                        @endif

                        @if(!($isFolderView ?? false)) {{-- 通常工程または重要納期の場合 --}}
                        <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm align-top">
                            @if(!$task->is_milestone && !$task->is_folder) {{-- 重要納期またはフォルダでなければステータスselect表示 --}}
                            <select class="task-status-select form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-300" data-task-id="{{ $task->id }}" data-project-id="{{ $task->project_id }}">
                                <option value="not_started" @if($task->status == 'not_started') selected @endif>未着手</option>
                                <option value="in_progress" @if($task->status == 'in_progress') selected @endif>進行中</option>
                                <option value="completed" @if($task->status == 'completed') selected @endif>完了</option>
                                <option value="on_hold" @if($task->status == 'on_hold') selected @endif>保留中</option>
                                <option value="cancelled" @if($task->status == 'cancelled') selected @endif>キャンセル</option>
                            </select>
                            @else
                            <span class="text-sm text-gray-500 dark:text-gray-400">-</span> {{-- 重要納期はステータス表示なし --}}
                            @endif
                        </td>
                        @endif
                        <td class="px-3 py-3 whitespace-nowrap text-sm font-medium align-top">
                            <div class="flex items-center space-x-1">
                                @can('update', $task)
                                <x-icon-button
                                    :href="route('projects.tasks.edit', [$task->project, $task])"
                                    icon="fas fa-edit"
                                    title="編集"
                                    color="blue" />
                                @endcan
                                @can('delete', $task)
                                    <form action="{{ route('projects.tasks.destroy', [$task->project, $task]) }}" method="POST" class="inline-block" onsubmit="return confirm('本当に削除しますか？');"> @csrf @method('DELETE')
                                        <x-icon-button
                                            icon="fas fa-trash"
                                            title="削除"
                                            color="red"
                                            type="submit" />
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endcan
                @else
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $rowClass }} {{ $hoverClass }}"
                    @if(!empty($task->description)) data-task-description="{{ htmlspecialchars($task->description) }}" @endif
                    data-progress="{{ $task->progress ?? 0 }}">
                    <td class="pl-4 pr-2 py-3 whitespace-nowrap align-top">
                        <a href="{{ route('projects.show', $task->project) }}" class="flex items-center group">
                            <span class="w-6 h-6 flex items-center justify-center rounded text-white text-xs font-bold mr-2 flex-shrink-0" style="background-color: {{ $task->project->color }};">
                                {{ mb_substr($task->project->title, 0, 1) }}
                            </span>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 align-top">
                        <div class="flex items-start">
                            <span class="task-status-icon-wrapper mr-2 mt-1 flex-shrink-0">
                                {{-- 動的アイコンロジック --}}
                                @if($task->is_milestone) <i class="fas fa-flag text-red-500" title="重要納期"></i>
                                @elseif($task->is_folder) <i class="fas fa-folder text-blue-500" title="フォルダ"></i> {{-- この分岐は $isFolderView が false の場合は通常通らないが、念のため --}}
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
                                    @if(!($isMilestoneView ?? false) && !($isFolderView ?? false) && !empty($task->parent->name) && $task->parent->is_folder) <span class="text-xs text-gray-400 dark:text-gray-500 block"> ({{ $task->parent->name }})</span> @endif
                                </a>
                                @if (!empty($task->description))
                                    <i class="far fa-comment-alt ml-1 text-gray-400 dark:text-gray-500 fa-xs" title="メモあり"></i>
                                @endif
                            </div>
                        </div>
                         @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && $task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled'])) <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">期限切れ</span> @endif
                         @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >=0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                            <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100">あと{{ $daysUntilDue }}日</span>
                        @endif
                    </td>
                    {{-- 通常工程・重要納期で表示される列 --}}

                    @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">
                        <div class="editable-cell" data-task-id="{{ $task->id }}" data-project-id="{{ $task->project_id }}" data-field="assignee" data-current-value="{{ $task->assignee }}">{{ $task->assignee ?? '-' }}</div>
                    </td>
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ optional($task->start_date)->format('n/j') }}</td>
                    @endif

                    @if($isMilestoneView ?? false)
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ optional($task->start_date)->format('n/j') }}</td>
                    @elseif(!($isFolderView ?? false))
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">
                         @if($task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled']))
                            <span class="text-red-600 dark:text-red-400">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ $task->end_date->format('n/j') }}
                            </span>
                        @elseif(!$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                            <span class="text-yellow-600 dark:text-yellow-400">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                {{ $task->end_date->format('n/j') }}
                            </span>
                        @else
                            {{ optional($task->end_date)->format('n/j') }}
                        @endif
                    </td>
                    @endif

                    @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ $task->duration ? $task->duration . '日' : '-'}}</td>
                    @endif

                    @if(!($isFolderView ?? false)) {{-- 通常工程または重要納期の場合 --}}
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm align-top">
                        @if(!$task->is_milestone) {{-- 重要納期でなければステータスselect表示 --}}
                        <select class="task-status-select form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-300" data-task-id="{{ $task->id }}" data-project-id="{{ $task->project_id }}">
                            <option value="not_started" @if($task->status == 'not_started') selected @endif>未着手</option>
                            <option value="in_progress" @if($task->status == 'in_progress') selected @endif>進行中</option>
                            <option value="completed" @if($task->status == 'completed') selected @endif>完了</option>
                            <option value="on_hold" @if($task->status == 'on_hold') selected @endif>保留中</option>
                            <option value="cancelled" @if($task->status == 'cancelled') selected @endif>キャンセル</option>
                        </select>
                        @else
                        <span class="text-sm text-gray-500 dark:text-gray-400">-</span> {{-- 重要納期はステータス表示なし --}}
                        @endif
                    </td>
                    @endif
                    <td class="px-3 py-3 whitespace-nowrap text-sm font-medium align-top">
                        <div class="flex items-center space-x-1">
                            @can('update', $task)
                            <x-icon-button
                                :href="route('projects.tasks.edit', [$task->project, $task])"
                                icon="fas fa-edit"
                                title="編集"
                                color="blue" />
                            @endcan
                            @can('delete', $task)
                                <form action="{{ route('projects.tasks.destroy', [$task->project, $task]) }}" method="POST" class="inline-block" onsubmit="return confirm('本当に削除しますか？');"> @csrf @method('DELETE')
                                    <x-icon-button
                                        icon="fas fa-trash"
                                        title="削除"
                                        color="red"
                                        type="submit" />
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @endif
            @endif
        @empty
            @php
                $colspan = 9; // デフォルト
                if ($isFolderView ?? false) {
                    $colspan = 5;
                } elseif ($isMilestoneView ?? false) {
                    $colspan = 6;
                }
            @endphp
            <tr><td colspan="{{ $colspan }}" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">表示する{{ ($isFolderView ?? false) ? 'フォルダ' : (($isMilestoneView ?? false) ? '重要納期' : '工程') }}がありません</td></tr>
        @endforelse
    </tbody>
</table>