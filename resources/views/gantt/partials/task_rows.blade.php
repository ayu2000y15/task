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
            $daysUntilDue = $now->diffInDays($task->end_date, false);

            if ($task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled') {
                $rowClass = 'task-overdue';
            } elseif ($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled') {
                $rowClass = 'task-due-soon';
            }
        }
        // 親から引き継いだキャラクターID、またはタスク自身のキャラクターIDを使用
        $taskCharacterId = $task->character_id ?? ($parent_character_id ?? null);
    @endphp

    <tr
        class="project-{{ $project->id }}-tasks {{ $task->parent_id ? 'task-parent-' . $task->parent_id : ($taskCharacterId ? 'task-parent-char-' . $taskCharacterId : '') }} {{ $rowClass }} task-level-{{$level}}"
        data-project-id-for-toggle="{{ $project->id }}" {{ $taskCharacterId ? 'data-character-id-for-toggle=' . $taskCharacterId : '' }}>
        <td class="gantt-sticky-col">
            <div class="d-flex justify-content-between align-items-center h-100">
                <div class="task-name-column d-flex align-items-center" style="--level: {{ $level }}; --char-level: {{ $taskCharacterId && $level > 0 ? 1 : 0 }};">
                    <span style="width:20px; text-align:center;" class="me-1 task-primary-icon">
                    @if(!$task->is_folder && !$task->is_milestone)
                        @switch($task->status)
                            @case('completed') <i class="fas fa-check-circle text-success" title="完了"></i> @break
                            @case('in_progress') <i class="fas fa-play-circle text-primary" title="進行中"></i> @break
                            @case('on_hold') <i class="fas fa-pause-circle text-warning" title="保留中"></i> @break
                            @case('cancelled') <i class="fas fa-times-circle text-danger" title="キャンセル"></i> @break
                            @default <i class="far fa-circle text-secondary" title="未着手"></i>
                        @endswitch
                    @elseif($task->is_folder)
                        <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="text-primary" title="{{ $task->name }} (フォルダ)">
                            <i class="fas fa-folder-open"></i>
                        </a>
                    @elseif($task->is_milestone)
                        <i class="fas fa-flag" title="重要納期"></i>
                    @endif
                    </span>

                    @if($task->children->count() > 0 && !$task->is_folder && !$task->is_milestone)
                        <span class="toggle-children me-1" data-task-id="{{ $task->id }}" style="width: 20px; text-align: center; cursor:pointer;">
                            <i class="fas fa-chevron-down"></i>
                        </span>
                    @else
                        <span class="me-1" style="width: 20px; display: inline-block;"></span>
                    @endif

                    @if($task->is_milestone || $task->is_folder)
                        <span>{{ $task->name }}</span>
                    @else
                        <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="text-decoration-none">{{ $task->name }}</a>
                    @endif

                    @if(!$task->is_folder && !$task->is_milestone && $task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled']))
                        <span class="ms-2 badge bg-danger">期限切れ</span>
                    @elseif(!$task->is_folder && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                        <span class="ms-2 badge bg-warning text-dark">あと{{ $daysUntilDue }}日</span>
                    @endif
                </div>
                <div class="task-actions">
                    @can('create', \App\Models\Task::class)
                        @if(!$task->is_folder && !$task->is_milestone)
                            <a href="{{ route('projects.tasks.create', ['project' => $project->id, 'parent' => $task->id, 'character_id_for_child' => $taskCharacterId]) }}"
                                class="btn btn-sm btn-outline-primary" title="子工程追加">
                                <i class="fas fa-plus"></i>
                            </a>
                        @endif
                    @endcan
                    @can('update', $task)
                        @if($task->is_folder)
                        <button type="button" class="btn btn-sm btn-outline-success gantt-upload-file-btn"
                                data-bs-toggle="modal" data-bs-target="#ganttFileUploadModal"
                                data-project-id="{{ $project->id }}" data-task-id="{{ $task->id }}" data-task-name="{{ $task->name }}"
                                title="ファイルアップロード">
                            <i class="fas fa-upload"></i>
                        </button>
                        @endif
                        <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="btn btn-sm btn-outline-warning" title="編集">
                            <i class="fas fa-edit"></i>
                        </a>
                    @endcan
                    @can('delete', $task)
                        <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('本当に削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="削除">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </td>
        <td class="detail-column editable-cell" data-field="assignee" data-task-id="{{ $task->id }}"
            data-value="{{ $task->assignee }}">
            {{ $task->assignee ?? '-' }}
        </td>
        <td class="detail-column assignee-edit-form d-none">
            <input type="text" class="form-control form-control-sm assignee-input" value="{{ $task->assignee }}"
                data-task-id="{{ $task->id }}" data-project-id="{{ $project->id }}">
        </td>
        <td class="detail-column">{{ $task->is_folder ? '-' : ($task->duration ? $task->duration . '日' : '-') }}</td>
        <td class="detail-column">{{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('Y/m/d') : '-' }}</td>
        <td class="detail-column">{{ $taskEndDate ? \Carbon\Carbon::parse($taskEndDate)->format('Y/m/d') : '-' }}</td>
        <td class="detail-column">
            @if($task->is_folder || $task->is_milestone)
                -
            @else
                <select class="form-select form-select-sm status-select" id="status-select-{{ $task->id }}"
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

                $hasMilestone = $task->is_milestone && $taskStartDate && $dateStr === $taskStartDate;
                $hasBar = !$task->is_folder && !$task->is_milestone && $taskStartDate && $taskEndDate && $startPosition >= 0 && $i >= $startPosition && $i < ($startPosition + $taskLength);
                $barColor = $task->character->project->color ?? ($task->project->color ?? '#6c757d');

                if ($hasMilestone || $hasBar) $classes[] = 'has-bar';
            @endphp
            <td class="gantt-cell {{ implode(' ', $classes) }} p-0" data-date="{{ $dateStr }}">
                @if($hasMilestone)
                    <div class="milestone-diamond" style="background-color: {{ $barColor }};"></div>
                    <div class="gantt-tooltip">
                        <div class="tooltip-content">
                            {{ $task->name }} (重要納期)<br>
                            {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('Y/m/d') : '' }}
                        </div>
                        <div class="tooltip-arrow"></div>
                    </div>
                @elseif($hasBar && !$task->is_folder && !$task->is_milestone)
                    <div class="h-100 w-100 gantt-bar" style="background-color: {{ $barColor }}; opacity: 0.7;">
                    </div>
                    <div class="gantt-tooltip task">
                        <div class="tooltip-content">
                            {{ $task->name }}<br>
                            期間: {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('m/d') : '' }}〜{{ $taskEndDate ? \Carbon\Carbon::parse($taskEndDate)->format('m/d') : '' }}<br>
                            担当: {{ $task->assignee ?? '未割当' }}
                        </div>
                        <div class="tooltip-arrow"></div>
                    </div>
                @endif
            </td>
        @endfor
    </tr>

    @if($task->children->count() > 0 && !$task->is_folder && !$task->is_milestone)
        @include('gantt.partials.task_rows', ['tasks' => $task->children->sortBy(function($childTask) { return $childTask->start_date ?? '9999-12-31'; }), 'project' => $project, 'character' => $character ?? ($task->character ?? null), 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => $level + 1, 'parent_character_id' => $taskCharacterId])
    @endif
@endforeach