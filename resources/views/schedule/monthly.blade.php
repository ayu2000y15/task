@extends('layouts.app')
@section('title', $targetMonth->format('Y年n月') . 'のスケジュール')

@push('styles')
<style>
    /* 編集フォームをスムーズに表示するためのスタイル */
    .schedule-form { transition: all 0.3s ease-in-out; }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="scheduleManager()">
    {{-- ヘッダーと月ナビゲーション --}}
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <div class="flex items-center space-x-4">
            {{-- ▼▼▼【デザイン統一】▼▼▼ --}}
            <a href="{{ route('schedule.monthly', ['month' => $targetMonth->copy()->subMonth()->format('Y-m')]) }}" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"><i class="fas fa-chevron-left"></i> 前月</a>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{ $targetMonth->format('Y年n月') }}</h1>
            <a href="{{ route('schedule.monthly', ['month' => $targetMonth->copy()->addMonth()->format('Y-m')]) }}" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">次月 <i class="fas fa-chevron-right"></i></a>
            {{-- ▲▲▲【デザイン統一】▲▲▲ --}}
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('schedule.monthly') }}" class="text-sm text-blue-600 hover:underline">今月へ</a>
            <x-secondary-button as="a" href="{{ route('shifts.default.edit') }}">
                <i class="fas fa-cog mr-2"></i> デフォルトパターン設定
            </x-secondary-button>
        </div>
    </div>

    {{-- ▼▼▼【凡例を追加】▼▼▼ --}}
    <div class="flex items-center gap-2 text-xs flex-wrap mb-4">
        <span class="font-semibold mr-2">凡例:</span>
        <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded">土曜</span>
        <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/40 rounded">日曜</span>
        <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/40 rounded">祝日</span>
        <span class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/40 rounded">登録休日</span>
    </div>
    {{-- ▲▲▲【凡例を追加】▲▲▲ --}}


    {{-- スケジュール一覧テーブル --}}
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">日付</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">デフォルト</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">実績 / 休日設定</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $typeLabels = ['full_day_off' => '全休', 'am_off' => '午前休', 'pm_off' => '午後休', 'work' => '時間変更'];
                    @endphp
                    @foreach ($days as $day)
                        @php
                            // ▼▼▼【背景色クラスの計算ロジックを追加】▼▼▼
                            $date = $day['date'];
                            $rowClass = '';
                            if ($day['override'] && in_array($day['override']->type, ['full_day_off', 'am_off', 'pm_off'])) {
                                $rowClass = 'bg-yellow-50 dark:bg-yellow-900/30';
                            } elseif ($date->isSaturday()) {
                                $rowClass = 'bg-blue-50 dark:bg-blue-900/30';
                            } elseif ($date->isSunday()) {
                                $rowClass = 'bg-red-50 dark:bg-red-900/30';
                            } elseif ($day['public_holiday']) {
                                $rowClass = 'bg-green-50 dark:bg-green-900/30';
                            }
                        @endphp
                        <tr id="row-{{ $date->format('Y-m-d') }}" x-data="{ editing: false, type: '{{ $day['override']?->type ?? 'work' }}' }" class="{{ $rowClass }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold {{ $date->isToday() ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-gray-100' }}">
                                    {{ $date->format('n/j') }} ({{ $date->isoFormat('ddd') }})
                                </div>
                                {{-- ▼▼▼【祝日名を表示】▼▼▼ --}}
                                @if($day['public_holiday'])
                                    <div class="text-xs text-green-700 dark:text-green-300">{{ $day['public_holiday']->name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($day['default'] && $day['default']->is_workday)
                                    {{ \Carbon\Carbon::parse($day['default']->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($day['default']->end_time)->format('H:i') }}
                                @else
                                    <span class="text-gray-400">休日</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{-- 表示部分 --}}
                                <div x-show="!editing">
                                    @if($day['override'])
                                        @if($day['override']->type === 'work')
                                            <span class="font-semibold">{{ \Carbon\Carbon::parse($day['override']->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($day['override']->end_time)->format('H:i') }}</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $day['override']->type == 'full_day_off' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $typeLabels[$day['override']->type] ?? '' }}
                                            </span>
                                            <span class="ml-2 text-gray-600 dark:text-gray-300">{{ $day['override']->name }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">デフォルトと同じ</span>
                                    @endif
                                </div>
                                {{-- 編集フォーム部分 --}}
                                <div x-show="editing" class="schedule-form" style="display: none;">
                                    <form @submit.prevent="submitForm($event.target, '{{ $date->format('Y-m-d') }}')">
                                        <div class="flex items-center gap-2">
                                            <select x-model="type" name="type" class="form-select-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm">
                                                <option value="work">時間変更</option>
                                                <option value="full_day_off">全休</option>
                                                <option value="am_off">午前休</option>
                                                <option value="pm_off">午後休</option>
                                            </select>
                                            <div x-show="type === 'work'" class="flex items-center gap-1">
                                                <input type="time" name="start_time" class="form-input-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm w-24" value="{{ optional($day['override'])->start_time }}">
                                                <span>-</span>
                                                <input type="time" name="end_time" class="form-input-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm w-24" value="{{ optional($day['override'])->end_time }}">
                                            </div>
                                            <div x-show="type.includes('off')">
                                                <input type="text" name="name" class="form-input-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm" placeholder="休日の名称" value="{{ optional($day['override'])->name }}">
                                            </div>
                                            <button type="submit" class="p-1.5 bg-blue-600 text-white rounded-md text-xs hover:bg-blue-700"><i class="fas fa-check"></i></button>
                                            <button type="button" @click="editing = false" class="p-1.5 bg-gray-500 text-white rounded-md text-xs hover:bg-gray-600"><i class="fas fa-times"></i></button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end items-center gap-3">
                                    <button @click="editing = true" x-show="!editing" class="text-blue-600 hover:text-blue-800">変更</button>
                                    <button @click="clearForm('{{ $date->format('Y-m-d') }}')" x-show="!editing && {{ $day['override'] ? 'true' : 'false' }}" class="text-red-600 hover:text-red-800">クリア</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- スクリプトは変更なし --}}
<script>
    function scheduleManager() {
        return {
            async submitForm(formElement, date) {
                const formData = new FormData(formElement);
                formData.append('date', date);
                const data = Object.fromEntries(formData.entries());

                try {
                    const response = await fetch('{{ route("schedule.updateOrClearDay") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(data),
                    });
                    if (!response.ok) throw new Error('サーバーエラーが発生しました。');

                    location.reload();
                } catch (error) {
                    console.error('Error:', error);
                    alert('更新に失敗しました。');
                }
            },
            async clearForm(date) {
                if (!confirm(date + ' の設定をクリアしますか？')) return;

                try {
                    const response = await fetch('{{ route("schedule.updateOrClearDay") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ date: date, type: 'clear' }),
                    });
                    if (!response.ok) throw new Error('サーバーエラーが発生しました。');

                    location.reload();
                } catch (error) {
                    console.error('Error:', error);
                    alert('クリアに失敗しました。');
                }
            }
        }
    }
</script>
@endpush