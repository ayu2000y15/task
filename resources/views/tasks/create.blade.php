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
                <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end">
                    <div class="md:col-span-4">
                        <x-select-input label="工程テンプレート" name="process_template_id" id="process_template_id"
                            :options="$processTemplates->pluck('name', 'id')" emptyOptionText="選択してください" required />
                    </div>
                    <div class="md:col-span-4">
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
                    <div class="md:col-span-2">
                        <x-input-label for="working_hours_start" value="稼働開始時刻" required />
                        <x-text-input type="time" id="working_hours_start" name="working_hours_start" class="mt-1 block w-full" value="{{ old('working_hours_start', '09:00') }}" required />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="working_hours_end" value="稼働終了時刻" required />
                        <x-text-input type="time" id="working_hours_end" name="working_hours_end" class="mt-1 block w-full" value="{{ old('working_hours_end', '18:00') }}" required />
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
                                required :hasError="$errors->has('name')" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div id="character_id_wrapper_individual">
                            <x-select-input label="所属先" name="character_id" id="character_id_individual"
                                :options="$project->characters->pluck('name', 'id')"
                                :selected="old('character_id', request('character_id_for_new_task'))"
                                emptyOptionText="案件全体"
                                :hasError="$errors->has('character_id')" />
                            <x-input-error :messages="$errors->get('character_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description_individual" value="メモ" />
                            <x-textarea-input id="description_individual" name="description" class="mt-1"
                                rows="3">{{ old('description') }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div id="task-fields-individual" class="space-y-4">
                            {{-- レイアウトをsm:grid-cols-3に変更 --}}
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4">
                                <div>
                                    <x-input-label for="start_date_individual" value="開始日時" />
                                    <x-text-input type="datetime-local" id="start_date_individual" name="start_date"
                                        class="mt-1 block w-full" :value="old('start_date', now()->format('Y-m-d\TH:i'))"
                                        :hasError="$errors->has('start_date')" />
                                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="duration_value" value="工数" />
                                    <div class="flex items-center mt-1 space-x-2">
                                        <x-text-input type="number" id="duration_value" name="duration_value" class="block w-1/2"
                                            :value="old('duration_value', 1)" min="0" step="any"
                                            :hasError="$errors->has('duration_value')" />
                                        <select name="duration_unit" id="duration_unit"
                                                class="block w-1/2 mt-0 form-select rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 {{ $errors->has('duration_unit') ? 'border-red-500' : '' }}">
                                            <option value="days" @if(old('duration_unit', 'days') == 'days') selected @endif>日</option>
                                            <option value="hours" @if(old('duration_unit') == 'hours') selected @endif>時間</option>
                                            <option value="minutes" @if(old('duration_unit') == 'minutes') selected @endif>分</option>
                                        </select>
                                    </div>
                                    <x-input-error :messages="$errors->get('duration_value')" class="mt-2" />
                                    <x-input-error :messages="$errors->get('duration_unit')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="end_date_individual" value="終了日時" />
                                    <x-text-input type="datetime-local" id="end_date_individual" name="end_date"
                                        class="mt-1 block w-full" :value="old('end_date')"
                                        :hasError="$errors->has('end_date')" />
                                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div id="assignee_wrapper_individual">
                            <x-input-label for="assignee_individual" value="担当者" />
                            <x-text-input type="text" id="assignee_individual" name="assignee" class="mt-1"
                                :value="old('assignee')" :hasError="$errors->has('assignee')" />
                            <x-input-error :messages="$errors->get('assignee')" class="mt-2" />
                        </div>

                        <div id="parent_id_wrapper_individual">
                            @php
                                $parentTaskOptions = $project->tasks->where('is_folder', true)->sortBy('name')->pluck('name', 'id');
                            @endphp
                            <x-select-input label="親工程 (フォルダのみ)" name="parent_id" id="parent_id_individual"
                                :options="$parentTaskOptions" :selected="old('parent_id', optional($parentTask)->id)"
                                emptyOptionText="なし"
                                :hasError="$errors->has('parent_id')" />
                            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>

                        <div id="status-field-individual">
                            @php
                                $statusOptionsFromController = $statusOptions ?? [
                                    'not_started' => '未着手',
                                    'in_progress' => '進行中',
                                    'completed' => '完了',
                                    'on_hold' => '保留中',
                                    'cancelled' => 'キャンセル',
                                ];
                            @endphp
                            <x-select-input label="ステータス" name="status" id="status_individual"
                                :options="$statusOptionsFromController" :selected="old('status', 'not_started')"
                                :hasError="$errors->has('status')" />
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const taskTypeRadios = document.querySelectorAll('input[name="is_milestone_or_folder"]');
    const taskFieldsIndividual = document.getElementById('task-fields-individual');
    const characterIdWrapper = document.getElementById('character_id_wrapper_individual');
    const statusField = document.getElementById('status-field-individual');
    const startDateInput = document.getElementById('start_date_individual');
    const durationValueInput = document.getElementById('duration_value');
    const durationUnitSelect = document.getElementById('duration_unit');
    const endDateInput = document.getElementById('end_date_individual');
    const parentIdSelect = document.getElementById('parent_id_individual');
    const assigneeInput = document.getElementById('assignee_individual');
    const assigneeWrapper = document.getElementById('assignee_wrapper_individual');
    const parentIdWrapper = document.getElementById('parent_id_wrapper_individual');


    function toggleTaskFields(selectedValue) {
        const isTask = selectedValue === 'task';
        const isTodoTask = selectedValue === 'todo_task';
        const isMilestone = selectedValue === 'milestone';
        const isFolder = selectedValue === 'folder';
        const isDateTimeDisabled = isFolder || isMilestone || isTodoTask;


        if (taskFieldsIndividual) taskFieldsIndividual.style.display = isFolder ? 'none' : 'block';
        if (characterIdWrapper) characterIdWrapper.style.display = isFolder ? 'none' : 'block';
        if (statusField) statusField.style.display = isFolder ? 'none' : 'block';
        if (assigneeWrapper) assigneeWrapper.style.display = isFolder ? 'none' : 'block';
        if (parentIdWrapper) parentIdWrapper.style.display = isFolder ? 'none' : 'block';


        if (startDateInput) startDateInput.disabled = isDateTimeDisabled;
        if (durationValueInput) durationValueInput.disabled = isDateTimeDisabled;
        if (durationUnitSelect) durationUnitSelect.disabled = isDateTimeDisabled;
        if (endDateInput) endDateInput.disabled = isDateTimeDisabled;
        if (assigneeInput) assigneeInput.disabled = isFolder;
        if (parentIdSelect) parentIdSelect.disabled = isFolder;


        if (isFolder) {
            if (startDateInput) startDateInput.value = '';
            if (durationValueInput) durationValueInput.value = '';
            if (endDateInput) endDateInput.value = '';
        } else if (isMilestone) {
            if (durationValueInput) durationValueInput.value = 0;
            if (durationUnitSelect) durationUnitSelect.value = 'minutes';
            // if (endDateInput && startDateInput && startDateInput.value) {
            //     endDateInput.value = startDateInput.value;
            // }
        } else if (isTodoTask) {
            if (startDateInput) startDateInput.value = '';
            if (durationValueInput) durationValueInput.value = '';
            if (endDateInput) endDateInput.value = '';
        }
    }

    taskTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            toggleTaskFields(this.value);
        });
        if (radio.checked) {
            toggleTaskFields(radio.value);
        }
    });

    function calculateEndDate() {
        if (!startDateInput || !durationValueInput || !durationUnitSelect || !endDateInput || endDateInput.disabled) {
            return;
        }

        const startDateValue = startDateInput.value;
        const duration = parseFloat(durationValueInput.value);
        const unit = durationUnitSelect.value;

        if (startDateValue && !isNaN(duration) && duration >= 0 && unit) {
            const start = new Date(startDateValue);
            let minutesToAdd = 0;

            if (unit === 'days') {
                minutesToAdd = duration * 24 * 60; // 1日 = 24時間 = 1440分
            } else if (unit === 'hours') {
                minutesToAdd = duration * 60;
            } else if (unit === 'minutes') {
                minutesToAdd = duration;
            }

            const end = new Date(start.getTime() + minutesToAdd * 60000); // 60000ミリ秒 = 1分

            if (!isNaN(end.getTime())) {
                const year = end.getFullYear();
                const month = ('0' + (end.getMonth() + 1)).slice(-2);
                const day = ('0' + end.getDate()).slice(-2);
                const hours = ('0' + end.getHours()).slice(-2);
                const minutes = ('0' + end.getMinutes()).slice(-2);
                endDateInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
            } else {
                endDateInput.value = '';
            }
        } else {
            endDateInput.value = '';
        }
    }

    if (startDateInput && durationValueInput && durationUnitSelect && endDateInput) {
        startDateInput.addEventListener('change', calculateEndDate);
        durationValueInput.addEventListener('input', calculateEndDate);
        durationUnitSelect.addEventListener('change', calculateEndDate);
        if(startDateInput.value) calculateEndDate();
    }
});
</script>
@endpush
