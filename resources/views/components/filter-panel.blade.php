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
                    {{ !isset($filters['project_id']) && $allCharacters->isEmpty() ? 'disabled' : '' }}>
                    <option value="">すべて</option>
                    @if(isset($filters['project_id']) || !$allCharacters->isEmpty())
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
            <div>
                <label for="assignee_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">担当者</label>
                <select id="assignee_filter" name="assignee"
                    class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500">
                    <option value="">すべて</option>
                    @foreach($allAssignees as $assignee)
                        <option value="{{ $assignee }}" @if(isset($filters['assignee']) && $filters['assignee'] == $assignee)
                        selected @endif>
                            {{ $assignee }}
                        </option>
                    @endforeach
                </select>
            </div>
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

            @if($showDueDateFilter)
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

            @if($showDateRangeFilter)
                <div>
                    <label for="start_date_filter"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">表示開始日</label>
                    <input type="date" id="start_date_filter" name="start_date" value="{{ $filters['start_date'] ?? '' }}"
                        class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500">
                </div>
                <div>
                    <label for="end_date_filter"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">表示終了日</label>
                    <input type="date" id="end_date_filter" name="end_date" value="{{ $filters['end_date'] ?? '' }}"
                        class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500">
                </div>
            @endif
        </div>
        <div class="mt-6 flex items-center justify-end space-x-3">
            <a href="{{ $action }}"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                リセット
            </a>
            <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-800 focus:outline-none focus:border-blue-800 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                フィルター適用
            </button>
        </div>
    </form>
</div>