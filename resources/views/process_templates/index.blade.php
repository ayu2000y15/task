@extends('layouts.app')

@section('title', '工程テンプレート管理')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>工程テンプレート管理</h1>
            <a href="{{ route('process-templates.create') }}" class="btn btn-primary">新規テンプレート作成</a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>テンプレート名</th>
                            <th>説明</th>
                            <th>工程数</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td><a href="{{ route('process-templates.show', $template) }}">{{ $template->name }}</a></td>
                                <td>{{ Str::limit($template->description, 50) }}</td>
                                <td>{{ $template->items_count ?? $template->items->count() }}</td>
                                <td>
                                    <a href="{{ route('process-templates.show', $template) }}"
                                        class="btn btn-sm btn-outline-primary">編集</a>
                                    <form action="{{ route('process-templates.destroy', $template) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('本当に削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">テンプレートがありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection