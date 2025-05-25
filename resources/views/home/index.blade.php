@extends('layouts.app')

@section('title', 'ホーム')

@section('styles')
<style>
    .project-icon-list { /* 工程一覧用衣装案件アイコン */
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        margin-right: 10px;
        font-weight: bold;
        color: white;
        vertical-align: middle; /* アイコンとテキストの縦位置を合わせる */
    }
    .todo-project-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px; /* アイコンのサイズ */
        height: 20px; /* アイコンのサイズ */
        border-radius: 4px; /* 少し角丸に */
        color: white;
        font-weight: bold;
        font-size: 0.75em; /* 文字サイズ */
        margin-right: 8px; /* テキストとの間隔 */
        flex-shrink: 0; /* 縮まないように */
    }
    .todo-item .todo-text {
        display: flex;
        flex-direction: column; /* 工程名とサブテキストを縦に並べる */
    }
    .todo-item .todo-project {
        font-size: 0.8em;
        color: #6c757d;
    }
    .todo-item .todo-actions {
        margin-left: auto; /* 右端に寄せる */
        padding-left: 10px; /* テキストとの間隔 */
    }
    /* ToDoリストヘッダーの背景色 */
    .todo-header.not_started { background-color: #6c757d; color: white; } /* 未着手: Secondary */
    .todo-header.in_progress { background-color: #0d6efd; color: white; } /* 進行中: Primary */
    .todo-header.on_hold { background-color: #ffc107; color: black; }    /* 保留中: Warning (文字色を黒に) */
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>ホーム</h1>
    <div>
        @can('create', App\Models\Project::class)
        <a href="{{ route('projects.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新規衣装案件
        </a>
        @endcan
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        {{-- 最近の工程 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">最近の工程</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>工程名</th>
                                <th>担当者</th>
                                <th>期限</th>
                                <th>ステータス</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($recentTasks->isEmpty())
                                <tr>
                                    <td colspan="6" class="text-center py-4">表示する工程がありません</td>
                                </tr>
                            @else
                                @foreach($recentTasks as $task)
                                    @php
                                        $rowClass = '';
                                        $now = \Carbon\Carbon::now()->startOfDay();
                                        $daysUntilDue = $task->end_date ? $now->diffInDays($task->end_date, false) : null;

                                        if($task->end_date && $task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled') {
                                            $rowClass = 'task-overdue';
                                        } elseif($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled') {
                                            $rowClass = 'task-due-soon';
                                        }
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td>
                                            <a href="{{ route('projects.show', $task->project) }}" style="color: {{ $task->project->color }};">
                                                <span class="project-icon-list" style="background-color: {{ $task->project->color }};">
                                                    {{ mb_substr($task->project->title, 0, 1) }}
                                                </span>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="task-icon me-2">
                                                    @if($task->is_milestone)
                                                        <i class="fas fa-flag"></i>
                                                    @elseif($task->is_folder)
                                                        <i class="fas fa-folder"></i>
                                                    @else
                                                    <span class="task-icon me-1">
                                                        @switch($task->status)
                                                            @case('completed')
                                                                <i class="fas fa-check-circle text-success" title="完了"></i>
                                                                @break
                                                            @case('in_progress')
                                                                <i class="fas fa-play-circle text-primary" title="進行中"></i>
                                                                @break
                                                            @case('on_hold')
                                                                <i class="fas fa-pause-circle text-warning" title="保留中"></i>
                                                                @break
                                                            @case('cancelled')
                                                                <i class="fas fa-times-circle text-danger" title="キャンセル"></i>
                                                                @break
                                                            @default
                                                                <i class="far fa-circle text-secondary" title="未着手"></i>
                                                        @endswitch
                                                    </span>
                                                    @endif
                                                </span>
                                                <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}">{{ $task->name }}</a>

                                                @if($task->end_date && $task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                    <span class="ms-2 badge bg-danger">期限切れ</span>
                                                @elseif($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                    <span class="ms-2 badge bg-warning text-dark">あと{{ ceil($daysUntilDue) }}日</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $task->assignee ?? '-' }}</td>
                                        <td>
                                            @if($task->end_date && $task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }}
                                                </span>
                                            @elseif($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }}
                                                </span>
                                            @else
                                                {{ optional($task->end_date)->format('Y/m/d') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$task->is_folder)
                                            <select class="form-select form-select-sm task-status-select"
                                                    data-task-id="{{ $task->id }}"
                                                    data-project-id="{{ $task->project->id }}">
                                                <option value="not_started" {{ $task->status === 'not_started' ? 'selected' : '' }}>未着手</option>
                                                <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>進行中</option>
                                                <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>完了</option>
                                                <option value="on_hold" {{ $task->status === 'on_hold' ? 'selected' : '' }}>保留中</option>
                                                <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                                            </select>
                                            @else
                                            -
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
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

    <div class="col-md-4">
        {{-- 衣装案件概要 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">衣装案件概要</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span>全衣装案件数:</span>
                    <span class="badge bg-primary">{{ $projectCount }}</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>進行中の衣装案件:</span>
                    <span class="badge bg-success">{{ $activeProjectCount }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>全工程数:</span>
                    <span class="badge bg-info">{{ $taskCount }}</span>
                </div>
            </div>
        </div>

        {{-- 期限間近の工程 --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">期限間近の工程</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @if($upcomingTasks->isEmpty())
                        <li class="list-group-item text-center py-4">表示する工程がありません</li>
                    @else
                        @foreach($upcomingTasks as $task)
                            @php
                                $itemClass = '';
                                $now = \Carbon\Carbon::now()->startOfDay();
                                $daysUntilDue = $task->end_date ? $now->diffInDays($task->end_date, false) : null;

                                if($task->end_date && $task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled') {
                                    $itemClass = 'task-overdue';
                                } elseif($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled') {
                                    $itemClass = 'task-due-soon';
                                }
                            @endphp
                            <li class="list-group-item {{ $itemClass }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}">{{ $task->name }}</a>
                                        <div class="small">
                                            @if($task->end_date && $task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }} ({{ $task->end_date->diffForHumans() }})
                                                </span>
                                            @elseif($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }} ({{ $task->end_date->diffForHumans() }})
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    {{ optional($task->end_date)->format('Y/m/d') }} {{ $task->end_date ? '(' . $task->end_date->diffForHumans() . ')' : '' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    @if(!$task->is_folder)
                                    <select class="form-select form-select-sm task-status-select"
                                            style="width: auto;"
                                            data-task-id="{{ $task->id }}"
                                            data-project-id="{{ $task->project->id }}">
                                        <option value="not_started" {{ $task->status === 'not_started' ? 'selected' : '' }}>未着手</option>
                                        <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>進行中</option>
                                        <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>完了</option>
                                        <option value="on_hold" {{ $task->status === 'on_hold' ? 'selected' : '' }}>保留中</option>
                                        <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                                    </select>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <h2>ToDoリスト（直近7日間）</h2>
        <div class="row">
            @php
                $todoColumnMap = [
                    'todoTasks' => ['label' => '未着手', 'status_class' => 'not_started'],
                    'inProgressTasks' => ['label' => '進行中', 'status_class' => 'in_progress'],
                    'onHoldTasks' => ['label' => '保留中', 'status_class' => 'on_hold'],
                ];
            @endphp

            @foreach($todoColumnMap as $varName => $columnData)
                @php $tasksInStatus = $$varName; @endphp
                <div class="col-md-4 col-lg-4 mb-4">
                    <div class="todo-section">
                        <div class="todo-header {{ $columnData['status_class'] }}">
                            <span>{{ $columnData['label'] }}</span>
                            <span class="badge bg-light text-dark">{{ $tasksInStatus->count() }}</span>
                        </div>
                        <div class="todo-body">
                            @if($tasksInStatus->isEmpty())
                                <div class="todo-item text-center text-muted p-3">
                                    工程がありません
                                </div>
                            @else
                                @foreach($tasksInStatus as $task)
                                    @php
                                        $itemClass = '';
                                        $now = \Carbon\Carbon::now()->startOfDay();
                                        $daysUntilDue = $task->end_date ? $now->diffInDays($task->end_date, false) : null;

                                        if($task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled'])) {
                                            $itemClass = 'task-overdue';
                                        } elseif($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled'])) {
                                            $itemClass = 'task-due-soon';
                                        }
                                    @endphp
                                    <div class="todo-item {{ $itemClass }} d-flex align-items-center">
                                        <span class="todo-project-icon" style="background-color: {{ $task->project->color }};">
                                            {{ mb_substr($task->project->title, 0, 1) }}
                                        </span>
                                        <div class="todo-text flex-grow-1">
                                            <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}">{{ $task->name }}</a>
                                            <div class="todo-project">
                                                @if($task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled']))
                                                    <span class="text-danger">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                        {{ $task->end_date->format('Y/m/d') }}
                                                    </span>
                                                @elseif($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                                                    <span class="text-warning">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        {{ $task->end_date->format('Y/m/d') }}
                                                    </span>
                                                @elseif($task->end_date)
                                                    <span>{{ $task->end_date->format('Y/m/d') }}</span>
                                                @endif
                                                <span class="ms-2 text-truncate" style="max-width: 100px;">{{ $task->assignee }}</span>
                                            </div>
                                        </div>
                                        <div class="todo-actions">
                                            @if(!$task->is_folder)
                                            <select class="form-select form-select-sm task-status-select"
                                                    data-task-id="{{ $task->id }}"
                                                    data-project-id="{{ $task->project->id }}">
                                                <option value="not_started" {{ $task->status === 'not_started' ? 'selected' : '' }}>未着手</option>
                                                <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>進行中</option>
                                                <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>完了</option>
                                                <option value="on_hold" {{ $task->status === 'on_hold' ? 'selected' : '' }}>保留中</option>
                                                <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                                            </select>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelects = document.querySelectorAll('.task-status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const projectId = this.dataset.projectId;
            const status = this.value;
            let progress = 0;

            const taskItem = this.closest('.todo-item') || this.closest('tr');
            // Try to get current progress if available (e.g., from a hidden field or another element)
            // For this example, we simplify:
            if (status === 'completed') {
                progress = 100;
            } else if (status === 'not_started' || status === 'on_hold' || status === 'cancelled') {
                progress = 0;
            } else if (status === 'in_progress') {
                // If task was previously completed, reset progress. Otherwise, keep or set to a default.
                // This requires knowing previous progress. For simplicity, set to 50 if not already set.
                // You might want to fetch current task progress if it's critical to preserve it.
                // For ToDo list, we don't display progress, so a default for 'in_progress' is okay.
                progress = (taskItem && taskItem.dataset.progress) ? parseInt(taskItem.dataset.progress) : 10; // Default 10% if starting
                if (progress === 100) progress = 10; // If it was 100 (completed) and now in_progress, reset
                if (progress === 0 && status === 'in_progress') progress = 10; // If not started and now in_progress
            }


            fetch(`/projects/${projectId}/tasks/${taskId}/progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: status, progress: progress })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('ステータスの更新に失敗しました。\n' + (data.message || ''));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('エラーが発生しました。');
            });
        });
    });
});
</script>
@endsection