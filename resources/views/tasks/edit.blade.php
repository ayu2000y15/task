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
            <a href="{{ url()->previous(route('projects.show', $project)) }}"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i> 戻る
            </a>
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

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('projects.tasks.update', [$project, $task]) }}" method="POST" id="task-edit-form">
                    @csrf
                    @method('PUT')
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">工程種別</label>
                            @php
                                $taskType = 'task';
                                if ($task->is_milestone)
                                    $taskType = 'milestone';
                                elseif ($task->is_folder)
                                    $taskType = 'folder';
                                elseif (!$task->start_date && !$task->end_date)
                                    $taskType = 'todo_task';
                            @endphp
                            <div class="space-y-2 sm:space-y-0 sm:flex sm:flex-wrap sm:gap-x-4 sm:gap-y-2">
                                <div class="flex items-center">
                                    <input
                                        class="form-radio h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500 disabled:cursor-not-allowed"
                                        type="radio" id="is_task_type_task_edit" name="is_milestone_or_folder" value="task"
                                        disabled {{ $taskType == 'task' ? 'checked' : '' }}>
                                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                                        for="is_task_type_task_edit"><i class="fas fa-tasks mr-1"></i>工程</label>
                                </div>
                                <div class="flex items-center">
                                    <input
                                        class="form-radio h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500 disabled:cursor-not-allowed"
                                        type="radio" id="is_task_type_todo_task_edit" name="is_milestone_or_folder"
                                        value="todo_task" disabled {{ $taskType == 'todo_task' ? 'checked' : '' }}>
                                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                                        for="is_task_type_todo_task_edit"><i
                                            class="fas fa-list-check mr-1"></i>タスク(期限なし)</label>
                                </div>
                                <div class="flex items-center">
                                    <input
                                        class="form-radio h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500 disabled:cursor-not-allowed"
                                        type="radio" id="is_task_type_milestone_edit" name="is_milestone_or_folder"
                                        value="milestone" disabled {{ $taskType == 'milestone' ? 'checked' : '' }}>
                                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                                        for="is_task_type_milestone_edit"><i class="fas fa-flag mr-1"></i>重要納期</label>
                                </div>
                                @can('canCreateFoldersForFileUpload', App\Models\Task::class) {{-- This check seems for
                                    general capability,
                                    might need adjustment if per-task --}}
                                    <div class="flex items-center">
                                        <input
                                            class="form-radio h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500 disabled:cursor-not-allowed"
                                            type="radio" id="is_task_type_folder_edit" name="is_milestone_or_folder"
                                            value="folder" disabled {{ $taskType == 'folder' ? 'checked' : '' }}>
                                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                                            for="is_task_type_folder_edit"><i class="fas fa-folder mr-1"></i>フォルダ</label>
                                    </div>
                                @endcan
                            </div>
                        </div>

                        <div>
                            <label for="name_individual"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">工程名 <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="name_individual" name="name" value="{{ old('name', $task->name) }}"
                                required
                                class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400 @error('name') border-red-500 @enderror">
                            @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div id="character_id_wrapper_individual" {{ $task->is_folder ? 'style="display:none;"' : '' }}>
                            <label for="character_id_individual"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">所属先</label>
                            <select id="character_id_individual" name="character_id"
                                class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400 @error('character_id') border-red-500 @enderror">
                                <option value="">案件全体</option>
                                @foreach($project->characters as $character)
                                    <option value="{{ $character->id }}" {{ old('character_id', $task->character_id) == $character->id ? 'selected' : '' }}>
                                        {{ $character->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('character_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        @if($task->is_folder) {{-- タスクがフォルダの場合のみ表示 --}}
                            @can('fileView', $task) {{-- ファイル閲覧権限がある場合のみセクション表示 --}}
                                <div id="file-management-section" class="mb-3">
                                    <hr class="my-4 dark:border-gray-600">
                                    <h3 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2"><i
                                            class="fas fa-file-alt mr-2"></i>ファイル管理</h3>
                                    @can('fileUpload', $task) {{-- ファイルアップロード権限がある場合のみDropzone表示 --}}
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
                                        {{-- file-list-tailwind の中で各ファイルの操作権限をチェック --}}
                                        @include('tasks.partials.file-list-tailwind', ['files' => $files, 'project' => $project, 'task' => $task])
                                    </ul>
                                    <hr class="mt-4 dark:border-gray-600">
                                </div>
                            @endcan
                        @endif

                        <div>
                            <label for="description_individual"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">メモ</label>
                            <textarea id="description_individual" name="description" rows="3"
                                class="form-textarea mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400 @error('description') border-red-500 @enderror">{{ old('description', $task->description) }}</textarea>
                            @error('description')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div id="task-fields-individual" class="space-y-4" {{ $task->is_folder ? 'style="display:none;"' : '' }}>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4">
                                <div>
                                    <label for="start_date_individual"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">開始日</label>
                                    <input type="date" id="start_date_individual" name="start_date"
                                        value="{{ old('start_date', optional($task->start_date)->format('Y-m-d')) }}"
                                        class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400 @error('start_date') border-red-500 @enderror"
                                        {{ $taskType == 'milestone' || $taskType == 'todo_task' || $task->is_folder ? 'disabled' : '' }}>
                                    @error('start_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="duration_individual"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">工数(日)</label>
                                    <input type="number" id="duration_individual" name="duration"
                                        value="{{ old('duration', $task->duration) }}" min="1"
                                        class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400 @error('duration') border-red-500 @enderror"
                                        {{ $taskType == 'milestone' || $taskType == 'todo_task' || $task->is_folder ? 'disabled' : '' }}>
                                    @error('duration')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="end_date_individual"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">終了日</label>
                                    <input type="date" id="end_date_individual" name="end_date"
                                        value="{{ old('end_date', optional($task->end_date)->format('Y-m-d')) }}"
                                        class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400 @error('end_date') border-red-500 @enderror"
                                        {{ $taskType == 'milestone' || $taskType == 'todo_task' || $task->is_folder ? 'disabled' : '' }}>
                                    @error('end_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        <div {{ $task->is_folder ? 'style="display:none;"' : '' }}>
                            <label for="assignee_individual"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">担当者</label>
                            <input type="text" id="assignee_individual" name="assignee"
                                value="{{ old('assignee', $task->assignee) }}"
                                class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400 @error('assignee') border-red-500 @enderror">
                            @error('assignee')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div {{ $task->is_folder ? 'style="display:none;"' : '' }}>
                            <label for="parent_id_individual"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">親工程 (フォルダのみ)</label>
                            <select id="parent_id_individual" name="parent_id"
                                class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400 @error('parent_id') border-red-500 @enderror">
                                <option value="">なし</option>
                                @foreach($project->tasks->where('is_folder', true)->where('id', '!=', $task->id)->sortBy('name') as $potentialParent)
                                    @if(!$task->isAncestorOf($potentialParent))
                                        <option value="{{ $potentialParent->id }}" {{ old('parent_id', $task->parent_id) == $potentialParent->id ? 'selected' : '' }}>
                                            {{ $potentialParent->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('parent_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div id="status-field-individual" {{ $task->is_folder ? 'style="display:none;"' : '' }}>
                            <label for="status_individual"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">ステータス</label>
                            <select id="status_individual" name="status"
                                class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 disabled:bg-gray-100 dark:disabled:bg-gray-700/50 disabled:cursor-not-allowed dark:disabled:text-gray-400 @error('status') border-red-500 @enderror">
                                <option value="not_started" {{ old('status', $task->status) == 'not_started' ? 'selected' : '' }}>未着手</option>
                                <option value="in_progress" {{ old('status', $task->status) == 'in_progress' ? 'selected' : '' }}>進行中</option>
                                <option value="completed" {{ old('status', $task->status) == 'completed' ? 'selected' : '' }}>
                                    完了</option>
                                <option value="on_hold" {{ old('status', $task->status) == 'on_hold' ? 'selected' : '' }}>保留中
                                </option>
                                <option value="cancelled" {{ old('status', $task->status) == 'cancelled' ? 'selected' : '' }}>
                                    キャンセル</option>
                            </select>
                            @error('status')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        @can('delete', $task)
                            <button type="button"
                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium"
                                onclick="document.getElementById('delete-task-form').submit();">
                                <i class="fas fa-trash mr-1"></i> この工程を削除
                            </button>
                        @endcan
                        <div>
                            <a href="{{ url()->previous(route('projects.show', $project)) }}"
                                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 mr-3">
                                キャンセル
                            </a>
                            <x-primary-button>
                                <i class="fas fa-save mr-2"></i> 更新
                            </x-primary-button>
                        </div>
                    </div>
                </form>
                <form id="delete-task-form" action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST"
                    style="display: none;" onsubmit="return confirm('本当に削除しますか？この工程に関連するすべての子工程も削除されます。');">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>
@endsection

{{-- @push('scripts') は削除し、tasks-form.js と tasks-edit-dropzone.js にロジックを記述 --}}