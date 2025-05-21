@extends('layouts.app')

@section('title', '新規タスク')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>タスク追加 - {{ $project->title }}</h1>
    </div>

    <div class="centered-form">
        <div class="card">
            <div class="card-header">
                <h2>タスク追加 - {{ $project->title }}</h2>
                @if(isset($parentTask))
                    <p class="text-muted">親タスク: {{ $parentTask->name }}</p>
                @endif
            </div>
            <div class="card-body">
                <form action="{{ route('projects.tasks.store', $project) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">タスク名</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                            value="{{ old('name') }}" required>
                        @error('name')
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
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">開始日</label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                id="start_date" name="start_date"
                                value="{{ old('start_date', $project->start_date->format('Y-m-d')) }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="duration" class="form-label">工数（日）</label>
                            <input type="number" class="form-control @error('duration') is-invalid @enderror" id="duration"
                                name="duration" value="{{ old('duration', 1) }}" min="1" required>
                            @error('duration')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="end_date" class="form-label">終了日</label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date"
                                name="end_date"
                                value="{{ old('end_date', $project->start_date->addDays(1)->format('Y-m-d')) }}" required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="assignee" class="form-label">担当者</label>
                        <input type="text" class="form-control @error('assignee') is-invalid @enderror" id="assignee"
                            name="assignee" value="{{ old('assignee') }}">
                        @error('assignee')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="color" class="form-label">タスクカラー</label>
                        <div class="color-picker-wrapper">
                            <div class="color-preview" id="color-preview"
                                style="background-color: {{ old('color', $project->color) }};"></div>
                            <input type="hidden" id="color" name="color" value="{{ old('color', $project->color) }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_milestone" name="is_milestone" {{ old('is_milestone') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_milestone">
                                <i class="fas fa-flag"></i> マイルストーン
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_folder" name="is_folder" {{ old('is_folder') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_folder">
                                <i class="fas fa-folder"></i> フォルダ
                            </label>
                        </div>
                    </div>

                    @if(isset($parentTask))
                        <input type="hidden" name="parent_id" value="{{ $parentTask->id }}">
                    @endif

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('gantt.index', ['project_id' => $project->id]) }}"
                            class="btn btn-secondary">キャンセル</a>
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
                default: '{{ old('color', $project->color) }}',
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

            // 工数または開始日が変更されたら終了日を自動計算
            const startDateInput = document.getElementById('start_date');
            const durationInput = document.getElementById('duration');
            const endDateInput = document.getElementById('end_date');

            function updateEndDate() {
                const startDate = new Date(startDateInput.value);
                const duration = parseInt(durationInput.value) || 1;

                if (!isNaN(startDate.getTime())) {
                    // 開始日 + (工数 - 1)日 = 終了日（開始日を含むため-1）
                    const endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + duration - 1);

                    // YYYY-MM-DD形式に変換
                    const year = endDate.getFullYear();
                    const month = String(endDate.getMonth() + 1).padStart(2, '0');
                    const day = String(endDate.getDate()).padStart(2, '0');
                    endDateInput.value = `${year}-${month}-${day}`;
                }
            }

            // 終了日が変更されたら工数を自動計算
            function updateDuration() {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);

                if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime())) {
                    // 日数の差分を計算（ミリ秒 → 日）
                    const diffTime = Math.abs(endDate - startDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // 開始日を含むため+1

                    durationInput.value = diffDays;
                }
            }

            startDateInput.addEventListener('change', updateEndDate);
            durationInput.addEventListener('change', updateEndDate);
            endDateInput.addEventListener('change', updateDuration);
        });
    </script>
@endsection