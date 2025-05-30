@extends('layouts.app')

@section('title', '工程編集 - ' . $task->name)

@push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <style>
        /* ... (既存のCSSスタイルは変更なし) ... */
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
            {{-- ★ ボタン配置をページ上部右側に変更 --}}
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

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('projects.tasks.update', [$project, $task]) }}" method="POST" id="task-edit-form">
                    @csrf
                    @method('PUT')
                    <div class="space-y-6">
                        {{-- 工程種別 (編集不可) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">工程種別</label>
                            @php
                                $taskType = 'task';
                                if ($task->is_milestone)
                                    $taskType = 'milestone';
                                elseif ($task->is_folder)
                                    $taskType = 'folder';
                                elseif (!$task->start_date && !$task->end_date && !$task->is_milestone && !$task->is_folder)
                                    $taskType = 'todo_task';
                            @endphp
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

                        {{-- 工程名 --}}
                        <div>
                            <x-input-label for="name_individual" value="工程名" :required="true" />
                            <x-text-input type="text" id="name_individual" name="name" class="mt-1 block w-full"
                                :value="old('name', $task->name)" required :hasError="$errors->has('name')" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        {{-- 所属先キャラクター (フォルダの場合は非表示) --}}
                        <div id="character_id_wrapper_individual" {{ $task->is_folder ? 'style="display:none;"' : '' }}>
                            <x-select-input label="所属先キャラクター" name="character_id" id="character_id_individual"
                                :options="$project->characters->pluck('name', 'id')"
                                :selected="old('character_id', $task->character_id)"
                                emptyOptionText="案件全体 (キャラクター未所属)"
                                :hasError="$errors->has('character_id')" />
                            <x-input-error :messages="$errors->get('character_id')" class="mt-2" />
                        </div>

                        {{-- ファイル管理セクション (フォルダの場合のみ表示) --}}
                        @if($task->is_folder)
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

                        {{-- メモ --}}
                        <div>
                            <x-input-label for="description_individual" value="メモ" />
                            <x-textarea-input id="description_individual" name="description" class="mt-1 block w-full"
                                rows="3" :hasError="$errors->has('description')">{{ old('description', $task->description) }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        {{-- 日付・工数関連フィールド (フォルダの場合は非表示・無効化) --}}
                        <div id="task-fields-individual" class="space-y-4" {{ $task->is_folder ? 'style="display:none;"' : '' }}>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4">
                                <div>
                                    <x-input-label for="start_date_individual" value="開始日" />
                                    <x-text-input type="date" id="start_date_individual" name="start_date"
                                        class="mt-1 block w-full"
                                        :value="old('start_date', optional($task->start_date)->format('Y-m-d'))"
                                        :disabled="$taskType == 'milestone' || $taskType == 'todo_task' || $task->is_folder"
                                        :hasError="$errors->has('start_date')" />
                                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="duration_individual" value="工数(日)" />
                                    <x-text-input type="number" id="duration_individual" name="duration"
                                        class="mt-1 block w-full"
                                        :value="old('duration', $task->duration)" min="1"
                                        :disabled="$taskType == 'milestone' || $taskType == 'todo_task' || $task->is_folder"
                                        :hasError="$errors->has('duration')" />
                                    <x-input-error :messages="$errors->get('duration')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="end_date_individual" value="終了日" />
                                    <x-text-input type="date" id="end_date_individual" name="end_date"
                                        class="mt-1 block w-full"
                                        :value="old('end_date', optional($task->end_date)->format('Y-m-d'))"
                                        :disabled="$taskType == 'milestone' || $taskType == 'todo_task' || $task->is_folder"
                                        :hasError="$errors->has('end_date')" />
                                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        {{-- 担当者 (フォルダの場合は非表示) --}}
                        <div {{ $task->is_folder ? 'style="display:none;"' : '' }}>
                            <x-input-label for="assignee_individual" value="担当者" />
                            <x-text-input type="text" id="assignee_individual" name="assignee" class="mt-1 block w-full"
                                :value="old('assignee', $task->assignee)" :hasError="$errors->has('assignee')" />
                            <x-input-error :messages="$errors->get('assignee')" class="mt-2" />
                        </div>

                        {{-- 親工程 (フォルダの場合は非表示) --}}
                        <div {{ $task->is_folder ? 'style="display:none;"' : '' }}>
                             @php
                                $parentTaskOptions = $project->tasks
                                    ->where('is_folder', true)
                                    ->where('id', '!=', $task->id)
                                    ->filter(function ($potentialParent) use ($task) {
                                        return !$task->isAncestorOf($potentialParent);
                                    })
                                    ->sortBy('name')
                                    ->pluck('name', 'id');
                            @endphp
                            <x-select-input label="親工程 (フォルダのみ)" name="parent_id" id="parent_id_individual"
                                :options="$parentTaskOptions"
                                :selected="old('parent_id', $task->parent_id)"
                                emptyOptionText="なし"
                                :hasError="$errors->has('parent_id')" />
                           <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>

                        {{-- ステータス (フォルダの場合は非表示) --}}
                        <div id="status-field-individual" {{ $task->is_folder ? 'style="display:none;"' : '' }}>
                            <x-select-input label="ステータス" name="status" id="status_individual"
                                :options="['not_started' => '未着手', 'in_progress' => '進行中', 'completed' => '完了', 'on_hold' => '保留中', 'cancelled' => 'キャンセル']"
                                :selected="old('status', $task->status)"
                                :hasError="$errors->has('status')" />
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>
                    </div>

                    {{-- ★ フォーム下部のボタン配置を変更 --}}
                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        {{-- 削除ボタンはページ上部に移動したため、ここからは削除 --}}
                        {{-- @can('delete', $task)
                            <button type="button"
                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium"
                                onclick="document.getElementById('delete-task-form').submit();">
                                <i class="fas fa-trash mr-1"></i> この工程を削除
                            </button>
                        @endcan --}}
                        <x-secondary-button as="a" href="{{ route('projects.show', $project) }}">
                            キャンセル
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            <i class="fas fa-save mr-2"></i> 更新
                        </x-primary-button>
                    </div>
                </form>
                {{-- 削除フォームはページ上部に移動したため、ここからは削除（または必要なら維持） --}}
                {{-- <form id="delete-task-form" action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST"
                    style="display: none;" onsubmit="return confirm('本当に削除しますか？この工程に関連するすべての子工程も削除されます。');">
                    @csrf
                    @method('DELETE')
                </form> --}}
            </div>
        </div>
    </div>
@endsection