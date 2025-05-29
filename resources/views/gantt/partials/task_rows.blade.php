@foreach($tasks as $task)
    @php
        $taskStartDate = $task->start_date ? $task->start_date->format('Y-m-d') : null;
        $taskEndDate = $task->end_date ? $task->end_date->format('Y-m-d') : null;
        $now = \Carbon\Carbon::now()->startOfDay();

        $startPosition = -1;
        $taskLength = 0;

        if ($taskStartDate && $taskEndDate) {
            foreach ($dates as $index => $dateInfo) {
                $currentDate = $dateInfo['date']->format('Y-m-d');
                if ($currentDate === $taskStartDate) {
                    $startPosition = $index;
                }
                if ($currentDate >= $taskStartDate && $currentDate <= $taskEndDate) {
                    $taskLength++;
                }
            }
        }

        $statusLabels = [
            'not_started' => '未着手',
            'in_progress' => '進行中',
            'completed' => '完了',
            'on_hold' => '保留中',
            'cancelled' => 'キャンセル',
        ];

        $rowClass = '';
        $daysUntilDue = null;
        if (!$task->is_folder && !$task->is_milestone && $task->end_date) {
            $daysUntilDue = $now->diffInDays($task->end_date->startOfDay(), false);
        }
        // 親から引き継いだキャラクターID、またはタスク自身のキャラクターIDを使用
        $taskCharacterId = $task->character_id ?? ($parent_character_id ?? null);
        $hoverClass = !empty($task->description) ? 'task-row-hoverable' : ''; // For JS tooltip
    @endphp

    @if($task->is_folder)
        @can('fileView', $task)
        <tr
        class="hover:bg-gray-100 dark:hover:bg-gray-700/50 project-{{ $project->id }}-tasks {{ $task->parent_id ? 'task-parent-' . $task->parent_id : ($taskCharacterId ? 'task-parent-char-' . $taskCharacterId : '') }} {{ $rowClass }} task-level-{{$level}} {{ $hoverClass }}"
        @if(!empty($task->description)) data-task-description="{{ htmlspecialchars($task->description) }}" @endif
        data-project-id-for-toggle="{{ $project->id }}" {{ $taskCharacterId ? 'data-character-id-for-toggle=' . $taskCharacterId : '' }}>
        <td class="gantt-sticky-col px-3 py-2.5">
            <div class="flex justify-between items-start h-full">
                <div class="gantt-task-name-wrapper" style="padding-left: {{ $level * 20 }}px; @if($taskCharacterId && $level > 0) padding-left: {{ ($level * 20) + 20 }}px; @endif">
                    <div class="gantt-task-icon-toggle-wrapper mr-1">
                        <span class="w-5 text-center task-primary-icon">
                        @if(!$task->is_folder && !$task->is_milestone)
                            @switch($task->status)
                                @case('completed') <i class="fas fa-check-circle text-green-500" title="完了"></i> @break
                                @case('in_progress') <i class="fas fa-play-circle text-blue-500" title="進行中"></i> @break
                                @case('on_hold') <i class="fas fa-pause-circle text-yellow-500" title="保留中"></i> @break
                                @case('cancelled') <i class="fas fa-times-circle text-red-500" title="キャンセル"></i> @break
                                @default <i class="far fa-circle text-gray-400" title="未着手"></i>
                            @endswitch
                        @elseif($task->is_folder)
                            <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="text-blue-600 dark:text-blue-400" title="{{ $task->name }} (フォルダ)">
                                <i class="fas fa-folder-open"></i>
                            </a>
                        @elseif($task->is_milestone)
                            <i class="fas fa-flag text-red-600" title="重要納期"></i>
                        @endif
                        </span>

                        @if($task->children->count() > 0 && !$task->is_folder && !$task->is_milestone)
                            <span class="toggle-children w-5 text-center cursor-pointer" data-task-id="{{ $task->id }}">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        @else
                            <span class="w-5 inline-block"></span>
                        @endif
                    </div>
                    <div class="gantt-task-name-block flex flex-col"> {{-- flex flex-col を追加 --}}
                        <div> {{-- 工程名とメモアイコンを同じ行にまとめるためのdiv (任意) --}}
                            @if($task->is_milestone || $task->is_folder)
                                <span class="gantt-task-name-text">{{ $task->name }}</span>
                            @else
                                <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 gantt-task-name-link">{{ $task->name }}</a>
                            @endif
                            @if (!empty($task->description))
                                <i class="far fa-comment-alt ml-1 text-gray-400 dark:text-gray-500 fa-xs" title="メモあり"></i>
                            @endif
                        </div>

                        {{-- 期限切れ/間近バッジをdivで囲み、mt-1で少し上にマージン --}}
                        @if(!$task->is_folder && !$task->is_milestone && $task->end_date && $task->end_date->startOfDay() < $now && !in_array($task->status, ['completed', 'cancelled']))
                            <div class="mt-1"><span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">期限切れ</span></div>
                        @elseif(!$task->is_folder && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                            <div class="mt-1"><span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100">あと{{ $daysUntilDue }}日</span></div>
                        @endif
                    </div>
                </div>
                <div class="task-actions flex space-x-1 flex-shrink-0">
                    @can('create', \App\Models\Task::class)
                        @if(!$task->is_folder && !$task->is_milestone)
                        <x-icon-button
                            :href="route('projects.tasks.create', ['project' => $project->id, 'parent' => $task->id, 'character_id_for_child' => $taskCharacterId])"
                            icon="fas fa-plus"
                            title="子工程追加"
                            color="blue"
                            size="sm" />
                        @endif
                    @endcan
                    @can('update', $task)
                        {{-- @if($task->is_folder)
                        <button type="button" class="p-1 text-green-500 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 gantt-upload-file-btn"
                                x-data @click="$dispatch('open-modal', { name: 'ganttFileUploadModal', projectId: {{ $project->id }}, taskId: {{ $task->id }}, taskName: '{{ htmlspecialchars($task->name, ENT_QUOTES) }}' })"
                                data-project-id="{{ $project->id }}" data-task-id="{{ $task->id }}" data-task-name="{{ $task->name }}"
                                title="ファイルアップロード">
                            <i class="fas fa-upload"></i>
                        </button>
                        @endif --}}
                        <x-icon-button
                            :href="route('projects.tasks.edit', [$project, $task])"
                            icon="fas fa-edit"
                            title="編集"
                            color="yellow"
                            size="sm" />
                    @endcan
                    @can('delete', $task)
                        <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('本当に削除しますか？');">
                            <x-icon-button icon="fas fa-trash" title="削除" color="red" size="sm" type="submit" method="DELETE" />
                        </form>
                    @endcan
                </div>
            </div>
        </td>
        <td class="detail-column px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap editable-cell" data-field="assignee" data-task-id="{{ $task->id }}" data-project-id="{{ $project->id }}" data-current-value="{{ $task->assignee }}">
            <span>{{ $task->assignee ?? '-' }}</span>
        </td>
        <td class="detail-column px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $task->is_folder ? '-' : ($task->duration ? $task->duration . '日' : '-') }}</td>
        @php
            $startDateClass = '';
            $endDateClass = '';
            if (!$task->is_folder && !$task->is_milestone && $task->end_date) {
                if ($task->end_date->startOfDay() < $now && !in_array($task->status, ['completed', 'cancelled'])) {
                    $startDateClass = 'text-red-600 dark:text-red-400 font-semibold';
                    $endDateClass = 'text-red-600 dark:text-red-400 font-semibold';
                } elseif ($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled'])) {
                    $startDateClass = 'text-yellow-600 dark:text-yellow-400 font-semibold';
                    $endDateClass = 'text-yellow-600 dark:text-yellow-400 font-semibold';
                }
            }
        @endphp
        <td class="detail-column px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap {{ $startDateClass }}">
            {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('Y/m/d') : '-' }}
        </td>
        <td class="detail-column px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap {{ $endDateClass }}">
            {{ $taskEndDate ? \Carbon\Carbon::parse($taskEndDate)->format('Y/m/d') : '-' }}
        </td>
        <td class="detail-column px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400">
            @if($task->is_folder || $task->is_milestone)
                -
            @else
                <select class="form-select status-select block w-full pl-3 pr-10 py-1.5 text-xs border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md dark:bg-gray-700 dark:text-gray-300" id="status-select-{{ $task->id }}"
                    data-task-id="{{ $task->id }}" data-project-id="{{ $project->id }}">
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" {{ $task->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            @endif
        </td>

        @for($i = 0; $i < count($dates); $i++)
            @php
                $dateStr = $dates[$i]['date']->format('Y-m-d');
                $isHoliday = isset($holidays[$dateStr]);
                $classes = [];

                if ($dates[$i]['is_saturday']) $classes[] = 'saturday';
                elseif ($dates[$i]['is_sunday'] || $isHoliday) $classes[] = 'sunday';
                if ($dates[$i]['date']->isSameDay($today)) $classes[] = 'today';

                $style = '';
                $hasMilestone = $task->is_milestone && $taskStartDate && $dateStr === $taskStartDate;
                $hasBar = !$task->is_folder && !$task->is_milestone && $taskStartDate && $taskEndDate && $startPosition >= 0 && $i >= $startPosition && $i < ($startPosition + $taskLength);
                $barColor = $task->character->project->color ?? ($task->project->color ?? '#6c757d');

                if ($hasMilestone || $hasBar) $classes[] = 'has-bar';

                if ($hasBar) {
                    // HEX to RGBA conversion
                    $color = ltrim($barColor ?? '#6c757d', '#');
                    $rgb = strlen($color) == 6 ? sscanf($color, "%2x%2x%2x") : (strlen($color) == 3 ? sscanf(str_repeat(substr($color,0,1),2).str_repeat(substr($color,1,1),2).str_repeat(substr($color,2,1),2), "%2x%2x%2x") : [108, 117, 125]);

                    $rgbaColor = sprintf('rgba(%d, %d, %d, 0.3)', $rgb[0], $rgb[1], $rgb[2]);
                    if ($dates[$i]['date']->isSameDay($today)) {
                        // 今日の場合、背景色を重ねて両方見えるようにする
                        $style = "background-image: linear-gradient({$rgbaColor}, {$rgbaColor});";
                    } else {
                        $style = "background-color: {$rgbaColor};";
                    }
                }
            @endphp
            <td class="gantt-cell {{ implode(' ', $classes) }} p-0 relative" data-date="{{ $dateStr }}" style="{{ $style }}">
                @if($hasMilestone)
                    <div class="milestone-diamond" style="background-color: {{ $barColor }}; opacity: 0.3;"></div>
                    <div class="gantt-tooltip" style="top: -50px; left: 50%; transform: translateX(-50%);">
                        <div class="gantt-tooltip-content">
                            {{ $task->name }} (重要納期)<br>
                            {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('Y/m/d') : '' }}
                        </div>
                        <div class="gantt-tooltip-arrow"></div>
                    </div>
                @elseif($hasBar && !$task->is_folder && !$task->is_milestone)
                    {{-- バーのdivは削除され、tdの背景色として描画される --}}
                    <div class="gantt-tooltip task" style="top: -65px; left: 50%; transform: translateX(-50%);"> {{-- Adjusted top for task tooltips --}}
                        <div class="gantt-tooltip-content">
                            {{ $task->name }}<br>
                            期間: {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('m/d') : '' }}〜{{ $taskEndDate ? \Carbon\Carbon::parse($taskEndDate)->format('m/d') : '' }}<br>
                            担当: {{ $task->assignee ?? '未割当' }}
                        </div>
                        <div class="gantt-tooltip-arrow"></div>
                    </div>
                @endif
            </td>
        @endfor
    </tr>
        @endcan
    @else
    <tr
        class="hover:bg-gray-100 dark:hover:bg-gray-700/50 project-{{ $project->id }}-tasks {{ $task->parent_id ? 'task-parent-' . $task->parent_id : ($taskCharacterId ? 'task-parent-char-' . $taskCharacterId : '') }} {{ $rowClass }} task-level-{{$level}} {{ $hoverClass }}"
        @if(!empty($task->description)) data-task-description="{{ htmlspecialchars($task->description) }}" @endif
        data-project-id-for-toggle="{{ $project->id }}" {{ $taskCharacterId ? 'data-character-id-for-toggle=' . $taskCharacterId : '' }}>
        <td class="gantt-sticky-col px-3 py-2.5">
            <div class="flex justify-between items-start h-full">
                <div class="gantt-task-name-wrapper" style="padding-left: {{ $level * 20 }}px; @if($taskCharacterId && $level > 0) padding-left: {{ ($level * 20) + 20 }}px; @endif">
                    <div class="gantt-task-icon-toggle-wrapper mr-1">
                        <span class="w-5 text-center task-primary-icon">
                        @if(!$task->is_folder && !$task->is_milestone)
                            @switch($task->status)
                                @case('completed') <i class="fas fa-check-circle text-green-500" title="完了"></i> @break
                                @case('in_progress') <i class="fas fa-play-circle text-blue-500" title="進行中"></i> @break
                                @case('on_hold') <i class="fas fa-pause-circle text-yellow-500" title="保留中"></i> @break
                                @case('cancelled') <i class="fas fa-times-circle text-red-500" title="キャンセル"></i> @break
                                @default <i class="far fa-circle text-gray-400" title="未着手"></i>
                            @endswitch
                        @elseif($task->is_folder)
                            <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="text-blue-600 dark:text-blue-400" title="{{ $task->name }} (フォルダ)">
                                <i class="fas fa-folder-open"></i>
                            </a>
                        @elseif($task->is_milestone)
                            <i class="fas fa-flag text-red-600" title="重要納期"></i>
                        @endif
                        </span>

                        @if($task->children->count() > 0 && !$task->is_folder && !$task->is_milestone)
                            <span class="toggle-children w-5 text-center cursor-pointer" data-task-id="{{ $task->id }}">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        @else
                            <span class="w-5 inline-block"></span>
                        @endif
                    </div>
                    <div class="gantt-task-name-block flex flex-col"> {{-- flex flex-col を追加 --}}
                        <div> {{-- 工程名とメモアイコンを同じ行にまとめるためのdiv (任意) --}}
                            @if($task->is_milestone || $task->is_folder)
                                <span class="gantt-task-name-text">{{ $task->name }}</span>
                            @else
                                <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 gantt-task-name-link">{{ $task->name }}</a>
                            @endif
                            @if (!empty($task->description))
                                <i class="far fa-comment-alt ml-1 text-gray-400 dark:text-gray-500 fa-xs" title="メモあり"></i>
                            @endif
                        </div>

                        {{-- 期限切れ/間近バッジをdivで囲み、mt-1で少し上にマージン --}}
                        @if(!$task->is_folder && !$task->is_milestone && $task->end_date && $task->end_date->startOfDay() < $now && !in_array($task->status, ['completed', 'cancelled']))
                            <div class="mt-1"><span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">期限切れ</span></div>
                        @elseif(!$task->is_folder && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                            <div class="mt-1"><span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100">あと{{ $daysUntilDue }}日</span></div>
                        @endif
                    </div>
                </div>
                <div class="task-actions flex space-x-1 flex-shrink-0">
                    @can('create', \App\Models\Task::class)
                        @if(!$task->is_folder && !$task->is_milestone)
                        <x-icon-button
                            :href="route('projects.tasks.create', ['project' => $project->id, 'parent' => $task->id, 'character_id_for_child' => $taskCharacterId])"
                            icon="fas fa-plus"
                            title="子工程追加"
                            color="blue"
                            size="sm" />
                        @endif
                    @endcan
                    @can('update', $task)
                        {{-- @if($task->is_folder)
                        <button type="button" class="p-1 text-green-500 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 gantt-upload-file-btn"
                                x-data @click="$dispatch('open-modal', { name: 'ganttFileUploadModal', projectId: {{ $project->id }}, taskId: {{ $task->id }}, taskName: '{{ htmlspecialchars($task->name, ENT_QUOTES) }}' })"
                                data-project-id="{{ $project->id }}" data-task-id="{{ $task->id }}" data-task-name="{{ $task->name }}"
                                title="ファイルアップロード">
                            <i class="fas fa-upload"></i>
                        </button>
                        @endif --}}
                        <x-icon-button
                            :href="route('projects.tasks.edit', [$project, $task])"
                            icon="fas fa-edit"
                            title="編集"
                            color="yellow"
                            size="sm" />
                    @endcan
                    @can('delete', $task)
                        <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('本当に削除しますか？');">
                            <x-icon-button icon="fas fa-trash" title="削除" color="red" size="sm" type="submit" method="DELETE" />
                        </form>
                    @endcan
                </div>
            </div>
        </td>
        <td class="detail-column px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap editable-cell" data-field="assignee" data-task-id="{{ $task->id }}" data-project-id="{{ $project->id }}" data-current-value="{{ $task->assignee }}">
            <span>{{ $task->assignee ?? '-' }}</span>
        </td>
        <td class="detail-column px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $task->is_folder ? '-' : ($task->duration ? $task->duration . '日' : '-') }}</td>
        @php
            $startDateClass = '';
            $endDateClass = '';
            if (!$task->is_folder && !$task->is_milestone && $task->end_date) {
                if ($task->end_date->startOfDay() < $now && !in_array($task->status, ['completed', 'cancelled'])) {
                    $startDateClass = 'text-red-600 dark:text-red-400 font-semibold';
                    $endDateClass = 'text-red-600 dark:text-red-400 font-semibold';
                } elseif ($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled'])) {
                    $startDateClass = 'text-yellow-600 dark:text-yellow-400 font-semibold';
                    $endDateClass = 'text-yellow-600 dark:text-yellow-400 font-semibold';
                }
            }
        @endphp
        <td class="detail-column px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap {{ $startDateClass }}">
            {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('Y/m/d') : '-' }}
        </td>
        <td class="detail-column px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap {{ $endDateClass }}">
            {{ $taskEndDate ? \Carbon\Carbon::parse($taskEndDate)->format('Y/m/d') : '-' }}
        </td>
        <td class="detail-column px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400">
            @if($task->is_folder || $task->is_milestone)
                -
            @else
                <select class="form-select status-select block w-full pl-3 pr-10 py-1.5 text-xs border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md dark:bg-gray-700 dark:text-gray-300" id="status-select-{{ $task->id }}"
                    data-task-id="{{ $task->id }}" data-project-id="{{ $project->id }}">
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" {{ $task->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            @endif
        </td>

        @for($i = 0; $i < count($dates); $i++)
            @php
                $dateStr = $dates[$i]['date']->format('Y-m-d');
                $isHoliday = isset($holidays[$dateStr]);
                $classes = [];

                if ($dates[$i]['is_saturday']) $classes[] = 'saturday';
                elseif ($dates[$i]['is_sunday'] || $isHoliday) $classes[] = 'sunday';
                if ($dates[$i]['date']->isSameDay($today)) $classes[] = 'today';

                $style = '';
                $hasMilestone = $task->is_milestone && $taskStartDate && $dateStr === $taskStartDate;
                $hasBar = !$task->is_folder && !$task->is_milestone && $taskStartDate && $taskEndDate && $startPosition >= 0 && $i >= $startPosition && $i < ($startPosition + $taskLength);
                $barColor = $task->character->project->color ?? ($task->project->color ?? '#6c757d');

                if ($hasMilestone || $hasBar) $classes[] = 'has-bar';

                if ($hasBar) {
                    // HEX to RGBA conversion
                    $color = ltrim($barColor ?? '#6c757d', '#');
                    $rgb = strlen($color) == 6 ? sscanf($color, "%2x%2x%2x") : (strlen($color) == 3 ? sscanf(str_repeat(substr($color,0,1),2).str_repeat(substr($color,1,1),2).str_repeat(substr($color,2,1),2), "%2x%2x%2x") : [108, 117, 125]);

                    $rgbaColor = sprintf('rgba(%d, %d, %d, 0.3)', $rgb[0], $rgb[1], $rgb[2]);
                    if ($dates[$i]['date']->isSameDay($today)) {
                        // 今日の場合、背景色を重ねて両方見えるようにする
                        $style = "background-image: linear-gradient({$rgbaColor}, {$rgbaColor});";
                    } else {
                        $style = "background-color: {$rgbaColor};";
                    }
                }
            @endphp
            <td class="gantt-cell {{ implode(' ', $classes) }} p-0 relative" data-date="{{ $dateStr }}" style="{{ $style }}">
                @if($hasMilestone)
                    <div class="milestone-diamond" style="background-color: {{ $barColor }}; opacity: 0.3;"></div>
                    <div class="gantt-tooltip" style="top: -50px; left: 50%; transform: translateX(-50%);">
                        <div class="gantt-tooltip-content">
                            {{ $task->name }} (重要納期)<br>
                            {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('Y/m/d') : '' }}
                        </div>
                        <div class="gantt-tooltip-arrow"></div>
                    </div>
                @elseif($hasBar && !$task->is_folder && !$task->is_milestone)
                    {{-- バーのdivは削除され、tdの背景色として描画される --}}
                    <div class="gantt-tooltip task" style="top: -65px; left: 50%; transform: translateX(-50%);"> {{-- Adjusted top for task tooltips --}}
                        <div class="gantt-tooltip-content">
                            {{ $task->name }}<br>
                            期間: {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('m/d') : '' }}〜{{ $taskEndDate ? \Carbon\Carbon::parse($taskEndDate)->format('m/d') : '' }}<br>
                            担当: {{ $task->assignee ?? '未割当' }}
                        </div>
                        <div class="gantt-tooltip-arrow"></div>
                    </div>
                @endif
            </td>
        @endfor
    </tr>
    @endif

    @if($task->children->count() > 0 && !$task->is_folder && !$task->is_milestone)
        @include('gantt.partials.task_rows', ['tasks' => $task->children->sortBy(function($childTask) { return $childTask->start_date ?? '9999-12-31'; }), 'project' => $project, 'character' => $character ?? ($task->character ?? null), 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => $level + 1, 'parent_character_id' => $taskCharacterId])
    @endif
@endforeach