<div class="bg-gray-50 dark:bg-gray-700 p-4 md:p-6 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
    <div class="flex justify-between items-center mb-4">
        <h6 class="text-md font-semibold text-gray-700 dark:text-gray-200">フィルターオプション</h6>
        <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="フィルターを閉じる"
            x-on:click="filtersOpen = false"> <i class="fas fa-times"></i>
        </button>
    </div>
    <form action="{{ $action }}" method="GET">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="project_id_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">衣装案件</label>
                <select id="project_id_filter" name="project_id"
                    class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500">
                    <option value="">すべて</option>
                    @foreach($allProjects as $project)
                        <option value="{{ $project->id }}" @if(isset($filters['project_id']) && $filters['project_id'] == $project->id) selected @endif>
                            {{ $project->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="character_id_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">キャラクター</label>
                <select id="character_id_filter" name="character_id"
                    class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500"
                    {{-- disabled属性の条件式を修正 --}} {{ (!isset($filters['project_id']) || empty($filters['project_id'])) && (!isset($allCharacters) || $allCharacters->isEmpty()) ? 'disabled' : '' }}>
                    <option value="">すべて</option>
                    @if((isset($filters['project_id']) && !empty($filters['project_id'])) || (isset($allCharacters) && !$allCharacters->isEmpty()))
                        <option value="none" @if(isset($filters['character_id']) && $filters['character_id'] == 'none')
                        selected @endif>案件全体(キャラクター未所属)</option>
                        @foreach($allCharacters as $character)
                            <option value="{{ $character->id }}" @if(isset($filters['character_id']) && $filters['character_id'] == $character->id) selected @endif>
                                {{ $character->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- ▼▼▼【ここから変更】担当者フィルターの修正 ▼▼▼ --}}
            <div>
                <label for="assignee_id_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">担当者</label>
                {{-- name を "assignee_id" に変更 --}}
                <select id="assignee_id_filter" name="assignee_id"
                    class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500">
                    <option value="">すべて</option>
                    {{-- $allAssignees は [id => name] の連想配列なので、キーと値でループ --}}
                    @foreach($allAssignees as $id => $name)
                        {{-- value に id を、selected の判定に assignee_id を使用 --}}
                        <option value="{{ $id }}" @if(isset($filters['assignee_id']) && $filters['assignee_id'] == $id)
                        selected @endif>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            {{-- ▲▲▲【変更】ここまで ▲▲▲ --}}

            <div>
                <label for="status_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ステータス</label>
                <select id="status_filter" name="status"
                    class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500">
                    <option value="">すべて</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @if(isset($filters['status']) && $filters['status'] == $value) selected
                        @endif>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="search_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">工程名検索</label>
                <input type="text" id="search_filter" name="search" value="{{ $filters['search'] ?? '' }}"
                    class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500"
                    placeholder="キーワード入力">
            </div>

            @if($showDueDateFilter ?? false)
                <div>
                    <label for="due_date_filter"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">期限</label>
                    <select id="due_date_filter" name="due_date"
                        class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500">
                        <option value="">すべて</option>
                        <option value="overdue" @if(isset($filters['due_date']) && $filters['due_date'] == 'overdue') selected
                        @endif>期限切れ</option>
                        <option value="today" @if(isset($filters['due_date']) && $filters['due_date'] == 'today') selected
                        @endif>今日</option>
                        <option value="tomorrow" @if(isset($filters['due_date']) && $filters['due_date'] == 'tomorrow')
                        selected @endif>明日</option>
                        <option value="this_week" @if(isset($filters['due_date']) && $filters['due_date'] == 'this_week')
                        selected @endif>今週</option>
                        <option value="next_week" @if(isset($filters['due_date']) && $filters['due_date'] == 'next_week')
                        selected @endif>来週</option>
                    </select>
                </div>
            @endif

            @if($showDateRangeFilter ?? false)
                {{-- ... 日付範囲フィルター（変更なし） ... --}}
            @endif
        </div>
        <div class="mt-6 flex items-center justify-end space-x-3">
            <a href="{{ $action }}"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                リセット
            </a>
            <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-800 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                フィルター適用
            </button>
        </div>
    </form>
</div>