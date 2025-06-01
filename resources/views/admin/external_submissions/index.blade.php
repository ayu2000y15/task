@extends('layouts.app')

@section('title', '衣装案件依頼フォーム一覧')

@push('styles')
    <style>
        /* 詳細データの表示スタイル (これは残す) */
        .submitted-data-table {
            width: 100%;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .submitted-data-table th,
        .submitted-data-table td {
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            /* Tailwind gray-200 */
            text-align: left;
        }

        .submitted-data-table th {
            background-color: #f9fafb;
            /* Tailwind gray-50 */
            width: 30%;
        }

        .dark .submitted-data-table th,
        .dark .submitted-data-table td {
            border-color: #4b5563;
            /* Tailwind gray-600 */
        }

        .dark .submitted-data-table th {
            background-color: #374151;
            /* Tailwind gray-700 */
        }

        .submitted-data-file-list li {
            margin-bottom: 0.25rem;
        }

        .submitted-data-file-list li:last-child {
            margin-bottom: 0;
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ filtersOpen: {{ count(array_filter(request()->query())) > 0 ? 'true' : 'false' }} }">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">衣装案件依頼フォーム 一覧</h1>
            <x-secondary-button @click="filtersOpen = !filtersOpen">
                <i class="fas fa-filter mr-1"></i>フィルター
                <span x-show="filtersOpen" style="display:none;"><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                <span x-show="!filtersOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
            </x-secondary-button>
        </div>

        <div x-show="filtersOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
            <form action="{{ route('admin.external-submissions.index') }}" method="GET">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="filter_submitter_name" value="依頼者名" />
                        <x-text-input id="filter_submitter_name" name="submitter_name" type="text" class="mt-1 block w-full"
                            :value="request('submitter_name')" placeholder="依頼者名で検索" />
                    </div>
                    <div>
                        <x-input-label for="filter_submitter_email" value="メールアドレス" />
                        <x-text-input id="filter_submitter_email" name="submitter_email" type="text"
                            class="mt-1 block w-full" :value="request('submitter_email')" placeholder="メールアドレスで検索" />
                    </div>
                    <div>
                        <x-input-label for="filter_status" value="ステータス" />
                        <x-select-input id="filter_status" name="status" class="mt-1 block w-full" :options="$statusOptions"
                            :selected="request('status')" :emptyOptionText="'すべて'" />
                    </div>
                    <div>
                        <x-input-label for="filter_start_date" value="依頼日 (開始)" />
                        <x-text-input id="filter_start_date" name="start_date" type="date" class="mt-1 block w-full"
                            :value="request('start_date')" />
                    </div>
                    <div>
                        <x-input-label for="filter_end_date" value="依頼日 (終了)" />
                        <x-text-input id="filter_end_date" name="end_date" type="date" class="mt-1 block w-full"
                            :value="request('end_date')" />
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end space-x-3">
                    <x-secondary-button as="a" :href="route('admin.external-submissions.index')">
                        リセット
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        <i class="fas fa-search mr-2"></i> 絞り込む
                    </x-primary-button>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ID</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                依頼者名</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                メールアドレス</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                依頼日時</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ステータス</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                処理者/日時</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($submissions as $submission)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $submission->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $submission->submitter_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $submission->submitter_email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $submission->created_at->format('Y/m/d') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{-- ★ ステータスバッジのスタイル変更 --}}
                                    <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @switch($submission->status)
                                            @case('new') bg-sky-100 text-sky-800 dark:bg-sky-700 dark:text-sky-100 @break
                                            @case('in_progress') bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100 @break
                                            @case('processed') bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100 @break
                                            @case('on_hold') bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-300 @break
                                            @case('rejected') bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100 @break
                                            @default bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-100
                                        @endswitch
                                    ">
                                        {{ $statusOptions[$submission->status] ?? $submission->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    @if($submission->processedBy)
                                        {{ $submission->processedBy->name }}
                                        @if($submission->processed_at)
                                            <br><span class="text-xs">{{ $submission->processed_at->format('Y/m/d H:i') }}</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <x-secondary-button as="a" :href="route('admin.external-submissions.show', $submission)"
                                        class="py-1 px-3">
                                        詳細確認
                                    </x-secondary-button>
                                    @if($submission->status === 'new' || $submission->status === 'in_progress')
                                        <x-primary-button as="a"
                                            href="{{ route('projects.create', ['external_request_id' => $submission->id]) }}"
                                            class="py-1 px-2 text-xs bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                                            title="この依頼から案件を作成">
                                            <i class="fas fa-plus-circle mr-1"></i> 案件化
                                        </x-primary-button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-envelope-open-text fa-3x text-gray-400 dark:text-gray-500 mb-3"></i>
                                        <span>該当する依頼はありません。</span>
                                        @if(count(array_filter(request()->query())) > 0)
                                            <p class="mt-1">絞り込み条件に一致する依頼がありませんでした。</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($submissions->hasPages())
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $submissions->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection