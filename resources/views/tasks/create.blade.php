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
                <div class="grid grid-cols-1 md:grid-cols-10 gap-4 items-start">
                    <div class="md:col-span-5">
                        <x-select-input label="工程テンプレート" name="process_template_id" id="process_template_id"
                            :options="$processTemplates->pluck('name', 'id')" emptyOptionText="選択してください" required />
                    </div>
                    <div class="md:col-span-5">
                        <x-select-input label="所属先キャラクター (任意)" name="character_id_for_template"
                            id="character_id_for_template" :options="$project->characters->pluck('name', 'id')"
                            :selected="old('character_id_for_template', request('character_id_for_new_task'))"
                            emptyOptionText="案件全体へ適用する" />
                        <div class="mt-2">
                            <x-input-label for="apply_template_to_all_characters" class="inline-flex items-center">
                                <input type="checkbox" id="apply_template_to_all_characters"
                                    name="apply_template_to_all_characters" value="1"
                                    class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                                    {{ old('apply_template_to_all_characters') ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">すべてのキャラクターへ工程を適用する</span>
                            </x-input-label>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="template_start_date" value="最初の工程の開始日" required />
                        <x-text-input type="date" id="template_start_date" name="template_start_date" class="mt-1 w-full"
                            :value="old('template_start_date', now()->format('Y-m-d'))" required />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="working_hours_start" value="稼働開始時刻" required />
                        <x-text-input type="time" id="working_hours_start" name="working_hours_start"
                            class="mt-1 block w-full" value="{{ old('working_hours_start', '09:00') }}" required />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="working_hours_end" value="稼働終了時刻" required />
                        <x-text-input type="time" id="working_hours_end" name="working_hours_end" class="mt-1 block w-full"
                            value="{{ old('working_hours_end', '18:00') }}" required />
                    </div>
                    <input type="hidden" name="parent_id_for_template" value="{{ optional($parentTask)->id }}">
                    <div class="md:col-span-2 md:self-end">
                        <x-primary-button type="submit"
                            class="w-full justify-center bg-teal-500 hover:bg-teal-600 active:bg-teal-700 focus:border-teal-700 focus:ring-teal-300 dark:bg-teal-600 dark:hover:bg-teal-700 dark:active:bg-teal-800 dark:focus:border-teal-800 dark:focus:ring-teal-400">
                            <i class="fas fa-magic mr-2"></i>適用して作成
                        </x-primary-button>
                    </div>
                    <div class="md:col-span-2"></div>
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
                                <x-radio-input name="is_milestone_or_folder" id="is_task_type_task" value="task" :label="'<i class=\'fas fa-tasks mr-1\'></i>工程'" :checked="old('is_milestone_or_folder', $preselectedType ?? 'task') == 'task'" />
                                <x-radio-input name="is_milestone_or_folder" id="is_task_type_todo_task" value="todo_task"
                                    :label="'<i class=\'fas fa-list-check mr-1\'></i>タスク(期限なし)'"
                                    :checked="old('is_milestone_or_folder', $preselectedType ?? 'task') == 'todo_task'" />
                                <x-radio-input name="is_milestone_or_folder" id="is_task_type_milestone" value="milestone"
                                    :label="'<i class=\'fas fa-flag mr-1\'></i>予定'" :checked="old('is_milestone_or_folder', $preselectedType ?? 'task') == 'milestone'" />
                                {{-- <x-radio-input name="is_milestone_or_folder" id="is_task_type_folder" value="folder"
                                    :label="'<i class=\'fas fa-folder mr-1\'></i>フォルダ'"
                                    :checked="old('is_milestone_or_folder', $preselectedType ?? 'task') == 'folder'" /> --}}
                            </div>
                        </div>

                        <div>
                            <x-input-label for="name_individual" value="工程名" required />
                            <x-text-input type="text" id="name_individual" name="name" class="mt-1" :value="old('name')"
                                required :hasError="$errors->has('name')" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div id="parent_id_wrapper_individual">
                            <x-select-input label="親工程" name="parent_id" id="parent_id_individual"
                                :options="$parentTaskOptions" :selected="old('parent_id', optional($parentTask)->id)"
                                emptyOptionText="なし" :hasError="$errors->has('parent_id')" />
                            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>

                        <div id="character_id_wrapper_individual">
                            <x-select-input label="所属先キャラクター" name="character_id" id="character_id_individual"
                                :options="$project->characters->pluck('name', 'id')" :selected="old('character_id', request('character_id_for_new_task'))" emptyOptionText="案件全体へ工程を追加する"
                                :hasError="$errors->has('character_id')" />
                            <div class="mt-2">
                                <x-input-label for="apply_individual_to_all_characters" class="inline-flex items-center">
                                    <input type="checkbox" id="apply_individual_to_all_characters"
                                        name="apply_individual_to_all_characters" value="1"
                                        class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                                        {{ old('apply_individual_to_all_characters') ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">すべてのキャラクターへ同じ内容で作成する</span>
                                </x-input-label>
                            </div>
                            <x-input-error :messages="$errors->get('character_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description_individual" value="メモ" />
                            <x-textarea-input id="description_individual" name="description" class="mt-1"
                                rows="3">{{ old('description') }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div id="task-fields-individual" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                <div>
                                    <x-input-label for="start_date_individual" value="開始日時" required />
                                    <x-text-input type="datetime-local" id="start_date_individual" name="start_date"
                                        class="mt-1 block w-full" :value="old('start_date', \Carbon\Carbon::parse($preselectedDate ?? now())->format('Y-m-d\TH:i'))"
                                        :hasError="$errors->has('start_date')" />
                                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="end_date_individual" value="終了日時" required />
                                    <x-text-input type="datetime-local" id="end_date_individual" name="end_date"
                                        class="mt-1 block w-full" :value="old('end_date')"
                                        :hasError="$errors->has('end_date')" />
                                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                                </div>
                                <div id="duration_wrapper_individual">
                                    <x-input-label for="duration_value" value="工数" required />
                                    <div class="flex items-center mt-1 space-x-2">
                                        <x-text-input type="number" id="duration_value" name="duration_value"
                                            class="block w-1/2" :value="old('duration_value', 1)" min="0" step="any"
                                            :hasError="$errors->has('duration_value')" />
                                        <select name="duration_unit" id="duration_unit"
                                            class="block w-1/2 mt-0 form-select rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 {{ $errors->has('duration_unit') ? 'border-red-500' : '' }}">
                                            <option value="days" @if(old('duration_unit', 'days') == 'days') selected @endif>日
                                            </option>
                                            <option value="hours" @if(old('duration_unit') == 'hours') selected @endif>時間
                                            </option>
                                            <option value="minutes" @if(old('duration_unit') == 'minutes') selected @endif>分
                                            </option>
                                        </select>
                                    </div>
                                    <x-input-error :messages="$errors->get('duration_value')" class="mt-2" />
                                    <x-input-error :messages="$errors->get('duration_unit')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div id="assignees_wrapper_individual">
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

                        <div id="status-field-individual">
                            @php
                                // 編集画面では「直し」を選択肢から除外する
                                $statusOptionsForEdit = \Illuminate\Support\Arr::except(App\Models\Task::STATUS_OPTIONS, 'rework');
                            @endphp
                            <x-select-input label="ステータス" name="status" id="status_individual"
                                :options="$statusOptionsForEdit" :selected="old('status', 'not_started')"
                                :hasError="$errors->has('status')" />
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <x-secondary-button as="a"
                            href="{{ url()->previous(route('projects.show', $project)) }}">キャンセル</x-secondary-button>
                        <x-primary-button type="submit"><i class="fas fa-plus mr-2"></i> 作成</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection