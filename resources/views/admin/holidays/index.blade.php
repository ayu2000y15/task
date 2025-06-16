@extends('layouts.app')

@section('title', '全ユーザーの休日一覧')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ filtersOpen: {{ count(array_filter(request()->except(['page', 'sort', 'direction']))) > 0 ? 'true' : 'false' }} }">

        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">全ユーザーの休日一覧</h1>
            <div class="flex items-center space-x-2">
                <a href="{{ route('my-holidays.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-user-edit mr-2"></i>自分の休日を登録
                </a>
                <x-secondary-button @click="filtersOpen = !filtersOpen">
                    <i class="fas fa-filter mr-1"></i>フィルター
                    <span x-show="filtersOpen" x-cloak><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                    <span x-show="!filtersOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
                </x-secondary-button>
            </div>
        </div>

        <div x-show="filtersOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
            <form action="{{ route('admin.holidays.index') }}" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-end">
                    <div>
                        <label for="user_id_filter"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">対象者</label>
                        <select id="user_id_filter" name="user_id" class="tom-select mt-1 block w-full">
                            <option value="">全員</option>
                            <option value="all_company" {{ ($filters['user_id'] ?? '') === 'all_company' ? 'selected' : '' }}>
                                全社共通</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="start_date"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">開始日</label>
                            <input type="date" name="start_date" id="start_date" value="{{ $filters['start_date'] ?? '' }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div>
                            <label for="end_date"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">終了日</label>
                            <input type="date" name="end_date" id="end_date" value="{{ $filters['end_date'] ?? '' }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600">
                        </div>
                    </div>
                    <div class="flex space-x-3 items-center">
                        <x-primary-button type="submit">
                            <i class="fas fa-search mr-2"></i> 絞り込む
                        </x-primary-button>
                        <a href="{{ route('admin.holidays.index') }}"
                            class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                            リセット
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            {{-- 見出しを「日付と種類」に変更 --}}
                            @include('admin.holidays.partials.sortable-th', ['label' => '日付と種類', 'sortKey' => 'date', 'currentSort' => $sort, 'currentDirection' => $direction])
                            @include('admin.holidays.partials.sortable-th', ['label' => '名称', 'sortKey' => 'name', 'currentSort' => $sort, 'currentDirection' => $direction])
                            @include('admin.holidays.partials.sortable-th', ['label' => '対象者', 'sortKey' => 'user', 'currentSort' => $sort, 'currentDirection' => $direction])
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @php
                            // DBの値と表示名の対応表
                            $periodTypes = ['full' => '全休', 'am' => '午前休', 'pm' => '午後休'];
                            // 種類ごとの色分け用CSSクラスの対応表
                            $periodClasses = [
                                'full' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'am'   => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'pm'   => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                            ];
                        @endphp
                        @forelse ($holidays as $holiday)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $holiday->date->isoFormat('YYYY年M月D日(ddd)') }}
                                    {{-- 期間タイプを色分けしたバッジで表示 --}}
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $periodClasses[$holiday->period_type] ?? $periodClasses['full'] }}">
                                        {{ $periodTypes[$holiday->period_type] ?? '全休' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $holiday->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($holiday->user)
                                        {{ $holiday->user->name }}
                                    @else
                                        <span class="font-semibold text-blue-600 dark:text-blue-400">全社共通</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <form action="{{ route('admin.holidays.destroy', $holiday) }}" method="POST"
                                        onsubmit="return confirm('本当に削除しますか？');" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            title="削除">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    該当する休日が見つかりません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                {{ $holidays->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.tom-select').forEach((el) => {
                new TomSelect(el, {
                    plugins: ['clear_button'],
                    create: false,
                });
            });
        });
    </script>
@endpush