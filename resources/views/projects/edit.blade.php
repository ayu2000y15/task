@extends('layouts.app')

@section('title', 'プロジェクト編集')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>プロジェクト編集</h1>
        <div>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondaryme-2">
                <i class="fas fa-arrow-left"></i> 戻る
            </a>
            <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline"
                onsubmit="return confirm('本当に削除しますか？プロジェクト内のすべてのタスクも削除されます。');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> 削除
                </button>
            </form>
        </div>
    </div>

    <div class="centered-form">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('projects.update', $project) }}" method="POST" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label for="title" class="form-label">プロジェクト名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                            value="{{ old('title', $project->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="color" class="form-label">カラー</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="color" name="color"
                                value="{{ old('color', $project->color) }}">
                            <input type="text" class="form-control" id="colorHex"
                                value="{{ old('color', $project->color) }}" readonly>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="start_date" class="form-label">開始日 <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date"
                            name="start_date" value="{{ old('start_date', $project->start_date->format('Y-m-d')) }}"
                            required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="end_date" class="form-label">終了日 <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date"
                            name="end_date" value="{{ old('end_date', $project->end_date->format('Y-m-d')) }}" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                            name="description" rows="4">{{ old('description', $project->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_favorite" name="is_favorite" value="1" {{ old('is_favorite', $project->is_favorite) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_favorite">
                                お気に入りに追加
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">
                            キャンセル
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // カラーピッカーの値が変更されたら、テキストフィールドにも反映
            const colorPicker = document.getElementById('color');
            const colorHex = document.getElementById('colorHex');

            colorPicker.addEventListener('input', function () {
                colorHex.value = this.value;
            });

            // 開始日と終了日のバリデーション
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            function validateDates() {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);

                if (endDate < startDate) {
                    endDateInput.setCustomValidity('終了日は開始日以降の日付を選択してください');
                } else {
                    endDateInput.setCustomValidity('');
                }
            }

            startDateInput.addEventListener('change', validateDates);
            endDateInput.addEventListener('change', validateDates);
        });
    </script>
@endsection