<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="{{ $tableId ?? '' }}">
    <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
            <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 pl-4 pr-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12"></th>
            <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[200px]">{{ ($isFolderView ?? false) ? 'フォルダ名' : (($isMilestoneView ?? false) ? '重要納期名' : '工程名') }}</th>
            <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">キャラクター</th>
            @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">担当者</th>
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">開始日</th>
            @endif
            @if($isMilestoneView ?? false)
                 <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">日付</th>
            @else
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
                                @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && !empty($task->parent->name) && $task->parent->is_folder) <span class="text-xs text-gray-400 dark:text-gray-500 block"> ({{ $task->parent->name }})</span> @endif
                            </a>
                            @if (!empty($task->description))
                                <i class="far fa-comment-alt ml-1 text-gray-400 dark:text-gray-500 fa-xs" title="メモあり"></i>
                            @endif
                            @if(($isFolderView ?? false) && $task->files->count() > 0)
                                <button type="button" class="ml-2 text-xs text-blue-500 hover:underline toggle-folder-files"
                                        data-target="folder-files-{{ $task->id }}">
                                    ファイル表示 ({{ $task->files->count() }}) <i class="fas fa-chevron-down fa-xs"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                     @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && $task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled'])) <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">期限切れ</span> @endif
                     @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >=0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                        <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100">あと{{ $daysUntilDue }}日</span>
                     @endif
                </td>
                <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ $task->character->name ?? '-' }}</td>
                @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">
                    <div class="editable-cell" data-task-id="{{ $task->id }}" data-project-id="{{ $task->project_id }}" data-field="assignee" data-current-value="{{ $task->assignee }}">{{ $task->assignee ?? '-' }}</div>
                </td>
                <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ optional($task->start_date)->format('Y/m/d') }}</td>
                @endif
                @if($isMilestoneView ?? false)
                <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ optional($task->start_date)->format('Y/m/d') }}</td>
                @else
                <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">
                     @if(!($isFolderView ?? false) && $task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled']))
                        <span class="text-red-600 dark:text-red-400">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ $task->end_date->format('Y/m/d') }}
                        </span>
                    @elseif(!($isFolderView ?? false) && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                        <span class="text-yellow-600 dark:text-yellow-400">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            {{ $task->end_date->format('Y/m/d') }}
                        </span>
                    @else
                        {{ optional($task->end_date)->format('Y/m/d') }}
                    @endif
                </td>
                @endif
                @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ $task->duration ? $task->duration . '日' : '-'}}</td>
                @endif
                @if($isFolderView ?? false)
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ $task->parent->name ?? '-' }}</td>
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top"><i class="fas fa-file mr-1"></i> {{ $task->files->count() }}</td>
                @endif
                @if(!($isFolderView ?? false))
                <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm align-top">
                    <select class="task-status-select form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-300" data-task-id="{{ $task->id }}" data-project-id="{{ $task->project_id }}">
                        <option value="not_started" @if($task->status == 'not_started') selected @endif>未着手</option>
                        <option value="in_progress" @if($task->status == 'in_progress') selected @endif>進行中</option>
                        <option value="completed" @if($task->status == 'completed') selected @endif>完了</option>
                        <option value="on_hold" @if($task->status == 'on_hold') selected @endif>保留中</option>
                        <option value="cancelled" @if($task->status == 'cancelled') selected @endif>キャンセル</option>
                    </select>
                </td>
                @endif
                <td class="px-3 py-3 whitespace-nowrap text-sm font-medium align-top">
                    <div class="flex items-center space-x-1">
                        <x-icon-button
                            :href="route('projects.tasks.edit', [$task->project, $task])"
                            icon="fas fa-edit"
                            title="編集"
                            color="blue" />
                        @if(!($isFolderView ?? false) || $task->files->isEmpty())
                        <form action="{{ route('projects.tasks.destroy', [$task->project, $task]) }}" method="POST" class="inline-block" onsubmit="return confirm('本当に削除しますか？{{ ($isFolderView ?? false) ? 'フォルダ内のすべての工程も削除されます。' : '' }}');"> @csrf @method('DELETE')
                            <x-icon-button
                                icon="fas fa-trash"
                                title="削除"
                                color="red"
                                type="submit" />
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @if(($isFolderView ?? false) && $task->files->count() > 0)
                <tr id="folder-files-{{ $task->id }}" class="hidden">
                    <td colspan="6" class="p-0">
                        <div class="pl-[calc(theme(spacing.4)_+_theme(spacing.12))] pr-4 py-3 bg-gray-50 dark:bg-gray-700/50">
                            <h6 class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-2"><i class="fas fa-paperclip mr-1"></i> 添付ファイル</h6>
                            <ul class="space-y-2 file-list-container" data-folder-id="{{ $task->id }}">
                                @foreach($task->files as $file)
                                <li class="flex items-center justify-between text-xs py-1" id="folder-file-item-{{ $file->id }}">
                                    <div class="flex items-center min-w-0">
                                        @if (Str::startsWith($file->mime_type, 'image/'))
                                            <img src="{{ route('projects.tasks.files.show', [$file->task->project, $file->task, $file]) }}"
                                                 alt="{{ $file->original_name }}"
                                                 class="flex-shrink-0 inline-block h-10 w-10 rounded object-cover cursor-pointer preview-image mr-2 border border-gray-300 dark:border-gray-600"
                                                 data-full-image-url="{{ route('projects.tasks.files.show', [$file->task->project, $file->task, $file]) }}">
                                        @else
                                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded mr-2">
                                                <i class="fas fa-file-alt fa-lg text-gray-400 dark:text-gray-500"></i>
                                            </div>
                                        @endif
                                        <div class="truncate min-w-0">
                                            <a href="{{ route('projects.tasks.files.download', [$file->task->project, $file->task, $file]) }}" class="text-blue-600 hover:underline dark:text-blue-400 truncate block" title="{{ $file->original_name }}">
                                                {{ $file->original_name }}
                                            </a>
                                            <span class="text-gray-500 dark:text-gray-400 block">({{ round($file->size / 1024, 1) }} KB)</span>
                                        </div>
                                    </div>
                                    <button type="button" class="folder-file-delete-btn p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 flex-shrink-0 ml-2"
                                            data-url="{{ route('projects.tasks.files.destroy', [$file->task->project, $file->task, $file]) }}"
                                            data-file-id="{{ $file->id }}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </td>
                </tr>
            @endif
        @empty
            <tr><td colspan="{{ ($isFolderView ?? false) ? 6 : (($isMilestoneView ?? false) ? 6 : 9) }}" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">表示する{{ ($isFolderView ?? false) ? 'フォルダ' : (($isMilestoneView ?? false) ? '重要納期' : '工程') }}がありません</td></tr>
        @endforelse
    </tbody>
</table>