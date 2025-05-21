@foreach($tasks->where('parent_id', null)->sortBy('start_date') as $task)
    @php
        $taskStartDate = $task->start_date->format('Y-m-d');
        $taskEndDate = $task->end_date->format('Y-m-d');

        // タスク開始位置と長さを計算
        $startPosition = 0;
        $taskLength = 0;

        foreach ($dates as $index => $date) {
            $currentDate = $date['date']->format('Y-m-d');

            if ($currentDate === $taskStartDate) {
                $startPosition = $index;
            }

            if ($currentDate >= $taskStartDate && $currentDate <= $taskEndDate) {
                $taskLength++;
            }
        }

        // ステータスに応じたクラス
        $statusClass = '';
        switch ($task->status) {
            case 'not_started':
                $statusClass = 'text-secondary';
                break;
            case 'in_progress':
                $statusClass = 'text-primary';
                break;
            case 'completed':
                $statusClass = 'text-success';
                break;
            case 'on_hold':
                $statusClass = 'text-warning';
                break;
            case 'cancelled':
                $statusClass = 'text-danger';
                break;
        }

        // ステータスの日本語表示
        $statusLabels = [
            'not_started' => '未着手',
            'in_progress' => '進行中',
            'completed' => '完了',
            'on_hold' => '保留中',
            'cancelled' => 'キャンセル',
        ];
    @endphp

    <tr class="project-{{ $project->id }}-tasks {{ $task->parent_id ? 'task-parent-' . $task->parent_id : '' }}">
        <td>
            <div class="d-flex justify-content-between">
                <div class="task-name" style="padding-left: {{ $level * 20 }}px;">
                    @if($task->children->count() > 0 || $task->is_folder)
                        <span class="toggle-children" data-project-id="{{ $project->id }}" data-task-id="{{ $task->id }}">
                            <i class="fas fa-chevron-down"></i>
                        </span>
                    @else
                        <span style="width: 20px; display: inline-block;"></span>
                    @endif

                    <span class="task-icon">
                        @if($task->is_milestone)
                            <i class="fas fa-flag"></i>
                        @elseif($task->is_folder)
                            <i class="fas fa-folder"></i>
                        @else
                            <i class="fas fa-tasks"></i>
                        @endif
                    </span>

                    <span class="{{ $statusClass }}">{{ $task->name }}</span>
                </div>
                <div class="task-actions">
                    <a href="{{ route('projects.tasks.create', [$project, 'parent' => $task->id]) }}"
                        class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus"></i>
                    </a>
                    <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('本当に削除しますか？');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </td>
        <td class="detail-column">{{ $task->assignee ?? '-' }}</td>
        <td class="detail-column">{{ $task->duration }}日</td>
        <td class="detail-column">{{ $task->start_date->format('Y/m/d') }}</td>
        <td class="detail-column">{{ $task->end_date->format('Y/m/d') }}</td>
        <td class="detail-column">
            <select class="form-select form-select-sm status-select" id="status-select-{{ $task->id }}"
                data-task-id="{{ $task->id }}" data-project-id="{{ $project->id }}">
                @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}" {{ $task->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </td>

        <!-- タスクのガントバー -->
        @for($i = 0; $i < count($dates); $i++)
            @php
                $dateStr = $dates[$i]['date']->format('Y-m-d');
                $isHoliday = isset($holidays[$dateStr]);
                $classes = [];

                if ($dates[$i]['is_saturday']) {
                    $classes[] = 'saturday';
                } elseif ($dates[$i]['is_sunday'] || $isHoliday) {
                    $classes[] = 'sunday';
                }

                if ($dates[$i]['date']->isSameDay($today)) {
                    $classes[] = 'today';
                }
            @endphp
            <td class="gantt-cell {{ implode(' ', $classes) }} p-0">
                @if($i >= $startPosition && $i < ($startPosition + $taskLength))
                    @if($task->is_milestone && $i === $startPosition)
                        <div class="milestone-diamond" style="background-color: {{ $task->color }};" data-task-id="{{ $task->id }}"
                            data-project-id="{{ $project->id }}" data-start-date="{{ $taskStartDate }}"></div>
                    @elseif(!$task->is_milestone && $i === $startPosition)
                        <div class="task-bar" style="background-color: {{ $task->color }}; width: {{ $taskLength * 30 }}px;"
                            data-task-id="{{ $task->id }}" data-project-id="{{ $project->id }}" data-start-date="{{ $taskStartDate }}"
                            data-end-date="{{ $taskEndDate }}" data-duration="{{ $task->duration }}">
                            <div class="task-progress" id="task-progress-bar-{{ $task->id }}" style="width: {{ $task->progress }}%;">
                            </div>
                        </div>
                    @endif
                @endif
            </td>
        @endfor
    </tr>

    <!-- 子タスク -->
    @if($task->children->count() > 0)
        @include('gantt.partials.task_rows', ['tasks' => $task->children, 'project' => $project, 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => $level + 1])
    @endif
@endforeach