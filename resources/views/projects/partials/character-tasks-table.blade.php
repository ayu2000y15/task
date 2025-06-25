{{-- resources/views/projects/partials/character-tasks-table.blade.php --}}
@php
    $isCharacterView = isset($character);
    $formIdentifier = $isCharacterView ? $character->id : 'project';
@endphp

<div id="task-list-container-{{ $tableId }}" class="overflow-x-auto overflow-y-auto max-h-[60vh]">
    <div class="mb-4">
        @php
            $hideCompletedParams = request()->query();
            $hideCompletedParams['context'] = 'character';
            $hideCompletedParams['character_id'] = $character->id;

            $isHidingCompleted = $hideCompleted ?? false;

            $baseClass = 'inline-flex items-center px-4 py-2 mx-2 my-2 border rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150';
            $activeClass = 'bg-blue-600 border-transparent text-white hover:bg-blue-700 focus:ring-blue-500';
            $inactiveClass = 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:ring-indigo-500';

            if ($isHidingCompleted) {
                unset($hideCompletedParams['hide_completed']);
                $buttonText = '完了を表示';
                $buttonIcon = 'fa-eye';
                $buttonClass = $activeClass;
            } else {
                $hideCompletedParams['hide_completed'] = 1;
                $buttonText = '完了を非表示';
                $buttonIcon = 'fa-eye-slash';
                $buttonClass = $inactiveClass;
            }
        @endphp
        <a href="{{ request()->url() }}?{{ http_build_query($hideCompletedParams) }}"
            class="{{ $baseClass }} {{ $buttonClass }}" id="toggle-completed-tasks-btn-{{ $tableId }}"
            data-container-id="task-list-container-{{ $tableId }}">
            <i class="fas {{ $buttonIcon }} mr-2"></i>{{ $buttonText }}
        </a>
        <div
            class="p-2 mx-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
            <i class="fas fa-info-circle mr-1"></i>
            工数の1日は8時間として計算しています。
        </div>
    </div>

    <div id="assignee-data-container-{{ $tableId }}" data-assignee-options='{{ json_encode($assigneeOptions ?? []) }}'>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="{{ $tableId }}">
            {{-- thead は変更なし --}}
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th
                        class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[200px]">
                        時間記録</th>
                    <th
                        class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[250px] sm:min-w-[300px]">
                        工程名</th>
                    <th class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                        style="min-width:120px;">担当者</th>
                    <th
                        class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        開始日時</th>
                    <th
                        class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        工数</th>
                    <th
                        class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        操作</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($tasksToList->where('is_folder', false) as $task)
                    @include('projects.partials.task-table-row', ['task' => $task, 'assigneeOptions' => $assigneeOptions ?? []])
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            このキャラクターの工程はありません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@can('create', [App\Models\Task::class, $project])
    <div class="mx-6 my-2 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h6 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-3">
            <i class="fas fa-plus-circle mr-2 text-green-500"></i>このキャラクターに工程を新規追加
        </h6>
        <form id="task-form-{{ $formIdentifier }}" class="space-y-4 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg"
            onsubmit="return false;">
            @csrf
            <input type="hidden" name="character_id" value="{{ $character->id }}">

            <div>
                <x-input-label for="task-name-{{ $formIdentifier }}" value="工程名" :required="true" />
                <x-text-input id="task-name-{{ $formIdentifier }}" name="name" type="text" class="mt-1 block w-full"
                    placeholder="例: パターン作成" required />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="task-start-date-{{ $formIdentifier }}" value="開始日時" :required="true" />
                    <x-text-input id="task-start-date-{{ $formIdentifier }}" name="start_date" type="datetime-local"
                        class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="task-end-date-{{ $formIdentifier }}" value="終了日時" :required="true" />
                    <x-text-input id="task-end-date-{{ $formIdentifier }}" name="end_date" type="datetime-local"
                        class="mt-1 block w-full" required />
                </div>
            </div>
            {{-- ▼▼▼【ここから修正】▼▼▼ --}}
            <div>
                <x-input-label for="task-duration-value-{{ $formIdentifier }}" value="予定工数" :required="true" />
                <div class="flex items-center mt-1 space-x-2">
                    <x-text-input id="task-duration-value-{{ $formIdentifier }}" name="duration_value" type="number"
                        class="block w-1/2" min="0" step="any" placeholder="例: 8" required />
                    <select name="duration_unit" id="task-duration-unit-{{ $formIdentifier }}"
                        class="block w-1/2 mt-0 form-select rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                        <option value="minutes">分</option>
                        <option value="hours">時間</option>
                        <option value="days">日</option>
                    </select>
                </div>
            </div>
            {{-- ▲▲▲【ここまで修正】▲▲▲ --}}
            <div>
                <x-input-label for="task-description-{{ $formIdentifier }}" value="メモ" />
                <x-textarea-input id="task-description-{{ $formIdentifier }}" name="description" class="mt-1 block w-full"
                    rows="2"></x-textarea-input>
            </div>
            <div class="flex justify-end pt-2">
                <x-primary-button type="submit">
                    <i class="fas fa-plus mr-2"></i>追加する
                </x-primary-button>
            </div>
            <div id="task-form-errors-{{ $formIdentifier }}" class="text-sm text-red-600 space-y-1 mt-2"></div>
        </form>
    </div>
@endcan
@can('create', App\Models\Task::class)
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('projects.tasks.create', ['project' => $project, 'character_id_for_new_task' => $character->id]) }}"
            class="inline-flex items-center px-3 py-2 bg-sky-500 hover:bg-sky-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
            <i class="fas fa-plus mr-1"></i> このキャラクターに工程を追加
        </a>
    </div>
@endcan