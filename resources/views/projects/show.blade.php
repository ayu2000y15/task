@extends('layouts.app')

@section('title', $project->title)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>{{ $project->title }}</h1>
        <div>
            <a href="{{ route('projects.tasks.create', $project) }}" class="btn btn-primary me-2">
                <i class="fas fa-plus"></i> タスク追加
            </a>
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-warning me-2">
                <i class="fas fa-edit"></i> 編集
            </a>
            <a href="{{ route('gantt.index', ['project_id' => $project->id]) }}" class="btn btn-info">
                <i class="fas fa-chart-gantt"></i> ガントチャート
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header" style="background-color: {{ $project->color }}; color: white;">
                    <h5 class="mb-0">プロジェクト情報</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>説明:</strong>
                        <p>{{ $project->description ?? '説明はありません' }}</p>
                    </div>
                    <div class="mb-3">
                        <strong>期間:</strong>
                        <p>{{ $project->start_date->format('Y年m月d日') }} 〜 {{ $project->end_date->format('Y年m月d日') }}</p>
                    </div>
                    <div class="mb-3">
                        <strong>ステータス:</strong>
                        @php
                            $totalTasks = $project->tasks->count();
                            $completedTasks = $project->tasks->where('status', 'completed')->count();
                            $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                        @endphp
                        <div class="progress mt-2">
                            <div class="progress-bar" role="progressbar"
                                style="width: {{ $progress }}%; background-color: {{ $project->color }};"
                                aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">{{ $progress }}%</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>タスク:</strong>
                        <p>{{ $totalTasks }}個 (完了: {{ $completedTasks }}個)</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">統計</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>ステータス別タスク数:</strong>
                        <ul class="list-group mt-2">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                未着手
                                <span
                                    class="badge bg-secondary rounded-pill">{{ $project->tasks->where('status', 'not_started')->count() }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                進行中
                                <span
                                    class="badge bg-primary rounded-pill">{{ $project->tasks->where('status', 'in_progress')->count() }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                完了
                                <span
                                    class="badge bg-success rounded-pill">{{ $project->tasks->where('status', 'completed')->count() }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                保留中
                                <span
                                    class="badge bg-warning rounded-pill">{{ $project->tasks->where('status', 'on_hold')->count() }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                キャンセル
                                <span
                                    class="badge bg-danger rounded-pill">{{ $project->tasks->where('status', 'cancelled')->count() }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <strong>タイプ別タスク数:</strong>
                        <ul class="list-group mt-2">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                通常タスク
                                <span
                                    class="badge bg-primary rounded-pill">{{ $project->tasks->where('is_milestone', false)->where('is_folder', false)->count() }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                マイルストーン
                                <span
                                    class="badge bg-info rounded-pill">{{ $project->tasks->where('is_milestone', true)->count() }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                フォルダ
                                <span
                                    class="badge bg-secondary rounded-pill">{{ $project->tasks->where('is_folder', true)->count() }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">タスク一覧</h5>
                    <div class="btn-group">
                        <a href="{{ route('projects.tasks.create', $project) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> タスク追加
                        </a>
                        <button class="btn btn-sm btn-outline-secondary" id="toggleCompletedBtn">
                            <i class="fas fa-eye-slash"></i> 完了タスクを隠す
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>タスク名</th>
                                    <th>担当者</th>
                                    <th>期間</th>
                                    <th>工数</th>
                                    <th>進捗</th>
                                    <th>ステータス</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($project->tasks->isEmpty())
                                    <tr>
                                        <td colspan="7" class="text-center py-4">タスクがありません</td>
                                    </tr>
                                @else
                                    @foreach($project->tasks->sortBy(function ($task) {
                                        return $task->start_date ?? '9999-12-31'; }) as $task)
                                        @php
                                            $rowClass = '';
                                            $now = \Carbon\Carbon::now()->startOfDay();
                                            $daysUntilDue = $task->end_date ? $now->diffInDays($task->end_date, false) : null;

                                            if ($task->status === 'completed' || $task->status === 'cancelled') {
                                                $rowClass = 'completed-task';
                                            } elseif ($task->end_date && $task->end_date < $now) {
                                                $rowClass = 'task-overdue';
                                            } elseif ($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2) {
                                                $rowClass = 'task-due-soon';
                                            }
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="task-icon me-2">
                                                        @if($task->is_milestone)
                                                            <i class="fas fa-flag text-danger"></i>
                                                        @elseif($task->is_folder)
                                                            <i class="fas fa-folder text-primary"></i>
                                                        @else
                                                            <i class="fas fa-tasks"></i>
                                                        @endif
                                                    </span>
                                                    <a href="{{ route('projects.tasks.edit', [$project, $task]) }}"
                                                        class="text-decoration-none">{{ $task->name }}</a>
                                                    @if(!$task->is_milestone && !$task->is_folder && $task->end_date && $task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                        <span class="ms-2 badge bg-danger">期限切れ</span>
                                                    @elseif(!$task->is_milestone && !$task->is_folder && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                        <span class="ms-2 badge bg-warning text-dark">あと{{ $daysUntilDue }}日</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $task->assignee ?? '-' }}</td>
                                            <td>
                                                {{ $task->start_date ? $task->start_date->format('Y/m/d') : '-' }}
                                                〜
                                                {{ $task->end_date ? $task->end_date->format('Y/m/d') : '-' }}
                                            </td>
                                            <td>
                                                {{ $task->is_folder ? '-' : ($task->duration ? $task->duration . '日' : '-') }}
                                            </td>
                                            <td>
                                                @if(!$task->is_folder && !$task->is_milestone)
                                                    <div class="progress" style="height: 10px;">
                                                        <div class="progress-bar" role="progressbar"
                                                            style="width: {{ $task->progress }}%; background-color: {{ $project->color }};"
                                                            aria-valuenow="{{ $task->progress }}" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <small>{{ $task->progress }}%</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if($task->is_folder)
                                                    -
                                                @else
                                                    @php
                                                        $statusClass = '';
                                                        $statusLabel = '';

                                                        switch ($task->status) {
                                                            case 'not_started':
                                                                $statusClass = 'secondary';
                                                                $statusLabel = '未着手';
                                                                break;
                                                            case 'in_progress':
                                                                $statusClass = 'primary';
                                                                $statusLabel = '進行中';
                                                                break;
                                                            case 'completed':
                                                                $statusClass = 'success';
                                                                $statusLabel = '完了';
                                                                break;
                                                            case 'on_hold':
                                                                $statusClass = 'warning';
                                                                $statusLabel = '保留中';
                                                                break;
                                                            case 'cancelled':
                                                                $statusClass = 'danger';
                                                                $statusLabel = 'キャンセル';
                                                                break;
                                                            default:
                                                                $statusClass = 'light';
                                                                $statusLabel = $task->status ?? '-';
                                                                break;
                                                        }
                                                    @endphp
                                                    <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('projects.tasks.edit', [$project, $task]) }}"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}"
                                                        method="POST" class="d-inline" onsubmit="return confirm('本当に削除しますか？');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 完了タスクの表示/非表示切り替え
            const toggleCompletedBtn = document.getElementById('toggleCompletedBtn');
            let completedTasksHidden = false;

            if (toggleCompletedBtn) {
                toggleCompletedBtn.addEventListener('click', function () {
                    const completedTasks = document.querySelectorAll('.completed-task');

                    if (completedTasksHidden) {
                        // 完了タスクを表示
                        completedTasks.forEach(taskRow => { // Renamed 'task' to 'taskRow' to avoid conflict
                            taskRow.style.display = '';
                        });
                        toggleCompletedBtn.innerHTML = '<i class="fas fa-eye-slash"></i> 完了タスクを隠す';
                        completedTasksHidden = false;
                    } else {
                        // 完了タスクを非表示
                        completedTasks.forEach(taskRow => { // Renamed 'task' to 'taskRow'
                            taskRow.style.display = 'none';
                        });
                        toggleCompletedBtn.innerHTML = '<i class="fas fa-eye"></i> 完了タスクを表示';
                        completedTasksHidden = true;
                    }
                });
            }
        });
    </script>
@endsection