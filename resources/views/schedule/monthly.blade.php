@extends('layouts.app')
@section('title', $targetMonth->format('Y年n月') . 'のスケジュール')

@push('styles')
    <style>
        /* 編集フォームをスムーズに表示するためのスタイル */
        .schedule-form { transition: all 0.3s ease-in-out; }
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- ヘッダーと月ナビゲーション --}}
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <div class="flex items-center space-x-4">
                <a href="{{ route('schedule.monthly', ['month' => $targetMonth->copy()->subMonth()->format('Y-m')]) }}" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"><i class="fas fa-chevron-left"></i> 前月</a>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{ $targetMonth->format('Y年n月') }}</h1>
                <a href="{{ route('schedule.monthly', ['month' => $targetMonth->copy()->addMonth()->format('Y-m')]) }}" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">次月 <i class="fas fa-chevron-right"></i></a>
                <a href="{{ route('schedule.monthly') }}" class="text-sm text-blue-600 hover:underline">今月へ</a>
            </div>

            <div class="flex items-center space-x-2">
                <x-secondary-button as="a" href="{{ route('shifts.default.edit') }}">
                    <i class="fas fa-cog mr-2"></i> デフォルトパターン設定
                </x-secondary-button>
                <form action="{{ route('transportation-expenses.batch-store') }}" method="POST" onsubmit="return confirm('{{ $targetMonth->format('Y年n月') }}の未登録の出勤日に、デフォルト交通費を一括登録します。よろしいですか？');">
                    @csrf
                    <input type="hidden" name="month" value="{{ $targetMonth->format('Y-m') }}">
                    <x-primary-button type="submit" :disabled="!auth()->user()->default_transportation_amount">
                        <i class="fas fa-bus mr-2"></i> 交通費一括登録
                    </x-primary-button>
                </form>
            </div>



        </div>

        {{-- 凡例 --}}
        <div class="flex items-center gap-2 text-xs flex-wrap mb-4">
            <span class="font-semibold mr-2">凡例:</span>
            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded">土曜</span>
            <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/40 rounded">日曜</span>
            <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/40 rounded">祝日</span>
            <span class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/40 rounded">登録休日</span>
        </div>


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
                                $date = $day['date'];
                                $dateString = $date->format('Y-m-d'); // 日付文字列を変数に
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
                            <tr id="row-{{ $date->format('Y-m-d') }}" x-data="scheduleRow(
                                    '{{ $day['override']?->type ?? 'work' }}',
                                    '{{ $day['override']?->location ?? 'office' }}',
                                    '{{ optional($day['override'])->start_time ? \Carbon\Carbon::parse($day['override']->start_time)->format('H:i') : (optional($day['default'])->start_time ? \Carbon\Carbon::parse($day['default']->start_time)->format('H:i') : '') }}',
                                    '{{ optional($day['override'])->end_time ? \Carbon\Carbon::parse($day['override']->end_time)->format('H:i') : (optional($day['default'])->end_time ? \Carbon\Carbon::parse($day['default']->end_time)->format('H:i') : '') }}',
                                    '{{ optional($day['override'])->name }}',
                                    '{{ optional($day['override'])->notes }}'
                                )" x-init="init()" class="{{ $rowClass }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold {{ $date->isToday() ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-gray-100' }}">
                                        {{ $date->format('n/j') }} ({{ $date->isoFormat('ddd') }})
                                    </div>
                                    @if($day['public_holiday'])
                                        <div class="text-xs text-green-700 dark:text-green-300">{{ $day['public_holiday']->name }}</div>
                                    @endif

                                    {{-- ▼▼▼【ここから交通費表示を追加】▼▼▼ --}}
                                    @if(isset($dailyExpenses[$dateString]) && $dailyExpenses[$dateString] > 0)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            <a href="{{ route('transportation-expenses.index', ['month' => $date->format('Y-m')]) }}" class="hover:underline" title="交通費詳細へ">
                                                <i class="fas fa-bus text-yellow-500"></i>
                                                <span class="ml-1">¥{{ number_format($dailyExpenses[$dateString]) }}</span>
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($day['default'] && $day['default']->is_workday)
                                        <div class="flex items-center">
                                            <span class="w-5">
                                                @if($day['default']->location === 'remote')
                                                    <i class="fas fa-home text-blue-500" title="在宅勤務"></i>
                                                @else
                                                    <i class="fas fa-building text-green-500" title="出勤"></i>
                                                @endif
                                            </span>
                                            <span class="ml-1">
                                                {{ \Carbon\Carbon::parse($day['default']->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($day['default']->end_time)->format('H:i') }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-gray-400">休日</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{-- 表示部分 --}}
                                    <div x-show="!editing" class="flex items-center">
                                        @if($day['override'])
                                            @if($day['override']->type === 'location_only')
                                                <span class="w-5">
                                                    @if($day['override']->location === 'remote')
                                                        <i class="fas fa-home text-blue-500" title="在宅勤務"></i>
                                                    @else
                                                        <i class="fas fa-building text-green-500" title="出勤"></i>
                                                    @endif
                                                </span>
                                                <span class="font-semibold ml-1 text-purple-600 dark:text-purple-400">場所のみ変更</span>
                                            @elseif($day['override']->type === 'work')
                                                <span class="w-5">
                                                    @if($day['override']->location === 'remote')
                                                        <i class="fas fa-home text-blue-500" title="在宅勤務"></i>
                                                    @else
                                                        <i class="fas fa-building text-green-500" title="出勤"></i>
                                                    @endif
                                                </span>
                                                <span class="font-semibold ml-1">{{ \Carbon\Carbon::parse($day['override']->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($day['override']->end_time)->format('H:i') }}</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $day['override']->type == 'full_day_off' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $typeLabels[$day['override']->type] ?? '' }}
                                                </span>
                                                <span class="ml-2 text-gray-600 dark:text-gray-300">{{ $day['override']->name }}</span>
                                            @endif
                                            @if($day['override']->notes)
                                                <i class="fas fa-comment-alt text-gray-400 ml-2" title="{{ $day['override']->notes }}"></i>
                                            @endif
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">デフォルトと同じ</span>
                                        @endif
                                    </div>
                                    {{-- 編集フォーム部分 --}}
                                    <div x-show="editing" class="schedule-form w-full max-w-md" style="display: none;">
                                        <form @submit.prevent="submitForm('{{ $date->format('Y-m-d') }}')">
                                            <div class="space-y-3 p-2">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <select x-model="type" @change="handleTypeChange()" class="form-select-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm">
                                                        <option value="work">時間変更</option>
                                                        <option value="location_only">場所のみ変更</option>
                                                        <option value="full_day_off">全休</option>
                                                        <option value="am_off">午前休</option>
                                                        <option value="pm_off">午後休</option>
                                                    </select>
                                                    <template x-if="type === 'work'">
                                                        <div class="flex items-center gap-1">
                                                            <input type="time" name="start_time" x-model="startTime" class="form-input-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm w-24">
                                                            <span>-</span>
                                                            <input type="time" name="end_time" x-model="endTime" class="form-input-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm w-24">
                                                        </div>
                                                    </template>
                                                    <template x-if="type.includes('off')">
                                                        <div>
                                                            <input type="text" name="name" x-model="name" class="form-input-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm" placeholder="休日の名称">
                                                        </div>
                                                    </template>
                                                </div>
                                                <div class="space-y-2">
                                                    <template x-if="type === 'work' || type === 'location_only'">
                                                        <div class="flex items-center gap-3 text-sm">
                                                            <span class="font-medium text-gray-600 dark:text-gray-400">場所:</span>
                                                            <label class="inline-flex items-center gap-1">
                                                                <input type="radio" name="location" value="office" x-model="workLocation" class="form-radio text-blue-600">
                                                                <span>出勤</span>
                                                            </label>
                                                            <label class="inline-flex items-center gap-1">
                                                                <input type="radio" name="location" value="remote" x-model="workLocation" class="form-radio text-blue-600">
                                                                <span>在宅</span>
                                                            </label>
                                                        </div>
                                                    </template>
                                                    <div>
                                                        <input type="text" name="notes" x-model="notes" class="form-input-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm w-full" placeholder="メモ (共有カレンダーで表示)">
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2 pt-1">
                                                    <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded-md text-xs hover:bg-blue-700"><i class="fas fa-check mr-1"></i>保存</button>
                                                    <button type="button" @click="editing = false" class="px-4 py-1.5 bg-gray-500 text-white rounded-md text-xs hover:bg-gray-600"><i class="fas fa-times mr-1"></i>中止</button>
                                                </div>
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
    <script>
        function scheduleRow(type, location, startTime, endTime, name, notes) {
            return {
                editing: false,
                type: type,
                workLocation: location,
                startTime: startTime,
                endTime: endTime,
                name: name,
                notes: notes,

                // コンポーネント初期化時に呼ばれる
                init() {
                    this.handleTypeChange();
                },

                // 種別が変更されたときに時刻のデフォルト値を設定する
                handleTypeChange() {
                    if (this.type === 'work') {
                        if (!this.startTime) this.startTime = '09:00';
                        if (!this.endTime) this.endTime = '18:00';
                    }
                },

                // フォームの送信処理
                async submitForm(date) {
                    const data = {
                        date: date,
                        type: this.type,
                        name: this.name,
                        notes: this.notes,
                        location: this.workLocation,
                        start_time: this.startTime,
                        end_time: this.endTime,
                    };

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
                        if (!response.ok) {
                            const errorData = await response.json();
                            console.error('Server Error:', errorData);
                            let errorMessage = '更新に失敗しました。入力内容を確認してください。\n\n';
                            if (errorData.errors) {
                                for (const key in errorData.errors) {
                                    errorMessage += `- ${errorData.errors[key].join(', ')}\n`;
                                }
                            }
                            alert(errorMessage);
                            return;
                        }

                        // ▼▼▼【location.reloadをwindow.location.reloadに修正】▼▼▼
                        window.location.reload();

                    } catch (error) {
                        console.error('Error:', error);
                        alert('更新中に予期せぬエラーが発生しました。');
                    }
                },

                // クリア処理
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

                        window.location.reload();
                    } catch (error) {
                        console.error('Error:', error);
                        alert('クリアに失敗しました。');
                    }
                }
            }
        }
    </script>
@endpush