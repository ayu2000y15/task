@extends('layouts.app')

@section('title', 'タスク作成')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>タスク作成 - {{ $project->title }}</h1>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="centered-form">
        <div class="card">
            <div class="card-header">
                <h2>新規タスク</h2>
            </div>
            <div class="card-body">
                <form action="{{ route('projects.tasks.store', $project) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">タスク種別</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="is_task" name="is_milestone_or_folder"
                                value="task" {{ old('is_milestone_or_folder', 'task') == 'task' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_task">
                                <i class="fas fa-tasks"></i> タスク
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input @error('is_milestone') is-invalid @enderror" type="radio"
                                id="is_milestone" name="is_milestone_or_folder" value="milestone" {{ old('is_milestone_or_folder') == 'milestone' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_milestone">
                                <i class="fas fa-flag"></i> マイルストーン
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input @error('is_folder') is-invalid @enderror" type="radio"
                                id="is_folder" name="is_milestone_or_folder" value="folder" {{ old('is_milestone_or_folder') == 'folder' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_folder">
                                <i class="fas fa-folder"></i> フォルダ
                            </label>
                        </div>
                    </div>

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

                    <div id="task-fields">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">開始日</label>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                    id="start_date" name="start_date"
                                    value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="duration" class="form-label">工数（日）</label>
                                <input type="number" class="form-control @error('duration') is-invalid @enderror"
                                    id="duration" name="duration" value="{{ old('duration', 1) }}" min="1" required>
                                @error('duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="end_date" class="form-label">終了日</label>
                                <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                    id="end_date" name="end_date" value="{{ old('end_date', now()->format('Y-m-d')) }}"
                                    required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
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
                        <label for="parent_id" class="form-label">親タスク</label>
                        <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id"
                            name="parent_id">
                            <option value="">なし</option>
                            @foreach($project->tasks as $potentialParent)
                                <option value="{{ $potentialParent->id }}" {{ old('parent_id') == $potentialParent->id ? 'selected' : '' }}>
                                    {{ $potentialParent->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="status-field">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">ステータス</label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status"
                                    required>
                                    <option value="not_started" {{ old('status', 'not_started') == 'not_started' ? 'selected' : '' }}>未着手
                                    </option>
                                    <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>進行中
                                    </option>
                                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>完了</option>
                                    <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>保留中</option>
                                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>キャンセル
                                    </option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ url()->previous(route('projects.show', $project)) }}"
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
            const startDateInput = document.getElementById('start_date');
            const durationInput = document.getElementById('duration');
            const endDateInput = document.getElementById('end_date');
            const taskTypeRadios = document.querySelectorAll('input[name="is_milestone_or_folder"]');
            const taskFields = document.getElementById('task-fields');
            const statusField = document.getElementById('status-field');
            const requiredDateAndDurationFields = [startDateInput, durationInput, endDateInput]; // ステータスは別扱い

            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function updateEndDate() {
                if (document.querySelector('input[name="is_milestone_or_folder"]:checked').value === 'milestone') {
                    endDateInput.value = startDateInput.value;
                    return;
                }
                if (!startDateInput.value || !durationInput.value) return;
                const startDate = new Date(startDateInput.value);
                const duration = parseInt(durationInput.value);
                if (!isNaN(startDate.getTime()) && duration > 0) {
                    const endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + duration - 1);
                    endDateInput.value = formatDate(endDate);
                }
            }

            function updateDuration() {
                if (document.querySelector('input[name="is_milestone_or_folder"]:checked').value === 'milestone') {
                    durationInput.value = 1;
                    return;
                }
                if (!startDateInput.value || !endDateInput.value) return;
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime()) && endDate >= startDate) {
                    const diffTime = Math.abs(endDate - startDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    durationInput.value = diffDays;
                } else if (endDate < startDate) {
                    durationInput.value = 1; // Or handle error
                }
            }

            function toggleTaskTypeFields() {
                const selectedType = document.querySelector('input[name="is_milestone_or_folder"]:checked').value;

                if (selectedType === 'folder') {
                    taskFields.style.display = 'none';
                    statusField.style.display = 'none'; // フォルダにステータスは不要
                    requiredDateAndDurationFields.forEach(field => field.removeAttribute('required'));
                    document.getElementById('status').removeAttribute('required');
                } else if (selectedType === 'milestone') {
                    taskFields.style.display = 'block';
                    statusField.style.display = 'block'; // マイルストーンにはステータスも表示

                    startDateInput.removeAttribute('disabled');
                    startDateInput.setAttribute('required', 'required');

                    durationInput.value = 1;
                    durationInput.setAttribute('disabled', true);
                    durationInput.removeAttribute('required'); // disabledなので不要だが念のため

                    endDateInput.setAttribute('disabled', true);
                    endDateInput.removeAttribute('required'); // disabledなので不要だが念のため
                    if (startDateInput.value) { // 開始日が入力されていれば終了日を同日に設定
                        endDateInput.value = startDateInput.value;
                    }

                    document.getElementById('status').setAttribute('required', 'required');
                } else { // 'task'
                    taskFields.style.display = 'block';
                    statusField.style.display = 'block';
                    requiredDateAndDurationFields.forEach(field => {
                        field.removeAttribute('disabled');
                        field.setAttribute('required', 'required');
                    });
                    durationInput.removeAttribute('disabled'); // readonly から disabled に変更したので念のため
                    endDateInput.removeAttribute('disabled');  // readonly から disabled に変更したので念のため
                    document.getElementById('status').setAttribute('required', 'required');
                }
            }

            startDateInput.addEventListener('change', function () {
                const selectedType = document.querySelector('input[name="is_milestone_or_folder"]:checked').value;
                if (selectedType === 'milestone') {
                    endDateInput.value = this.value; // マイルストーンなら終了日を開始日に追従
                } else {
                    updateEndDate(); // 通常タスクなら工数に基づいて終了日を計算
                }
            });
            durationInput.addEventListener('input', updateEndDate); // 工数が変更されたら終了日を更新
            endDateInput.addEventListener('change', updateDuration);   // 終了日が変更されたら工数を更新

            taskTypeRadios.forEach(radio => radio.addEventListener('change', toggleTaskTypeFields));

            // 初期表示時の処理
            toggleTaskTypeFields();
            if (document.querySelector('input[name="is_milestone_or_folder"]:checked').value !== 'milestone') {
                updateEndDate(); // フォルダ以外で、かつ初期値がある場合に終了日を計算
            }
        });
    </script>
@endsection