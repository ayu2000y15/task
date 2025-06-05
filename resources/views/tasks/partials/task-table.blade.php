{{-- resources/views/tasks/partials/task-table.blade.php --}}
{{-- OR resources/views/projects/partials/projects-task-table.blade.php --}}
<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="{{ $tableId ?? '' }}">
    <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
            <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 pl-4 pr-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12"></th>
            <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[250px] sm:min-w-[300px]">{{ ($isFolderView ?? false) ? 'フォルダ名' : (($isMilestoneView ?? false) ? '重要納期名' : '工程名') }}</th>

            @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && !isset($character)) {{-- $characterの存在チェックを projects-task-table.blade.php に合わせて追加 --}}
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">キャラクター</th>
            @endif

            @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">担当者</th>
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">開始日時</th>
            @endif

            @if($isMilestoneView ?? false)
                 <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">日付</th>
            @elseif(!($isFolderView ?? false)) {{-- フォルダビューでない場合に終了日を表示 --}}
                <th scope="col" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">終了日時</th>
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
                if (!($isFolderView ?? false) && $task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled'])) {
                    $rowClass = 'bg-red-50 dark:bg-red-900/50';
                } elseif (!($isFolderView ?? false) && !($isMilestoneView ?? false) && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled'])) {
                    $rowClass = 'bg-yellow-50 dark:bg-yellow-700/50';
                }
                $hoverClass = !empty($task->description) ? 'task-row-hoverable' : '';
                $level = $task->level ?? 0; // Get task level for indentation
                // Ensure $task->children is loaded if used for $hasChildren, ideally eager loaded in controller
                // For example: $hasChildren = $task->relationLoaded('children') ? $task->children->isNotEmpty() : false;
                // Or ensure children are always eager loaded when this partial is used.
                // Assuming children relation is loaded:
                $hasChildren = $task->children->isNotEmpty();
            @endphp

            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $rowClass }} {{ $hoverClass }} @if($task->parent_id) child-row child-of-{{ $task->parent_id }} @endif"
                @if($task->parent_id) style="display:none;" @endif
                data-task-id="{{ $task->id }}"
                data-project-id="{{ $task->project_id }}"
                @if(!empty($task->description)) data-task-description="{{ htmlspecialchars($task->description) }}" @endif
                data-progress="{{ $task->progress ?? 0 }}">

                @if(($isFolderView ?? false) && $task->is_folder)
                    @can('fileView', $task)
                    <td class="pl-4 pr-2 py-3 whitespace-nowrap align-top">
                        <a href="{{ route('projects.show', $task->project) }}" class="flex items-center group">
                            <span class="w-6 h-6 flex items-center justify-center rounded text-white text-xs font-bold mr-2 flex-shrink-0" style="background-color: {{ $task->project->color }};">
                                {{ mb_substr($task->project->title, 0, 1) }}
                            </span>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 align-top">
                        <div class="flex items-start" style="padding-left: {{ $level * 1.5 }}rem;">
                            <div class="task-toggle-container mr-1" style="width: 1.2em; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 0.125rem;">
                                @if($hasChildren)
                                    <a href="#" class="task-toggle-trigger text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-task-id="{{ $task->id }}" aria-expanded="false" title="子要素を展開/折りたたむ">
                                        <i class="fas fa-chevron-right toggle-icon fa-fw"></i>
                                    </a>
                                @endif
                            </div>
                            <span class="task-status-icon-wrapper mr-2 mt-1 flex-shrink-0">
                                <i class="fas fa-folder text-blue-500" title="フォルダ"></i>
                            </span>
                            <div class="min-w-0">
                                <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="hover:text-blue-600 dark:hover:text-blue-400 whitespace-normal break-words inline-block">
                                    {{ $task->name }}
                                </a>
                                @if (!empty($task->description))
                                    <i class="far fa-comment-alt ml-1 text-gray-400 dark:text-gray-500 fa-xs" title="メモあり"></i>
                                @endif
                                @if($task->files->count() > 0)
                                    @can('fileView', $task)
                                        <button type="button" class="ml-2 text-xs text-blue-500 hover:underline toggle-folder-files"
                                                data-target="folder-files-{{ $tableId ?? 'default' }}-{{ $task->id }}">
                                            ファイル表示 ({{ $task->files->count() }}) <i class="fas fa-chevron-down fa-xs"></i>
                                        </button>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ $task->parent->name ?? '-' }}</td>
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top"><i class="fas fa-file mr-1"></i> {{ $task->files->count() }}</td>
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
                                @if($task->files->isEmpty())
                                <form action="{{ route('projects.tasks.destroy', [$task->project, $task]) }}" method="POST" class="inline-block" onsubmit="return confirm('本当に削除しますか？フォルダ内のすべての工程も削除されます。');"> @csrf @method('DELETE')
                                    <x-icon-button
                                        icon="fas fa-trash"
                                        title="削除"
                                        color="red"
                                        type="submit" />
                                </form>
                                @endif
                            @endcan
                        </div>
                    </td>
                    @endcan
                @elseif(!($isFolderView ?? false)) {{-- 通常の工程または重要納期の場合 --}}
                    <td class="pl-4 pr-2 py-3 whitespace-nowrap align-top">
                        <a href="{{ route('projects.show', $task->project) }}" class="flex items-center group">
                            <span class="w-6 h-6 flex items-center justify-center rounded text-white text-xs font-bold mr-2 flex-shrink-0" style="background-color: {{ $task->project->color }};">
                                {{ mb_substr($task->project->title, 0, 1) }}
                            </span>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 align-top">
                        <div class="flex items-center gap-x-3">
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

                            <div class="flex items-start flex-grow min-w-0" style="padding-left: {{ $level * 1.5 }}rem;">
                                <div class="task-toggle-container mr-1" style="width: 1.2em; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 0.125rem;">
                                     @if($hasChildren)
                                        <a href="#" class="task-toggle-trigger text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-task-id="{{ $task->id }}" aria-expanded="false" title="子工程を展開/折りたたむ">
                                            <i class="fas fa-chevron-right toggle-icon fa-fw"></i>
                                        </a>
                                    @endif
                                </div>
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
                                    <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="hover:text-blue-600 dark:hover:text-blue-400 whitespace-normal break-words inline-block">
                                        {{ $task->name }}
                                        @if(!($isMilestoneView ?? false) && !($isFolderView ?? false) && !empty($task->parent->name) && $task->parent->is_folder) <span class="text-xs text-gray-400 dark:text-gray-500 block"> ({{ $task->parent->name }})</span> @endif
                                    </a>
                                    @if (!($isFolderView ?? false) && !($isMilestoneView ?? false) && $task->parent && !$task->parent->is_folder)
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mt-0.5" title="Parent Task: {{ $task->parent->name }}">
                                            <i class="fas fa-level-up-alt fa-rotate-90 fa-xs mr-1 text-gray-400 dark:text-gray-500"></i>{{ \Illuminate\Support\Str::limit($task->parent->name, 30) }}
                                        </span>
                                    @endif
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
                         @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && $task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled'])) <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">期限切れ</span> @endif
                         @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >=0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                            <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100">あと{{ floor($daysUntilDue) }}日</span>
                         @endif
                    </td>
                    @if(!($isFolderView ?? false) && !($isMilestoneView ?? false) && !isset($character)) {{-- $characterの存在チェック --}}
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ $task->character->name ?? '-' }}</td>
                    @endif

                    @if(!($isFolderView ?? false) && !($isMilestoneView ?? false))
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">
                        <div class="editable-cell" data-task-id="{{ $task->id }}" data-project-id="{{ $task->project_id }}" data-field="assignee" data-current-value="{{ $task->assignee }}">{{ $task->assignee ?? '-' }}</div>
                    </td>
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ optional($task->start_date)->format('n/j H:i') }}</td>
                    @endif

                    @if($isMilestoneView ?? false) {{-- 重要納期時 --}}
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">{{ optional($task->start_date)->format('n/j') }}</td> {{-- 日付 --}}
                    @elseif(!($isFolderView ?? false)) {{-- 通常工程時 --}}
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top"> {{-- 終了日 --}}
                         @if($task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled']))
                            <span class="text-red-600 dark:text-red-400">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ $task->end_date->format('n/j H:i') }}
                            </span>
                        @elseif(!$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                            <span class="text-yellow-600 dark:text-yellow-400">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                {{ $task->end_date->format('n/j H:i') }}
                            </span>
                        @else
                            {{ optional($task->end_date)->format('n/j H:i') }}
                        @endif
                    </td>
                    @endif

                    @if(!($isFolderView ?? false) && !($isMilestoneView ?? false)) {{-- 通常工程時 --}}
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">
                        @php
                            $duration = $task->duration;
                            if($duration == 0) {
                                $duration = '-';
                            } else if($duration >= 60) {
                                $duration = $duration / 60;
                                $duration = $duration . '時間';
                            } else {
                                $duration = $duration . '分';
                            }
                        @endphp
                        {{ $duration }}
                    </td>
                    @endif

                    @if(!($isFolderView ?? false)) {{-- フォルダビューでない場合 (通常工程 or 重要納期) --}}
                    <td class="hidden sm:table-cell px-4 py-3 whitespace-nowrap text-sm align-top">
                        @if(!$task->is_milestone && !$task->is_folder) {{-- 重要納期とフォルダでなければステータスselect表示 --}}
                        <select class="task-status-select form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-300" data-task-id="{{ $task->id }}" data-project-id="{{ $task->project_id }}">
                            <option value="not_started" @if($task->status == 'not_started') selected @endif>未着手</option>
                            <option value="in_progress" @if($task->status == 'in_progress') selected @endif>進行中</option>
                            <option value="completed" @if($task->status == 'completed') selected @endif>完了</option>
                            <option value="on_hold" @if($task->status == 'on_hold') selected @endif>保留中</option>
                            <option value="cancelled" @if($task->status == 'cancelled') selected @endif>キャンセル</option>
                        </select>
                        @else
                        <span class="text-sm text-gray-500 dark:text-gray-400">-</span> {{-- 重要納期またはフォルダはステータスなし --}}
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
                @endif
            </tr>
            @if(($isFolderView ?? false) && $task->is_folder) {{-- This is for the folder's file list, separate from task hierarchy --}}
                @if($task->files->count() > 0)
                    @can('fileView', $task)
                        <tr id="folder-files-{{ $tableId ?? 'default' }}-{{ $task->id }}" class="hidden"> {{-- Ensure tableId has a fallback --}}
                            <td colspan="5" class="p-0"> {{-- Adjusted colspan if needed based on folder view columns --}}
                                <div class="pl-[calc(theme(spacing.4)_+_theme(spacing.12))] pr-4 py-3 bg-gray-50 dark:bg-gray-700/50">
                                    <h6 class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-2"><i class="fas fa-paperclip mr-1"></i> 添付ファイル</h6>
                                    @include('tasks.partials.file-list-tailwind', ['files' => $task->files, 'project' => $task->project, 'task' => $task])
                                </div>
                            </td>
                        </tr>
                    @endcan
                @endif
            @endif
        @empty
            @php
                // Colspan calculation logic from projects-task-table.blade.php for consistency
                $colCount = 6;
                if (!($isFolderView ?? false) && !($isMilestoneView ?? false) && !isset($character)) $colCount++;
                if (!($isFolderView ?? false) && !($isMilestoneView ?? false)) $colCount++;
                if (!($isFolderView ?? false) && !($isMilestoneView ?? false)) $colCount++;
                if ($isFolderView ?? false) $colCount = 5;

                // Recalculate colspan more directly based on visible headers
                if ($isFolderView ?? false) {
                    $colspan = 5; // Icon, Name, Parent, Files, Actions
                } elseif ($isMilestoneView ?? false) {
                    // #, Name, Assignee, StartDate(hidden), Date, Status, Actions
                    // Visible: #, Name, Date, Status, Actions = 5
                    // Actual columns: #, Name, (no character), Assignee, (no start), Date (milestone), (no duration), Status, Actions
                    // Let's count headers: #, Name, Date, Status, Actions -> 5
                    // If including Assignee, StartDate (hidden), that makes it different.
                    // The original template calculated 6.
                    // #, 重要納期名, 日付(sm), ステータス(sm), 操作 => 5 headers visible on `sm`
                    // #, Name, (NO Char), Assignee, StartDate (HIDDEN), Date (YES), (NO Duration), Status (YES), Actions (YES)
                    // Visible headers: #, Name, Assignee, Date, Status, Actions => 6
                    $colspan = 6;
                } else { // Normal Task View
                    // #, Name, Character, Assignee, Start, End, Duration, Status, Action
                    $colspan = 6; // Base: #, Name, Assignee, Start, End, Status, Action
                    if (!isset($character)) $colspan++; // Character column present
                    $colspan++; // Duration column present
                    // Original calculation was 9.
                    // Let's use the template's original logic.
                     $colspan = 9;
                     if ($isFolderView ?? false) {
                         $colspan = 5;
                     } elseif ($isMilestoneView ?? false) {
                         $colspan = 6;
                     }
                }
            @endphp
            <tr><td colspan="{{ $colspan }}" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">表示する{{ ($isFolderView ?? false) ? 'フォルダ' : (($isMilestoneView ?? false) ? '重要納期' : '工程') }}がありません</td></tr>
        @endforelse
    </tbody>
</table>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleTriggers = document.querySelectorAll('.task-toggle-trigger');

    toggleTriggers.forEach(trigger => {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            const taskId = this.dataset.taskId;
            const icon = this.querySelector('.toggle-icon');
            const isExpanded = this.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
                this.setAttribute('aria-expanded', 'false');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
                this.setAttribute('aria-expanded', 'true');
            }
            toggleChildRows(taskId, !isExpanded);
        });
    });

    function toggleChildRows(parentId, show) {
        const childRows = document.querySelectorAll('tr.child-row.child-of-' + parentId);
        childRows.forEach(row => {
            // Toggle direct children's display
            row.style.display = show ? '' : 'none';

            // Handle visibility of deeper descendants
            const currentTaskId = row.dataset.taskId; // ID of the current child row
            const nestedToggleTrigger = document.querySelector('.task-toggle-trigger[data-task-id="' + currentTaskId + '"]');

            if (!show) { // If collapsing the parent (parentId)
                // If this child (currentTaskId) was an expanded parent itself,
                // visually collapse its icon and recursively hide its children.
                if (nestedToggleTrigger && nestedToggleTrigger.getAttribute('aria-expanded') === 'true') {
                    const nestedIcon = nestedToggleTrigger.querySelector('.toggle-icon');
                    if (nestedIcon) {
                        nestedIcon.classList.remove('fa-chevron-down');
                        nestedIcon.classList.add('fa-chevron-right');
                    }
                    nestedToggleTrigger.setAttribute('aria-expanded', 'false');
                    // Recursively hide children of this (now hidden) child row
                    toggleChildRows(currentTaskId, false);
                }
            } else { // If expanding the parent (parentId)
                // If this child (currentTaskId) was previously expanded (and now its direct parent is telling it to show),
                // then its children should also be shown.
                if (nestedToggleTrigger && nestedToggleTrigger.getAttribute('aria-expanded') === 'true') {
                    toggleChildRows(currentTaskId, true); // Recursively show children
                }
            }
        });
    }
});
</script>