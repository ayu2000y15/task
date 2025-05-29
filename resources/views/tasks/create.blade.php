@extends('layouts.app')

@section('title', '工程作成 - ' . $project->title)

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" id="task-form-page">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                工程作成 - <span class="font-normal text-xl truncate"
                    title="{{ $project->title }}">{{ Str::limit($project->title, 30) }}</span>
                @if($parentTask)
                    <span class="text-lg text-gray-600 dark:text-gray-400 ml-2">（親工程:
                        {{ Str::limit($parentTask->name, 20) }}）</span>
                @endif
            </h1>
            <x-secondary-button as="a" href="{{ url()->previous(route('projects.show', $project)) }}">
                <i class="fas fa-arrow-left mr-2"></i> 戻る
            </x-secondary-button>
        </div>

        <div
            class="mb-8 p-4 sm:p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b pb-3 dark:border-gray-600">
                テンプレートから工程を一括作成</h2>
            <form action="{{ route('projects.tasks.fromTemplate', $project) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-10 gap-4 items-end">
                    <div class="md:col-span-3">
                        <x-select-input label="工程テンプレート" name="process_template_id" id="process_template_id"
                            :options="$processTemplates->pluck('name', 'id')" emptyOptionText="選択してください" required />
                    </div>
                    <div class="md:col-span-3">
                        <x-select-input label="所属先キャラクター (任意)" name="character_id_for_template"
                            id="character_id_for_template" :options="$project->characters->pluck('name', 'id')"
                            :selected="old('character_id_for_template', request('character_id_for_new_task'))"
                            emptyOptionText="案件全体 (キャラクター未所属)" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="template_start_date" value="最初の工程の開始日" required />
                        <x-text-input type="date" id="template_start_date" name="template_start_date" class="mt-1"
                            :value="old('template_start_date', now()->format('Y-m-d'))" required />
                    </div>
                    <input type="hidden" name="parent_id_for_template" value="{{ optional($parentTask)->id }}">
                    <div class="md:col-span-2">
                        <x-primary-button type="submit"
                            class="w-full justify-center bg-teal-500 hover:bg-teal-600 active:bg-teal-700 focus:border-teal-700 focus:ring-teal-300 dark:bg-teal-600 dark:hover:bg-teal-700 dark:active:bg-teal-800 dark:focus:border-teal-800 dark:focus:ring-teal-400">
                            <i class="fas fa-magic mr-2"></i>適用して作成
                        </x-primary-button>
                    </div>
                </div>
            </form>
        </div>
        <hr class="my-6 dark:border-gray-600">

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">新規工程を個別作成</h2>
            </div>
            <div class="p-6 sm:p-8">
                <form action="{{ route('projects.tasks.store', $project) }}" method="POST">
                    @csrf
                    <div class="space-y-6">
                        <div>
                            <x-input-label value="工程種別" class="mb-2" />
                            <div class="space-y-2 sm:space-y-0 sm:flex sm:flex-wrap sm:gap-x-4 sm:gap-y-2">
                                {{-- アイコンは<x-radio-input>コンポーネントのlabelスロットやCSSで調整可能 --}}
                                    <x-radio-input name="is_milestone_or_folder" id="is_task_type_task" value="task"
                                        :label="'<i class=\'fas fa-tasks mr-1\'></i>工程'"
                                        :checked="old('is_milestone_or_folder', 'task') == 'task'" />
                                    <x-radio-input name="is_milestone_or_folder" id="is_task_type_todo_task"
                                        value="todo_task" :label="'<i class=\'fas fa-list-check mr-1\'></i>タスク(期限なし)'"
                                        :checked="old('is_milestone_or_folder') == 'todo_task'" />
                                    <x-radio-input name="is_milestone_or_folder" id="is_task_type_milestone"
                                        value="milestone" :label="'<i class=\'fas fa-flag mr-1\'></i>重要納期'"
                                        :checked="old('is_milestone_or_folder') == 'milestone'" />
                                    @can('canCreateFoldersForFileUpload', App\Models\Task::class)
                                        <x-radio-input name="is_milestone_or_folder" id="is_task_type_folder" value="folder"
                                            :label="'<i class=\'fas fa-folder mr-1\'></i>フォルダ'"
                                            :checked="old('is_milestone_or_folder') == 'folder'" />
                                    @endcan
                            </div>
                        </div>

                        <div>
                            <x-input-label for="name_individual" value="工程名" required />
                            <x-text-input type="text" id="name_individual" name="name" class="mt-1" :value="old('name')"
                                required />
                        </div>

                        <div id="character_id_wrapper_individual">
                            <x-select-input label="所属先" name="character_id" id="character_id_individual"
                                :options="$project->characters->pluck('name', 'id')" :selected="old('character_id', request('character_id_for_new_task'))" emptyOptionText="案件全体" />
                        </div>

                        <div>
                            <x-input-label for="description_individual" value="メモ" />
                            <x-textarea-input id="description_individual" name="description" class="mt-1"
                                rows="3">{{ old('description') }}</x-textarea-input>
                        </div>

                        <div id="task-fields-individual" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-1 gap-x-6 gap-y-6"> {{-- 縦積みに変更 --}}
                                <div>
                                    <x-input-label for="start_date_individual" value="開始日" />
                                    <x-text-input type="date" id="start_date_individual" name="start_date" class="mt-1"
                                        :value="old('start_date')" />
                                </div>
                                <div>
                                    <x-input-label for="duration_individual" value="工数(日)" />
                                    <x-text-input type="number" id="duration_individual" name="duration" class="mt-1"
                                        :value="old('duration', 1)" min="1" />
                                </div>
                                <div>
                                    <x-input-label for="end_date_individual" value="終了日" />
                                    <x-text-input type="date" id="end_date_individual" name="end_date" class="mt-1"
                                        :value="old('end_date')" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="assignee_individual" value="担当者" />
                            <x-text-input type="text" id="assignee_individual" name="assignee" class="mt-1"
                                :value="old('assignee')" />
                        </div>

                        <div>
                            @php
                                $parentTaskOptions = $project->tasks->where('is_folder', true)->sortBy('name')->pluck('name', 'id');
                            @endphp
                            <x-select-input label="親工程 (フォルダのみ)" name="parent_id" id="parent_id_individual"
                                :options="$parentTaskOptions" :selected="old('parent_id', optional($parentTask)->id)"
                                emptyOptionText="なし" />
                        </div>

                        <div id="status-field-individual">
                            @php
                                $statusOptionsFromController = $statusOptions ?? [ // Controllerから渡される$statusOptionsを使用
                                    'not_started' => '未着手',
                                    'in_progress' => '進行中',
                                    'completed' => '完了',
                                    'on_hold' => '保留中',
                                    'cancelled' => 'キャンセル',
                                ];
                            @endphp
                            <x-select-input label="ステータス" name="status" id="status_individual"
                                :options="$statusOptionsFromController" :selected="old('status', 'not_started')" />
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <x-secondary-button as="a" href="{{ url()->previous(route('projects.show', $project)) }}">
                            キャンセル
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            <i class="fas fa-plus mr-2"></i> 作成
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection