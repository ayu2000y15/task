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
                        {{-- ▼▼▼【x-dataの変数名を'isWorkday'に統一し、値をboolean型に修正】▼▼▼ --}}
                        <div class="p-4 border dark:border-gray-700 rounded-lg"
                            x-data="{ isWorkday: {{ old("days.{$dayOfWeek}.is_workday", optional($pattern)->is_workday ?? true) ? 'true' : 'false' }} }">
                            <div class="flex items-center justify-between">
                                <label for="day_{{ $dayOfWeek }}_is_workday" class="flex items-center space-x-3 cursor-pointer">
                                    {{-- ▼▼▼【x-modelを追加して双方向バインディング】▼▼▼ --}}
                                    <input type="checkbox" id="day_{{ $dayOfWeek }}_is_workday"
                                        name="days[{{ $dayOfWeek }}][is_workday]" x-model="isWorkday"
                                        class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $dayName }}曜日</span>
                                </label>
                            </div>

                            {{-- ▼▼▼【x-showの変数名を'isWorkday'に統一し、トランジションを追加】▼▼▼ --}}
                            <div x-show="isWorkday" x-transition
                                class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4" style="display: none;">
                                <div>
                                    <x-input-label for="day_{{ $dayOfWeek }}_start_time" value="開始時刻" />
                                    <x-text-input type="time" id="day_{{ $dayOfWeek }}_start_time"
                                        name="days[{{ $dayOfWeek }}][start_time]" class="mt-1 block w-full"
                                        :value="old('days.{$dayOfWeek}.start_time', optional($pattern)->start_time)" />
                                </div>
                                <div>
                                    <x-input-label for="day_{{ $dayOfWeek }}_end_time" value="終了時刻" />
                                    <x-text-input type="time" id="day_{{ $dayOfWeek }}_end_time"
                                        name="days[{{ $dayOfWeek }}][end_time]" class="mt-1 block w-full"
                                        :value="old('days.{$dayOfWeek}.end_time', optional($pattern)->end_time)" />
                                </div>
                                <div>
                                    <x-input-label for="day_{{ $dayOfWeek }}_break_minutes" value="休憩時間（分）" />
                                    <x-text-input type="number" id="day_{{ $dayOfWeek }}_break_minutes"
                                        name="days[{{ $dayOfWeek }}][break_minutes]" class="mt-1 block w-full"
                                        :value="old('days.{$dayOfWeek}.break_minutes', $pattern->break_minutes ?? 60)" min="0"
                                        step="15" />
                                </div>
                                <div>
                                    <x-input-label value="勤務場所" />
                                    <div class="flex items-center space-x-4 mt-2">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="days[{{ $dayOfWeek }}][location]" value="office"
                                                class="form-radio" {{ (old("days.{$dayOfWeek}.location", optional($pattern)->location) ?? 'office') == 'office' ? 'checked' : '' }}>
                                            <span class="ml-2 dark:text-gray-300">出勤</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="days[{{ $dayOfWeek }}][location]" value="remote"
                                                class="form-radio" {{ (old("days.{$dayOfWeek}.location", optional($pattern)->location) ?? 'office') == 'remote' ? 'checked' : '' }}>
                                            <span class="ml-2 dark:text-gray-300">在宅</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 pt-6 border-t dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">デフォルト交通費設定</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        「出勤」の日に適用される標準的な交通費を設定します。月間スケジュール画面から一括で登録できます。<br>
                        交通費は<strong>往復分の合計</strong>を登録してください。
                    </p>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="default_transportation_departure" value="出発地" />
                                <x-text-input id="default_transportation_departure" name="default_transportation_departure"
                                    type="text" class="mt-1 block w-full" :value="old('default_transportation_departure', auth()->user()->default_transportation_departure)" placeholder="例: 自宅" />
                            </div>
                            <div>
                                <x-input-label for="default_transportation_destination" value="到着地（主な勤務地）" />
                                <x-text-input id="default_transportation_destination"
                                    name="default_transportation_destination" type="text" class="mt-1 block w-full"
                                    :value="old('default_transportation_destination', auth()->user()->default_transportation_destination)" placeholder="例: 〇〇オフィス" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="default_transportation_amount" value="金額（円）" />
                            <x-text-input id="default_transportation_amount" name="default_transportation_amount"
                                type="number" class="mt-1 block w-full" :value="old('default_transportation_amount', auth()->user()->default_transportation_amount)" placeholder="例: 880" min="0" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-8 pt-4 border-t dark:border-gray-700">
                    <x-primary-button type="submit">更新する</x-primary-button>
                </div>
            </form>
        </div>
    </div>
@endsection