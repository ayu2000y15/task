@extends('layouts.app')

@section('title', 'タスク一覧')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>タスク一覧</h1>
    <div>
        <button class="btn btn-outline-primary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel">
            <i class="fas fa-filter"></i> フィルター
        </button>
    </div>
</div>

<!-- フィルターパネル -->
<div class="collapse {{ array_filter($filters) ? 'show' : '' }}" id="filterPanel">
    <div class="filter-panel mb-4">
        <form action="{{ route('tasks.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="project_id" class="form-label">プロジェクト</label>
                <select class="form-select" id="project_id" name="project_id">
                    <option value="">すべて</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ $filters['project_id'] == $project->id ? 'selected' : '' }}>
                            {{ $project->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="assignee" class="form-label">担当者</label>
                <select class="form-select" id="assignee" name="assignee">
                    <option value="">すべて</option>
                    @foreach($assignees as $assignee)
                        <option value="{{ $assignee }}" {{ $filters['assignee'] == $assignee ? 'selected' : '' }}>
                            {{ $assignee }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">ステータス</label>
                <select class="form-select" id="status" name="status">
                    <option value="">すべて</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ $filters['status'] == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="search" class="form-label">タスク名検索</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $filters['search'] }}">
            </div>
            <div class="col-md-12 d-flex">
                <button type="submit" class="btn btn-primary me-2">フィルター適用</button>
                <a href="{{ route('tasks.index') }}" class="btn btn-secondary">リセット</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">タスク一覧</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>プロジェクト</th>
                        <th>タスク名</th>
                        <th>担当者</th>
                        <th>開始日</th>
                        <th>終了日</th>
                        <th>工数</th>
                        <th>ステータス</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @if($tasks->isEmpty())
                        <tr>
                            <td colspan="9" class="text-center py-4">表示するタスクがありません</td>
                        </tr>
                    @else
                        @foreach($tasks as $task)
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $task->project) }}" style="color: {{ $task->project->color }};">
                                        {{ $task->project->title }}
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
                                                <i class="fas fa-tasks"></i>
                                            @endif
                                        </span>
                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}">{{ $task->name }}</a>
                                    </div>
                                </td>
                                <td>{{ $task->assignee ?? '-' }}</td>
                                <td>{{ $task->start_date->format('Y/m/d') }}</td>
                                <td>{{ $task->end_date->format('Y/m/d') }}</td>
                                <td>{{ $task->duration }}日</td>
                                <td>
                                    @php
                                        $statusClass = '';
                                        $statusLabel = '';

                                        switch($task->status) {
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
                                        }
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('projects.tasks.destroy', [$task->project, $task]) }}" method="POST" class="d-inline" onsubmit="return confirm('本当に削除しますか？');">
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
@endsection
