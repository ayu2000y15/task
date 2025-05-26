@extends('layouts.app')

@section('title', '工程作成')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>工程作成 - {{ $project->title }}</h1>
    </div>

    {{-- テンプレート適用フォーム --}}
    <div class="card mb-4">
        <div class="card-header">テンプレートから工程を一括作成</div>
        <div class="card-body">
            <form action="{{ route('projects.tasks.fromTemplate', $project) }}" method="POST">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="process_template_id" class="form-label">工程テンプレートを選択</label>
                        <select class="form-select" id="process_template_id" name="process_template_id" required>
                            <option value="">選択してください</option>
                            @foreach($processTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="character_id_for_template" class="form-label">所属先キャラクター (任意)</label>
                        <select class="form-select" id="character_id_for_template" name="character_id_for_template">
                            <option value="">案件全体 (キャラクター未所属)</option>
                            @foreach($project->characters as $character)
                                <option value="{{ $character->id }}">{{ $character->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="template_start_date" class="form-label">最初の工程の開始日</label>
                        <input type="date" class="form-control" id="template_start_date" name="template_start_date"
                            value="{{ old('template_start_date', now()->format('Y-m-d')) }}" required>
                    </div>
                    <input type="hidden" name="parent_id_for_template" value="{{ optional($parentTask)->id }}"> {{--
                    親タスクがある場合、それも引き継ぐ --}}
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-info w-100">テンプレートを適用して作成</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <hr>

    <div class="centered-form">
        <div class="card">
            <div class="card-header">
                <h2>新規工程</h2>
            </div>
            <div class="card-body">
                <form action="{{ route('projects.tasks.store', $project) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">工程種別</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="is_task" name="is_milestone_or_folder"
                                value="task" {{ old('is_milestone_or_folder', 'task') == 'task' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_task">
                                <i class="fas fa-tasks"></i> 工程
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="is_todo_task" name="is_milestone_or_folder" value="todo_task">
                            <label class="form-check-label" for="is_todo_task">
                                <i class="fas fa-list-check"></i> タスク（期限なし）
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input @error('is_milestone') is-invalid @enderror" type="radio"
                                id="is_milestone" name="is_milestone_or_folder" value="milestone" {{ old('is_milestone_or_folder') == 'milestone' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_milestone">
                                <i class="fas fa-flag"></i> 重要納期
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
                        <label for="name" class="form-label">工程名</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                            value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ★ここからキャラクター選択を追加 --}}
                    <div class="mb-3">
                        <label for="character_id" class="form-label">所属先</label>
                        <select class="form-select @error('character_id') is-invalid @enderror" id="character_id" name="character_id">
                            <option value="">案件全体</option>
                            @foreach($project->characters as $character)
                                <option value="{{ $character->id }}" {{ old('character_id') == $character->id ? 'selected' : '' }}>
                                    {{ $character->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('character_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    {{-- ★ここまでキャラクター選択 --}}

                    <div class="mb-3">
                        <label for="description" class="form-label">メモ</label>
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
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}">
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="duration" class="form-label">工数（日）</label>
                                <input type="number" class="form-control @error('duration') is-invalid @enderror" id="duration" name="duration" value="{{ old('duration', 1) }}" min="1">
                                @error('duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="end_date" class="form-label">終了日</label>
                                <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}">
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
                        <label for="parent_id" class="form-label">親工程</label>
                        <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id"
                            name="parent_id">
                            <option value="">なし</option>
                            @foreach($project->tasks as $potentialParent)
                                <option value="{{ $potentialParent->id }}" {{ (old('parent_id', optional($parentTask)->id)) == $potentialParent->id ? 'selected' : '' }}>
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
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
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
            const requiredDateAndDurationFields = [startDateInput, durationInput, endDateInput];

            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function updateEndDate() {
                if (document.querySelector('input[name="is_milestone_or_folder"]:checked').value === 'milestone') {
                    if (startDateInput.value) {
                        endDateInput.value = startDateInput.value;
                    } else {
                        endDateInput.value = '';
                    }
                    return;
                }
                // 「工程（期限なし）」の場合は日付計算をスキップ
                if (document.querySelector('input[name="is_milestone_or_folder"]:checked').value === 'todo_task') {
                    endDateInput.value = ''; // 念のためクリア
                    return;
                }

                if (!startDateInput.value || !durationInput.value) { endDateInput.value = ''; return; }
                const startDate = new Date(startDateInput.value);
                if (isNaN(startDate.getTime())) { endDateInput.value = ''; return; } // 無効な日付ならクリア
                const duration = parseInt(durationInput.value);
                if (!isNaN(startDate.getTime()) && duration > 0) {
                    const endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + duration - 1);
                    endDateInput.value = formatDate(endDate);
                } else if (isNaN(startDate.getTime()) && duration > 0 && document.querySelector('input[name="is_milestone_or_folder"]:checked').value === 'task') {
                    // 開始日なしで工数のみ入力された場合（通常工程）は、今日を開始日として終了日を計算する (任意)
                    const tempStartDate = new Date();
                    startDateInput.value = formatDate(tempStartDate);
                    const startDate = new Date(startDateInput.value); // 再度取得
                    const endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + duration - 1);
                    endDateInput.value = formatDate(endDate);
                } else {
                     endDateInput.value = '';
                }
            }

            function updateDuration() {
                if (document.querySelector('input[name="is_milestone_or_folder"]:checked').value === 'milestone') {
                    durationInput.value = startDateInput.value ? 1 : '';
                    return;
                }
                 // 「工程（期限なし）」の場合は日付計算をスキップ
                if (document.querySelector('input[name="is_milestone_or_folder"]:checked').value === 'todo_task') {
                    durationInput.value = ''; // 念のためクリア
                    return;
                }

                if (!startDateInput.value || !endDateInput.value) { durationInput.value = ''; return; }
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) { durationInput.value = ''; return; }

                if (endDate >= startDate) {
                    const diffTime = Math.abs(endDate - startDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    durationInput.value = diffDays;
                } else if (endDate < startDate) {
                    durationInput.value = 1;
                } else {
                    durationInput.value = '';
                }
            }

            function toggleTaskTypeFields() {
                const selectedType = document.querySelector('input[name="is_milestone_or_folder"]:checked').value;
                const characterIdFieldWrapper = document.getElementById('character_id').closest('.mb-3');


                if (selectedType === 'folder') {
                    taskFields.style.display = 'none';
                    statusField.style.display = 'none';
                    if (characterIdFieldWrapper) characterIdFieldWrapper.style.display = 'block';
                    requiredDateAndDurationFields.forEach(field => { field.removeAttribute('required'); field.value = ''; });
                    if(document.getElementById('status')) document.getElementById('status').removeAttribute('required');
                } else if (selectedType === 'milestone') {
                    taskFields.style.display = 'block';
                    statusField.style.display = 'block';
                    if (characterIdFieldWrapper) characterIdFieldWrapper.style.display = 'block';

                    startDateInput.removeAttribute('disabled');
                    startDateInput.setAttribute('required', 'required');
                    if (!startDateInput.value && requiredDateAndDurationFields.includes(startDateInput)) {
                        startDateInput.value = formatDate(new Date());
                    }

                    durationInput.value = 1;
                    durationInput.setAttribute('disabled', true);
                    durationInput.removeAttribute('required');

                    endDateInput.setAttribute('disabled', true);
                    endDateInput.removeAttribute('required');

                    if (startDateInput.value) {
                        endDateInput.value = startDateInput.value;
                    }

                    if(document.getElementById('status')) document.getElementById('status').setAttribute('required', 'required');
                } else if (selectedType === 'task') {
                    taskFields.style.display = 'block';
                    statusField.style.display = 'block';
                    if (characterIdFieldWrapper) characterIdFieldWrapper.style.display = 'block';
                    requiredDateAndDurationFields.forEach(field => {
                        field.removeAttribute('disabled');
                        field.setAttribute('required', 'required');
                    });

                    if (!startDateInput.value) startDateInput.value = formatDate(new Date());
                    if (!durationInput.value) durationInput.value = 1;
                    updateEndDate(); // 「工程」選択時に日付を初期設定・計算

                    durationInput.removeAttribute('disabled');
                    endDateInput.removeAttribute('disabled');
                    if(document.getElementById('status')) document.getElementById('status').setAttribute('required', 'required');
                } else if (selectedType === 'todo_task') {
                    taskFields.style.display = 'block';
                    statusField.style.display = 'block';
                    if (characterIdFieldWrapper) characterIdFieldWrapper.style.display = 'block';
                    requiredDateAndDurationFields.forEach(field => {
                        field.removeAttribute('disabled');
                        field.removeAttribute('required');
                        field.value = '';
                    });

                    durationInput.removeAttribute('disabled');
                    endDateInput.removeAttribute('disabled');
                    if(document.getElementById('status')) document.getElementById('status').setAttribute('required', 'required');
                }
            }

            startDateInput.addEventListener('change', function() {
                const selectedType = document.querySelector('input[name="is_milestone_or_folder"]:checked').value;
                if (selectedType === 'milestone') {
                    endDateInput.value = this.value;
                     durationInput.value = this.value ? 1 : ''; // 開始日がクリアされたら工数もクリア
                } else if (selectedType !== 'todo_task') { // todo_task 以外の場合のみ日付計算
                    updateEndDate();
                }
            });
            durationInput.addEventListener('input', function() {
                 if (document.querySelector('input[name="is_milestone_or_folder"]:checked').value !== 'todo_task') {
                    updateEndDate();
                 }
            });
            endDateInput.addEventListener('change', function() {
                 if (document.querySelector('input[name="is_milestone_or_folder"]:checked').value !== 'todo_task') {
                    updateDuration();
                 }
            });


            taskTypeRadios.forEach(radio => radio.addEventListener('change', toggleTaskTypeFields));

            toggleTaskTypeFields();
        });
    </script>
@endsection