@extends('layouts.app')
@section('title', '作業依頼一覧')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
                        tab: 'assigned',
                        filtersOpen: {{ count(array_filter(request()->except('page', 'tab'))) > 0 ? 'true' : 'false' }}
                    }">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">作業依頼一覧</h1>
            <div class="flex items-center space-x-2">
                <x-secondary-button @click="filtersOpen = !filtersOpen">
                    <i class="fas fa-filter mr-1"></i>フィルター
                    <span x-show="filtersOpen" style="display:none;"><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                    <span x-show="!filtersOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
                </x-secondary-button>
                <x-primary-button as="a" href="{{ route('requests.create') }}">
                    <i class="fas fa-plus mr-2"></i>新規依頼を作成
                </x-primary-button>
            </div>
        </div>

        {{-- ▼▼▼【ここから追加】フィルターパネル ▼▼▼ --}}
        <div x-show="filtersOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
            <form action="{{ route('requests.index') }}" method="GET">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="filter_category_id" value="カテゴリ" />
                        <x-select-input id="filter_category_id" name="category_id" class="mt-1 block w-full"
                            :emptyOptionText="'すべてのカテゴリ'">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @if(request('category_id') == $category->id) selected @endif>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="filter_date" value="日付" />
                        <x-text-input id="filter_date" name="date" type="date" class="mt-1 block w-full"
                            :value="request('date')" />
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end space-x-3">
                    <a href="{{ route('requests.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                        リセット
                    </a>
                    <x-primary-button type="submit">
                        <i class="fas fa-search mr-2"></i> 絞り込む
                    </x-primary-button>
                </div>
            </form>
        </div>
        {{-- ▲▲▲【追加ここまで】▲▲▲ --}}

        <div class="p-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500"
            role="alert">
            <i class="fas fa-info-circle mr-1"></i>
            各項目の開始・終了日時を設定すると、カレンダーやホーム画面にタスクが表示されます。
        </div>

        {{-- タブ切り替え --}}
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6 mt-4">
            <nav class="-mb-px flex space-x-6 sm:space-x-8 overflow-x-auto" aria-label="Tabs">
                <button @click="tab = 'assigned'"
                    :class="tab === 'assigned' ? 'font-semibold border-blue-600 text-blue-600 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500'"
                    class="py-4 px-1 inline-flex items-center gap-x-2 border-b-2 font-medium text-sm whitespace-nowrap focus:outline-none">
                    受信した依頼
                    @if($pendingAssigned->count() > 0)
                        <span
                            class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">{{ $pendingAssigned->count() }}</span>
                    @endif
                </button>
                <button @click="tab = 'personal'"
                    :class="tab === 'personal' ? 'font-semibold border-blue-600 text-blue-600 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500'"
                    class="py-4 px-1 inline-flex items-center gap-x-2 border-b-2 font-medium text-sm whitespace-nowrap focus:outline-none">
                    自分用
                    @if($pendingPersonal->count() > 0)
                        <span
                            class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">{{ $pendingPersonal->count() }}</span>
                    @endif
                </button>
                <button @click="tab = 'created'"
                    :class="tab === 'created' ? 'font-semibold border-blue-600 text-blue-600 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500'"
                    class="py-4 px-1 inline-flex items-center gap-x-2 border-b-2 font-medium text-sm whitespace-nowrap focus:outline-none">
                    送信した依頼
                </button>
            </nav>
        </div>

        {{-- 受信依頼パネル --}}
        <div x-show="tab === 'assigned'" class="space-y-8">
            @include('requests.partials.request-list', ['title' => '未完了の受信依頼', 'requests' => $pendingAssigned, 'isEmptyMessage' => '未完了の受信依頼はありません。'])
            @include('requests.partials.request-list', ['title' => '完了済みの受信依頼', 'requests' => $completedAssigned, 'isEmptyMessage' => '完了済みの受信依頼はありません。', 'collapsible' => true])
        </div>

        {{-- 自分用パネル --}}
        <div x-show="tab === 'personal'" x-cloak class="space-y-8">
            @include('requests.partials.request-list', ['title' => '未完了の自分用タスク', 'requests' => $pendingPersonal, 'isEmptyMessage' => '未完了のタスクはありません。'])
            @include('requests.partials.request-list', ['title' => '完了済みの自分用タスク', 'requests' => $completedPersonal, 'isEmptyMessage' => '完了済みのタスクはありません。', 'collapsible' => true])
        </div>

        {{-- 送信依頼パネル --}}
        <div x-show="tab === 'created'" x-cloak class="space-y-8">
            @include('requests.partials.request-list', ['title' => '未完了の送信依頼', 'requests' => $pendingCreated, 'isEmptyMessage' => '未完了の送信依頼はありません。'])
            @include('requests.partials.request-list', ['title' => '完了済みの送信依頼', 'requests' => $completedCreated, 'isEmptyMessage' => '完了済みの送信依頼はありません。', 'collapsible' => true])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    @include('requests.partials.request-card-scripts')
@endpush