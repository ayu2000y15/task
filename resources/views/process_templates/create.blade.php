@extends('layouts.app')

@section('title', '新規工程テンプレート作成')

@section('content')
    <div class="container">
        <h1>新規工程テンプレート作成</h1>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('process-templates.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">テンプレート名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                            value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                            name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">作成して工程項目を編集</button>
                    <a href="{{ route('process-templates.index') }}" class="btn btn-secondary">キャンセル</a>
                </form>
            </div>
        </div>
    </div>
@endsection