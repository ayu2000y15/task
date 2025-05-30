@extends('layouts.app')

@section('title', '操作ログ閲覧')

@push('styles')
    <style>
        .table-cell-properties {
            max-width: 300px;
            /* プロパティ表示の最大幅 */
            overflow-wrap: break-word;
        }

        .description-cell {
            min-width: 250px;
            /* 説明文の最小幅 */
        }

        .subject-cell {
            min-width: 150px;
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ filtersOpen: {{ request()->except('page') ? 'true' : 'false' }} }">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">操作ログ閲覧</h1>
            <x-secondary-button @click="filtersOpen = !filtersOpen">
                <i class="fas fa-filter mr-1"></i>フィルター
                <span x-show="filtersOpen" style="display:none;"><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                <span x-show="!filtersOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
            </x-secondary-button>
        </div>

        <div x-show="filtersOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
            <form action="{{ route('admin.logs.index') }}" method="GET">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="user_name" value="操作者名" />
                        <x-text-input id="user_name" name="user_name" type="text" class="mt-1 block w-full"
                            :value="request('user_name')" placeholder="例: 山田太郎" />
                    </div>
                    <div>
                        <x-input-label for="date_from" value="日時 (From)" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full"
                            :value="request('date_from')" />
                    </div>
                    <div>
                        <x-input-label for="date_to" value="日時 (To)" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full"
                            :value="request('date_to')" />
                    </div>
                    <div>
                        <x-input-label for="subject_type" value="操作対象モデル" />
                        <x-select-input id="subject_type" name="subject_type" class="mt-1 block w-full"
                            :emptyOptionText="'すべてのモデル'">
                            @foreach($availableSubjectTypes as $classPath => $displayName)
                                <option value="{{ $classPath }}" @if(request('subject_type') == $classPath) selected @endif>
                                    {{ $displayName }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="description" value="操作内容 (メソッド等)" />
                        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full"
                            :value="request('description')" placeholder="例: store, updated" />
                    </div>
                    <div>
                        <x-input-label for="keyword" value="キーワード (説明・プロパティ内)" />
                        <x-text-input id="keyword" name="keyword" type="text" class="mt-1 block w-full"
                            :value="request('keyword')" placeholder="変更内容など" />
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end space-x-3">
                    <a href="{{ route('admin.logs.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                        リセット
                    </a>
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
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                日時</th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                操作者</th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider description-cell">
                                説明</th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider subject-cell">
                                対象</th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider table-cell-properties">
                                プロパティ (変更内容等)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($activities as $activity)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                                    title="{{ $activity->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $activity->created_at->diffForHumans() }}
                                    <span class="block text-xs">{{ $activity->created_at->format('Y/m/d H:i') }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $activity->causer->name ?? 'システム' }}
                                    @if($activity->causer)
                                        <span
                                            class="block text-xs text-gray-400 dark:text-gray-500">(ID:{{ $activity->causer_id }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 description-cell">
                                    <div class="whitespace-pre-wrap break-words">{{ $activity->description }}</div>
                                    @if($activity->log_name !== 'default')
                                        <span
                                            class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $activity->log_name }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 subject-cell">
                                    @if ($activity->subject)
                                        @php
                                            $subjectTypeParts = explode('\\', $activity->subject_type);
                                            $subjectModelName = end($subjectTypeParts);
                                        @endphp
                                        {{ $subjectModelName }} ID: {{ $activity->subject_id }}
                                        {{-- 例えば、対象モデルへのリンクを生成することも可能 --}}
                                        {{-- @if (method_exists($activity->subject, 'getViewRouteName')) --}}
                                        {{-- <a href="{{ route($activity->subject->getViewRouteName(), $activity->subject) }}"
                                            class="text-blue-500 hover:underline">詳細</a> --}}
                                        {{-- @endif --}}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 table-cell-properties">
                                    @if ($activity->properties && $activity->properties->count() > 0)
                                        <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded max-h-32 overflow-y-auto">
                                            <pre
                                                class="whitespace-pre-wrap break-all">{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-history fa-3x text-gray-400 dark:text-gray-500 mb-3"></i>
                                        <span>操作ログはありません。</span>
                                        @if(count(request()->except('page')) > 0)
                                            <p class="mt-1">絞り込み条件を変更してみてください。</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($activities->hasPages())
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection