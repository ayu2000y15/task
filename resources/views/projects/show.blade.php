@extends('layouts.app')

@section('title', '案件詳細 - ' . $project->title)

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/tooltip.css') }}">
    <style>
        .project-header-card {
            background: linear-gradient(135deg,
                    {{ $project->color }}
                    15,
                    {{ $project->color }}
                    05);
            border-left: 4px solid
                {{ $project->color }}
            ;
        }

        .stats-card {
            transition: transform 0.2s ease-in-out;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .character-card {
            border-left: 3px solid
                {{ $project->color }}
            ;
        }

        .task-row {
            transition: background-color 0.2s ease;
        }

        .task-row:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .action-buttons .btn {
            margin: 2px;
        }

        .data-table {
            font-size: 0.9rem;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .badge-status {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }

        .icon-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .section-divider {
            border-top: 2px solid #e9ecef;
            margin: 20px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            min-width: 80px;
            flex-shrink: 0;
        }

        .info-value {
            text-align: right;
            flex-grow: 1;
            margin-left: 10px;
        }

        /* 折り畳み可能なセクション */
        .collapsible-section {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .collapsible-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            border-bottom: 1px solid #e9ecef;
        }

        .collapsible-header:hover {
            background: linear-gradient(135deg,
                    {{ $project->color }}
                    10,
                    {{ $project->color }}
                    05);
        }

        .collapsible-header.collapsed {
            border-bottom: none;
        }

        .collapsible-title {
            display: flex;
            align-items: center;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
        }

        .collapsible-meta {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .collapsible-toggle {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #6c757d;
            transition: transform 0.3s ease;
            padding: 5px;
            border-radius: 4px;
        }

        .collapsible-toggle:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }

        .collapsible-toggle.collapsed {
            transform: rotate(-90deg);
        }

        .collapsible-content {
            padding: 20px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .collapsible-content.collapsed {
            display: none;
        }

        /* キャラクターグリッド */
        .character-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .character-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .character-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .character-header {
            background: linear-gradient(135deg,
                    {{ $project->color }}
                    15,
                    {{ $project->color }}
                    05);
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .character-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .character-actions {
            display: flex;
            gap: 5px;
        }

        .character-body {
            padding: 15px;
        }

        .character-tabs {
            display: flex;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 15px;
        }

        .character-tab {
            flex: 1;
            padding: 8px 12px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            color: #6c757d;
            transition: all 0.2s ease;
            position: relative;
        }

        .character-tab.active {
            color:
                {{ $project->color }}
            ;
            font-weight: 600;
        }

        .character-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background-color:
                {{ $project->color }}
            ;
        }

        .character-tab:hover {
            background-color: #f8f9fa;
        }

        .character-content {
            display: none;
        }

        .character-content.active {
            display: block;
        }

        .character-table {
            font-size: 0.85rem;
        }

        .character-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            padding: 8px;
        }

        .character-table td {
            padding: 6px 8px;
        }

        .character-form {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
        }

        .character-form .row {
            align-items: end;
        }

        .cost-summary {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 10px;
        }

        .add-character-form {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .add-character-form:hover {
            border-color:
                {{ $project->color }}
            ;
            background: linear-gradient(135deg,
                    {{ $project->color }}
                    08,
                    {{ $project->color }}
                    04);
        }

        .empty-characters {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-characters i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* 工程一覧のレスポンシブ対応改善 */
        .table-container {
            margin-left: 0;
            margin-right: 0;
            padding-left: 0;
            padding-right: 0;
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .tasks-table {
            margin-bottom: 0;
        }

        .tasks-table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            padding: 12px 8px;
            font-size: 0.875rem;
        }

        .tasks-table td {
            padding: 10px 8px;
            vertical-align: middle;
        }

        /* 折り畳み状態の表示 */
        .section-summary {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .summary-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .summary-item i {
            font-size: 0.8rem;
        }

        /* アニメーション */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* レスポンシブ対応 */
        @media (max-width: 768px) {
            .action-buttons {
                text-align: center !important;
                margin-top: 15px;
            }

            .action-buttons .btn {
                margin: 2px;
                font-size: 0.875rem;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .info-value {
                text-align: left;
                margin-left: 0;
                margin-top: 4px;
            }

            .character-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .character-tabs {
                flex-wrap: wrap;
            }

            .character-tab {
                min-width: 60px;
                font-size: 0.8rem;
                padding: 6px 8px;
            }

            .tasks-table th,
            .tasks-table td {
                padding: 8px 4px;
                font-size: 0.8rem;
            }

            .btn-group-sm .btn {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }

            .collapsible-header {
                padding: 12px 15px;
            }

            .collapsible-title {
                font-size: 1rem;
            }

            .section-summary {
                flex-wrap: wrap;
                gap: 10px;
            }
        }

        @media (max-width: 576px) {

            .character-form .col-md-3,
            .character-form .col-md-4,
            .character-form .col-md-5 {
                margin-bottom: 8px;
            }

            .tasks-table {
                font-size: 0.75rem;
            }

            .tasks-table .btn {
                padding: 0.25rem 0.35rem;
                font-size: 0.7rem;
            }

            .collapsible-meta {
                flex-direction: column;
                align-items: flex-end;
                gap: 5px;
            }
        }
    </style>
@endsection

@section('content')
    <!-- プロジェクトヘッダー -->
    <div class="project-header-card card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-2">
                        <div class="icon-circle" style="background-color: {{ $project->color }}; color: white;">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <div>
                            <h1 class="mb-0">{{ $project->title }}</h1>
                            @if($project->series_title)
                                <small class="text-muted">{{ $project->series_title }}</small>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="action-buttons text-end">
                        @can('create', App\Models\Task::class)
                            <a href="{{ route('projects.tasks.create', $project) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> 工程追加
                            </a>
                        @endcan
                        @can('update', $project)
                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-edit"></i> 編集
                            </a>
                        @endcan
                        <a href="{{ route('gantt.index', ['project_id' => $project->id]) }}"
                            class="btn btn-outline-info btn-sm">
                            <i class="fas fa-chart-gantt"></i> ガント
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 左カラム：案件情報・統計 -->
        <div class="col-lg-4">
            <!-- 案件情報カード -->
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center"
                    style="background-color: {{ $project->color }}; color: white;">
                    <i class="fas fa-info-circle me-2"></i>
                    <h5 class="mb-0">案件情報</h5>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">作品名</span>
                        <span class="info-value">{{ $project->series_title ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">依頼主</span>
                        <span class="info-value">{{ $project->client_name ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">期間</span>
                        <span class="info-value">
                            {{ $project->start_date->format('Y/m/d') }}<br>
                            <small class="text-muted">〜 {{ $project->end_date->format('Y/m/d') }}</small>
                        </span>
                    </div>
                    @if($project->description)
                        <div class="info-row">
                            <span class="info-label">備考</span>
                            <span class="info-value">
                                <small>{{ $project->description }}</small>
                            </span>
                        </div>
                    @endif
                    <div class="section-divider"></div>
                    <div class="info-row">
                        <span class="info-label">進捗状況</span>
                        <div class="info-value">
                            @php
                                $totalTasks = $project->tasks()->where('is_folder', false)->where('is_milestone', false)->count();
                                $completedTasks = $project->tasks()->where('is_folder', false)->where('is_milestone', false)->where('status', 'completed')->count();
                                $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                            @endphp
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                    <div class="progress-bar" role="progressbar"
                                        style="width: {{ $progress }}%; background-color: {{ $project->color }};"
                                        aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="badge" style="background-color: {{ $project->color }};">{{ $progress }}%</span>
                            </div>
                            <small class="text-muted">{{ $completedTasks }}/{{ $totalTasks }} 工程完了</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 統計カード -->
            <div class="card stats-card mb-4">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-chart-pie me-2"></i>
                    <h5 class="mb-0">統計情報</h5>
                </div>
                <div class="card-body">
                    <!-- ステータス別統計 -->
                    <h6 class="text-muted mb-3">ステータス別工程数</h6>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="text-center p-2 bg-light rounded">
                                <div class="h5 mb-1 text-secondary">
                                    {{ $project->tasks->where('status', 'not_started')->count() }}
                                </div>
                                <small class="text-muted">未着手</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-2 bg-light rounded">
                                <div class="h5 mb-1 text-primary">
                                    {{ $project->tasks->where('status', 'in_progress')->count() }}
                                </div>
                                <small class="text-muted">進行中</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-2 bg-light rounded">
                                <div class="h5 mb-1 text-success">
                                    {{ $project->tasks->where('status', 'completed')->count() }}
                                </div>
                                <small class="text-muted">完了</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-2 bg-light rounded">
                                <div class="h5 mb-1 text-warning">{{ $project->tasks->where('status', 'on_hold')->count() }}
                                </div>
                                <small class="text-muted">保留中</small>
                            </div>
                        </div>
                    </div>

                    <!-- タイプ別統計 -->
                    <h6 class="text-muted mb-3">タイプ別工程数</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">通常工程</span>
                        <span
                            class="badge bg-primary">{{ $project->tasks->where('is_milestone', false)->where('is_folder', false)->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">重要納期</span>
                        <span class="badge bg-danger">{{ $project->tasks->where('is_milestone', true)->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small">フォルダ</span>
                        <span class="badge bg-secondary">{{ $project->tasks->where('is_folder', true)->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 右カラム：キャラクター・工程 -->
        <div class="col-lg-8">
            <!-- キャラクター管理セクション -->
            <div class="collapsible-section">
                <div class="collapsible-header" data-target="characters-section">
                    <div class="collapsible-title">
                        <i class="fas fa-users me-2"></i>
                        登場キャラクター
                    </div>
                    <div class="collapsible-meta">
                        <div class="section-summary">
                            <div class="summary-item">
                                <i class="fas fa-user"></i>
                                <span>{{ $project->characters->count() }}体</span>
                            </div>
                            @if($project->characters->count() > 0)
                                                <div class="summary-item">
                                                    <span>{{ number_format($project->characters->sum(function ($char) {
                                return $char->costs->sum('amount'); })) }}円</span>
                                                </div>
                            @endif
                        </div>
                        <button class="collapsible-toggle" type="button">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="collapsible-content" id="characters-section">
                    @can('update', $project)
                        <!-- キャラクター追加フォーム -->
                        <div class="add-character-form">
                            <h6 class="mb-3"><i class="fas fa-plus me-2"></i>新しいキャラクターを追加</h6>
                            <form action="{{ route('projects.characters.store', $project) }}" method="POST">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <label class="form-label small">キャラクター名</label>
                                        <input type="text" name="name"
                                            class="form-control form-control-sm @error('name') is-invalid @enderror"
                                            placeholder="キャラクター名を入力" required value="{{ old('name') }}">
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small">備考</label>
                                            <textarea name="description" class="form-control form-control-sm @error('description') is-invalid @enderror"
                                                      placeholder="備考（任意）" rows="1">{{ old('description') }}</textarea>
                                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                                <i class="fas fa-plus"></i> 追加
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                    @endcan

                        @if($project->characters->isEmpty())
                            <div class="empty-characters">
                                <i class="fas fa-user-plus"></i>
                                <h6>キャラクターが登録されていません</h6>
                                <p class="text-muted small">上のフォームから新しいキャラクターを追加してください</p>
                            </div>
                        @else
                            <div class="character-grid">
                                @foreach($project->characters as $character)
                                    <div class="character-item" data-character-id="{{ $character->id }}">
                                        <div class="character-header">
                                            <div class="character-name">
                                                <div>
                                                    <i class="fas fa-user me-2"></i>
                                                    {{ $character->name }}
                                                    @if($character->description)
                                                        <div class="small text-muted mt-1">{{ $character->description }}</div>
                                                    @endif
                                                </div>
                                                @can('update', $project)
                                                    <div class="character-actions">
                                                        <a href="{{ route('characters.edit', $character) }}" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('characters.destroy', $character) }}" method="POST" class="d-inline"
                                                              onsubmit="return confirm('このキャラクターを削除しますか？関連データも全て削除されます。');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endcan
                                            </div>
                                        </div>

                                        <div class="character-body">
                                            <div class="character-tabs">
                                                @can('manageMeasurements', $project)
                                                    <button class="character-tab active" data-tab="measurements-{{ $character->id }}">
                                                        <i class="fas fa-ruler"></i> 採寸
                                                    </button>
                                                @endcan
                                                @can('manageMaterials', $project)
                                                    <button class="character-tab" data-tab="materials-{{ $character->id }}">
                                                        <i class="fas fa-box"></i> 材料
                                                    </button>
                                                @endcan
                                                @can('viewAny', App\Models\Task::class)
                                                    <button class="character-tab" data-tab="tasks-{{ $character->id }}">
                                                        <i class="fas fa-tasks"></i> 工程 ({{ $character->tasks->count() }})
                                                    </button>
                                                @endcan
                                                @can('manageCosts', $project)
                                                    <button class="character-tab" data-tab="costs-{{ $character->id }}">
                                                        <i class="fas fa-yen-sign"></i> コスト
                                                    </button>
                                                @endcan
                                            </div>

                                            @can('manageMeasurements', $project)
                                                <!-- 採寸タブ -->
                                                <div class="character-content active" id="measurements-{{ $character->id }}">
                                                    <table class="table table-sm character-table">
                                                        <thead>
                                                            <tr>
                                                                <th>項目</th>
                                                                <th>数値</th>
                                                                <th>単位</th>
                                                                <th width="30"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse($character->measurements as $measurement)
                                                                <tr>
                                                                    <td>{{ $measurement->item }}</td>
                                                                    <td>{{ $measurement->value }}</td>
                                                                    <td>{{ $measurement->unit }}</td>
                                                                    <td>
                                                                        <form action="{{ route('projects.characters.measurements.destroy', [$project, $character, $measurement]) }}"
                                                                              method="POST" onsubmit="return confirm('削除しますか？');">
                                                                            @csrf @method('DELETE')
                                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                        </form>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr><td colspan="4" class="text-center text-muted">採寸データなし</td></tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                    <div class="character-form">
                                                        <form action="{{ route('projects.characters.measurements.store', [$project, $character]) }}" method="POST" class="row g-2">
                                                            @csrf
                                                            <div class="col-4">
                                                                <input type="text" name="item" class="form-control form-control-sm" placeholder="項目" required>
                                                            </div>
                                                            <div class="col-4">
                                                                <input type="text" name="value" class="form-control form-control-sm" placeholder="数値" required>
                                                            </div>
                                                            <div class="col-2">
                                                                <select name="unit" class="form-select form-select-sm">
                                                                    <option value="cm">cm</option>
                                                                    <option value="mm">mm</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-2">
                                                                <button type="submit" class="btn btn-primary btn-sm w-100">追加</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endcan

                                            @can('manageMaterials', $project)
                                                <!-- 材料タブ -->
                                                <div class="character-content" id="materials-{{ $character->id }}">
                                                    <div class="alert alert-info alert-sm p-2 small mb-3">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        材料を「購入済」にすると価格が登録されていれば同名の「材料費」としてコストに自動追加されます。逆に「未購入」に戻したり材料自体を削除した場合、または対応する「材料費」コスト（同名・同額）を手動で削除した場合、材料のステータスが「未購入」に戻ることがあります。
                                                    </div>
                                                    <table class="table table-sm character-table">
                                                        <thead>
                                                            <tr>
                                                                <th width="50">購入</th>
                                                                <th>材料名</th>
                                                                <th>価格</th>
                                                                <th>必要量</th>
                                                                <th width="30"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse($character->materials as $material)
                                                                <tr>
                                                                    <td>
                                                                        <input type="checkbox" class="form-check-input material-status-check"
                                                                               data-url="{{ route('projects.characters.materials.update', [$project, $character, $material]) }}"
                                                                               {{ $material->status === '購入済' ? 'checked' : '' }}>
                                                                    </td>
                                                                    <td>{{ $material->name }}</td>
                                                                    <td>{{ !is_null($material->price) ? number_format($material->price) . '円' : '-' }}</td>
                                                                    <td>{{ $material->quantity_needed }}</td>
                                                                    <td>
                                                                        <form action="{{ route('projects.characters.materials.destroy', [$project, $character, $material]) }}"
                                                                              method="POST" onsubmit="return confirm('削除しますか？');">
                                                                            @csrf @method('DELETE')
                                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                        </form>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr><td colspan="5" class="text-center text-muted">材料なし</td></tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                    <div class="character-form">
                                                        <form action="{{ route('projects.characters.materials.store', [$project, $character]) }}" method="POST" class="row g-2">
                                                            @csrf
                                                            <div class="col-4">
                                                                <input type="text" name="name" class="form-control form-control-sm" placeholder="材料名" required>
                                                            </div>
                                                            <div class="col-3">
                                                                <input type="number" name="price" class="form-control form-control-sm" placeholder="価格">
                                                            </div>
                                                            <div class="col-3">
                                                                <input type="text" name="quantity_needed" class="form-control form-control-sm" placeholder="必要量" required>
                                                            </div>
                                                            <div class="col-2">
                                                                <button type="submit" class="btn btn-primary btn-sm w-100">追加</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endcan

                                            @can('viewAny', App\Models\Task::class)
                                                {{-- キャラクター工程タブコンテンツ --}}
                                                <div class="character-content" id="tasks-{{ $character->id }}">
                                                        @if($character->tasks->isEmpty())
                                                            <p class="text-center text-muted mt-3">このキャラクターの工程はありません。</p>
                                                        @else
                                                            <table class="table table-sm character-table mt-2">
                                                                <thead>
                                                                    <tr>
                                                                        <th width="30"></th>
                                                                        <th>工程名</th>
                                                                        <th>担当者</th>
                                                                        <th>期間</th>
                                                                        <th>工数</th>
                                                                        <th><i class="fas fa-edit"></i></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($character->tasks()->orderBy('start_date')->get() as $task)
                                                                    @php
                                                                        // メモがある場合はホバー可能クラスを追加
                                                                        $hoverClass = !empty($task->description) ? 'task-row-hoverable' : '';
                                                                    @endphp
                                                                    <tr class="{{ $hoverClass }}"
                                                                        @if(!empty($task->description))
                                                                            data-task-description="{{ htmlspecialchars($task->description) }}"
                                                                        @endif>
                                                                        <td class="text-center">
                                                                            @if($task->is_milestone)
                                                                                <i class="fas fa-flag text-danger" title="重要納期"></i>
                                                                            @elseif($task->is_folder)
                                                                                <i class="fas fa-folder text-primary" title="フォルダ"></i>
                                                                            @else
                                                                                @switch($task->status)
                                                                                    @case('completed') <i class="fas fa-check-circle text-success" title="完了"></i> @break
                                                                                    @case('in_progress') <i class="fas fa-play-circle text-primary" title="進行中"></i> @break
                                                                                    @case('on_hold') <i class="fas fa-pause-circle text-warning" title="保留中"></i> @break
                                                                                    @case('cancelled') <i class="fas fa-times-circle text-danger" title="キャンセル"></i> @break
                                                                                    @default <i class="far fa-circle text-secondary" title="未着手"></i>
                                                                                @endswitch
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            <a href="{{ route('projects.tasks.edit', [$project, $task]) }}"
                                                                               class="text-decoration-none {{ !empty($task->description) ? 'task-name-with-description' : '' }}">
                                                                                {{ $task->name }}
                                                                            </a>
                                                                            @if(!$task->is_milestone && !$task->is_folder && $task->end_date && $task->end_date < now() && !in_array($task->status, ['completed', 'cancelled']))
                                                                                <span class="ms-1 badge bg-danger">期限切れ</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            <span class="text-muted">{{ $task->assignee ?? '-' }}</span>
                                                                        </td>
                                                                        <td class="small">
                                                                            {{ $task->start_date ? $task->start_date->format('m/d') : '-' }}
                                                                            ~
                                                                            {{ $task->end_date ? $task->end_date->format('m/d') : '-' }}
                                                                        </td>
                                                                        <td>
                                                                            <span class="text-muted">{{ $task->is_folder ? '-' : ($task->duration ? $task->duration . '日' : '-') }}</span>
                                                                        </td>
                                                                        <td><a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="btn btn-xs btn-outline-primary"><i class="fas fa-edit"></i></a></td>
                                                                    </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        @endif
                                                    </div>
                                            @endcan

                                            @can('manageCosts', $project)
                                                <!-- コストタブ -->
                                                <div class="character-content" id="costs-{{ $character->id }}">
                                                    @include('projects.partials.character_costs_list', ['project' => $project, 'character' => $character])
                                                </div>
                                            @endcan
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 工程一覧セクション -->
                <div class="collapsible-section">
                    <div class="collapsible-header" data-target="tasks-section">
                        <div class="collapsible-title">
                            <i class="fas fa-tasks me-2"></i>
                            工程一覧
                        </div> <span class="text-muted small ms-2">(案件全体の工程)</span>
                        <div class="collapsible-meta">
                            <div class="section-summary">
                                <div class="summary-item">
                                    <i class="fas fa-list"></i>
                                    <span>{{ $project->tasksWithoutCharacter->count() }}件</span>
                                </div>
                                <div class="summary-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>{{ $project->tasksWithoutCharacter->where('status', 'completed')->count() }}完了</span>
                                </div>
                                <div class="summary-item">
                                    <i class="fas fa-play-circle text-primary"></i>
                                    <span>{{ $project->tasksWithoutCharacter->where('status', 'in_progress')->count() }}進行中</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @can('create', App\Models\Task::class)
                                    <a href="{{ route('projects.tasks.create', $project) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> 追加
                                    </a>
                                    <button class="btn btn-outline-secondary btn-sm" id="toggleCompletedBtn">
                                        <i class="fas fa-eye-slash"></i>
                                    </button>
                                @endcan
                                <button class="collapsible-toggle" type="button">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="collapsible-content" id="tasks-section">
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover tasks-table">
                                    <thead>
                                        <tr>
                                            <th width="30"></th>
                                            <th>工程名</th>
                                            <th>担当者</th>
                                            <th>期間</th>
                                            <th>工数</th>
                                            <th width="120">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($project->tasksWithoutCharacter->isEmpty())
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="fas fa-tasks text-muted mb-2" style="font-size: 2rem;"></i>
                                                    <p class="text-muted mb-0">案件全体の工程がありません</p>
                                                </td>
                                            </tr>
                                        @else
                                            @foreach($project->tasksWithoutCharacter->sortBy(function ($task) {
                                                return $task->start_date ?? '9999-12-31'; }) as $task)
                                                    @php
                                                        $rowClass = '';
                                                        $now = \Carbon\Carbon::now()->startOfDay();
                                                        $daysUntilDue = $task->end_date ? $now->diffInDays($task->end_date, false) : null;

                                                        if ($task->status === 'completed' || $task->status === 'cancelled') {
                                                            $rowClass = 'completed-task';
                                                        } elseif (!$task->is_folder && !$task->is_milestone && $task->end_date && $task->end_date < $now) {
                                                            $rowClass = 'task-overdue';
                                                        } elseif (!$task->is_folder && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2) {
                                                            $rowClass = 'task-due-soon';
                                                        }

                                                        // メモがある場合はホバー可能クラスを追加
                                                        $hoverClass = !empty($task->description) ? 'task-row-hoverable' : '';
                                                    @endphp
                                                    <tr class="task-row {{ $rowClass }} {{ $hoverClass }}"
                                                        @if(!empty($task->description))
                                                            data-task-description="{{ htmlspecialchars($task->description) }}"
                                                        @endif>
                                                        <td class="text-center">
                                                            @if($task->is_milestone)
                                                                <i class="fas fa-flag text-danger" title="重要納期"></i>
                                                            @elseif($task->is_folder)
                                                                <i class="fas fa-folder text-primary" title="フォルダ"></i>
                                                            @else
                                                                @switch($task->status)
                                                                    @case('completed') <i class="fas fa-check-circle text-success" title="完了"></i> @break
                                                                    @case('in_progress') <i class="fas fa-play-circle text-primary" title="進行中"></i> @break
                                                                    @case('on_hold') <i class="fas fa-pause-circle text-warning" title="保留中"></i> @break
                                                                    @case('cancelled') <i class="fas fa-times-circle text-danger" title="キャンセル"></i> @break
                                                                    @default <i class="far fa-circle text-secondary" title="未着手"></i>
                                                                @endswitch
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <a href="{{ route('projects.tasks.edit', [$project, $task]) }}"
                                                                   class="text-decoration-none fw-medium {{ !empty($task->description) ? 'task-name-with-description' : '' }}">
                                                                    {{ $task->name }}
                                                                </a>
                                                                @if(!$task->is_milestone && !$task->is_folder && $task->end_date && $task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                                    <span class="ms-2 badge bg-danger">期限切れ</span>
                                                                @elseif(!$task->is_milestone && !$task->is_folder && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                                    <span class="ms-2 badge bg-warning text-dark">あと{{ $daysUntilDue }}日</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="text-muted">{{ $task->assignee ?? '-' }}</span>
                                                        </td>
                                                        <td>
                                                            <div class="small">
                                                                <div>{{ $task->start_date ? $task->start_date->format('m/d') : '-' }}</div>
                                                                <div class="text-muted">{{ $task->end_date ? $task->end_date->format('m/d') : '-' }}</div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="text-muted">{{ $task->is_folder ? '-' : ($task->duration ? $task->duration . '日' : '-') }}</span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                @can('update', $task)
                                                                    <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="btn btn-outline-primary btn-sm">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                @endcan
                                                                @can('delete', $task)
                                                                    <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST" class="d-inline"
                                                                          onsubmit="return confirm('本当に削除しますか？');">
                                                                        @csrf @method('DELETE')
                                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                @endcan
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
        </div>

    <!-- ツールチップ要素 -->
    <div id="taskDescriptionTooltip" class="task-description-tooltip"></div>
@endsection

@section('scripts')
    <script src="{{ asset('js/task-tooltip.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 折り畳みセクションの制御
            document.querySelectorAll('.collapsible-header').forEach(header => {
                header.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const content = document.getElementById(targetId);
                    const toggle = this.querySelector('.collapsible-toggle');

                    if (content.classList.contains('collapsed')) {
                        // 展開
                        content.classList.remove('collapsed');
                        content.classList.add('fade-in');
                        this.classList.remove('collapsed');
                        toggle.classList.remove('collapsed');

                        // ローカルストレージに状態を保存
                        localStorage.setItem(`section-${targetId}`, 'expanded');
                    } else {
                        // 折り畳み
                        content.classList.add('collapsed');
                        content.classList.remove('fade-in');
                        this.classList.add('collapsed');
                        toggle.classList.add('collapsed');

                        // ローカルストレージに状態を保存
                        localStorage.setItem(`section-${targetId}`, 'collapsed');
                    }
                });
            });

            // ページ読み込み時に前回の状態を復元
            document.querySelectorAll('.collapsible-header').forEach(header => {
                const targetId = header.getAttribute('data-target');
                const savedState = localStorage.getItem(`section-${targetId}`);

                if (savedState === 'collapsed') {
                    const content = document.getElementById(targetId);
                    const toggle = header.querySelector('.collapsible-toggle');

                    content.classList.add('collapsed');
                    header.classList.add('collapsed');
                    toggle.classList.add('collapsed');
                }
            });

            // キャラクタータブの切り替え
            document.querySelectorAll('.character-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-tab');
                    const characterItem = this.closest('.character-item');

                    // 同じキャラクター内のタブとコンテンツをリセット
                    characterItem.querySelectorAll('.character-tab').forEach(t => t.classList.remove('active'));
                    characterItem.querySelectorAll('.character-content').forEach(c => c.classList.remove('active'));

                    // 選択されたタブとコンテンツをアクティブに
                    this.classList.add('active');
                    document.getElementById(targetId).classList.add('active');
                });
            });

            // 完了タスクの表示/非表示切り替え
            const toggleCompletedBtn = document.getElementById('toggleCompletedBtn');
            if (toggleCompletedBtn) {
                let completedTasksHidden = false;
                toggleCompletedBtn.addEventListener('click', function (e) {
                    e.stopPropagation(); // 親の折り畳み動作を防ぐ

                    const taskRows = document.querySelectorAll('.task-row.completed-task');
                    taskRows.forEach(taskRow => {
                        taskRow.style.display = completedTasksHidden ? '' : 'none';
                    });
                    completedTasksHidden = !completedTasksHidden;
                    this.innerHTML = completedTasksHidden ?
                        '<i class="fas fa-eye"></i>' :
                        '<i class="fas fa-eye-slash"></i>';

                    this.title = completedTasksHidden ? '完了工程を表示' : '完了工程を隠す';
                });
            }

            // 材料ステータス更新とコスト表示リフレッシュ
            document.body.addEventListener('change', function(event) {
                if (event.target.classList.contains('material-status-check')) {
                    const checkbox = event.target;
                    const url = checkbox.dataset.url;
                    const newStatus = checkbox.checked ? '購入済' : '未購入';
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const characterItem = checkbox.closest('.character-item');
                    const characterId = characterItem ? characterItem.dataset.characterId : null;
                    const projectId = '{{ $project->id }}';

                    if (!characterId) {
                        console.error('Character ID not found for material status update.');
                        return;
                    }

                    fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({ status: newStatus })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            refreshCharacterCosts(projectId, characterId);
                        } else {
                            console.error('材料ステータス更新に失敗しました');
                            checkbox.checked = !checkbox.checked;
                        }
                    })
                    .catch(error => {
                        console.error('Error updating material status:', error);
                        checkbox.checked = !checkbox.checked;
                    });
                }
            });

            function refreshCharacterCosts(projectId, characterId) {
                const costsTabContent = document.getElementById(`costs-${characterId}`);
                if (!costsTabContent) {
                    console.error(`Costs tab content for character ${characterId} not found.`);
                    return;
                }

                const costsPartialUrl = `/projects/${projectId}/characters/${characterId}/costs-partial`;

                fetch(costsPartialUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Network response was not ok: ${response.statusText}`);
                        }
                        return response.text();
                    })
                    .then(html => {
                        costsTabContent.innerHTML = html;
                    })
                    .catch(error => console.error('Error refreshing costs tab:', error));
            }

            // 折り畳みボタンのクリック時に親の動作を防ぐ
            document.querySelectorAll('.collapsible-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
        });
    </script>
@endsection
