@extends('layouts.app')

@section('title', '作業実績')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">作業実績</h1>
        </div>

        <div class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <form action="{{ route('work-records.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
                <div>
                    <label for="period" class="block text-sm font-medium text-gray-700 dark:text-gray-300">期間</label>
                    <select name="period" id="period"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-200">
                        <option value="today" @if($period == 'today') selected @endif>本日</option>
                        <option value="week" @if($period == 'week') selected @endif>今週</option>
                        <option value="month" @if($period == 'month') selected @endif>今月</option>
                    </select>
                </div>
                <div>
                    <x-primary-button type="submit">表示</x-primary-button>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 border-b dark:border-gray-700">
                <h3 class="text-lg font-semibold">
                    合計作業時間: <span class="text-blue-600 dark:text-blue-400">{{ floor($totalSeconds / 3600) }}時間
                        {{ floor(($totalSeconds % 3600) / 60) }}分</span>
                </h3>
                {{-- @if(Auth::user()->hourly_rate)
                    <h4 class="text-md font-medium mt-2">
                        概算給与: <span
                            class="text-green-600 dark:text-green-400">¥{{ number_format(($totalSeconds / 3600) * Auth::user()->hourly_rate, 0) }}</span>
                    </h4>
                @endif --}}
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                開始日時</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                終了日時</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                案件</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                工程</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                作業時間</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($workLogs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $log->start_time->format('Y/m/d H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ optional($log->end_time)->format('Y/m/d H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->task->project->title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->task->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @php $duration = $log->effective_duration; @endphp
                                    {{ floor($duration / 3600) }}時間 {{ floor(($duration % 3600) / 60) }}分
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    作業実績がありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection