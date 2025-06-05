{{-- resources/views/projects/partials/character-tasks-tailwind.blade.php --}}
@php
    // ProjectController@show で $character->sorted_tasks に階層ソート済みのタスクが格納されていることを期待
    // もし $character->tasks を直接使用する場合は、このパーシャル内でソートするか、
    // ProjectController側で $character->tasks 自体をソート済みのものに置き換える。
    // ここでは $character->sorted_tasks が存在すればそれを使用し、なければ $character->tasks をフォールバックとする。
    $tasksForCharacterDisplay = $character->sorted_tasks ?? $character->tasks;
    if (!($tasksForCharacterDisplay instanceof \Illuminate\Support\Collection)) {
        $tasksForCharacterDisplay = collect($tasksForCharacterDisplay); // 念のためコレクションに変換
    }
@endphp

@if($tasksForCharacterDisplay->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">このキャラクターの工程はありません。</p>
@else
    <div class="overflow-x-auto max-h-[60vh] overflow-y-auto">
        @include('projects.partials.projects-task-table', [
            'tasksToList' => $tasksForCharacterDisplay, // ★ 階層ソート済みのキャラクタータスク
            'tableId' => 'character-tasks-table-' . $character->id, // ★ キャラクターごとに一意なテーブルID
            'projectForTable' => $project, // 親のプロジェクト情報
            // 'isCharacterTaskView' => true, // このフラグは projects-task-table 側でキャラクター列の表示制御に使われる想定
            'character' => $character, // ★ 現在のキャラクターオブジェクトを渡す
            'isFolderView' => $isFolderView ?? false, // 親ビューから引き継ぐか、キャラクタータスクビュー特有の値を設定
            'isMilestoneView' => $isMilestoneView ?? false, // 同上
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