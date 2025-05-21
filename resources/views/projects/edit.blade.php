@extends('layouts.app')

@section('title', 'プロジェクト編集')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>プロジェクト編集</h1>
    </div>

    <div class="centered-form">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('projects.update', $project) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="title" class="form-label">プロジェクト名</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                            value="{{ old('title', $project->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                            name="description" rows="3">{{ old('description', $project->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">開始日</label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                id="start_date" name="start_date"
                                value="{{ old('start_date', $project->start_date->format('Y-m-d')) }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="end_date" class="form-label">終了日</label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date"
                                name="end_date" value="{{ old('end_date', $project->end_date->format('Y-m-d')) }}" required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="color" class="form-label">プロジェクトカラー</label>
                        <div class="color-picker-wrapper">
                            <div class="color-preview" id="color-preview" style="background-color: {{ $project->color }};">
                            </div>
                            <input type="hidden" id="color" name="color" value="{{ $project->color }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_favorite" name="is_favorite" {{ $project->is_favorite ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_favorite">
                                <i class="fas fa-star text-warning"></i> お気に入りに追加
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">キャンセル</a>
                        <div>
                            <button type="submit" class="btn btn-primary">更新</button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                data-bs-target="#deleteProjectModal">
                                削除
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 削除確認モーダル -->
    <div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-labelledby="deleteProjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProjectModalLabel">プロジェクト削除の確認</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>プロジェクト「{{ $project->title }}」を削除しますか？</p>
                    <p class="text-danger">この操作は取り消せません。プロジェクトに関連するすべてのタスクも削除されます。</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <form action="{{ route('projects.destroy', $project) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">削除</button>
                    </form>
                </div>
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
                default: '{{ $project->color }}',
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