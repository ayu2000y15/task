@extends('layouts.tool')

@section('title', '購読者を追加 - ' . $emailList->name)

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.email-lists.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">メールリスト管理</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.email-lists.show', $emailList) }}"
        class="hover:text-blue-600 dark:hover:text-blue-400 truncate"
        title="{{ $emailList->name }}">{{ Str::limit($emailList->name, 20) }}</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">購読者を追加</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            {{-- ... (ページタイトルと戻るボタンは変更なし) ... --}}
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                        購読者を追加: <span class="font-normal text-xl">{{ $emailList->name }}</span>
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        管理連絡先の一覧から選択して、このメールリストに購読者として追加します。<br>(既にリストに登録済みの連絡先、または「連絡不要」「アーカイブ済」の連絡先は表示されません)</p>
                </div>
                <x-primary-button as="a" href="{{ route('tools.sales.email-lists.show', $emailList) }}">
                    <i class="fas fa-arrow-left mr-2"></i> リスト詳細へ戻る
                </x-primary-button>
            </div>
        </div>

        <div
            x-data="{ filtersOpen: {{ request()->hasAny(['filter_company_name', 'filter_postal_code', 'filter_address', 'filter_establishment_date_from', 'filter_establishment_date_to', 'filter_industry', 'filter_notes', 'filter_status', 'keyword']) ? 'true' : 'false' }} }">
            <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                {{-- ... (フィルターフォームは変更なし、ただしactionのルートパラメータ $emailList を渡す) ... --}}
                <form action="{{ route('tools.sales.email-lists.subscribers.create', $emailList) }}" method="GET"
                    class="flex flex-col sm:flex-row gap-3 items-center">
                    <div class="flex-grow relative">
                        <x-input-label for="keyword_select" value="キーワード検索" class="sr-only" />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <x-text-input type="search" name="keyword" id="keyword_select" placeholder="メール、名前、会社名..."
                            class="w-full pl-10" :value="$filterValues['keyword'] ?? ''" />
                    </div>
                    <x-primary-button type="submit" class="h-10">検索</x-primary-button>
                    @if(request('keyword'))
                        <x-secondary-button as="a"
                            href="{{ route('tools.sales.email-lists.subscribers.create', array_merge(['emailList' => $emailList->id], request()->except(['keyword', 'page']))) }}"
                            class="h-10">
                            ｷｰﾜｰﾄﾞｸﾘｱ
                        </x-secondary-button>
                    @endif
                    <x-secondary-button type="button" @click="filtersOpen = !filtersOpen" class="ml-auto h-10">
                        <i class="fas fa-filter mr-1"></i><span class="hidden sm:inline">詳細フィルター</span>
                        <span x-show="filtersOpen" style="display:none;"><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                        <span x-show="!filtersOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
                    </x-secondary-button>
                </form>

                <div x-show="filtersOpen" x-collapse class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <form action="{{ route('tools.sales.email-lists.subscribers.create', $emailList) }}" method="GET">
                        @if(request('keyword'))
                            <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                        @endif
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="filter_company_name_sub" value="会社名" />
                                <x-text-input id="filter_company_name_sub" name="filter_company_name" type="text"
                                    class="mt-1 block w-full" :value="$filterValues['filter_company_name'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="filter_postal_code_sub" value="郵便番号" />
                                <x-text-input id="filter_postal_code_sub" name="filter_postal_code" type="text"
                                    class="mt-1 block w-full" :value="$filterValues['filter_postal_code'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="filter_address_sub" value="住所" />
                                <x-text-input id="filter_address_sub" name="filter_address" type="text"
                                    class="mt-1 block w-full" :value="$filterValues['filter_address'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="filter_industry_sub" value="業種" />
                                <x-text-input id="filter_industry_sub" name="filter_industry" type="text"
                                    class="mt-1 block w-full" :value="$filterValues['filter_industry'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="filter_establishment_date_from_sub" value="設立年月日 (From)" />
                                <x-text-input id="filter_establishment_date_from_sub" name="filter_establishment_date_from"
                                    type="date" class="mt-1 block w-full"
                                    :value="$filterValues['filter_establishment_date_from'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="filter_establishment_date_to_sub" value="設立年月日 (To)" />
                                <x-text-input id="filter_establishment_date_to_sub" name="filter_establishment_date_to"
                                    type="date" class="mt-1 block w-full"
                                    :value="$filterValues['filter_establishment_date_to'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="filter_notes_sub" value="備考" />
                                <x-text-input id="filter_notes_sub" name="filter_notes" type="text"
                                    class="mt-1 block w-full" :value="$filterValues['filter_notes'] ?? ''" />
                            </div>
                            <input type="hidden" name="filter_status" value="active">

                        </div>
                        <div class="mt-6 flex items-center justify-end space-x-3">
                            <x-secondary-button as="a"
                                href="{{ route('tools.sales.email-lists.subscribers.create', array_merge(['emailList' => $emailList->id], request()->only('keyword'))) }}">
                                フィルターリセット
                            </x-secondary-button>
                            <x-primary-button type="submit">
                                <i class="fas fa-filter mr-2"></i> 絞り込む
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <form action="{{ route('tools.sales.email-lists.subscribers.store', $emailList) }}" method="POST"
            id="addSubscribersForm">
            @csrf
            {{-- ★★★ フィルター条件をhidden inputとしてフォームに含める ★★★ --}}
            @if(request('keyword'))<input type="hidden" name="keyword" value="{{ request('keyword') }}">@endif
            @if(request('filter_company_name'))<input type="hidden" name="filter_company_name"
            value="{{ request('filter_company_name') }}">@endif
            @if(request('filter_postal_code'))<input type="hidden" name="filter_postal_code"
            value="{{ request('filter_postal_code') }}">@endif
            @if(request('filter_address'))<input type="hidden" name="filter_address"
            value="{{ request('filter_address') }}">@endif
            @if(request('filter_establishment_date_from'))<input type="hidden" name="filter_establishment_date_from"
            value="{{ request('filter_establishment_date_from') }}">@endif
            @if(request('filter_establishment_date_to'))<input type="hidden" name="filter_establishment_date_to"
            value="{{ request('filter_establishment_date_to') }}">@endif
            @if(request('filter_industry'))<input type="hidden" name="filter_industry"
            value="{{ request('filter_industry') }}">@endif
            @if(request('filter_notes'))<input type="hidden" name="filter_notes"
            value="{{ request('filter_notes') }}">@endif
            {{-- filter_status は 'active' 固定か、もしユーザーが選択できるようにする場合はそれもhiddenで渡す --}}
            <input type="hidden" name="filter_status" value="{{ request('filter_status', 'active') }}">


            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                <div
                    class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-2">
                    <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                        管理連絡先を選択 ({{ $managedContacts->total() }}件中 {{ $managedContacts->count() }}件表示)
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        @if($managedContacts->total() > 0) {{-- フィルター結果が1件以上あれば表示 --}}
                            <x-primary-button type="submit" name="add_all_filtered_action" value="1"
                                onclick="return confirm('現在のフィルター条件に一致する全ての連絡先 ({{ $managedContacts->total() }}件) を購読者として追加します。よろしいですか？\n（処理に時間がかかる場合があります。）');">
                                <i class="fas fa-layer-group mr-2"></i> フィルター結果の全件を追加
                            </x-primary-button>
                        @endif
                        @if($managedContacts->count() > 0) {{-- 現在のページに表示されている連絡先が1件以上あれば表示 --}}
                            <x-primary-button type="submit" name="add_selected_action" value="1"> {{-- name属性で区別 --}}
                                <i class="fas fa-user-plus mr-2"></i> チェックした連絡先を追加
                            </x-primary-button>
                        @else
                            @if($managedContacts->total() === 0 && !request()->has('keyword') && !request()->hasAny(['filter_company_name', 'filter_postal_code', 'filter_address', 'filter_establishment_date_from', 'filter_establishment_date_to', 'filter_industry', 'filter_notes', 'filter_status']))
                                <p class="text-sm text-gray-500 dark:text-gray-400 py-2">追加可能な有効な連絡先がありません。</p>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="overflow-x-auto max-h-[60vh]">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                            <tr>
                                <th scope="col"
                                    class="px-4 py-3 text-center w-12 sticky left-0 z-20 bg-gray-50 dark:bg-gray-700">
                                    @if($managedContacts->count() > 0)
                                        <x-checkbox-input id="select_all_contacts_sub" name="select_all_contacts"
                                            title="このページの連絡先をすべて選択/解除"
                                            onchange="toggleAllCheckboxes(this, 'managed_contact_ids[]');" />
                                    @endif
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    メールアドレス</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    業種</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    会社名</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    連絡先ステータス</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($managedContacts as $contact)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 group"> {{-- group クラスを追加 --}}
                                    <td
                                        class="px-4 py-2 text-center sticky left-0 z-0 bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-700/50">
                                        <x-checkbox-input name="managed_contact_ids[]" value="{{ $contact->id }}"
                                            class="contact-checkbox" />
                                    </td>
                                    <td
                                        class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $contact->email }}
                                    </td>
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $contact->industry ?? '-' }}</td>
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ Str::limit($contact->company_name ?? '-', 30) }}</td>
                                    <td class="px-6 py-2 whitespace-nowrap text-sm">
                                        @php
                                            $statusCfg = \App\Models\ManagedContact::getStatusConfig($contact->status);
                                        @endphp
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusCfg['class'] }}">
                                            {{ $statusCfg['label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                {{-- ... (変更なし) ... --}}
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        @if(count(array_filter($filterValues ?? [])) > 0 && empty(array_filter($filterValues)['keyword'] ?? null) && count(array_filter(Arr::except($filterValues ?? [], 'keyword'))) > 0)
                                            フィルター条件に一致する追加可能な管理連絡先は見つかりませんでした。
                                        @elseif(isset($filterValues['keyword']) && $filterValues['keyword'] !== '')
                                            検索条件「{{ $filterValues['keyword'] }}」に一致する追加可能な管理連絡先は見つかりませんでした。
                                        @else
                                            追加可能な管理連絡先は現在登録されていません。<br>
                                            (既にこのメールリストに登録済みの連絡先、またはステータスが「有効」でない連絡先は表示されません)
                                        @endif
                                        <br>
                                        <a href="{{ route('tools.sales.managed-contacts.create') }}"
                                            class="mt-2 inline-block text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 hover:underline">
                                            <i class="fas fa-plus mr-1"></i> 新しい管理連絡先を登録する
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($managedContacts->hasPages() && $managedContacts->count() > 0)
                    <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                        {{ $managedContacts->appends(request()->query())->links() }}
                    </div>
                @endif
                @if($managedContacts->count() > 0) {{-- 現在のページに表示されている連絡先が1件以上あれば表示 --}}
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap justify-end gap-2">
                        <x-primary-button type="submit" name="add_all_filtered_action" value="1"
                            onclick="return confirm('現在のフィルター条件に一致する全ての連絡先 ({{ $managedContacts->total() }}件) を購読者として追加します。よろしいですか？\n（処理に時間がかかる場合があります。）');"
                            :disabled="$managedContacts->total() === 0" {{-- フィルター結果が0件なら無効化 --}}>
                            <i class="fas fa-layer-group mr-2"></i> フィルター結果の全件を追加
                        </x-primary-button>
                        <x-primary-button type="submit" name="add_selected_action" value="1">
                            <i class="fas fa-user-plus mr-2"></i> チェックした連絡先を追加
                        </x-primary-button>
                    </div>
                @endif
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    {{-- ... (JavaScriptは変更なし) ... --}}
    <script>
        function toggleAllCheckboxes(source, name) {
            const checkboxes = document.querySelectorAll(`input[type="checkbox"][name="${name}"]`);
            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const mainCheckboxSub = document.getElementById('select_all_contacts_sub');
            const itemCheckboxesSub = document.querySelectorAll('input[type="checkbox"].contact-checkbox');

            if (mainCheckboxSub && itemCheckboxesSub.length > 0) {
                itemCheckboxesSub.forEach(checkbox => {
                    checkbox.addEventListener('change', function () {
                        let allChecked = true;
                        itemCheckboxesSub.forEach(cb => {
                            if (!cb.checked) {
                                allChecked = false;
                            }
                        });
                        mainCheckboxSub.checked = allChecked;
                    });
                });
                let initialAllChecked = true;
                itemCheckboxesSub.forEach(cb => {
                    if (!cb.checked) initialAllChecked = false;
                });
                if (itemCheckboxesSub.length > 0) mainCheckboxSub.checked = initialAllChecked; else mainCheckboxSub.checked = false;
            }
        });
    </script>
@endpush