@extends('layouts.app')

@section('title', '工程編集 - ' . $task->name)

@push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <style>
        .dropzone-custom-style {
            @apply border-2 border-dashed border-blue-500 rounded-md p-4 flex flex-wrap gap-3 min-h-[150px] bg-gray-50 dark:bg-gray-700/50;
        }
        .dropzone-custom-style .dz-message {
            @apply text-gray-600 dark:text-gray-400 font-medium w-full text-center self-center;
        }
        .dropzone-custom-style .dz-message p {
            @apply mb-2;
        }
        .dropzone-custom-style .dz-button-bootstrap {
            @apply inline-flex items-center px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500;
        }
        .dropzone-custom-style .dz-preview {
            @apply w-32 h-auto m-1 bg-transparent border border-gray-300 dark:border-gray-600 flex flex-col items-center relative rounded-lg overflow-hidden;
        }
        .dropzone-custom-style .dz-image {
            @apply w-20 h-20 flex border border-gray-300 dark:border-gray-600 items-center justify-center overflow-hidden relative z-10;
        }
        .dropzone-custom-style .dz-image img {
            @apply max-w-full max-h-full object-contain bg-transparent;
        }
        .dropzone-custom-style .dz-details {
            @apply block text-center w-full relative p-1;
        }
        .dropzone-custom-style .dz-filename {
            @apply block text-xs text-gray-700 dark:text-gray-200 break-words leading-tight mt-1;
        }
        .dropzone-custom-style .dz-filename span {
            @apply bg-transparent;
        }
        .dropzone-custom-style .dz-size {
            @apply text-[0.65em] text-gray-500 dark:text-gray-400 mt-0.5 bg-transparent;
        }
        .dropzone-custom-style .dz-progress,
        .dropzone-custom-style .dz-error-message,
        .dropzone-custom-style .dz-success-mark,
        .dropzone-custom-style .dz-error-mark {
            @apply hidden;
        }
        .dropzone-custom-style .dz-remove {
            @apply absolute top-1 right-1 bg-red-600/80 hover:bg-red-700/90 text-white rounded-full w-[18px] h-[18px] text-xs leading-[18px] text-center font-bold no-underline cursor-pointer opacity-100 z-30;
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" id="task-form-page" data-project-id="{{ $project->id }}"
        data-task-id="{{ $task->id }}">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                工程編集 - <span class="font-normal text-xl truncate"
                    title="{{ $task->name }}">{{ Str::limit($task->name, 30) }}</span>
                <span class="text-lg text-gray-500 dark:text-gray-400 ml-2"> (案件:
                    {{ Str::limit($project->title, 20) }})</span>
            </h1>
            <div>
                <a href="{{ route('projects.show', $project) }}"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 mr-2">
                    <i class="fas fa-arrow-left mr-2"></i>案件詳細へ戻る
                </a>
                @can('delete', $task)
                    <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST" class="inline-block"
                        onsubmit="return confirm('本当に削除しますか？この工程に関連するすべての子工程も削除されます。');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">
                            <i class="fas fa-trash mr-2"></i> 削除
                        </x-danger-button>
                    </form>
                @endcan
            </div>
        </div>

        @if ($errors->any())
            <div
                class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $taskType = 'task';
            if ($task->is_milestone) {
                $taskType = 'milestone';
            } elseif ($task->is_folder) {
                $taskType = 'folder';
            } elseif (!$task->start_date && !$task->end_date && !$task->is_milestone && !$task->is_folder) {
                $taskType = 'todo_task';
            }

            $displayDurationValue = old('duration_value');
            $displayDurationUnit = old('duration_unit', 'days');

            if (!$errors->any() && $task->duration !== null) {
                $totalMinutes = $task->duration;
                if ($totalMinutes == 0 && $taskType !== 'milestone') {
                    $displayDurationValue = 0;
                    $displayDurationUnit = 'minutes';
                } elseif ($taskType === 'milestone') {
                    $displayDurationValue = 0;
                    $displayDurationUnit = 'minutes';
                } elseif ($totalMinutes > 0) {
                    if ($totalMinutes % (24 * 60) === 0 && ($totalMinutes / (24 * 60)) >=1 ) {
                        $displayDurationValue = $totalMinutes / (24 * 60);
                        $displayDurationUnit = 'days';
                    } elseif ($totalMinutes % 60 === 0 && ($totalMinutes / 60) >= 1) {
                        $displayDurationValue = $totalMinutes / 60;
                        $displayDurationUnit = 'hours';
                    } else {
                        $displayDurationValue = $totalMinutes;
                        $displayDurationUnit = 'minutes';
                    }
                }
            } elseif (is_null($displayDurationValue) && $taskType === 'task') {
                 $displayDurationValue = 1;
                 $displayDurationUnit = 'days';
            } elseif (is_null($displayDurationValue)) {
                $displayDurationValue = 0;
                $displayDurationUnit = 'minutes';
            }
            $isDurationDisabled = $taskType === 'milestone' || $taskType === 'todo_task' || $taskType === 'folder';
        @endphp

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('projects.tasks.update', [$project, $task]) }}" method="POST" id="task-edit-form">
                    @csrf
                    @method('PUT')
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">工程種別</label>
                            <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-md text-sm text-gray-600 dark:text-gray-300">
                                @switch($taskType)
                                    @case('milestone')
                                        <i class="fas fa-flag mr-1 text-red-500"></i>重要納期
                                        @break
                                    @case('folder')
                                        <i class="fas fa-folder mr-1 text-blue-500"></i>フォルダ
                                        @break
                                    @case('todo_task')
                                        <i class="fas fa-list-check mr-1 text-purple-500"></i>タスク(期限なし)
                                        @break
                                    @default
                                        <i class="fas fa-tasks mr-1 text-green-500"></i>工程
                                @endswitch
                            </div>
                        </div>

                        <div>
                            <x-input-label for="name_individual" value="工程名" :required="true" />
                            <x-text-input type="text" id="name_individual" name="name" class="mt-1 block w-full"
                                :value="old('name', $task->name)" required :hasError="$errors->has('name')" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div id="parent_id_wrapper_individual_edit" {{ $taskType === 'folder' ? 'style="display:none;"' : '' }}>
                            <x-select-input label="親工程" name="parent_id" id="parent_id_individual_edit"
                                :options="$parentTaskOptions"
                                :selected="old('parent_id', $task->parent_id)"
                                emptyOptionText="なし"
                                :hasError="$errors->has('parent_id')"
                                :disabled="$taskType === 'folder'" />
                           <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>

                        <div id="character_id_wrapper_individual_edit" {{ $taskType === 'folder' ? 'style="display:none;"' : '' }}>
                            <x-select-input label="所属先キャラクター" name="character_id" id="character_id_individual_edit"
                                :options="$project->characters->pluck('name', 'id')"
                                :selected="old('character_id', $task->character_id)"
                                emptyOptionText="キャラクターを選択してください"
                                :hasError="$errors->has('character_id')"
                                {{-- :required="$taskType !== 'folder' && !old('parent_id', $task->parent_id) && !old('apply_edit_to_all_characters_same_name')" --}}
                                :disabled="old('apply_edit_to_all_characters_same_name', false) || $taskType === 'folder' || old('parent_id', $task->parent_id) !== null" />
                            <x-input-error :messages="$errors->get('character_id')" class="mt-2" />

                            @if ($taskType !== 'folder')
                            <div class="mt-2">
                                <x-input-label for="apply_edit_to_all_characters_same_name" class="inline-flex items-center">
                                    <input type="checkbox" id="apply_edit_to_all_characters_same_name" name="apply_edit_to_all_characters_same_name" value="1" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" {{ old('apply_edit_to_all_characters_same_name') ? 'checked' : '' }} {{ old('parent_id', $task->parent_id) !== null ? 'disabled' : '' }}>
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">この案件のすべてのキャラクターの同名工程に同じ内容を反映する</span>
                                </x-input-label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">注意: このオプションを有効にすると、現在編集中の工程と同じ名前を持つ、この案件内の他のキャラクターに紐づいた工程の内容が上書きされます。（親工程が選択されている場合は無効です）</p>
                            </div>
                            @endif
                        </div>


                        @if($taskType === 'folder')
                            @can('fileView', $task)
                                <div id="file-management-section" class="mb-3">
                                    <hr class="my-4 dark:border-gray-600">
                                    <h3 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2"><i
                                            class="fas fa-file-alt mr-2"></i>ファイル管理</h3>
                                    @can('fileUpload', $task)
                                        <div class="dropzone dropzone-custom-style mb-3" id="file-upload-dropzone-edit">
                                            <div class="dz-message text-center" data-dz-message>
                                                <p class="mb-2">ここにファイルをドラッグ＆ドロップ</p>
                                                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">または</p>
                                                <button type="button" class="dz-button-bootstrap">
                                                    <i class="fas fa-folder-open mr-1"></i>ファイルを選択
                                                </button>
                                            </div>
                                        </div>
                                    @endcan
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">アップロード済みファイル</h4>
                                    <ul class="space-y-2 text-sm" id="file-list-edit">
                                        @include('tasks.partials.file-list-tailwind', ['files' => $files, 'project' => $project, 'task' => $task])
                                    </ul>
                                    <hr class="mt-4 dark:border-gray-600">
                                </div>
                            @endcan
                        @endif

                        <div>
                            <x-input-label for="description_individual" value="メモ" />
                            <x-textarea-input id="description_individual" name="description" class="mt-1 block w-full"
                                rows="3" :hasError="$errors->has('description')">{{ old('description', $task->description) }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div id="task-fields-individual" class="space-y-4" {{ $taskType === 'folder' ? 'style="display:none;"' : '' }}>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4">
                                <div>
                                    <x-input-label for="start_date_individual" value="開始日時" required/>
                                    <x-text-input type="datetime-local" id="start_date_individual" name="start_date"
                                        class="mt-1 block w-full"
                                        :value="old('start_date', optional($task->start_date)->format('Y-m-d\TH:i'))"
                                        :disabled="$isDurationDisabled"
                                        :hasError="$errors->has('start_date')" />
                                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="duration_value" value="工数" required/>
                                    <div class="flex items-center mt-1 space-x-2">
                                        <x-text-input type="number" id="duration_value" name="duration_value" class="block w-1/2"
                                            :value="$displayDurationValue" min="0" step="any"
                                            :disabled="$isDurationDisabled"
                                            :hasError="$errors->has('duration_value')" />
                                        <select name="duration_unit" id="duration_unit"
                                                class="block w-1/2 mt-0 form-select rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 {{ $isDurationDisabled ? 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed' : '' }} {{ $errors->has('duration_unit') ? 'border-red-500' : '' }}"
                                                {{ $isDurationDisabled ? 'disabled' : '' }}>
                                            <option value="days" @if($displayDurationUnit == 'days') selected @endif>日</option>
                                            <option value="hours" @if($displayDurationUnit == 'hours') selected @endif>時間</option>
                                            <option value="minutes" @if($displayDurationUnit == 'minutes') selected @endif>分</option>
                                        </select>
                                    </div>
                                    <x-input-error :messages="$errors->get('duration_value')" class="mt-2" />
                                    <x-input-error :messages="$errors->get('duration_unit')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="end_date_individual" value="終了日時" required/>
                                    <x-text-input type="datetime-local" id="end_date_individual" name="end_date"
                                        class="mt-1 block w-full"
                                        :value="old('end_date', optional($task->end_date)->format('Y-m-d\TH:i'))"
                                        :disabled="$isDurationDisabled"
                                        :hasError="$errors->has('end_date')" />
                                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                                </div>
                            </div>
                            <div
                                class="p-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                工数の1日は8時間として計算しています。
                            </div>
                        </div>

                        {{-- ▼▼▼【変更】担当者選択UI ▼▼▼ --}}
                        <div id="assignees_wrapper_individual" {{ $taskType === 'folder' ? 'style="display:none;"' : '' }}>
                            <x-input-label for="assignees_select" value="担当者" />
                            <select name="assignees[]" id="assignees_select" multiple class="mt-1 block w-full">
                                @foreach($assigneeOptions as $id => $name)
                                    <option value="{{ $id }}" {{ in_array($id, $selectedAssignees) ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('assignees')" class="mt-2" />
                            <x-input-error :messages="$errors->get('assignees.*')" class="mt-2" />
                        </div>
                        {{-- ▲▲▲【変更】ここまで ▲▲▲ --}}


                        <div id="status-field-individual" {{ $taskType === 'folder' ? 'style="display:none;"' : '' }}>
                            <x-select-input label="ステータス" name="status" id="status_individual"
                                :options="['not_started' => '未着手', 'in_progress' => '進行中', 'completed' => '完了', 'on_hold' => '一時停止中', 'cancelled' => 'キャンセル']"
                                :selected="old('status', $task->status)"
                                :hasError="$errors->has('status')" />
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <x-secondary-button as="a" href="{{ route('projects.show', $project) }}">
                            キャンセル
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            <i class="fas fa-save mr-2"></i> 更新
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const taskType = "{{ $taskType }}";
    const taskFieldsIndividual = document.getElementById('task-fields-individual');
    const characterIdWrapperEdit = document.getElementById('character_id_wrapper_individual_edit');
    const characterIdSelectEdit = document.getElementById('character_id_individual_edit');
    const applyEditToAllCharsCheckbox = document.getElementById('apply_edit_to_all_characters_same_name');

    const statusField = document.getElementById('status-field-individual');
    const startDateInput = document.getElementById('start_date_individual');
    const durationValueInput = document.getElementById('duration_value');
    const durationUnitSelect = document.getElementById('duration_unit');
    const endDateInput = document.getElementById('end_date_individual');
    const parentIdSelectEdit = document.getElementById('parent_id_individual_edit');

    const assigneeWrapper = document.getElementById('assignees_wrapper_individual');
    const parentIdWrapperEdit = document.getElementById('parent_id_wrapper_individual_edit');

    let tomSelectInstance = null;
    if (document.getElementById('assignees_select')) {
        tomSelectInstance = new TomSelect('#assignees_select',{
            plugins: ['remove_button'],
            create: false,
            placeholder: '担当者を検索・選択...'
        });
    }

    // ▼▼▼【ここから】工程編集画面用のステータス更新機能（警告処理付き）▼▼▼
    const statusSelect = document.getElementById("status_individual");
    const taskFormPage = document.getElementById("task-form-page");

    if (statusSelect && taskFormPage) {
        const taskId = taskFormPage.dataset.taskId;
        const projectId = taskFormPage.dataset.projectId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let originalValue = statusSelect.value;
        let isUpdating = false;

        statusSelect.addEventListener("change", async function () {
            if (isUpdating) return;
            isUpdating = true;
            const newStatus = this.value;

            try {
                await sendUpdateRequest(newStatus, false);
                originalValue = newStatus; // 成功時に元の値を更新
            } catch (error) {
                if (error.requires_confirmation) {
                    const confirmed = await showConfirmationDialogs(error.warnings);
                    if (confirmed) {
                        try {
                            await sendUpdateRequest(newStatus, true); // 強制更新
                            originalValue = newStatus; // 成功時に元の値を更新
                        } catch (finalError) {
                            alert(finalError.message || "ステータスの更新に失敗しました。");
                            this.value = originalValue; // 最終的に失敗したら元に戻す
                        }
                    } else {
                        this.value = originalValue; // キャンセルされたら元に戻す
                    }
                } else {
                    alert(error.message || "ステータスの更新中にエラーが発生しました。");
                    this.value = originalValue; // エラー時も元に戻す
                }
            } finally {
                isUpdating = false;
            }
        });

        async function sendUpdateRequest(status, force = false) {
            const response = await fetch(`/projects/${projectId}/tasks/${taskId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: status, force_update: force })
            });

            const data = await response.json();
            if (!response.ok) {
                const error = new Error(data.message || 'サーバーエラーが発生しました。');
                if (data.requires_confirmation) {
                    error.requires_confirmation = true;
                    error.warnings = data.warnings;
                }
                throw error;
            }
            handleSuccess(data);
        }

        async function showConfirmationDialogs(warnings) {
            for (const warning of warnings) {
                const confirmed = confirm(warning.message + "\n\nこのまま続行しますか？");
                if (!confirmed) return false;
            }
            return true;
        }

        function handleSuccess(data) {
            if (data.work_log_message) {
                showWorkLogNotification(data.work_log_message);
            }
            if (data.updated_assignees && tomSelectInstance) {
                const newAssigneeIds = data.updated_assignees.map(assignee => assignee.id);
                tomSelectInstance.setValue(newAssigneeIds, true); // 第2引数 true でイベントを発火させずに更新
            }

            updateTimerDisplayInEdit(data);
        }

        function updateTimerDisplayInEdit(responseData) {
            const timerContainers = document.querySelectorAll('.timer-controls, .timer-display-only');
            timerContainers.forEach(container => {
                if (responseData.task_status) {
                    container.dataset.taskStatus = responseData.task_status;
                }
                if (typeof responseData.is_paused !== 'undefined') {
                    container.dataset.isPaused = responseData.is_paused ? 'true' : 'false';
                }
            });

            if (responseData.running_logs) {
                const runningLogsElement = document.getElementById("running-work-logs-data");
                if (runningLogsElement) {
                    runningLogsElement.textContent = JSON.stringify(responseData.running_logs);
                }
                window.dispatchEvent(
                    new CustomEvent("work-log-status-changed", {
                        detail: { hasActiveWorkLog: responseData.running_logs.some(log => log.status === 'active') },
                    })
                );
            }

            if (window.initializeWorkTimers) {
                window.initializeWorkTimers();
            }
        }

        function showWorkLogNotification(message) {
            const notification = document.createElement("div");
            notification.className = "fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-md shadow-lg z-50 transition-opacity duration-300";
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = "0";
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    }
    // ▲▲▲【ここまで】▲▲▲


    function handleParentIdChangeForEdit() {
        const parentIsSelected = parentIdSelectEdit.value !== '';

        if (characterIdSelectEdit) {
            characterIdSelectEdit.disabled = parentIsSelected || taskType === 'folder' || (applyEditToAllCharsCheckbox && applyEditToAllCharsCheckbox.checked);
        }
        if (applyEditToAllCharsCheckbox) {
            applyEditToAllCharsCheckbox.disabled = parentIsSelected || taskType === 'folder';
            if (parentIsSelected) {
                applyEditToAllCharsCheckbox.checked = false;
            }
        }
    }

    function setFieldsBasedOnTaskType(currentTaskType) {
        // (この関数は元のままで変更なし)
        const isFolder = currentTaskType === 'folder';
        const isMilestone = currentTaskType === 'milestone';
        const isTodoTask = currentTaskType === 'todo_task';
        const isDateTimeDisabled = isFolder || isMilestone || isTodoTask;

        if (taskFieldsIndividual) taskFieldsIndividual.style.display = isFolder ? 'none' : 'block';
        if (statusField) statusField.style.display = isFolder ? 'none' : 'block';
        if (assigneeWrapper) assigneeWrapper.style.display = isFolder ? 'none' : 'block';

        if (parentIdWrapperEdit) parentIdWrapperEdit.style.display = isFolder ? 'none' : 'block';
        if (characterIdWrapperEdit) characterIdWrapperEdit.style.display = isFolder ? 'none' : 'block';

        if (startDateInput) startDateInput.disabled = isDateTimeDisabled;
        if (durationValueInput) durationValueInput.disabled = isDateTimeDisabled;
        if (durationUnitSelect) durationUnitSelect.disabled = isDateTimeDisabled;
        if (endDateInput) endDateInput.disabled = isDateTimeDisabled;

        if (parentIdSelectEdit) parentIdSelectEdit.disabled = isFolder;

        if (isFolder) {
            if (characterIdSelectEdit) characterIdSelectEdit.disabled = true;
            if (applyEditToAllCharsCheckbox) {
                applyEditToAllCharsCheckbox.checked = false;
                applyEditToAllCharsCheckbox.disabled = true;
            }
        } else {
             handleParentIdChangeForEdit();
        }
         if (isMilestone) {
            if (durationValueInput) durationValueInput.value = 0;
            if (durationUnitSelect) durationUnitSelect.value = 'minutes';
        } else if (isTodoTask || isFolder) {
            if (startDateInput && !startDateInput.value) startDateInput.value = '';
            if (durationValueInput && !durationValueInput.value) durationValueInput.value = '';
            if (endDateInput && !endDateInput.value) endDateInput.value = '';
        }
    }

    setFieldsBasedOnTaskType(taskType);

    if (parentIdSelectEdit) {
        parentIdSelectEdit.addEventListener('change', handleParentIdChangeForEdit);
    }

    if (applyEditToAllCharsCheckbox && characterIdSelectEdit) {
        applyEditToAllCharsCheckbox.addEventListener('change', function() {
            if (!parentIdSelectEdit || parentIdSelectEdit.value === '') {
                 characterIdSelectEdit.disabled = this.checked || taskType === 'folder';
            }
        });
    }
    handleParentIdChangeForEdit();

    // (元のDropzoneなどのロジックがあれば、この下に続きます)

});
</script>
@endpush
