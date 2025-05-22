@extends('layouts.app')

@section('title', 'タスク一覧')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>タスク一覧</h1>
        <div>
            <button class="btn btn-outline-primary me-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#filterPanel">
                <i class="fas fa-filter"></i> フィルター
            </button>
            <a href="{{ route('projects.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> 新規プロジェクト
            </a>
        </div>
    </div>

    <!-- フィルターパネル -->
    <div class="collapse {{ array_filter($filters) ? 'show' : '' }}" id="filterPanel">
        <div class="filter-panel mb-4">
            <div class="filter-close" id="closeFilterBtn">
                <i class="fas fa-times"></i>
            </div>
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
                <div class="col-md-3">
                    <label for="due_date" class="form-label">期限</label>
                    <select class="form-select" id="due_date" name="due_date">
                        <option value="">すべて</option>
                        <option value="overdue" {{ $filters['due_date'] == 'overdue' ? 'selected' : '' }}>期限切れ</option>
                        <option value="today" {{ $filters['due_date'] == 'today' ? 'selected' : '' }}>今日</option>
                        <option value="tomorrow" {{ $filters['due_date'] == 'tomorrow' ? 'selected' : '' }}>明日</option>
                        <option value="this_week" {{ $filters['due_date'] == 'this_week' ? 'selected' : '' }}>今週</option>
                        <option value="next_week" {{ $filters['due_date'] == 'next_week' ? 'selected' : '' }}>来週</option>
                    </select>
                </div>
                <div class="col-md-12 d-flex">
                    <button type="submit" class="btn btn-primary me-2">フィルター適用</button>
                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">リセット</a>
                </div>
            </form>
        </div>
    </div>

    <!-- タブメニュー -->
    <ul class="nav nav-tabs mb-3" id="taskTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button"
                role="tab" aria-controls="tasks" aria-selected="true">
                <i class="fas fa-tasks"></i> タスク
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="milestones-tab" data-bs-toggle="tab" data-bs-target="#milestones" type="button"
                role="tab" aria-controls="milestones" aria-selected="false">
                <i class="fas fa-flag"></i> マイルストーン
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="folders-tab" data-bs-toggle="tab" data-bs-target="#folders" type="button"
                role="tab" aria-controls="folders" aria-selected="false">
                <i class="fas fa-folder"></i> フォルダ
            </button>
        </li>
    </ul>

    <!-- タブコンテンツ -->
    <div class="tab-content" id="taskTabsContent">
        <!-- タスク一覧タブ -->
        <div class="tab-pane fade show active" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">タスク一覧</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-secondary active" id="listViewBtn">
                            <i class="fas fa-list"></i> リスト
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="boardViewBtn">
                            <i class="fas fa-columns"></i> ボード
                        </button>
                    </div>
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
                                @if($tasks->where('is_milestone', false)->where('is_folder', false)->isEmpty())
                                    <tr>
                                        <td colspan="8" class="text-center py-4">表示するタスクがありません</td>
                                    </tr>
                                @else
                                    @foreach($tasks->where('is_milestone', false)->where('is_folder', false) as $task)
                                        @php
                                            $rowClass = '';
                                            $now = \Carbon\Carbon::now()->startOfDay();
                                            ;
                                            $daysUntilDue = $now->diffInDays($task->end_date, false);

                                            if ($task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled') {
                                                $rowClass = 'task-overdue';
                                            } elseif ($daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled') {
                                                $rowClass = 'task-due-soon';
                                            }

                                            // 親フォルダのパスを取得
                                            $folderPath = '';
                                            $parentTask = $task->parent;
                                            $folderHierarchy = [];

                                            while ($parentTask && $parentTask->is_folder) {
                                                array_unshift($folderHierarchy, $parentTask->name);
                                                $parentTask = $parentTask->parent;
                                            }

                                            if (!empty($folderHierarchy)) {
                                                $folderPath = implode(' > ', $folderHierarchy);
                                            }

                                            // ステータスに応じた色
                                            $statusColor = '';
                                            switch ($task->status) {
                                                case 'not_started':
                                                    $statusColor = '#6c757d'; // 灰色
                                                    break;
                                                case 'in_progress':
                                                    $statusColor = '#0d6efd'; // 青色
                                                    break;
                                                case 'completed':
                                                    $statusColor = '#198754'; // 緑色
                                                    break;
                                                case 'on_hold':
                                                    $statusColor = '#ffc107'; // 黄色
                                                    break;
                                                case 'cancelled':
                                                    $statusColor = '#dc3545'; // 赤色
                                                    break;
                                            }
                                        @endphp
                                        <tr style="border-left: 5px solid {{ $statusColor }};" class="{{ $rowClass }}">
                                            <td>
                                                <a href="{{ route('projects.show', $task->project) }}" class="text-decoration-none"
                                                    style="color: {{ $task->project->color }};">
                                                    {{ $task->project->title }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="task-icon">
                                                        <i class="fas fa-tasks"></i>
                                                    </span>
                                                    <div>
                                                        @if(!empty($folderPath))
                                                            <small class="text-muted d-block">{{ $folderPath }}</small>
                                                        @endif
                                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}"
                                                            class="text-decoration-none">{{ $task->name }}</a>
                                                        @if($task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                            <span class="ms-2 badge bg-danger">期限切れ</span>
                                                        @elseif($daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                            <span class="ms-2 badge bg-warning text-dark">あと{{ $daysUntilDue }}日</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="editable-cell" data-task-id="{{ $task->id }}" data-field="assignee"
                                                    data-value="{{ $task->assignee }}">
                                                    {{ $task->assignee ?? '-' }}
                                                </div>
                                                <div class="assignee-edit-form d-none">
                                                    <input type="text" class="form-control form-control-sm assignee-input"
                                                        value="{{ $task->assignee }}" data-task-id="{{ $task->id }}"
                                                        data-project-id="{{ $task->project->id }}">
                                                </div>
                                            </td>
                                            <td>{{ $task->start_date->format('Y/m/d') }}</td>
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
                                            <td>{{ $task->duration }}日</td>
                                            <td>
                                                <select class="form-select form-select-sm task-status-select"
                                                    data-task-id="{{ $task->id }}" data-project-id="{{ $task->project->id }}">
                                                    <option value="not_started" {{ $task->status === 'not_started' ? 'selected' : '' }}>未着手</option>
                                                    <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>進行中</option>
                                                    <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>完了
                                                    </option>
                                                    <option value="on_hold" {{ $task->status === 'on_hold' ? 'selected' : '' }}>保留中
                                                    </option>
                                                    <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>
                                                        キャンセル</option>
                                                </select>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('projects.tasks.destroy', [$task->project, $task]) }}"
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

        <!-- マイルストーンタブ -->
        <div class="tab-pane fade" id="milestones" role="tabpanel" aria-labelledby="milestones-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">マイルストーン一覧</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>プロジェクト</th>
                                    <th>マイルストーン名</th>
                                    <th>日付</th>
                                    <th>ステータス</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($tasks->where('is_milestone', true)->isEmpty())
                                    <tr>
                                        <td colspan="5" class="text-center py-4">表示するマイルストーンがありません</td>
                                    </tr>
                                @else
                                    @foreach($tasks->where('is_milestone', true) as $milestone)
                                        @php
                                            $rowClass = '';
                                            $now = \Carbon\Carbon::now()->startOfDay();
                                            $daysUntilDue = $now->diffInDays($milestone->end_date, false);

                                            if ($milestone->end_date < $now && $milestone->status !== 'completed' && $milestone->status !== 'cancelled') {
                                                $rowClass = 'task-overdue';
                                            } elseif ($daysUntilDue >= 0 && $daysUntilDue <= 2 && $milestone->status !== 'completed' && $milestone->status !== 'cancelled') {
                                                $rowClass = 'task-overdue';
                                            } elseif ($daysUntilDue >= 0 && $daysUntilDue <= 2 && $milestone->status !== 'completed' && $milestone->status !== 'cancelled') {
                                                $rowClass = 'task-due-soon';
                                            }

                                            // ステータスに応じた色
                                            $statusColor = '';
                                            switch ($milestone->status) {
                                                case 'not_started':
                                                    $statusColor = '#6c757d'; // 灰色
                                                    break;
                                                case 'in_progress':
                                                    $statusColor = '#0d6efd'; // 青色
                                                    break;
                                                case 'completed':
                                                    $statusColor = '#198754'; // 緑色
                                                    break;
                                                case 'on_hold':
                                                    $statusColor = '#ffc107'; // 黄色
                                                    break;
                                                case 'cancelled':
                                                    $statusColor = '#dc3545'; // 赤色
                                                    break;
                                            }
                                        @endphp
                                        <tr style="border-left: 5px solid {{ $statusColor }};" class="{{ $rowClass }}">
                                            <td>
                                                <a href="{{ route('projects.show', $milestone->project) }}"
                                                    class="text-decoration-none" style="color: {{ $milestone->project->color }};">
                                                    {{ $milestone->project->title }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="task-icon">
                                                        <i class="fas fa-flag text-danger"></i>
                                                    </span>
                                                    <a href="{{ route('projects.tasks.edit', [$milestone->project, $milestone]) }}"
                                                        class="text-decoration-none">{{ $milestone->name }}</a>
                                                </div>
                                            </td>
                                            <td>
                                                @if($milestone->end_date < $now && $milestone->status !== 'completed' && $milestone->status !== 'cancelled')
                                                    <span class="text-danger">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                        {{ $milestone->end_date->format('Y/m/d') }}
                                                    </span>
                                                @elseif($daysUntilDue >= 0 && $daysUntilDue <= 2 && $milestone->status !== 'completed' && $milestone->status !== 'cancelled')
                                                    <span class="text-warning">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        {{ $milestone->end_date->format('Y/m/d') }}
                                                    </span>
                                                @else
                                                    {{ $milestone->end_date->format('Y/m/d') }}
                                                @endif
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm task-status-select"
                                                    data-task-id="{{ $milestone->id }}"
                                                    data-project-id="{{ $milestone->project->id }}">
                                                    <option value="not_started" {{ $milestone->status === 'not_started' ? 'selected' : '' }}>未着手</option>
                                                    <option value="in_progress" {{ $milestone->status === 'in_progress' ? 'selected' : '' }}>進行中</option>
                                                    <option value="completed" {{ $milestone->status === 'completed' ? 'selected' : '' }}>完了</option>
                                                    <option value="on_hold" {{ $milestone->status === 'on_hold' ? 'selected' : '' }}>
                                                        保留中</option>
                                                    <option value="cancelled" {{ $milestone->status === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                                                </select>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('projects.tasks.edit', [$milestone->project, $milestone]) }}"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form
                                                        action="{{ route('projects.tasks.destroy', [$milestone->project, $milestone]) }}"
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

        <!-- フォルダタブ -->
        <div class="tab-pane fade" id="folders" role="tabpanel" aria-labelledby="folders-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">フォルダ一覧</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>プロジェクト</th>
                                    <th>フォルダ名</th>
                                    <th>親フォルダ</th>
                                    <th>タスク数</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($tasks->where('is_folder', true)->isEmpty())
                                    <tr>
                                        <td colspan="5" class="text-center py-4">表示するフォルダがありません</td>
                                    </tr>
                                @else
                                    @foreach($tasks->where('is_folder', true) as $folder)
                                        @php
                                            // 親フォルダのパスを取得
                                            $parentFolder = $folder->parent;
                                            $parentName = $parentFolder ? $parentFolder->name : '-';

                                            // フォルダ内のタスク数を取得
                                            $taskCount = $folder->children->count();
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td>
                                                <a href="{{ route('projects.show', $folder->project) }}"
                                                    class="text-decoration-none" style="color: {{ $folder->project->color }};">
                                                    {{ $folder->project->title }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="task-icon">
                                                        <i class="fas fa-folder text-primary"></i>
                                                    </span>
                                                    <a href="{{ route('projects.tasks.edit', [$folder->project, $folder]) }}"
                                                        class="text-decoration-none">{{ $folder->name }}</a>
                                                </div>
                                            </td>
                                            <td>{{ $parentName }}</td>
                                            <td>
                                                <span class="badge bg-primary rounded-pill">{{ $taskCount }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('projects.tasks.edit', [$folder->project, $folder]) }}"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form
                                                        action="{{ route('projects.tasks.destroy', [$folder->project, $folder]) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('本当に削除しますか？フォルダ内のすべてのタスクも削除されます。');">
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
            // フィルターパネルの閉じるボタン
            const closeFilterBtn = document.getElementById('closeFilterBtn');
            if (closeFilterBtn) {
                closeFilterBtn.addEventListener('click', function () {
                    const filterPanel = document.getElementById('filterPanel');
                    const bsCollapse = new bootstrap.Collapse(filterPanel);
                    bsCollapse.hide();
                });
            }

            // タスクステータス変更処理
            const statusSelects = document.querySelectorAll('.task-status-select');

            statusSelects.forEach(select => {
                select.addEventListener('change', function () {
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

            // 担当者の編集機能
            const editableCells = document.querySelectorAll('.editable-cell[data-field="assignee"]');

            editableCells.forEach(cell => {
                cell.addEventListener('click', function () {
                    const taskId = this.dataset.taskId;
                    const currentValue = this.dataset.value || '';

                    // 現在のセルを非表示にして入力フォームを表示
                    this.classList.add('d-none');
                    const editForm = this.nextElementSibling;
                    editForm.classList.remove('d-none');

                    // 入力フィールドにフォーカスを当てる
                    const input = editForm.querySelector('input');
                    input.focus();
                    input.select();

                    // 入力完了時の処理（Enterキーまたはフォーカスが外れた時）
                    function completeEdit() {
                        const newValue = input.value.trim();

                        // 値が変更された場合のみ更新
                        if (newValue !== currentValue) {
                            const projectId = input.dataset.projectId;

                            // 担当者を更新
                            fetch(`/projects/${projectId}/tasks/${taskId}/assignee`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    assignee: newValue
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // 成功時の処理
                                        cell.textContent = newValue || '-';
                                        cell.dataset.value = newValue;
                                    } else {
                                        alert('担当者の更新に失敗しました。');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('エラーが発生しました。');
                                });
                        }

                        // 入力フォームを非表示にしてセルを表示
                        editForm.classList.add('d-none');
                        cell.classList.remove('d-none');
                    }

                    // Enterキーで編集完了
                    input.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            completeEdit();
                        }
                    });

                    // フォーカスが外れたら編集完了
                    input.addEventListener('blur', completeEdit);
                });
            });

            // ビュー切り替え（リスト/ボード）
            const listViewBtn = document.getElementById('listViewBtn');
            const boardViewBtn = document.getElementById('boardViewBtn');

            if (listViewBtn && boardViewBtn) {
                listViewBtn.addEventListener('click', function () {
                    listViewBtn.classList.add('active');
                    boardViewBtn.classList.remove('active');
                    // ここにリストビューの表示ロジックを追加
                });

                boardViewBtn.addEventListener('click', function () {
                    boardViewBtn.classList.add('active');
                    listViewBtn.classList.remove('active');
                    // ここにボードビューの表示ロジックを追加
                });
            }
        });
    </script>
@endsection