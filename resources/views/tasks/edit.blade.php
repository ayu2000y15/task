@extends('layouts.app')

@section('title', '工程編集 - ' . $task->name)

@push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        /* 編集不可の項目をグレーアウトするためのスタイル */
        .readonly-style {
            background-color: #f3f4f6 !important; /* bg-gray-100 */
            pointer-events: none;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .dark .readonly-style {
            background-color: #374151 !important; /* dark:bg-gray-700 */
        }
        /* Tom-selectの非活性スタイル */
        .ts-control.readonly-style {
            background-color: #f3f4f6 !important;
            pointer-events: none;
            cursor: not-allowed;
        }
        .dark .ts-control.readonly-style {
             background-color: #374151 !important;
        }
        .ts-control.readonly-style .ts-input {
            cursor: not-allowed;
        }

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

        .loader {
            border-top-color: #3498db;
            -webkit-animation: spinner 1.5s linear infinite;
            animation: spinner 1.5s linear infinite;
        }
        @-webkit-keyframes spinner {
            0% { -webkit-transform: rotate(0deg); }
            100% { -webkit-transform: rotate(360deg); }
        }
        @keyframes spinner {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" id="task-form-page" data-project-id="{{ $project->id }}"
        data-task-id="{{ $task->id }}" data-task-type="{{ $taskType }}" data-task-status="{{ $task->status }}">
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
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $displayDurationValue = old('duration_value');
            $displayDurationUnit = old('duration_unit', 'days');
            if (!$errors->any() && $task->duration !== null) {
                $totalMinutes = $task->duration;
                 if ($taskType === 'milestone') {
                    $displayDurationValue = 0;
                    $displayDurationUnit = 'minutes';
                } elseif ($totalMinutes >= 0) {
                     if ($totalMinutes % (8 * 60) === 0 && $totalMinutes / (8 * 60) >= 1) {
                        $displayDurationValue = $totalMinutes / (8 * 60);
                        $displayDurationUnit = 'days';
                    } elseif ($totalMinutes % 60 === 0 && $totalMinutes / 60 >= 1) {
                        $displayDurationValue = $totalMinutes / 60;
                        $displayDurationUnit = 'hours';
                    } else {
                        $displayDurationValue = $totalMinutes;
                        $displayDurationUnit = 'minutes';
                    }
                }
            }
            // 権限チェックを修正。フォルダ・マイルストーンなら常に許可
            $canUpdateCritical = Gate::allows('updateCriticalFields', $task) || $task->is_folder || $task->is_milestone;
        @endphp


        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden relative">
            <div id="upload-loader-overlay" class="hidden absolute inset-0 bg-white/70 dark:bg-gray-800/80 z-40 flex items-center justify-center rounded-lg">
                <div class="flex flex-col items-center text-center">
                    <div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-12 w-12 mb-4"></div>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">アップロード中...</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">完了までしばらくお待ちください</p>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <form action="{{ route('projects.tasks.update', [$project, $task]) }}" method="POST" id="task-edit-form">
                    @csrf
                    @method('PUT')
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">工程種別</label>
                            <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-md text-sm text-gray-600 dark:text-gray-300">
                                @switch($taskType)
                                    @case('milestone') <i class="fas fa-flag mr-1 text-red-500"></i>予定 @break
                                    @case('folder') <i class="fas fa-folder mr-1 text-blue-500"></i>フォルダ @break
                                    @case('todo_task') <i class="fas fa-list-check mr-1 text-purple-500"></i>タスク(期限なし) @break
                                    @default <i class="fas fa-tasks mr-1 text-green-500"></i>工程
                                @endswitch
                            </div>
                        </div>

                        <div>
                            <x-input-label for="name_individual" value="工程名" :required="true" />
                            <x-text-input type="text" id="name_individual" name="name"
                                class="mt-1 block w-full {{ !$canUpdateCritical ? 'readonly-style' : '' }}"
                                :value="old('name', $task->name)" required :hasError="$errors->has('name')" :readonly="!$canUpdateCritical" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        @if($taskType !== 'folder')
                            <div id="parent_id_wrapper_individual_edit">
                                <x-select-input label="親工程" name="parent_id" id="parent_id_individual_edit"
                                    :options="$parentTaskOptions" :selected="old('parent_id', $task->parent_id)"
                                    emptyOptionText="なし" :hasError="$errors->has('parent_id')"
                                    class="{{ !$canUpdateCritical ? 'readonly-style' : '' }}" />
                               <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                            </div>

                            @if($taskType !== 'milestone')
                                <div id="character_id_wrapper_individual_edit">
                                    <x-select-input label="所属先キャラクター" name="character_id" id="character_id_individual_edit"
                                        :options="$project->characters->pluck('name', 'id')" :selected="old('character_id', $task->character_id)"
                                        emptyOptionText="キャラクターを選択してください" :hasError="$errors->has('character_id')"
                                        class="{{ !$canUpdateCritical ? 'readonly-style' : '' }}" />
                                    <x-input-error :messages="$errors->get('character_id')" class="mt-2" />
                                    <div class="mt-2">
                                        <x-input-label for="apply_edit_to_all_characters_same_name" class="inline-flex items-center">
                                            <input type="checkbox" id="apply_edit_to_all_characters_same_name" name="apply_edit_to_all_characters_same_name" value="1" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" {{ old('apply_edit_to_all_characters_same_name') ? 'checked' : '' }} @if(!$canUpdateCritical) disabled @endif>
                                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">この案件のすべてのキャラクターの同名工程に同じ内容を反映する</span>
                                        </x-input-label>
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($taskType === 'folder')
                            @can('fileView', $task)
                                <div id="file-management-section">
                                    <hr class="my-4 dark:border-gray-600">
                                    <h3 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2"><i class="fas fa-file-alt mr-2"></i>ファイル管理</h3>
                                    @can('fileUpload', $task)
                                        <div class="dropzone dropzone-custom-style mb-2" id="file-upload-dropzone-edit">
                                             <div class="dz-message text-center" data-dz-message>
                                                <p class="mb-2">ここにファイルをドラッグ＆ドロップ</p>
                                                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">または</p>
                                                <button type="button" class="dz-button-bootstrap"><i class="fas fa-folder-open mr-1"></i>ファイルを選択</button>
                                                <p class="mt-2 text-xs">最大ファイルサイズ: 100MB</p>
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

                        @if($taskType !== 'folder' && $taskType !== 'todo_task')
                            <div id="task-fields-individual" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                    <div>
                                        <x-input-label for="start_date_individual" value="開始日時" required/>
                                        <x-text-input type="datetime-local" id="start_date_individual" name="start_date"
                                            class="mt-1 block w-full {{ !$canUpdateCritical ? 'readonly-style' : '' }}"
                                            :value="old('start_date', optional($task->start_date)->format('Y-m-d\TH:i'))"
                                            :hasError="$errors->has('start_date')" :readonly="!$canUpdateCritical" />
                                        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="end_date_individual" value="終了日時" required/>
                                        <x-text-input type="datetime-local" id="end_date_individual" name="end_date"
                                            class="mt-1 block w-full {{ !$canUpdateCritical ? 'readonly-style' : '' }}"
                                            :value="old('end_date', optional($task->end_date)->format('Y-m-d\TH:i'))"
                                            :hasError="$errors->has('end_date')" :readonly="!$canUpdateCritical" />
                                        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                                    </div>

                                    @if($taskType !== 'milestone')
                                    <div id="duration_wrapper_individual_edit">
                                        <x-input-label for="duration_value" value="工数" required/>
                                        <div class="flex items-center mt-1 space-x-2">
                                            <x-text-input type="number" id="duration_value" name="duration_value"
                                                class="block w-1/2 {{ !$canUpdateCritical ? 'readonly-style' : '' }}"
                                                :value="$displayDurationValue" min="0" step="any" :hasError="$errors->has('duration_value')" :readonly="!$canUpdateCritical" />
                                            <select name="duration_unit" id="duration_unit"
                                                    class="block w-1/2 mt-0 form-select rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 {{ $errors->has('duration_unit') ? 'border-red-500' : '' }} {{ !$canUpdateCritical ? 'readonly-style' : '' }}">
                                                <option value="days" @if($displayDurationUnit == 'days') selected @endif>日</option>
                                                <option value="hours" @if($displayDurationUnit == 'hours') selected @endif>時間</option>
                                                <option value="minutes" @if($displayDurationUnit == 'minutes') selected @endif>分</option>
                                            </select>
                                        </div>
                                        <x-input-error :messages="$errors->get('duration_value')" class="mt-2" />
                                        <x-input-error :messages="$errors->get('duration_unit')" class="mt-2" />
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($taskType !== 'folder')
                            <div id="assignees_wrapper_individual">
                                <x-input-label for="assignees_select" value="担当者" />
                                <div id="assignees_select_wrapper" class="{{ !$canUpdateCritical ? 'readonly-style' : '' }}">
                                    <select name="assignees[]" id="assignees_select" multiple>
                                        @foreach($assigneeOptions as $id => $name)
                                            <option value="{{ $id }}" {{ in_array($id, $selectedAssignees) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-input-error :messages="$errors->get('assignees')" class="mt-2" />
                                <x-input-error :messages="$errors->get('assignees.*')" class="mt-2" />
                            </div>

                            <div id="status-field-individual">
                                 <x-input-label for="status_individual" value="ステータス" />
                                 @php
                                     $statusOptionsForEdit = \Illuminate\Support\Arr::except(App\Models\Task::STATUS_OPTIONS, 'rework');
                                 @endphp
                                 <select name="status" id="status_individual" class="form-select mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 {{ $errors->has('status') ? 'border-red-500' : '' }} {{ $task->status === 'rework' ? 'readonly-style' : '' }}">
                                     @foreach(($task->status === 'rework' ? App\Models\Task::STATUS_OPTIONS : $statusOptionsForEdit) as $key => $value)
                                         <option value="{{ $key }}"
                                             {{ old('status', $task->status) == $key ? 'selected' : '' }}
                                             @if($key === 'cancelled' && !$canUpdateCritical) disabled @endif
                                         >
                                             {{ $value }}
                                         </option>
                                     @endforeach
                                 </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                <div id="rework-status-note" class="mt-2 p-2 text-xs bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-md dark:bg-yellow-700/30 dark:text-yellow-200 dark:border-yellow-500 {{ $task->status !== 'rework' ? 'hidden' : '' }}">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    「直し」ステータスの工程は、関連する「直し」の子工程をすべて完了することで自動的に「完了」ステータスに更新されます。
                                </div>
                            </div>
                        @endif

                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <x-secondary-button as="a" href="{{ route('projects.show', $project) }}">キャンセル</x-secondary-button>
                        <x-primary-button type="submit"><i class="fas fa-save mr-2"></i> 更新</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canUpdateCritical = {{ Js::from($canUpdateCritical) }};

    if (!canUpdateCritical) {
        // Tom-selectのロック処理
        const tomSelectIds = ['parent_id_individual_edit', 'character_id_individual_edit', 'assignees_select'];
        tomSelectIds.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.tomselect) {
                el.tomselect.lock();
                el.tomselect.control.classList.add('readonly-style');
            }
        });
    }

    const statusSelect = document.getElementById('status_individual');
    if (statusSelect && statusSelect.classList.contains('readonly-style')) {
        statusSelect.style.pointerEvents = 'none';
    }

    // ステータス変更のAJAX処理
    const taskFormPage = document.getElementById('task-form-page');
    if (statusSelect && taskFormPage) {
        let originalStatus = taskFormPage.dataset.taskStatus;
        const taskId = taskFormPage.dataset.taskId;
        const projectId = taskFormPage.dataset.projectId;

        statusSelect.addEventListener('change', async function() {
            const newStatus = this.value;

            // ステータスが実際に変更されていなければ何もしない
            if (newStatus === originalStatus) return;

            // サーバーにステータス更新を試行する関数
            const sendUpdateRequest = async (force = false) => {
                try {
                    // 警告チェック機能を持つ updateProgress エンドポイントを呼び出す
                    const response = await axios.post(`/projects/${projectId}/tasks/${taskId}/progress`, {
                        status: newStatus,
                        force_update: force
                    });

                    // 成功した場合の処理
                    if (response.data.success) {
                        // 保持している元のステータスを更新
                        originalStatus = newStatus;
                        taskFormPage.dataset.taskStatus = newStatus;

                        // 成功メッセージの通知
                        if (response.data.work_log_message) {
                            const notification = document.createElement("div");
                            notification.className = "fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-md shadow-lg z-50 transition-opacity duration-300";
                            notification.textContent = response.data.work_log_message;
                            document.body.appendChild(notification);
                            setTimeout(() => {
                                notification.style.opacity = "0";
                                setTimeout(() => { notification.remove(); }, 300);
                            }, 3000);
                        }

                        // タイマーUIなど、他のコンポーネントに変更を通知
                        window.dispatchEvent(
                            new CustomEvent("timer-ui-update", {
                                detail: { taskId, newStatus },
                            })
                        );
                    } else {
                        // 成功レスポンスだが success: false の場合 (念のため)
                        alert(response.data.message || 'ステータスの更新に失敗しました。');
                        statusSelect.value = originalStatus; // 表示を元に戻す
                    }
                } catch (error) {
                    // --- エラー処理 ---
                    if (error.response && error.response.status === 422 && error.response.data.requires_confirmation) {
                        // 【ここが核心】サーバーから詳細な警告が返ってきた場合
                        const warningMessages = error.response.data.warnings.map(w => w.message).join('\n');

                        if (confirm(warningMessages)) {
                            // ユーザーが確認ダイアログで「OK」を押したら、強制実行フラグを立てて再リクエスト
                            await sendUpdateRequest(true);
                        } else {
                            // ユーザーが「キャンセル」を押したら、表示を元に戻す
                            statusSelect.value = originalStatus;
                        }
                    } else {
                        // その他の予期せぬエラーの場合
                        console.error("Status update failed:", error);
                        const errorMessage = error.response?.data?.message || "ステータスの更新中に予期せぬエラーが発生しました。";
                        alert(errorMessage);
                        statusSelect.value = originalStatus; // 表示を元に戻す
                    }
                }
            };

            // まずは通常のリクエストを送信
            await sendUpdateRequest(false);
        });

        window.addEventListener('task-status-updated', (event) => {
            const { taskId: updatedTaskId, newStatus } = event.detail;
            if (String(updatedTaskId) === String(taskId)) {
                statusSelect.value = newStatus;
                originalStatus = newStatus;
                taskFormPage.dataset.taskStatus = newStatus;
            }
        });
    }

    const fileListContainer = document.querySelector('#file-list-edit');
    if (fileListContainer) {
        fileListContainer.addEventListener('change', async function(event) {
            if (event.target.classList.contains('soft-delete-file-checkbox')) {
                const checkbox = event.target;
                const url = checkbox.dataset.toggleUrl;
                const fileItem = checkbox.closest('li');
                const fileTextContainer = fileItem.querySelector('.truncate');
                checkbox.disabled = true;
                const isTrashed = checkbox.checked;
                fileItem.classList.toggle('opacity-60', isTrashed);
                fileTextContainer.classList.toggle('line-through', isTrashed);
                fileTextContainer.classList.toggle('text-gray-500', isTrashed);
                fileTextContainer.classList.toggle('dark:text-gray-500', isTrashed);

                try {
                    const response = await axios.post(url, {
                        _token: '{{ csrf_token() }}'
                    });

                    if (response.data.success) {
                        checkbox.title = response.data.is_trashed ? '復元する' : '論理削除する';
                    } else {
                        throw new Error(response.data.message || '操作に失敗しました。');
                    }
                } catch (error) {
                    console.error('File soft delete toggle failed:', error);
                    alert(error.message || 'エラーが発生しました。ページをリロードしてください。');
                    checkbox.checked = !isTrashed;
                    fileItem.classList.toggle('opacity-60', !isTrashed);
                    fileTextContainer.classList.toggle('line-through', !isTrashed);
                    fileTextContainer.classList.toggle('text-gray-500', !isTrashed);
                    fileTextContainer.classList.toggle('dark:text-gray-500', !isTrashed);
                } finally {
                    checkbox.disabled = false;
                }
            }
        });
    }
});
</script>
@endpush