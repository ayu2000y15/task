@extends('layouts.app')

@section('title', '工程テンプレート編集: ' . $processTemplate->name)

@section('content')
    <div class="container">
        <h1>工程テンプレート編集: {{ $processTemplate->name }}</h1>

        {{-- テンプレート自体の情報編集フォーム --}}
        <div class="card mb-4">
            <div class="card-header">テンプレート情報</div>
            <div class="card-body">
                <form action="{{ route('process-templates.update', $processTemplate) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="name" class="form-label">テンプレート名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                            value="{{ old('name', $processTemplate->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                            name="description" rows="3">{{ old('description', $processTemplate->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">テンプレート情報を更新</button>
                </form>
            </div>
        </div>

        {{-- 工程項目リストと追加フォーム --}}
        <div class="card">
            <div class="card-header">工程項目</div>
            <div class="card-body">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>順序</th>
                            <th>工程名</th>
                            <th>標準工数(日)</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($processTemplate->items as $item)
                            <tr>
                                <td>{{ $item->order }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->default_duration ?? '-' }}</td>
                                <td>
                                    <form action="{{ route('process-templates.items.destroy', [$processTemplate, $item]) }}"
                                        method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">工程項目がありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <h5>工程項目を追加</h5>
                <form action="{{ route('process-templates.items.store', $processTemplate) }}" method="POST"
                    class="row gx-2">
                    @csrf
                    <div class="col-md-5 mb-2">
                        <label for="item_name" class="form-label">工程名</label>
                        <input type="text" name="name"
                            class="form-control form-control-sm @error('name', 'itemErrors') is-invalid @enderror"
                            placeholder="工程名" required>
                        @error('name', 'itemErrors') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="item_default_duration" class="form-label">標準工数(日)</label>
                        <input type="number" name="default_duration"
                            class="form-control form-control-sm @error('default_duration', 'itemErrors') is-invalid @enderror"
                            placeholder="標準工数(日)">
                        @error('default_duration', 'itemErrors') <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="item_order" class="form-label">順序</label>
                        <input type="number" name="order"
                            class="form-control form-control-sm @error('order', 'itemErrors') is-invalid @enderror"
                            placeholder="順序" value="{{ ($processTemplate->items->max('order') ?? -1) + 1 }}" required>
                        @error('order', 'itemErrors') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">追加</button>
                    </div>
                </form>
                @if ($errors->itemErrors->any()) {{-- バリデーションエラーがあれば表示 --}}
                    <div class="alert alert-danger mt-2 p-2">
                        <ul class="mb-0">
                            @foreach ($errors->itemErrors->all() as $error)
                                <li><small>{{ $error }}</small></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
        <div class="mt-3">
            <a href="{{ route('process-templates.index') }}" class="btn btn-secondary">テンプレート一覧へ戻る</a>
        </div>
    </div>
@endsection