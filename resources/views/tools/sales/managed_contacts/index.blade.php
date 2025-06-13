@extends('layouts.tool')

@section('title', '管理連絡先一覧')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">管理連絡先一覧</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- ページタイトルと主要ボタン --}}
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">管理連絡先一覧</h1>
            <div class="flex-shrink-0">
                <x-primary-button as="a" href="{{ route('tools.sales.managed-contacts.create') }}" class="h-10">
                    <i class="fas fa-plus mr-1"></i><span class="hidden sm:inline">新規連絡先追加</span>
                </x-primary-button>
            </div>
        </div>

        @if (session('info'))
            <div class="mb-4 p-4 bg-blue-100 border-l-4 border-blue-500 text-blue-700 dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-600 rounded-md shadow-md"
                role="alert">
                <div class="flex">
                    <div class="py-1"><i class="fas fa-info-circle fa-lg mr-3 text-blue-500 dark:text-blue-400"></i></div>
                    <div>
                        <p class="font-bold">お知らせ</p>
                        <p class="text-sm">{{ session('info') }}</p>
                    </div>
                </div>
            </div>
        @endif
        @if(isset($csvImportInterruptedMessage) && $csvImportInterruptedMessage)
            <div class="mb-4 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 dark:bg-yellow-700/30 dark:text-yellow-200 dark:border-yellow-600 rounded-md shadow-md"
                role="alert">
                <div class="flex">
                    <div class="py-1"><i
                            class="fas fa-exclamation-triangle fa-lg mr-3 text-yellow-500 dark:text-yellow-400"></i></div>
                    <div>
                        <p class="font-bold">前回の処理に関する通知</p>
                        <p class="text-sm">{{ $csvImportInterruptedMessage }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- フィルターセクション (既存のコード) --}}
        <div
            x-data="{ filtersOpen: {{ request()->hasAny(['filter_company_name', 'filter_postal_code', 'filter_address', 'filter_establishment_date_from', 'filter_establishment_date_to', 'filter_industry', 'filter_notes', 'filter_status', 'keyword']) ? 'true' : 'false' }} }">
            <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <form action="{{ route('tools.sales.managed-contacts.index') }}" method="GET"
                    class="flex flex-col sm:flex-row gap-3 items-center">
                    <div class="flex-grow relative">
                        <x-input-label for="keyword_search_main" value="キーワード検索" class="sr-only" />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <x-text-input type="search" name="keyword" id="keyword_search_main" placeholder="メール、名前、会社名..."
                            class="w-full pl-10" :value="$filterValues['keyword'] ?? ''" />
                    </div>
                    <x-primary-button type="submit" class="h-10 w-full sm:w-auto">検索</x-primary-button>
                    @if(request('keyword'))
                        <x-secondary-button as="a"
                            href="{{ route('tools.sales.managed-contacts.index', array_merge(request()->except(['keyword', 'page']), ['filtersOpen' => request()->hasAny(['filter_company_name', 'filter_postal_code', 'filter_address', 'filter_establishment_date_from', 'filter_establishment_date_to', 'filter_industry', 'filter_notes', 'filter_status']) ? 'true' : 'false'])) }}"
                            class="h-10 w-full sm:w-auto">
                            ｷｰﾜｰﾄﾞｸﾘｱ
                        </x-secondary-button>
                    @endif
                    <x-secondary-button type="button" @click="filtersOpen = !filtersOpen"
                        class="ml-auto h-10 w-full sm:w-auto">
                        <i class="fas fa-filter mr-1"></i><span class="hidden sm:inline">詳細フィルター</span>
                        <span x-show="filtersOpen" style="display:none;"><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                        <span x-show="!filtersOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
                    </x-secondary-button>
                </form>

                <div x-show="filtersOpen" x-collapse class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <form action="{{ route('tools.sales.managed-contacts.index') }}" method="GET">
                        @if(request('keyword'))
                            <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                        @endif
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            @php
                                // プルダウンの選択肢を定義
                                $blankOptions = [
                                    '' => '指定なし',
                                    'is_null' => '空欄のみ',
                                    'is_not_null' => '空欄以外',
                                ];
                            @endphp

                            {{-- 会社名 --}}
                            <div>
                                <x-input-label for="filter_company_name" value="会社名" />
                                <x-text-input id="filter_company_name" name="filter_company_name" type="text"
                                    class="mt-1 block w-full" :value="$filterValues['filter_company_name'] ?? ''"
                                    placeholder="株式会社〇〇" />
                                <x-select-input id="blank_filter_company_name" name="blank_filter_company_name" class="mt-1 block w-full"
                                    :options="$blankOptions" :selected="$filterValues['blank_filter_company_name'] ?? ''" />
                            </div>

                            {{-- 郵便番号 --}}
                            <div>
                                <x-input-label for="filter_postal_code" value="郵便番号" />
                                <x-text-input id="filter_postal_code" name="filter_postal_code" type="text"
                                    class="mt-1 block w-full" :value="$filterValues['filter_postal_code'] ?? ''"
                                    placeholder="123-4567" />
                                <x-select-input id="blank_filter_postal_code" name="blank_filter_postal_code" class="mt-1 block w-full"
                                    :options="$blankOptions" :selected="$filterValues['blank_filter_postal_code'] ?? ''" />
                            </div>

                            {{-- 住所 --}}
                            <div>
                                <x-input-label for="filter_address" value="住所" />
                                <x-text-input id="filter_address" name="filter_address" type="text"
                                    class="mt-1 block w-full" :value="$filterValues['filter_address'] ?? ''"
                                    placeholder="東京都..." />
                                <x-select-input id="blank_filter_address" name="blank_filter_address" class="mt-1 block w-full"
                                    :options="$blankOptions" :selected="$filterValues['blank_filter_address'] ?? ''" />
                            </div>

                            {{-- 業種 --}}
                            <div>
                                <x-input-label for="filter_industry" value="業種" />
                                <x-text-input id="filter_industry" name="filter_industry" type="text"
                                    class="mt-1 block w-full" :value="$filterValues['filter_industry'] ?? ''"
                                    placeholder="ITサービス" />
                                <x-select-input id="blank_filter_industry" name="blank_filter_industry" class="mt-1 block w-full"
                                    :options="$blankOptions" :selected="$filterValues['blank_filter_industry'] ?? ''" />
                            </div>

                            {{-- 備考 --}}
                            <div>
                                <x-input-label for="filter_notes" value="備考" />
                                <x-text-input id="filter_notes" name="filter_notes" type="text" class="mt-1 block w-full"
                                    :value="$filterValues['filter_notes'] ?? ''" />
                                <x-select-input id="blank_filter_notes" name="blank_filter_notes" class="mt-1 block w-full"
                                    :options="$blankOptions" :selected="$filterValues['blank_filter_notes'] ?? ''" />
                            </div>

                            {{-- (日付とステータスのフィルターは変更なし) --}}
                            <div>
                                <x-input-label for="filter_establishment_date_from" value="設立年月日 (From)" />
                                <x-text-input id="filter_establishment_date_from" name="filter_establishment_date_from"
                                    type="date" class="mt-1 block w-full"
                                    :value="$filterValues['filter_establishment_date_from'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="filter_establishment_date_to" value="設立年月日 (To)" />
                                <x-text-input id="filter_establishment_date_to" name="filter_establishment_date_to"
                                    type="date" class="mt-1 block w-full"
                                    :value="$filterValues['filter_establishment_date_to'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label for="filter_status" value="ステータス" />
                                <x-select-input id="filter_status" name="filter_status" class="mt-1 block w-full"
                                    :options="$statusOptions" :selected="$filterValues['filter_status'] ?? ''"
                                    emptyOptionText="すべて" />
                            </div>
                        </div>
                        <div class="mt-6 flex items-center justify-end space-x-3">
                            <x-secondary-button as="a"
                                href="{{ route('tools.sales.managed-contacts.index', request()->only('keyword')) }}">
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

        {{-- CSVインポートセクション --}}
        <div class="my-8 bg-white dark:bg-gray-800 shadow-md rounded-lg" x-data="{ csvImportOpen: false }">
            <h2 @click="csvImportOpen = !csvImportOpen"
                class="text-lg font-semibold text-gray-700 dark:text-gray-200 p-4 sm:p-6 cursor-pointer flex justify-between items-center">
                <span>
                    <i class="fas fa-file-csv mr-2 text-green-600 dark:text-green-400"></i> CSVファイルから連絡先をインポート
                </span>
                <span>
                    <i class="fas fa-chevron-down transition-transform" :class="{'rotate-180': csvImportOpen}"></i>
                </span>
            </h2>
            <div x-show="csvImportOpen" x-collapse class="p-4 sm:p-6 border-t dark:border-gray-700">
                <form action="{{ route('tools.sales.managed-contacts.importCsv') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="csv_file" value="CSVファイルを選択" :required="true" />
                            <input type="file" name="csv_file" id="csv_file" required accept=".csv, text/csv"
                                class="mt-1 block w-full text-sm text-gray-900 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none focus:border-indigo-500 focus:ring-indigo-500
                                                                    file:mr-4 file:py-2 file:px-4
                                                                    file:rounded-md file:border-0
                                                                    file:text-sm file:font-semibold
                                                                    file:bg-indigo-100 dark:file:bg-indigo-700 file:text-indigo-600 dark:file:text-indigo-300
                                                                    hover:file:bg-indigo-200 dark:hover:file:bg-indigo-600" />
                            <x-input-error :messages="$errors->get('csv_file')" class="mt-2" />
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <p>
                                CSVファイルの1行目はヘッダー行（列名）である必要があります。ファイルのエンコーディングはUTF-8を推奨します。<br>
                                <strong>必須列: メールアドレス。</strong>その他は任意です。列の順番は問いません。
                            </p>
                            <p class="font-medium pt-1">認識可能なヘッダー名の例（大文字・小文字は区別しません。各項目、下記のいずれかが含まれていれば読み込まれます）：</p>
                            <ul class="list-disc list-inside pl-2 space-y-0.5">
                                <li><strong>メールアドレス</strong>: 「メールアドレス」「Email」「email_address」「メール」</li>
                                <li><strong>名前</strong>: 「名前」 「氏名」 「Name」 「フルネーム」</li>
                                <li><strong>会社名</strong>: 「会社名」 「Company Name」 「法人名」 「所属」</li>
                                <li><strong>郵便番号</strong>: 「郵便番号」 「Postal Code」 「郵便」</li>
                                <li><strong>住所</strong>: 「住所」 「Address」</li>
                                <li><strong>電話番号</strong>: 「電話番号」 「Phone Number」 「電話」</li>
                                <li><strong>FAX番号</strong>: 「FAX番号」 「FAX Number」 「FAX」</li>
                                <li><strong>URL</strong>: 「URL」 「Website」 「ウェブサイト」</li>
                                <li><strong>代表者名</strong>: 「代表者名」 「Representative Name」 「代表」</li>
                                <li><strong>設立年月日</strong>: 「設立年月日」 「Establishment Date」 「設立日」 (例: 2000-01-30 or 2000/01/30)
                                </li>
                                <li><strong>業種</strong>: 「業種」 「Industry」</li>
                                <li><strong>備考</strong>: 「備考」 「Notes」 「メモ」</li>
                                <li><strong>ステータス</strong>: 「ステータス」 「Status」 (値の例: active, do_not_contact, archived)</li>
                            </ul>
                        </div>
                        <div class="flex justify-end">
                            <x-primary-button type="submit">
                                <i class="fas fa-file-import mr-2"></i> インポート実行
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        {{-- 管理連絡先一覧テーブル --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            メールアドレス</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            名前</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            会社名</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ステータス</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            最終更新日</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[100px]">
                            操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($managedContacts as $contact)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                <a href="{{ route('tools.sales.managed-contacts.edit', $contact) }}"
                                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 hover:underline">
                                    {{ $contact->email }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $contact->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ Str::limit($contact->company_name ?? '-', 30) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @php
                                    $statusCfg = \App\Models\ManagedContact::getStatusConfig($contact->status);
                                @endphp
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusCfg['class'] }}">
                                    {{ $statusCfg['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                                title="{{ $contact->updated_at->format('Y-m-d H:i:s') }}">
                                {{ $contact->updated_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <x-secondary-button as="a" href="{{ route('tools.sales.managed-contacts.edit', $contact) }}"
                                        class="py-1 px-3 text-xs">
                                        <i class="fas fa-edit mr-1"></i>編集
                                    </x-secondary-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if(count(array_filter($filterValues ?? [])) > 0)
                                    フィルター条件に一致する管理連絡先は見つかりませんでした。
                                @else
                                    管理連絡先はまだ登録されていません。
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($managedContacts->hasPages())
            <div class="mt-4">
                {{ $managedContacts->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection