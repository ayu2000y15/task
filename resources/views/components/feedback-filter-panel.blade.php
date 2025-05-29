<div class="bg-gray-50 dark:bg-gray-700 p-4 md:p-6 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
    <div class="flex justify-between items-center mb-4">
        <h6 class="text-md font-semibold text-gray-700 dark:text-gray-200">フィルターオプション</h6>
        <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="フィルターを閉じる"
            x-on:click="filtersOpen = false"> <i class="fas fa-times"></i>
        </button>
    </div>
    <form action="{{ $action }}" method="GET">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"> {{-- xlを追加して4列に --}}
            <div>
                <label for="submitter_name_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">送信者</label>
                <input type="text" id="submitter_name_filter" name="submitter_name"
                    value="{{ $filters['submitter_name'] ?? '' }}"
                    class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500"
                    placeholder="送信者名で検索">
            </div>

            <div>
                <label for="category_id_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">カテゴリ</label>
                <select id="category_id_filter" name="category_id"
                    class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500">
                    <option value="">すべて</option>
                    @foreach($feedbackCategories as $id => $name)
                        <option value="{{ $id }}" @if(isset($filters['category_id']) && $filters['category_id'] == $id)
                        selected @endif>
                            {{ $name }}
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

            {{-- ★★★ 優先度フィルターを追加 ★★★ --}}
            <div>
                <label for="priority_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">優先度</label>
                <select id="priority_filter" name="priority"
                    class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500">
                    <option value="">すべて</option>
                    @foreach($priorityOptions as $value => $label)
                        <option value="{{ $value }}" @if(isset($filters['priority']) && $filters['priority'] == $value)
                        selected @endif>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            {{-- ★★★ ここまで ★★★ --}}

            <div>
                <label for="assignee_text_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">担当者</label>
                <input type="text" id="assignee_text_filter" name="assignee_text"
                    value="{{ $filters['assignee_text'] ?? '' }}"
                    class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500"
                    placeholder="担当者名で検索">
            </div>

            <div class="lg:col-span-1">
                <label for="keyword_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">キーワード検索</label>
                <input type="text" id="keyword_filter" name="keyword" value="{{ $filters['keyword'] ?? '' }}"
                    class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500"
                    placeholder="タイトル, 内容, メモ等">
            </div>

            <div>
                <label for="created_at_from_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">送信日時 (From)</label>
                <input type="date" id="created_at_from_filter" name="created_at_from"
                    value="{{ $filters['created_at_from'] ?? '' }}"
                    class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500">
            </div>

            <div>
                <label for="created_at_to_filter"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">送信日時 (To)</label>
                <input type="date" id="created_at_to_filter" name="created_at_to"
                    value="{{ $filters['created_at_to'] ?? '' }}"
                    class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-indigo-500">
            </div>
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