@extends('layouts.app')

@section('title', '自分の休日設定')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ formOpen: !({{ $myHolidays->count() > 0 ? 'true' : 'false' }}) }">

        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">自分の休日設定</h1>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.holidays.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-users mr-2"></i>全員の休日一覧
                </a>
                <x-secondary-button @click="formOpen = !formOpen">
                    <i class="fas fa-plus mr-1"></i>新規登録
                    <span x-show="formOpen" x-cloak><i class="fas fa-chevron-up fa-xs ml-2"></i></span>
                    <span x-show="!formOpen"><i class="fas fa-chevron-down fa-xs ml-2"></i></span>
                </x-secondary-button>
            </div>
        </div>

        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600"
                role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div x-show="formOpen" x-collapse class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 sm:p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 border-b dark:border-gray-700 pb-2">
                新規休日登録</h2>
            <form action="{{ route('my-holidays.store') }}" method="POST">
                @csrf
                {{-- Gridレイアウトで各項目を横並びに配置 --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-end">

                    {{-- 休日の名称 --}}
                    <div class="lg:col-span-1">
                        <x-input-label for="name" value="休日の名称" :required="true" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')"
                            required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    {{-- 日付 --}}
                    <div class="lg:col-span-1">
                        <x-input-label for="date" value="日付" :required="true" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date')"
                            required />
                        <x-input-error :messages="$errors->get('date')" class="mt-2" />
                    </div>

                    {{-- 種類 --}}
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">種類</label>
                        <div class="mt-2 flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio text-indigo-600" name="period_type" value="full"
                                    checked>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">全休</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio text-indigo-600" name="period_type" value="am">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">午前</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio text-indigo-600" name="period_type" value="pm">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">午後</span>
                            </label>
                        </div>
                    </div>

                    {{-- 登録ボタン --}}
                    <div class="lg:col-span-1">
                        <x-primary-button type="submit" class="w-full justify-center">登録する</x-primary-button>
                    </div>
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
                                日付と種類</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                名称</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        {{-- ▼▼▼【ここから修正】▼▼▼ --}}
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
                        @forelse ($myHolidays as $holiday)
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
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-4">
                                        <a href="{{ route('my-holidays.edit', $holiday) }}"
                                            class="text-blue-600 hover:text-blue-800" title="編集"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('my-holidays.destroy', $holiday) }}" method="POST"
                                            onsubmit="return confirm('本当に削除しますか？');" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="削除"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    登録されている休日はありません。</td>
                            </tr>
                        @endforelse
                        {{-- ▲▲▲【修正ここまで】▲▲▲ --}}
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $myHolidays->links() }}
            </div>
        </div>
    </div>
@endsection

