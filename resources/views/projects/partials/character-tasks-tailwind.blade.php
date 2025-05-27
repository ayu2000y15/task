@if($tasks->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">このキャラクターの工程はありません。</p>
@else
    <div class="overflow-x-auto max-h-[60vh] overflow-y-auto">
        @include('tasks.partials.task-table', [
            'tasksToList' => $tasks,
            'tableId' => 'character-tasks-table-' . $character->id,
            'projectForTable' => $project, // task-tableがプロジェクト情報を必要とする場合
            'isCharacterTaskView' => true // 必要に応じてパーシャル側で利用するフラグ
        ])

               </div>
@endif
@can('create', App\Models\Task::class)
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('projects.tasks.create', ['project' => $project, 'character_id_for_new_task' => $character->id]) }}" class="inline-flex items-center px-3 py-2 bg-sky-500 hover:bg-sky-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
            <i class="fas fa-plus mr-1"></i> このキャラクターに工程を追加
        </a>
    </div>
@endcan