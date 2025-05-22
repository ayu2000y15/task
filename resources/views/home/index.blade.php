@extends('layouts.app')

@section('title', 'ホーム')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>ホーム</h1>
    <div>
        <a href="{{ route('projects.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新規プロジェクト
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">最近のタスク</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>プロジェクト</th>
                                <th>タスク名</th>
                                <th>担当者</th>
                                <th>期限</th>
                                <th>ステータス</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($recentTasks->isEmpty())
                                <tr>
                                    <td colspan="6" class="text-center py-4">表示するタスクがありません</td>
                                </tr>
                            @else
                                @foreach($recentTasks as $task)
                                    @php
                                        $rowClass = '';
                                        $now = \Carbon\Carbon::now();
                                        $daysUntilDue = $now->diffInDays($task->end_date, false);

                                        if($task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled') {
                                            $rowClass = 'task-overdue';
                                        } elseif($daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled') {
                                            $rowClass = 'task-due-soon';
                                        }
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td>
                                            <a href="{{ route('projects.show', $task->project) }}" style="color: {{ $task->project->color }};">
                                                {{ $task->project->title }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="task-icon">
                                                    @if($task->is_milestone)
                                                        <i class="fas fa-flag"></i>
                                                    @elseif($task->is_folder)
                                                        <i class="fas fa-folder"></i>
                                                    @else
                                                        <i class="fas fa-tasks"></i>
                                                    @endif
                                                </span>
                                                <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}">{{ $task->name }}</a>

                                                @if($task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                    <span class="ms-2 badge bg-danger">期限切れ</span>
                                                @elseif($daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                    <span class="ms-2 badge bg-warning text-dark">あと{{ $daysUntilDue }}日</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $task->assignee ?? '-' }}</td>
                                        <td>
                                            @if($task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }}
                                                </span>
                                            @elseif($daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }}
                                                </span>
                                            @else
                                                {{ $task->end_date->format('Y/m/d') }}
                                            @endif
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm task-status-select"
                                                    data-task-id="{{ $task->id }}"
                                                    data-project-id="{{ $task->project->id }}">
                                                <option value="not_started" {{ $task->status === 'not_started' ? 'selected' : '' }}>未着手</option>
                                                <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>進行中</option>
                                                <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>完了</option>
                                                <option value="on_hold" {{ $task->status === 'on_hold' ? 'selected' : '' }}>保留中</option>
                                                <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                                            </select>
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
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">プロジェクト概要</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span>全プロジェクト数:</span>
                    <span class="badge bg-primary">{{ $projectCount }}</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>進行中のプロジェクト:</span>
                    <span class="badge bg-success">{{ $activeProjectCount }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>全タスク数:</span>
                    <span class="badge bg-info">{{ $taskCount }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">期限間近のタスク</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @if($upcomingTasks->isEmpty())
                        <li class="list-group-item text-center py-4">表示するタスクがありません</li>
                    @else
                        @foreach($upcomingTasks as $task)
                            @php
                                $itemClass = '';
                                $now = \Carbon\Carbon::now();
                                $daysUntilDue = $now->diffInDays($task->end_date, false);

                                if($task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled') {
                                    $itemClass = 'task-overdue';
                                } elseif($daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled') {
                                    $itemClass = 'task-due-soon';
                                }
                            @endphp
                            <li class="list-group-item {{ $itemClass }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}">{{ $task->name }}</a>
                                        <div class="small">
                                            @if($task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }} ({{ $task->end_date->diffForHumans() }})
                                                </span>
                                            @elseif($daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }} ({{ $task->end_date->diffForHumans() }})
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    {{ $task->end_date->format('Y/m/d') }} ({{ $task->end_date->diffForHumans() }})
                                                </span>
                                            @endif
                                        </div>
                                    </div>
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
        <h2>ToDoリスト</h2>
        <div class="row">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="todo-section">
                    <div class="todo-header todo">
                        <span>未着手</span>
                        <span class="badge bg-light text-dark">{{ $todoTasks->count() }}</span>
                    </div>
                    <div class="todo-body">
                        @if($todoTasks->isEmpty())
                            <div class="todo-item text-center text-muted">
                                タスクがありません
                            </div>
                        @else
                            @foreach($todoTasks as $task)
                                @php
                                    $itemClass = '';
                                    $now = \Carbon\Carbon::now();
                                    $daysUntilDue = $now->diffInDays($task->end_date, false);

                                    if($task->end_date < $now) {
                                        $itemClass = 'task-overdue';
                                    } elseif($daysUntilDue >= 0 && $daysUntilDue <= 2) {
                                        $itemClass = 'task-due-soon';
                                    }
                                @endphp
                                <div class="todo-item {{ $itemClass }}">
                                    <div class="todo-text">
                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}">{{ $task->name }}</a>
                                        <div class="todo-project">
                                            @if($task->end_date < $now)
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }}
                                                </span>
                                            @elseif($daysUntilDue >= 0 && $daysUntilDue <= 2)
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }}
                                                </span>
                                            @endif
                                            {{ $task->assignee }}
                                        </div>
                                    </div>
                                    <div class="todo-actions">
                                        <select class="form-select form-select-sm task-status-select"
                                                data-task-id="{{ $task->id }}"
                                                data-project-id="{{ $task->project->id }}">
                                            <option value="not_started" selected>未着手</option>
                                            <option value="in_progress">進行中</option>
                                            <option value="completed">完了</option>
                                            <option value="on_hold">保留中</option>
                                            <option value="cancelled">キャンセル</option>
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="todo-section">
                    <div class="todo-header in-progress">
                        <span>進行中</span>
                        <span class="badge bg-light text-dark">{{ $inProgressTasks->count() }}</span>
                    </div>
                    <div class="todo-body">
                        @if($inProgressTasks->isEmpty())
                            <div class="todo-item text-center text-muted">
                                タスクがありません
                            </div>
                        @else
                            @foreach($inProgressTasks as $task)
                                @php
                                    $itemClass = '';
                                    $now = \Carbon\Carbon::now();
                                    $daysUntilDue = $now->diffInDays($task->end_date, false);

                                    if($task->end_date < $now) {
                                        $itemClass = 'task-overdue';
                                    } elseif($daysUntilDue >= 0 && $daysUntilDue <= 2) {
                                        $itemClass = 'task-due-soon';
                                    }
                                @endphp
                                <div class="todo-item {{ $itemClass }}">
                                    <div class="todo-text">
                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}">{{ $task->name }}</a>
                                        <div class="todo-project">
                                            @if($task->end_date < $now)
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }}
                                                </span>
                                            @elseif($daysUntilDue >= 0 && $daysUntilDue <= 2)
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }}
                                                </span>
                                            @endif
                                            {{ $task->assignee }}
                                        </div>
                                    </div>
                                    <div class="todo-actions">
                                        <select class="form-select form-select-sm task-status-select"
                                                data-task-id="{{ $task->id }}"
                                                data-project-id="{{ $task->project->id }}">
                                            <option value="not_started">未着手</option>
                                            <option value="in_progress" selected>進行中</option>
                                            <option value="completed">完了</option>
                                            <option value="on_hold">保留中</option>
                                            <option value="cancelled">キャンセル</option>
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="todo-section">
                    <div class="todo-header review">
                        <span>保留中</span>
                        <span class="badge bg-light text-dark">{{ $onHoldTasks->count() }}</span>
                    </div>
                    <div class="todo-body">
                        @if($onHoldTasks->isEmpty())
                            <div class="todo-item text-center text-muted">
                                タスクがありません
                            </div>
                        @else
                            @foreach($onHoldTasks as $task)
                                @php
                                    $itemClass = '';
                                    $now = \Carbon\Carbon::now();
                                    $daysUntilDue = $now->diffInDays($task->end_date, false);

                                    if($task->end_date < $now) {
                                        $itemClass = 'task-overdue';
                                    } elseif($daysUntilDue >= 0 && $daysUntilDue <= 2) {
                                        $itemClass = 'task-due-soon';
                                    }
                                @endphp
                                <div class="todo-item {{ $itemClass }}">
                                    <div class="todo-text">
                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}">{{ $task->name }}</a>
                                        <div class="todo-project">
                                            @if($task->end_date < $now)
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }}
                                                </span>
                                            @elseif($daysUntilDue >= 0 && $daysUntilDue <= 2)
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    {{ $task->end_date->format('Y/m/d') }}
                                                </span>
                                            @endif
                                            {{ $task->assignee }}
                                        </div>
                                    </div>
                                    <div class="todo-actions">
                                        <select class="form-select form-select-sm task-status-select"
                                                data-task-id="{{ $task->id }}"
                                                data-project-id="{{ $task->project->id }}">
                                            <option value="not_started">未着手</option>
                                            <option value="in_progress">進行中</option>
                                            <option value="completed">完了</option>
                                            <option value="on_hold" selected>保留中</option>
                                            <option value="cancelled">キャンセル</option>
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="todo-section">
                    <div class="todo-header completed">
                        <span>完了</span>
                        <span class="badge bg-light text-dark">{{ $completedTasks->count() }}</span>
                    </div>
                    <div class="todo-body">
                        @if($completedTasks->isEmpty())
                            <div class="todo-item text-center text-muted">
                                タスクがありません
                            </div>
                        @else
                            @foreach($completedTasks as $task)
                                <div class="todo-item">
                                    <div class="todo-text">
                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}">{{ $task->name }}</a>
                                        <div class="todo-project">{{ $task->assignee }}</div>
                                    </div>
                                    <div class="todo-actions">
                                        <select class="form-select form-select-sm task-status-select"
                                                data-task-id="{{ $task->id }}"
                                                data-project-id="{{ $task->project->id }}">
                                            <option value="not_started">未着手</option>
                                            <option value="in_progress">進行中</option>
                                            <option value="completed" selected>完了</option>
                                            <option value="on_hold">保留中</option>
                                            <option value="cancelled">キャンセル</option>
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // タスクステータス変更処理
    const statusSelects = document.querySelectorAll('.task-status-select');

    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const projectId = this.dataset.projectId;
            const status = this.value;

            // ステータスを更新
            fetch(`/projects/${projectId}/tasks/${taskId}/progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    status: status,
                    progress: status === 'completed' ? 100 : 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 成功時の処理（必要に応じてページをリロードするなど）
                    location.reload();
                } else {
                    alert('ステータスの更新に失敗しました。');
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