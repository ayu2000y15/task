@extends('layouts.app')
@section('title', '交通費一覧')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            {{-- ▼▼▼【ここから月ナビゲーションを追加】▼▼▼ --}}
            <div class="flex items-center space-x-4">
                <a href="{{ route('transportation-expenses.index', ['month' => $targetMonth->copy()->subMonth()->format('Y-m')]) }}"
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"><i
                        class="fas fa-chevron-left"></i> 前月</a>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{ $targetMonth->format('Y年n月') }}</h1>
                <a href="{{ route('transportation-expenses.index', ['month' => $targetMonth->copy()->addMonth()->format('Y-m')]) }}"
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">次月 <i
                        class="fas fa-chevron-right"></i></a>
                <a href="{{ route('transportation-expenses.index') }}" class="text-sm text-blue-600 hover:underline">今月へ</a>
            </div>
            {{-- ▲▲▲【ここまで】▲▲▲ --}}

            <div class="flex items-center space-x-2">
                <x-secondary-button as="a" href="{{ route('schedule.monthly') }}">
                    <i class="fas fa-calendar-alt mr-2"></i> シフト管理へ
                </x-secondary-button>
                <x-primary-button as="a" href="{{ route('transportation-expenses.create') }}">
                    <i class="fas fa-plus mr-2"></i> 新規登録
                </x-primary-button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                利用日</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                区間</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                備考</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                関連案件</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                金額</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($expenses as $expense)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $expense->date->isoFormat('YYYY/MM/DD (ddd)') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $expense->departure ?? '...' }} → {{ $expense->destination }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400" title="{{ $expense->notes }}">
                                    <div class="w-32 truncate">{!! nl2br($expense->notes) !!}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $expense->project->title ?? 'その他' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-100">
                                    ¥{{ number_format($expense->amount) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-4">
                                        <a href="{{ route('transportation-expenses.edit', $expense) }}"
                                            class="text-blue-600 hover:text-blue-800" title="編集"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('transportation-expenses.destroy', $expense) }}" method="POST"
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
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    この月の交通費はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection