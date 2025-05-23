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
    @endphp

    <tr
        class="project-{{ $project->id }}-tasks {{ $task->parent_id ? 'task-parent-' . $task->parent_id : '' }} {{ $rowClass }} task-level-{{$level}}">
        <td class="gantt-sticky-col position-relative">
            <div class="d-flex justify-content-between align-items-center">
                <div class="task-name-column d-flex align-items-center" style="padding-left: calc(15px + {{ $level * 20 }}px);">
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
                        <i class="fas fa-flag text-danger" title="マイルストーン"></i>
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
                    @if(!$task->is_folder && !$task->is_milestone)
                        <a href="{{ route('projects.tasks.create', ['project' => $project->id, 'parent' => $task->id]) }}"
                            class="btn btn-sm btn-outline-primary" title="子タスク追加">
                            <i class="fas fa-plus"></i>
                        </a>
                    @endif
                    <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="btn btn-sm btn-outline-warning" title="編集">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('本当に削除しますか？');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="削除">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
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

                if ($hasMilestone || $hasBar) $classes[] = 'has-bar';
            @endphp
            <td class="gantt-cell {{ implode(' ', $classes) }} p-0" data-date="{{ $dateStr }}">
                @if($hasMilestone)
                    <div class="milestone-diamond" style="background-color: {{ $task->project->color ?? $project->color }};"></div>
                    <div class="gantt-tooltip">
                        <div class="tooltip-content">
                            {{ $task->name }} (マイルストーン)<br>
                            {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('Y/m/d') : '' }}
                        </div>
                        <div class="tooltip-arrow"></div>
                    </div>
                @elseif($hasBar)
                    {{-- 進捗バー表示を削除し、単色のバーにする --}}
                    <div class="h-100 w-100 gantt-bar" style="background-color: {{ $task->project->color ?? $project->color }}; opacity: 0.3;">
                        {{-- <div class="task-progress" id="task-progress-bar-{{ $task->id }}" data-progress="{{ $task->progress }}"
                            style="width: {{ $task->progress }}%; height: 100%; background-color: rgba(255, 255, 255, 0.3);"></div> --}}
                    </div>
                    <div class="gantt-tooltip task">
                        <div class="tooltip-content">
                            {{ $task->name }}<br>
                            期間: {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('Y/m/d') : '' }} 〜 {{ $taskEndDate ? \Carbon\Carbon::parse($taskEndDate)->format('Y/m/d') : '' }}<br>
                            {{-- 進捗: {{ $task->progress }}%<br> --}} {{-- 進捗表示もツールチップから削除 --}}
                            担当: {{ $task->assignee ?? '未割当' }}
                        </div>
                        <div class="tooltip-arrow"></div>
                    </div>
                @endif
            </td>
        @endfor
    </tr>

    @if($task->children->count() > 0 && !$task->is_folder && !$task->is_milestone)
        @include('gantt.partials.task_rows', ['tasks' => $task->children->sortBy(function($childTask) { return $childTask->start_date ?? '9999-12-31'; }), 'project' => $project, 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => $level + 1])
    @endif
@endforeach