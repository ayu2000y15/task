@extends('layouts.app')

@section('title', '操作ログ閲覧')

@push('styles')
    <style>
        .table-cell-properties {
            max-width: 350px;
            /* プロパティ表示の最大幅を少し広げる */
            /* overflow-wrap: break-word; は詳細表示部分で個別に適用 */
        }

        .description-cell {
            min-width: 200px;
            /* 説明文の最小幅 */
        }

        .subject-cell {
            min-width: 150px;
        }

        /* 詳細表示時のスタイル */
        .properties-details {
            max-height: 200px;
            /* 詳細表示の最大高さ */
            overflow-y: auto;
            /* 縦スクロールを可能にする */
            background-color: #f9fafb;
            /* 少し背景色をつける */
        }

        .dark .properties-details {
            background-color: #1f2937;
            /*ダークモード時の背景色 */
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ filtersOpen: {{ count(array_filter(request()->except('page'))) > 0 ? 'true' : 'false' }} }">
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
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
                        <x-input-label for="subject_type_short" value="操作対象モデル" />
                        <x-select-input id="subject_type_short" name="subject_type_short" class="mt-1 block w-full"
                            :emptyOptionText="'すべてのモデル'">
                            @foreach($availableSubjectTypesForFilter as $shortName => $displayName)
                                <option value="{{ $shortName }}" @if(request('subject_type_short') == $shortName) selected @endif>
                                    {{ $displayName }}
                                </option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="event" value="操作イベント" />
                        <x-select-input id="event" name="event" class="mt-1 block w-full" :emptyOptionText="'すべてのイベント'">
                            @foreach($availableEvents as $value => $label)
                                <option value="{{ $value }}" @if(request('event') == $value) selected @endif>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="description" value="操作内容 (メソッド等)" />
                        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full"
                            :value="request('description')" placeholder="例: store, ログイン" />
                    </div>
                    <div class="lg:col-span-2">
                        <x-input-label for="keyword" value="キーワード (説明・プロパティ内)" />
                        <x-text-input id="keyword" name="keyword" type="text" class="mt-1 block w-full"
                            :value="request('keyword')" placeholder="変更内容、ファイル名など" />
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
            <div class="overflow-x-auto"> {{-- ★横スクロールはこのdivで制御 --}}
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="sticky left-0 top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                日時</th>
                            <th scope="col"
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                操作者</th>
                            <th scope="col"
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider description-cell">
                                説明</th>
                            <th scope="col"
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider subject-cell">
                                対象 (イベント)</th>
                            <th scope="col"
                                class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider table-cell-properties">
                                プロパティ (変更内容等)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($activities as $activity)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 sticky left-0 bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-700/50"
                                    title="{{ $activity->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $activity->created_at->setTimezone('Asia/Tokyo')->diffForHumans() }}
                                    <span
                                        class="block text-xs">{{ $activity->created_at->setTimezone('Asia/Tokyo')->format('m/d H:i:s') }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $activity->causer->name ?? ($activity->causer_type ? class_basename($activity->causer_type) : 'システム') }}
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
                                    @if ($activity->subject_type)
                                        @php
                                            $subjectModelName = class_basename($activity->subject_type);
                                        @endphp
                                        {{ $subjectModelName }} ID: {{ $activity->subject_id }}
                                    @else
                                        N/A
                                    @endif
                                    @if($activity->event)
                                        <span
                                            class="block text-xs italic text-gray-400 dark:text-gray-500">({{ $activity->event }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 table-cell-properties align-top">
                                    {{-- ★★★ プロパティ表示の修正箇所 ★★★ --}}
                                    <div x-data="{ expanded: false }">
                                        {{-- 展開ボタンとサマリー表示 --}}
                                        <div x-show="!expanded">
                                            @if ($activity->properties && $activity->properties->count() > 0)
                                                @php
                                                    $props = $activity->properties;
                                                    $attributes = $props->get('attributes');
                                                    $old = $props->get('old');
                                                    $summary = '';
                                                    $propCount = $props->count();

                                                    if ($attributes && $old && is_array($attributes) && is_array($old)) {
                                                        $changedCount = 0;
                                                        foreach ($attributes as $key => $value) {
                                                            if (array_key_exists($key, $old) && $old[$key] !== $value) {
                                                                $changedCount++;
                                                            } elseif (!array_key_exists($key, $old)) {
                                                                $changedCount++;
                                                            }
                                                        }
                                                        if ($changedCount > 0) {
                                                            $summary = $changedCount . '件の属性変更';
                                                        } else {
                                                            $summary = '属性変更なし';
                                                        }
                                                        $otherPropsCount = $props->except(['attributes', 'old'])->count();
                                                        if ($otherPropsCount > 0) {
                                                            $summary .= ($summary ? '、' : '') . $otherPropsCount . '件の追加情報あり';
                                                        }
                                                    } elseif ($props->has('original_name') && $props->has('path')) { // ファイル操作ログの可能性
                                                        $summary = Str::limit($props->get('original_name', 'ファイル操作'), 25);
                                                        if ($props->count() > 2)
                                                            $summary .= ' (他' . ($props->count() - 2) . '件の情報)';
                                                    } elseif ($props->has('action')) {
                                                        $summary = Str::limit($props->get('action'), 30);
                                                        if ($props->count() > 1)
                                                            $summary .= ' (他' . ($props->count() - 1) . '件の情報)';
                                                    } else {
                                                        $summary = $propCount . '件のプロパティ';
                                                    }
                                                @endphp
                                                <div class="flex items-center justify-between">
                                                    <span class="truncate text-gray-600 dark:text-gray-400"
                                                        title="{{ $summary }}">{{ Str::limit($summary, 40) ?: '-' }}</span>
                                                    <button @click="expanded = !expanded"
                                                        class="ml-2 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-xs focus:outline-none"
                                                        title="詳細表示/非表示">
                                                        <i class="fas" :class="expanded ? 'fa-minus-square' : 'fa-plus-square'"></i>
                                                        詳細
                                                    </button>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </div>

                                        {{-- 詳細表示 (x-collapseでアニメーション) --}}
                                        <div x-show="expanded" x-collapse style="display: none;"
                                            class="mt-2 border-t border-gray-200 dark:border-gray-700 pt-2 properties-details">
                                            @if ($activity->properties && $activity->properties->count() > 0)
                                                @php
                                                    $props = $activity->properties; // 再度アクセスを容易に
                                                    $attributes = $props->get('attributes');
                                                    $old = $props->get('old');
                                                @endphp
                                                @if ($attributes && $old && is_array($attributes) && is_array($old))
                                                    <div class="space-y-1">
                                                        <strong class="block text-gray-700 dark:text-gray-200 mb-1">属性変更:</strong>
                                                        @foreach ($attributes as $key => $value)
                                                            @if (array_key_exists($key, $old) && $old[$key] !== $value)
                                                                <div
                                                                    class="grid grid-cols-1 sm:grid-cols-3 gap-x-1 gap-y-0 border-b border-gray-100 dark:border-gray-600 py-0.5">
                                                                    <span
                                                                        class="font-medium text-gray-600 dark:text-gray-300 sm:col-span-1 truncate"
                                                                        title="{{ $key }}">{{ Str::limit($key, 25) }}:</span>
                                                                    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-x-1">
                                                                        <span class="text-red-600 dark:text-red-400 break-all whitespace-normal"
                                                                            title="旧: {{ is_array($old[$key]) ? json_encode($old[$key], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : $old[$key] }}">
                                                                            <span class="font-semibold">旧:</span>
                                                                            {{ is_array($old[$key]) ? json_encode($old[$key], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : $old[$key] }}
                                                                        </span>
                                                                        <span
                                                                            class="text-green-600 dark:text-green-400 break-all whitespace-normal"
                                                                            title="新: {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : $value }}">
                                                                            <span class="font-semibold">新:</span>
                                                                            {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : $value }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            @elseif (!array_key_exists($key, $old))
                                                                <div
                                                                    class="grid grid-cols-1 sm:grid-cols-3 gap-x-1 gap-y-0 border-b border-gray-100 dark:border-gray-600 py-0.5">
                                                                    <span
                                                                        class="font-medium text-gray-600 dark:text-gray-300 sm:col-span-1 truncate"
                                                                        title="{{ $key }}">{{ Str::limit($key, 25) }}:</span>
                                                                    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-x-1">
                                                                        <span
                                                                            class="text-gray-500 dark:text-gray-400 break-all whitespace-normal">(なし)</span>
                                                                        <span
                                                                            class="text-green-600 dark:text-green-400 break-all whitespace-normal"
                                                                            title="新: {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : $value }}">
                                                                            <span class="font-semibold">新:</span>
                                                                            {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : $value }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                        @foreach ($props as $key => $value)
                                                            @if (!in_array($key, ['attributes', 'old']))
                                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-1 gap-y-0 py-0.5">
                                                                    <span
                                                                        class="font-medium text-gray-600 dark:text-gray-300 sm:col-span-1 truncate"
                                                                        title="{{ $key }}">{{ Str::limit($key, 25) }}:</span>
                                                                    <span
                                                                        class="text-gray-700 dark:text-gray-300 sm:col-span-2 break-all whitespace-normal"
                                                                        title="{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : $value }}">
                                                                        {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : $value }}
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="space-y-1">
                                                        @foreach ($props as $key => $value)
                                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-1 gap-y-0 py-0.5">
                                                                <span
                                                                    class="font-medium text-gray-600 dark:text-gray-300 sm:col-span-1 truncate"
                                                                    title="{{ $key }}">{{ Str::limit($key, 25) }}:</span>
                                                                <span
                                                                    class="text-gray-700 dark:text-gray-300 sm:col-span-2 break-all whitespace-normal"
                                                                    title="{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : $value }}">
                                                                    {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : $value }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                <button @click="expanded = false"
                                                    class="mt-2 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-xs focus:outline-none">
                                                    <i class="fas fa-minus-square"></i> 詳細を閉じる
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    {{-- ★★★ ここまでプロパティ表示の修正箇所 ★★★ --}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-history fa-3x text-gray-400 dark:text-gray-500 mb-3"></i>
                                        <span>操作ログはありません。</span>
                                        @if(count(array_filter(request()->except('page'))) > 0)
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
                    {{ $activities->appends(request()->except('page'))->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection