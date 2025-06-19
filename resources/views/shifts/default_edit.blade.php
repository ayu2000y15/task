@extends('layouts.app')
@section('title', 'デフォルトシフト設定')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">デフォルトシフト設定</h1>
        <x-secondary-button as="a" href="{{ route('schedule.monthly') }}">
            <i class="fas fa-arrow-left mr-2"></i> シフト管理へ戻る
        </x-secondary-button>
    </div>

    <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <form action="{{ route('shifts.default.update') }}" method="POST">
            @csrf
            <div class="space-y-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">曜日ごとの標準的な勤務パターンを設定してください。チェックを外した曜日は休日として扱われます。</p>

                @foreach ($days as $dayOfWeek => $dayName)
                    @php
                        $pattern = $patterns[$dayOfWeek] ?? null;
                    @endphp
                    <div class="p-4 border dark:border-gray-700 rounded-lg">
                        <div class="flex items-center justify-between">
                            <label for="day_{{ $dayOfWeek }}_is_workday" class="flex items-center space-x-3">
                                <input type="checkbox" id="day_{{ $dayOfWeek }}_is_workday" name="days[{{ $dayOfWeek }}][is_workday]"
                                       class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                       {{ old("days.{$dayOfWeek}.is_workday", $pattern->is_workday ?? true) ? 'checked' : '' }}>
                                <span class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $dayName }}曜日</span>
                            </label>
                        </div>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="day_{{ $dayOfWeek }}_start_time" value="開始時刻" />
                                <x-text-input type="time" id="day_{{ $dayOfWeek }}_start_time" name="days[{{ $dayOfWeek }}][start_time]" class="mt-1 block w-full"
                                              :value="old('days.{$dayOfWeek}.start_time', optional($pattern)->start_time)" />
                            </div>
                            <div>
                                <x-input-label for="day_{{ $dayOfWeek }}_end_time" value="終了時刻" />
                                <x-text-input type="time" id="day_{{ $dayOfWeek }}_end_time" name="days[{{ $dayOfWeek }}][end_time]" class="mt-1 block w-full"
                                              :value="old('days.{$dayOfWeek}.end_time', optional($pattern)->end_time)" />
                            </div>
                            <div>
                                <x-input-label for="day_{{ $dayOfWeek }}_break_minutes" value="休憩時間（分）" />
                                <x-text-input type="number" id="day_{{ $dayOfWeek }}_break_minutes" name="days[{{ $dayOfWeek }}][break_minutes]" class="mt-1 block w-full"
                                              :value="old('days.{$dayOfWeek}.break_minutes', $pattern->break_minutes ?? 60)" min="0" step="15" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end mt-8 pt-4 border-t dark:border-gray-700">
                <x-primary-button type="submit">更新する</x-primary-button>
            </div>
        </form>
    </div>
</div>
@endsection