@extends('layouts.app')

@section('title', 'プロジェクト一覧')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>プロジェクト一覧</h1>
        <a href="{{ route('projects.create') }}" class="btn btn-primary">新規プロジェクト</a>
    </div>

    @if($projects->isEmpty())
        <div class="alert alert-info">
            プロジェクトがありません。新規プロジェクトを作成してください。
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>プロジェクト名</th>
                        <th>開始日</th>
                        <th>終了日</th>
                        <th>タスク数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($projects as $project)
                        <tr>
                            <td>{{ $project->title }}</td>
                            <td>{{ $project->start_date->format('Y/m/d') }}</td>
                            <td>{{ $project->end_date->format('Y/m/d') }}</td>
                            <td>{{ $project->tasks->count() }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-info">表示</a>
                                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-warning">編集</a>
                                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('本当に削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">削除</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection