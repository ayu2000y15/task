@extends('layouts.app')

@section('title', '工程一覧')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/tooltip.css') }}">
<style>
    .project-icon-list {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        margin-right: 10px;
        font-weight: bold;
        color: white;
        vertical-align: middle;
    }
    .table th.col-project, .table td.col-project {
        width: 5%;
    }
    .table th.col-task-name, .table td.col-task-name {
        width: 30%;
    }
    @media(max-width: 768px) {
        .mobile-hide {
            display: none !important;
        }
        /* 担当者名は残すため、特別に表示する */
        .mobile-show-assignee {
            display: table-cell !important; /* テーブルセルの場合 */
        }
        /* ホーム画面のリストアイテム用 */
        .list-item-mobile-hide {
            display: none !important;
        }
        .list-item-assignee-mobile {
            display: inline-block !important; /* または block, inline など適切に */
        }
    }

</style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>工程一覧</h1>
        <div>
            <button class="btn btn-outline-primary me-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#filterPanel">
                <i class="fas fa-filter"></i> フィルター
            </button>
            @can('create', App\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> 新規衣装案件
            </a>
            @endcan
        </div>
    </div>

    <x-filter-panel
        :action="route('tasks.index')"
        :filters="$filters"
        :all-projects="$allProjects"
        :all-characters="$charactersForFilter"
        :all-assignees="$assignees"
        :status-options="$statusOptions"
        :show-due-date-filter="true"
    />

    <ul class="nav nav-tabs mb-3" id="taskTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button"
                role="tab" aria-controls="tasks" aria-selected="true">
                <i class="fas fa-tasks"></i> 工程
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="milestones-tab" data-bs-toggle="tab" data-bs-target="#milestones" type="button"
                role="tab" aria-controls="milestones" aria-selected="false">
                <i class="fas fa-flag"></i> 重要納期
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="folders-tab" data-bs-toggle="tab" data-bs-target="#folders" type="button"
                role="tab" aria-controls="folders" aria-selected="false">
                <i class="fas fa-folder"></i> フォルダ
            </button>
        </li>
    </ul>

    <div class="tab-content" id="taskTabsContent">
        <div class="tab-pane fade show active" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">工程一覧</h5>
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
                                    <th class="col-project"></th>
                                    <th class="col-task-name">工程名</th>
                                    <th>キャラクター</th>
                                    <th>担当者</th>
                                    <th class="mobile-hide">開始日</th>
                                    <th class="mobile-hide">終了日</th>
                                    <th class="mobile-hide">工数</th>
                                    <th class="mobile-hide">ステータス</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($tasks->where('is_milestone', false)->where('is_folder', false)->isEmpty())
                                    <tr>
                                        <td colspan="9" class="text-center py-4">表示する工程がありません</td>
                                    </tr>
                                @else
                                    @foreach($tasks->where('is_milestone', false)->where('is_folder', false) as $task)
                                        @php
                                            $rowClass = '';
                                            $now = \Carbon\Carbon::now()->startOfDay();
                                            $daysUntilDue = $task->end_date ? $now->diffInDays($task->end_date, false) : null;

                                            if ($task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled'])) {
                                                $rowClass = 'task-overdue';
                                            } elseif ($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled'])) {
                                                $rowClass = 'task-due-soon';
                                            }
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
                                            $statusColor = '';
                                            switch ($task->status) {
                                                case 'not_started': $statusColor = '#6c757d'; break;
                                                case 'in_progress': $statusColor = '#0d6efd'; break;
                                                case 'completed': $statusColor = '#198754'; break;
                                                case 'on_hold': $statusColor = '#ffc107'; break;
                                                case 'cancelled': $statusColor = '#dc3545'; break;
                                            }

                                            // メモがある場合はホバー可能クラスを追加
                                            $hoverClass = !empty($task->description) ? 'task-row-hoverable' : '';
                                        @endphp
                                        <tr class="{{ $rowClass }} {{ $hoverClass }}"
                                            @if(!empty($task->description))
                                                data-task-description="{{ htmlspecialchars($task->description) }}"
                                            @endif>
                                            <td class="col-project">
                                                <a href="{{ route('projects.show', $task->project) }}" class="text-decoration-none"
                                                    style="color: {{ $task->project->color }};">
                                                    <span class="project-icon-list" style="background-color: {{ $task->project->color }};">
                                                        {{ mb_substr($task->project->title, 0, 1) }}
                                                    </span>
                                                </a>
                                            </td>
                                            <td class="col-task-name">
                                                <div class="d-flex align-items-center">
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
                                                <div>
                                                        @if(!empty($folderPath))
                                                            <small class="text-muted d-block">{{ $folderPath }}</small>
                                                        @endif
                                                        <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}"
                                                            class="text-decoration-none {{ !empty($task->description) ? 'task-name-with-description' : '' }}">{{ $task->name }}</a>
                                                        @if($task->end_date && $task->end_date < $now && !in_array($task->status, ['completed', 'cancelled']))
                                                            <span class="ms-2 badge bg-danger">期限切れ</span>
                                                        @elseif(!$task->is_folder && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >=0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                                                            <span class="ms-2 badge bg-warning text-dark">あと{{ $daysUntilDue }}日</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                {{ $task->character->name ?? '-' }}
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
                                            <td class="mobile-hide">{{ optional($task->start_date)->format('Y/m/d') }}</td>
                                            <td class="mobile-hide">
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
                                                @else
                                                    {{ optional($task->end_date)->format('Y/m/d') }}
                                                @endif
                                            </td>
                                            <td class="mobile-hide">{{ $task->duration }}日</td>
                                            <td class="mobile-hide">
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

        <div class="tab-pane fade" id="milestones" role="tabpanel" aria-labelledby="milestones-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">重要納期一覧</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="col-project"></th>
                                    <th class="col-task-name">重要納期名</th>
                                    <th>キャラクター</th>
                                    <th class="mobile-hide">日付</th>
                                    <th class="mobile-hide">ステータス</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($tasks->where('is_milestone', true)->isEmpty())
                                    <tr>
                                        <td colspan="6" class="text-center py-4">表示する重要納期がありません</td>
                                    </tr>
                                @else
                                    @foreach($tasks->where('is_milestone', true) as $milestone)
                                        @php
                                            $rowClass = '';
                                            $now = \Carbon\Carbon::now()->startOfDay();
                                            $daysUntilDue = $milestone->end_date ? $now->diffInDays($milestone->end_date, false) : null;
                                            if ($milestone->end_date && $milestone->end_date < $now && $milestone->status !== 'completed' && $milestone->status !== 'cancelled') {
                                                $rowClass = 'task-overdue';
                                            } elseif ($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && $milestone->status !== 'completed' && $milestone->status !== 'cancelled') {
                                                $rowClass = 'task-due-soon';
                                            }
                                            $statusColor = '';
                                            switch ($milestone->status) {
                                                case 'not_started': $statusColor = '#6c757d'; break;
                                                case 'in_progress': $statusColor = '#0d6efd'; break;
                                                case 'completed': $statusColor = '#198754'; break;
                                                case 'on_hold': $statusColor = '#ffc107'; break;
                                                case 'cancelled': $statusColor = '#dc3545'; break;
                                            }

                                            // メモがある場合はホバー可能クラスを追加
                                            $hoverClass = !empty($milestone->description) ? 'task-row-hoverable' : '';
                                        @endphp
                                        <tr class="{{ $rowClass }} {{ $hoverClass }}"
                                            @if(!empty($milestone->description))
                                                data-task-description="{{ htmlspecialchars($milestone->description) }}"
                                            @endif>
                                            <td class="col-project">
                                                <a href="{{ route('projects.show', $milestone->project) }}"
                                                    class="text-decoration-none" style="color: {{ $milestone->project->color }};">
                                                    <span class="project-icon-list" style="background-color: {{ $milestone->project->color }};">
                                                        {{ mb_substr($milestone->project->title, 0, 1) }}
                                                    </span>
                                                </a>
                                            </td>
                                            <td class="col-task-name">
                                                <div class="d-flex align-items-center">
                                                    <span class="task-icon me-1"><i class="fas fa-flag" title="重要納期"></i></span>
                                                    <a href="{{ route('projects.tasks.edit', [$milestone->project, $milestone]) }}"
                                                        class="text-decoration-none {{ !empty($milestone->description) ? 'task-name-with-description' : '' }}">{{ $milestone->name }}</a>
                                                </div>
                                            </td>
                                            <td>
                                                {{ $milestone->character->name ?? '-' }}
                                            </td>
                                            <td class="mobile-hide">
                                                @if($milestone->end_date && $milestone->end_date < $now && $milestone->status !== 'completed' && $milestone->status !== 'cancelled')
                                                    <span class="text-danger">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                        {{ $milestone->end_date->format('Y/m/d') }}
                                                    </span>
                                                @elseif($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && $milestone->status !== 'completed' && $milestone->status !== 'cancelled')
                                                    <span class="text-warning">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        {{ $milestone->end_date->format('Y/m/d') }}
                                                    </span>
                                                @else
                                                    {{ optional($milestone->end_date)->format('Y/m/d') }}
                                                @endif
                                            </td>
                                            <td class="mobile-hide">
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
                                    <th class="col-project"></th>
                                    <th class="col-task-name">フォルダ名</th>
                                    <th>キャラクター</th>
                                    <th>親工程</th>
                                    <th>ファイル数</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($tasks->where('is_folder', true)->isEmpty())
                                    <tr>
                                        <td colspan="6" class="text-center py-4">表示するフォルダがありません</td>
                                    </tr>
                                @else
                                    @foreach($tasks->where('is_folder', true)->sortBy('name') as $folder)
                                        @php
                                            $parentFolder = $folder->parent;
                                            $parentName = $parentFolder ? $parentFolder->name : '-';
                                            $fileCount = ($folder->files && is_countable($folder->files)) ? $folder->files->count() : 0;
                                            $hasFiles = $fileCount > 0;

                                            // メモがある場合はホバー可能クラスを追加
                                            $hoverClass = !empty($folder->description) ? 'task-row-hoverable' : '';
                                        @endphp
                                        <tr class="{{ $hoverClass }}"
                                            @if(!empty($folder->description))
                                                data-task-description="{{ htmlspecialchars($folder->description) }}"
                                            @endif>
                                            <td class="col-project">
                                                <a href="{{ route('projects.show', $folder->project) }}"
                                                    class="text-decoration-none" style="color: {{ $folder->project->color }};">
                                                    <span class="project-icon-list" style="background-color: {{ $folder->project->color }};">
                                                        {{ mb_substr($folder->project->title, 0, 1) }}
                                                    </span>
                                                </a>
                                            </td>
                                            <td class="col-task-name">
                                                <div class="d-flex align-items-center">
                                                    <span class="task-icon me-1"><i class="fas fa-folder text-primary"></i></span>
                                                    @if($hasFiles)
                                                        <button class="btn btn-sm btn-outline-secondary me-2 py-0 px-1" type="button" data-bs-toggle="collapse"
                                                                data-bs-target="#folder-files-{{ $folder->id }}" aria-expanded="false" aria-controls="folder-files-{{ $folder->id }}">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                    @endif
                                                    <a href="{{ route('projects.tasks.edit', [$folder->project, $folder]) }}"
                                                       class="text-decoration-none {{ !empty($folder->description) ? 'task-name-with-description' : '' }}">{{ $folder->name }}</a>
                                                </div>
                                            </td>
                                            <td>
                                                {{ $folder->character->name ?? '-' }}
                                            </td>
                                            <td>{{ $parentName }}</td>
                                            <td>
                                                <i class="fas fa-file"></i> {{ $fileCount }}
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
                                                        onsubmit="return confirm('本当に削除しますか？フォルダ内のすべての工程も削除されます。');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @if($hasFiles)
                                        <tr class="collapse" id="folder-files-{{ $folder->id }}">
                                            <td colspan="6" class="p-3 bg-light">
                                                <h6 class="mb-2"><i class="fas fa-paperclip"></i> 添付ファイル ({{ $fileCount }}件)</h6>
                                                <ul class="list-group list-group-flush" id="file-list-for-folder-{{ $folder->id }}">
                                                    @foreach($folder->files as $file)
                                                    <li class="list-group-item d-flex align-items-center justify-content-between" id="folder-{{$folder->id}}-file-item-{{ $file->id }}">
                                                        <div class="d-flex align-items-center">
                                                            @if(Str::startsWith($file->mime_type, 'image/'))
                                                                <a href="{{ route('projects.tasks.files.show', [$file->task->project, $file->task, $file]) }}" data-bs-toggle="tooltip" title="プレビュー" target="_blank">
                                                                    <img src="{{ route('projects.tasks.files.show', [$file->task->project, $file->task, $file]) }}" alt="{{ $file->original_name }}" class="img-thumbnail me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                                </a>
                                                            @else
                                                                <i class="fas fa-file-alt fa-2x me-2 text-secondary" style="width: 40px;"></i>
                                                            @endif
                                                            <div>
                                                                <a href="{{ route('projects.tasks.files.download', [$file->task->project, $file->task, $file]) }}" class="text-decoration-none">
                                                                    {{ $file->original_name }}
                                                                </a>
                                                                <small class="text-muted d-block">{{ round($file->size / 1024, 1) }} KB</small>
                                                            </div>
                                                        </div>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('projects.tasks.files.download', [$file->task->project, $file->task, $file]) }}" class="btn btn-outline-secondary" title="ダウンロード">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-outline-danger folder-file-delete-btn"
                                                                    data-folder-id="{{ $folder->id }}"
                                                                    data-file-id="{{ $file->id }}"
                                                                    data-url="{{ route('projects.tasks.files.destroy', [$file->task->project, $file->task, $file]) }}"
                                                                    title="削除">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ツールチップ要素 -->
    <div id="taskDescriptionTooltip" class="task-description-tooltip"></div>
