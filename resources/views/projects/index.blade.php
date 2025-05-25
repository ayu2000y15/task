@extends('layouts.app')

@section('title', '衣装案件一覧')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>衣装案件一覧</h1>
        @can('create', App\Models\Project::class)
        <a href="{{ route('projects.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新規衣装案件
        </a>
        @endcan
    </div>

    @if($projects->isEmpty())
        <div class="alert alert-info">
            衣装案件がありません。新規衣装案件を作成してください。
        </div>
    @else
        <div class="row">
            @foreach($projects as $project)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center"
                            style="background-color: {{ $project->color }}; color: white;">
                            <h5 class="mb-0">{{ $project->title }}</h5>
                            @if($project->is_favorite)
                                <i class="fas fa-star text-warning"></i>
                            @endif
                        </div>
                        <div class="card-body">
                            <p class="card-text">{{ Str::limit($project->description, 100) ?: '説明はありません' }}</p>

                            <div class="mb-3">
                                <small class="text-muted">期間:</small>
                                <p class="mb-0">{{ $project->start_date->format('Y/m/d') }} 〜
                                    {{ $project->end_date->format('Y/m/d') }}</p>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted">工程:</small>
                                <div class="d-flex justify-content-between">
                                    <span>全 {{ $project->tasks->count() }} 工程</span>
                                    <span>完了: {{ $project->tasks->where('status', 'completed')->count() }}</span>
                                </div>
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
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100">
                                <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> 詳細
                                </a>
                                <a href="{{ route('gantt.index', ['project_id' => $project->id]) }}"
                                    class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-chart-gantt"></i> ガント
                                </a>
                                @can('update', $project)
                                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-edit"></i> 編集
                                    </a>
                                @endcan

                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection