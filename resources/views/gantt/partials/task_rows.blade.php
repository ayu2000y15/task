@foreach($tasks as $task)
    @php
        $taskStartDate = $task->start_date ? $task->start_date->format('Y-m-d') : null;
        $taskEndDate = $task->end_date ? $task->end_date->format('Y-m-d') : null;
        $now = \Carbon\Carbon::now()->startOfDay();

        // タスク開始位置と長さを計算
        $startPosition = -1;
        $taskLength = 0;

        if ($taskStartDate && $taskEndDate) { // フォルダでない場合のみ計算
            foreach ($dates as $index => $date) {
                $currentDate = $date['date']->format('Y-m-d');
                if ($currentDate === $taskStartDate) {
                    $startPosition = $index;
                }
                if ($currentDate >= $taskStartDate && $currentDate <= $taskEndDate) {
                    $taskLength++;
                }
            }
        }

        // ステータスに応じた色
        $statusColor = '';
        switch ($task->status) {
            case 'not_started':
                $statusColor = '#6c757d';
                break;
            case 'in_progress':
                $statusColor = '#0d6efd';
                break;
            case 'completed':
                $statusColor = '#198754';
                break;
            case 'on_hold':
                $statusColor = '#ffc107';
                break;
            case 'cancelled':
                $statusColor = '#dc3545';
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

        // 期限切れ・期限間近の判定（フォルダとマイルストーンは除外）
        $rowClass = '';
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
        class="project-{{ $project->id }}-tasks {{ $task->parent_id ? 'task-parent-' . $task->parent_id : '' }} {{ $rowClass }}">
        <td class="gantt-sticky-col position-relative">
            @if(!$task->is_folder)
                <div class="status-indicator status-indicator-{{ $task->id }}" style="background-color: {{ $statusColor }};">
                </div>
            @endif
            <div class="d-flex justify-content-between" style="padding-left: 15px;">
                <div class="task-name">
                    <div style="padding-left: {{ $level * 20 }}px; display: flex; align-items: center;">
                        @if($task->children->count() > 0)
                            <span class="toggle-children" data-project-id="{{ $project->id }}" data-task-id="{{ $task->id }}">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        @elseif($task->is_folder)
                            <span class="toggle-children" data-project-id="{{ $project->id }}" data-task-id="{{ $task->id }}">
                                <i class="fas fa-chevron-down"></i> {{-- フォルダのトグルはD3でリンクに変更予定 --}}
                            </span>
                        @else
                            <span style="width: 20px; display: inline-block;"></span>
                        @endif

                        <span class="task-icon">
                            @if($task->is_milestone)
                                <i class="fas fa-flag text-danger"></i>
                            @elseif($task->is_folder)
                                <i class="fas fa-folder text-primary"></i>
                            @else
                                <i class="fas fa-tasks"></i>
                            @endif
                        </span>

                        <span>{{ $task->name }}</span>

                        @if(!$task->is_folder && !$task->is_milestone && $task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled']))
                            <span class="ms-2 badge bg-danger">期限切れ</span>
                        @elseif(!$task->is_folder && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                            <span class="ms-2 badge bg-warning text-dark">あと{{ $daysUntilDue }}日</span>
                        @endif
                    </div>
                </div>
                <div class="task-actions">
                    @if($task->is_folder)
                        <a href="{{ route('projects.tasks.edit', [$project, $task]) }}#fileUploadDropzone"
                            class="btn btn-sm btn-outline-secondary me-1" title="ファイル管理">
                            <i class="fas fa-file-upload"></i>
                        </a>
                    @elseif(!$task->is_milestone) {{-- マイルストーンでなく、かつフォルダでもない場合（つまり通常タスク）--}}
                        <a href="{{ route('projects.tasks.create', [$project, 'parent' => $task->id]) }}"
                            class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus"></i>
                        </a>
                    @endif
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
            @if($task->is_folder)
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

                if ($dates[$i]['is_saturday']) {
                    $classes[] = 'saturday';
                } elseif ($dates[$i]['is_sunday'] || $isHoliday) {
                    $classes[] = 'sunday';
                }

                if ($dates[$i]['date']->isSameDay($today)) {
                    $classes[] = 'today';
                }

                $hasMilestone = $task->is_milestone && $taskStartDate && $dateStr === $taskStartDate; // マイルストーンは開始日にのみ表示
                $hasBar = !$task->is_folder && !$task->is_milestone && $taskStartDate && $taskEndDate && $startPosition >= 0 && $i >= $startPosition && $i < ($startPosition + $taskLength);


                if ($hasMilestone || $hasBar) {
                    $classes[] = 'has-bar';
                }
            @endphp
            <td class="gantt-cell {{ implode(' ', $classes) }} p-0" data-date="{{ $dateStr }}">
                @if($hasMilestone)
                    <div class="milestone-diamond" style="background-color: {{ $project->color }};"></div>
                    <div class="gantt-tooltip">
                        <div class="tooltip-content">
                            {{ $task->name }} (マイルストーン)<br>
                            {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('Y/m/d') : '' }}
                        </div>
                        <div class="tooltip-arrow"></div>
                    </div>
                @elseif($hasBar)
                    <div class="h-100 w-100 gantt-bar" style="background-color: {{ $project->color }}; opacity: 0.3;">
                        <div class="task-progress" id="task-progress-bar-{{ $task->id }}"
                            style="width: {{ $task->progress }}%; height: 100%; background-color: rgba(255, 255, 255, 0.3);"></div>
                    </div>
                    <div class="gantt-tooltip task">
                        <div class="tooltip-content">
                            {{ $task->name }}<br>
                            期間: {{ $taskStartDate ? \Carbon\Carbon::parse($taskStartDate)->format('Y/m/d') : '' }} 〜
                            {{ $taskEndDate ? \Carbon\Carbon::parse($taskEndDate)->format('Y/m/d') : '' }}<br>
                            進捗: {{ $task->progress }}%<br>
                            担当: {{ $task->assignee ?? '未割当' }}
                        </div>
                        <div class="tooltip-arrow"></div>
                    </div>
                @endif
            </td>
        @endfor
    </tr>

    @if($task->children->count() > 0)
        @include('gantt.partials.task_rows', ['tasks' => $task->children->sortBy(function ($childTask) {
        return $childTask->start_date ?? '9999-12-31'; }), 'project' => $project, 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => $level + 1])
    @endif
@endforeach