@endsection

@section('scripts')
    <script src="{{ asset('js/task-tooltip.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Tooltipの初期化
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })

            const statusSelects = document.querySelectorAll('.task-status-select');

            statusSelects.forEach(select => {
                select.addEventListener('change', function () {
                    const taskId = this.dataset.taskId;
                    const projectId = this.dataset.projectId;
                    const status = this.value;

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
                                location.reload();
                            } else {
                                alert('ステータスの更新に失敗しました.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('エラーが発生しました。');
                        });
                });
            });

            const editableCells = document.querySelectorAll('.editable-cell[data-field="assignee"]');

            editableCells.forEach(cell => {
                cell.addEventListener('click', function () {
                    const taskId = this.dataset.taskId;
                    const currentValue = this.dataset.value || '';

                    this.classList.add('d-none');
                    const editForm = this.nextElementSibling;
                    editForm.classList.remove('d-none');

                    const input = editForm.querySelector('input');
                    input.focus();
                    input.select();

                    function completeEdit() {
                        const newValue = input.value.trim();

                        if (newValue !== currentValue) {
                            const projectId = input.dataset.projectId;

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

                        editForm.classList.add('d-none');
                        cell.classList.remove('d-none');
                    }

                    input.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            completeEdit();
                        }
                    });

                    input.addEventListener('blur', completeEdit);
                });
            });

            const listViewBtn = document.getElementById('listViewBtn');
            const boardViewBtn = document.getElementById('boardViewBtn');

            if (listViewBtn && boardViewBtn) {
                listViewBtn.addEventListener('click', function () {
                    listViewBtn.classList.add('active');
                    boardViewBtn.classList.remove('active');
                });

                boardViewBtn.addEventListener('click', function () {
                    boardViewBtn.classList.add('active');
                    listViewBtn.classList.remove('active');
                });
            }

            // フォルダ内ファイル削除処理
            document.addEventListener('click', function(e) {
                const deleteButton = e.target.closest('.folder-file-delete-btn');
                if (deleteButton) {
                    e.preventDefault();
                    const url = deleteButton.dataset.url;
                    const fileId = deleteButton.dataset.fileId;
                    const folderId = deleteButton.dataset.folderId;

                    if (confirm('本当にこのファイルを削除しますか？')) {
                        fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const fileItem = document.getElementById(`folder-${folderId}-file-item-${fileId}`);
                                if (fileItem) {
                                    fileItem.remove();
                                }
                                // ファイル数が0になったかチェックして表示を更新
                                const fileList = document.getElementById(`file-list-for-folder-${folderId}`);
                                if (fileList && fileList.children.length === 0) {
                                    fileList.innerHTML = '<li class="list-group-item text-center text-muted">ファイルがありません。</li>';
                                }
                            } else {
                                alert('ファイルの削除に失敗しました: ' + (data.message || '不明なエラー'));
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                }
            });
        });
    </script>
@endsection
