@extends('layouts.app')

@section('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <style>
        .dropzone-custom-style {
            border: 2px dashed #007bff !important;
            border-radius: .25rem;
            padding: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            min-height: 150px;
        }

        .dropzone-custom-style .dz-message {
            color: #6c757d;
            font-weight: 500;
            width: 100%;
            text-align: center;
            align-self: center;
        }

        .dropzone-custom-style .dz-message p {
            margin-bottom: 0.5rem;
        }

        .dropzone-custom-style .dz-preview {
            width: 120px;
            height: auto;
            margin: 0.25rem;
            background-color: transparent;
            border: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            border-radius: 20px;
        }

        .dropzone-custom-style .dz-image {
            width: 80px;
            height: 80px;
            display: flex;
            border: 1px solid #dee2e6;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            z-index: 1;
            }

            .dropzone-custom-style .dz-image img {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
                background-color: transparent;
            }

                .dropzone-custom-style .dz-details {
                    display: block;
                    text-align: center;
                    width: 100%;
                    position: relative;
                }

                .dropzone-custom-style .dz-filename {
                    display: block;
                    font-size: 0.75em;
                    color: #495057;
                    white-space: normal;
                    word-wrap: break-word;
                    line-height: 1.2;
                    margin-top: 0.25rem;
                }
                .dropzone-custom-style .dz-filename span {
                    background-color: transparent;
                }

                    .dropzone-custom-style .dz-size {
                        font-size: 0.65em;
                        color: #6c757d;
                        margin-top: 0.15rem;
                        background-color: transparent;
                    }
                    .dropzone-custom-style .dz-progress { display: none; }
                    .dropzone-custom-style .dz-error-message { display: none; }
                    .dropzone-custom-style .dz-success-mark,
                    .dropzone-custom-style .dz-error-mark { display: none; }

                    .dropzone-custom-style .dz-remove {
                        position: absolute;
                        top: 5px;
                        right: 5px;
                        background: rgba(220, 53, 69, 0.8);
                        color: white;
                        border-radius: 50%;
                        width: 18px;
                        height: 18px;
                        font-size: 12px;
                        line-height: 18px;
                        text-align: center;
                        font-weight: bold;
                        text-decoration: none;
                        cursor: pointer;
                        opacity: 1;
                        z-index: 30;
                    }

                    .dropzone-custom-style .dz-remove:hover {
                        text-decoration: none !important;
                        color: #aaaaaa;
                    }
                </style>
@endsection

@section('title', '工程編集')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>工程編集 - {{ $project->title }}</h1>
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
                <h2>工程編集</h2>
            </div>
            <div class="card-body">
                <form action="{{ route('projects.tasks.update', [$project, $task]) }}" method="POST" id="task-edit-form">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">工程種別</label>
                        @php
                            $taskType = 'task';
                            if ($task->is_milestone) {
                                $taskType = 'milestone';
                            } elseif ($task->is_folder) {
                                $taskType = 'folder';
                            }
                        @endphp
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="is_task_edit" name="is_milestone_or_folder" value="task" disabled
                                {{ old('is_milestone_or_folder', $taskType) == 'task' ? 'checked' : '' }} >
                            <label class="form-check-label" for="is_task_edit">
                                <i class="fas fa-tasks"></i> 工程
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="is_milestone_edit" name="is_milestone_or_folder" value="milestone" disabled
                                {{ old('is_milestone_or_folder', $taskType) == 'milestone' ? 'checked' : '' }} >
                            <label class="form-check-label" for="is_milestone_edit">
                                <i class="fas fa-flag"></i> 重要納期
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="is_folder_edit" name="is_milestone_or_folder" value="folder" disabled
                                {{ old('is_milestone_or_folder', $taskType) == 'folder' ? 'checked' : '' }} >
                            <label class="form-check-label" for="is_folder_edit">
                                <i class="fas fa-folder"></i> フォルダ
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">工程名</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                            value="{{ old('name', $task->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ★ここからキャラクター選択を追加 --}}
                    <div class="mb-3" id="character_id_wrapper">
                        <label for="character_id" class="form-label">所属先</label>
                        <select class="form-select @error('character_id') is-invalid @enderror" id="character_id" name="character_id" >
                            <option value="">案件全体</option>
                            @foreach($project->characters as $character)
                                <option value="{{ $character->id }}" {{ old('character_id', $task->character_id) == $character->id ? 'selected' : '' }}>
                                    {{ $character->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('character_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    {{-- ★ここまでキャラクター選択 --}}


                    {{-- ファイル管理セクション --}}
                    @if($task->is_folder)
                        <div id="file-management-section" class="mb-3">
                            <hr>
                            <h5><i class="fas fa-file-alt"></i> ファイル管理</h5>
                            <div class="dropzone dropzone-custom-style mb-3"
                                    id="file-upload-dropzone-edit">
                                <div class="dz-message text-center" data-dz-message>
                                    <p class="mb-2">ここにドラッグアンドドロップ</p>
                                    <p class="mb-3">または</p>
                                    <div class="btn btn-outline-primary dz-button-bootstrap">
                                        <i class="fas fa-folder-open me-1"></i>ファイルを選択
                                    </div>
                                </div>
                            </div>

                            <h6>アップロード済みファイル</h6>
                            <ul class="list-group mb-3" id="file-list-edit">
                                @include('tasks.partials.file-list', ['files' => $files, 'project' => $project, 'task' => $task])
                            </ul>
                            <hr>
                        </div>
                    @endif


                    <div class="mb-3">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                            name="description" rows="3">{{ old('description', $task->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="task-fields">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">開始日</label>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date"
                                    value="{{ old('start_date', optional($task->start_date)->format('Y-m-d')) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="duration" class="form-label">工数（日）</label>
                                <input type="number" class="form-control @error('duration') is-invalid @enderror" id="duration" name="duration" value="{{ old('duration', $task->duration) }}" min="1">
                                @error('duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="end_date" class="form-label">終了日</label>
                                @php
                                    $nowCarbon = \Carbon\Carbon::now()->startOfDay();
                                    $daysUntilDue = $task->end_date ? $nowCarbon->diffInDays($task->end_date, false) : null;
                                    $inputClass = 'form-control';

                                    if ($task->end_date && $task->end_date < $nowCarbon && !in_array($task->status, ['completed', 'cancelled'])) {
                                        $inputClass .= ' border-danger';
                                    } elseif ($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled'])) {
                                        $inputClass .= ' border-warning';
                                    }

                                    if ($errors->has('end_date')) {
                                        $inputClass .= ' is-invalid';
                                    }
                                @endphp

                                <div class="input-group">
                                    <input type="date" class="{{ $inputClass }}" id="end_date" name="end_date" value="{{ old('end_date', optional($task->end_date)->format('Y-m-d')) }}">
                                    @if($task->end_date && $task->end_date < $nowCarbon && !in_array($task->status, ['completed', 'cancelled']))
                                        <span class="input-group-text bg-danger text-white">
                                            <i class="fas fa-exclamation-circle" title="期限切れ"></i>
                                        </span>
                                    @elseif($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                                        <span class="input-group-text bg-warning text-dark">
                                            <i class="fas fa-exclamation-triangle" title="期限間近"></i>
                                        </span>
                                    @endif

                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if($task->end_date && $task->end_date < $nowCarbon && !in_array($task->status, ['completed', 'cancelled']))
                                    <div class="text-danger small mt-1">
                                        <i class="fas fa-exclamation-circle"></i> 期限が過ぎています
                                    </div>
                                @elseif($daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && !in_array($task->status, ['completed', 'cancelled']))
                                    <div class="text-warning small mt-1">
                                        <i class="fas fa-exclamation-triangle"></i> 期限が間近です（残り{{ $daysUntilDue }}日）
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="assignee" class="form-label">担当者</label>
                        <input type="text" class="form-control @error('assignee') is-invalid @enderror" id="assignee"
                            name="assignee" value="{{ old('assignee', $task->assignee) }}">
                        @error('assignee')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">親工程</label>
                        <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id"
                            name="parent_id">
                            <option value="">なし</option>
                            @foreach($project->tasks->where('id', '!=', $task->id) as $potentialParent)
                                @if(!$task->isAncestorOf($potentialParent))
                                    <option value="{{ $potentialParent->id }}" {{ old('parent_id', $task->parent_id) == $potentialParent->id ? 'selected' : '' }}>
                                        {{ $potentialParent->name }}
                                    </option>
                                @endif
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
                                    <option value="not_started" {{ old('status', $task->status) == 'not_started' ? 'selected' : '' }}>未着手</option>
                                    <option value="in_progress" {{ old('status', $task->status) == 'in_progress' ? 'selected' : '' }}>進行中</option>
                                    <option value="completed" {{ old('status', $task->status) == 'completed' ? 'selected' : '' }}>
                                        完了</option>
                                    <option value="on_hold" {{ old('status', $task->status) == 'on_hold' ? 'selected' : '' }}>保留中
                                    </option>
                                    <option value="cancelled" {{ old('status', $task->status) == 'cancelled' ? 'selected' : '' }}>
                                        キャンセル</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ url()->previous(route('projects.show', $project)) }}" class="btn btn-secondary">キャンセル</a>
                        <div>
                            <button type="submit" class="btn btn-primary" id="update-task-button">更新</button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                data-bs-target="#deleteTaskModal">
                                削除
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTaskModalLabel">工程削除の確認</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>工程「{{ $task->name }}」を削除しますか？</p>
                    <p class="text-danger">この操作は取り消せません。この工程に関連するすべての子工程も削除されます。</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST">
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        Dropzone.autoDiscover = false;

        document.addEventListener('DOMContentLoaded', function () {
            const startDateInput = document.getElementById('start_date');
            const durationInput = document.getElementById('duration');
            const endDateInput = document.getElementById('end_date');
            const taskFields = document.getElementById('task-fields');
            const statusField = document.getElementById('status-field');
            const characterIdWrapper = document.getElementById('character_id_wrapper');
            const fileManagementSection = document.getElementById('file-management-section');
            const currentTaskType = '{{ $taskType }}'; // PHPから渡される現在のタスク種別
            let myDropzone;

            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function updateEndDateForTask() {
                if (!startDateInput || !durationInput || !endDateInput) { return; }
                if (!startDateInput.value || !durationInput.value) { endDateInput.value = ''; return; }

                const startDate = new Date(startDateInput.value);
                if (isNaN(startDate.getTime())) { endDateInput.value = ''; return; }

                const duration = parseInt(durationInput.value);
                if (duration <= 0 && currentTaskType === 'task') { endDateInput.value = ''; return; }


                if (duration > 0) {
                    const endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + duration - 1);
                    endDateInput.value = formatDate(endDate);
                } else {
                    endDateInput.value = '';
                }
            }

            function updateDurationForTask() {
                if (!startDateInput || !endDateInput || !durationInput) { return; }
                if (!startDateInput.value || !endDateInput.value) { durationInput.value = ''; return; }

                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) { durationInput.value = ''; return; }

                if (endDate >= startDate) {
                    const diffTime = Math.abs(endDate - startDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    if(durationInput) durationInput.value = diffDays;
                } else if (endDate < startDate) {
                    if(durationInput) durationInput.value = 1;
                } else {
                    if(durationInput) durationInput.value = '';
                }
            }

            function setTaskTypeSpecificFields() {
                const requiredDateAndDurationFields = [startDateInput, durationInput, endDateInput];
                const statusSelect = document.getElementById('status');

                if(characterIdWrapper) characterIdWrapper.style.display = 'block';

                if (currentTaskType === 'folder') {
                    if(taskFields) taskFields.style.display = 'none';
                    if(statusField) statusField.style.display = 'none';
                    requiredDateAndDurationFields.forEach(field => {
                        if(field) {
                            field.removeAttribute('required');
                        }
                    });
                    if(statusSelect) statusSelect.removeAttribute('required');
                    if(fileManagementSection) fileManagementSection.style.display = 'block';
                } else if (currentTaskType === 'milestone') {
                    if(taskFields) taskFields.style.display = 'block';
                    if(statusField) statusField.style.display = 'block';

                    if(startDateInput) {
                        startDateInput.removeAttribute('disabled');
                        startDateInput.setAttribute('required', 'required');
                    } else {
                        return;
                    }
                    if(durationInput) {
                        durationInput.value = 1;
                        durationInput.setAttribute('disabled', true);
                        durationInput.removeAttribute('required');
                    }
                    if(endDateInput) {
                        endDateInput.setAttribute('disabled', true);
                        endDateInput.removeAttribute('required');
                        if(startDateInput && startDateInput.value) {
                            endDateInput.value = startDateInput.value;
                        }
                    }
                    if(statusSelect) statusSelect.setAttribute('required', 'required');
                    if(fileManagementSection) fileManagementSection.style.display = 'none';
                } else { // 'task' (通常工程)
                    if(taskFields) taskFields.style.display = 'block';
                    if(statusField) statusField.style.display = 'block';
                    requiredDateAndDurationFields.forEach(field => {
                        if(field) {
                            field.removeAttribute('disabled');
                            field.removeAttribute('required'); // 通常タスクでは任意入力
                        }
                    });
                    if(durationInput) durationInput.removeAttribute('disabled');
                    if(endDateInput) endDateInput.removeAttribute('disabled');
                    // ステータスは通常タスクでも必須とする（仕様による）
                    if(statusSelect) statusSelect.setAttribute('required', 'required');
                    if(fileManagementSection) fileManagementSection.style.display = 'none';
                }
            }

            if(startDateInput) {
                startDateInput.addEventListener('change', function() {
                    if (currentTaskType === 'milestone') {
                        if(endDateInput) endDateInput.value = this.value;
                    } else if (currentTaskType === 'task') {
                        if(durationInput && endDateInput) updateEndDateForTask();
                    }
                });
            }
            if(durationInput) durationInput.addEventListener('input', updateEndDateForTask);
            if(endDateInput) endDateInput.addEventListener('change', updateDurationForTask);

            setTaskTypeSpecificFields();

            // 初期値に基づいて日付を計算（編集画面用）
            if (currentTaskType === 'task' && startDateInput && startDateInput.value && durationInput && durationInput.value && endDateInput) {
                 if (!endDateInput.value && durationInput.value) {
                    updateEndDateForTask();
                 } else if (!durationInput.value && endDateInput.value) {
                    updateDurationForTask();
                 }
            } else if (currentTaskType === 'milestone' && startDateInput && startDateInput.value && endDateInput) {
                endDateInput.value = startDateInput.value;
                if(durationInput) durationInput.value = 1;
            }


            const dropzoneElement = document.getElementById('file-upload-dropzone-edit');
            if (currentTaskType === 'folder' && dropzoneElement) {
                myDropzone = new Dropzone(dropzoneElement, {
                    url: '{{ route('projects.tasks.files.upload', [$project, $task]) }}',
                    method: 'post',
                    paramName: "file",
                    maxFilesize: 100,
                    acceptedFiles: ".jpeg,.jpg,.png,.gif,.svg,.bmp,.tiff,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.tar,.gz,.txt,.md,.csv,.json,.xml,.html,.css,.js,.php,.py,.java,.c,.cpp,.cs,.rb,.go,.sql,.ai,.psd,.fig,.sketch,video/*,audio/*,application/octet-stream",
                    addRemoveLinks: true,
                    dictDefaultMessage: "",
                    dictRemoveFile: "×",
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    autoProcessQueue: false,
                    init: function() {
                        const dropzoneInstance = this;
                        const customButton = dropzoneElement.querySelector('.dz-button-bootstrap');
                        if (customButton) {
                            customButton.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                dropzoneInstance.hiddenFileInput.click();
                            });
                        }
                        this.on("success", function(file, response) {
                            updateFileListEdit();
                            dropzoneInstance.removeFile(file);
                        });
                        this.on("error", function(file, message) {
                            let errorMessage = "アップロードに失敗しました。";
                            if(typeof message === "string") {
                                errorMessage = message;
                            } else if (message.errors && message.errors.file) {
                                errorMessage = message.errors.file[0];
                            } else if (message.message) {
                                errorMessage = message.message;
                            }
                            alert("エラー: " + errorMessage);
                            dropzoneInstance.removeFile(file);
                        });
                        this.on("queuecomplete", function() {
                            if (this.getRejectedFiles().length === 0 && this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                                document.getElementById('task-edit-form').submit();
                            } else if (this.getRejectedFiles().length > 0) {
                                alert('ファイルアップロードに失敗したファイルがあるため、工程の更新は行われませんでした。');
                                this.removeAllFiles(true);
                            }
                        });
                    }
                });
            }

            const updateTaskButton = document.getElementById('update-task-button');
            if (updateTaskButton) {
                updateTaskButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (currentTaskType === 'folder' && myDropzone && myDropzone.getQueuedFiles().length > 0) {
                        myDropzone.processQueue();
                    } else {
                        document.getElementById('task-edit-form').submit();
                    }
                });
            }

            function updateFileListEdit() {
                const fileListElement = document.getElementById('file-list-edit');
                if (!fileListElement) return;
                axios.get('{{ route('projects.tasks.files.index', [$project, $task]) }}')
                    .then(function(response) {
                        fileListElement.innerHTML = response.data;
                    });
            }

            const fileListElementForEvent = document.getElementById('file-list-edit');
            if(fileListElementForEvent) {
                fileListElementForEvent.addEventListener('click', function(e) {
                    if (e.target.classList.contains('delete-file-btn') || e.target.closest('.delete-file-btn')) {
                        e.preventDefault();
                        const button = e.target.closest('.delete-file-btn');
                        const fileId = button.dataset.fileId;
                        const url = button.dataset.url;
                        if (confirm('本当にこのファイルを削除しますか？')) {
                            axios.delete(url, {
                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                            })
                            .then(function(response) {
                                if (response.data.success) {
                                    const fileItem = document.getElementById('file-item-' + fileId);
                                    if(fileItem) fileItem.remove();
                                } else {
                                    alert('ファイルの削除に失敗しました。\n' + (response.data.message || ''));
                                }
                            })
                            .catch(function(error) {
                                alert('ファイルの削除中にエラーが発生しました。');
                                console.error(error);
                            });
                        }
                    }
                });
            }
        });
    </script>
@endsection