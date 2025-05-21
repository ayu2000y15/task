@extends('layouts.app')

@section('title', '新規プロジェクト')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>新規プロジェクト</h1>
    </div>

    <div class="centered-form">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('projects.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="title" class="form-label">プロジェクト名</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                            value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                            name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">開始日</label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                id="start_date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}"
                                required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="end_date" class="form-label">終了日</label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date"
                                name="end_date" value="{{ old('end_date', now()->addMonths(1)->format('Y-m-d')) }}"
                                required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="color" class="form-label">プロジェクトカラー</label>
                        <div class="color-picker-wrapper">
                            <div class="color-preview" id="color-preview" style="background-color: #007bff;"></div>
                            <input type="hidden" id="color" name="color" value="#007bff">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('projects.index') }}" class="btn btn-secondary">キャンセル</a>
                        <button type="submit" class="btn btn-primary">作成</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // カラーピッカーの初期化
            const pickr = Pickr.create({
                el: '#color-preview',
                theme: 'classic',
                default: '#007bff',
                swatches: [
                    '#007bff',
                    '#28a745',
                    '#dc3545',
                    '#ffc107',
                    '#17a2b8',
                    '#6610f2',
                    '#fd7e14',
                    '#20c997',
                    '#e83e8c',
                    '#6c757d'
                ],
                components: {
                    preview: true,
                    opacity: true,
                    hue: true,
                    interaction: {
                        hex: true,
                        rgba: true,
                        hsla: false,
                        hsva: false,
                        cmyk: false,
                        input: true,
                        clear: false,
                        save: true
                    }
                }
            });

            pickr.on('save', (color, instance) => {
                const hexColor = color.toHEXA().toString();
                document.getElementById('color').value = hexColor;
                document.getElementById('color-preview').style.backgroundColor = hexColor;
                instance.hide();
            });
        });
    </script>
@endsection