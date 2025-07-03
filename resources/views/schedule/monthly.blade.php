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
                <form action="{{ route('transportation-expenses.batch-store') }}" method="POST"
                    onsubmit="
                        if (!{{ auth()->user()->default_transportation_amount > 0 ? 'true' : 'false' }}) {
                            alert('デフォルトの交通費が設定されていません。先に「デフォルトパターン設定」から交通費を登録してください。');
                            return false;
                        }
                        return confirm('{{ $targetMonth->format('Y年n月') }}の未登録の出勤日に、デフォルト交通費を一括登録します。よろしいですか？');
                    "
                >
                    @csrf
                    <input type="hidden" name="month" value="{{ $targetMonth->format('Y-m') }}">
                    <x-primary-button type="submit">
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
                                $dateString = $date->format('Y-m-d');
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
                                    '{{ $date->format('Y-m-d') }}',
                                    '{{ $day['override']?->type ?? 'location_only' }}',
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
                                        <form @submit.prevent="submitForm()">
                                            <div class="space-y-3 p-2">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <select x-model="type" @change="handleTypeChange()" class="form-select-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm">
                                                        <option value="location_only">場所のみ変更</option>
                                                        <option value="work">時間変更</option>
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
                                    <div x-show="!editing" class="flex justify-end items-center gap-3">
                                        <button @click="handleEditClick()"
                                                class="text-blue-600 hover:text-blue-800"
                                                x-text="isAfterDeadline() ? '変更申請' : '変更'">
                                        </button>
                                        <button @click="clearForm()"
                                                x-show="!editing && {{ $day['override'] ? 'true' : 'false' }} && !isAfterDeadline()"
                                                class="text-red-600 hover:text-red-800">
                                            クリア
                                        </button>
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
        function scheduleRow(date, type, location, startTime, endTime, name, notes) {
            return {
                date: date,
                type: type,
                workLocation: location,
                startTime: startTime,
                endTime: endTime,
                name: name,
                notes: notes,
                editing: false,

                init() { this.handleTypeChange(); },

                isAfterDeadline() {
                    const deadline = new Date();
                    deadline.setDate(deadline.getDate() + 14);
                    deadline.setHours(0, 0, 0, 0);
                    return new Date(this.date) < deadline;
                },

                handleEditClick() {
                    if (this.isAfterDeadline()) {
                        this.$dispatch('open-request-modal', {
                            date: this.date, type: this.type, location: this.workLocation,
                            startTime: this.startTime, endTime: this.endTime,
                            name: this.name, notes: this.notes
                        });
                    } else {
                        this.editing = true;
                    }
                },

                handleTypeChange() {
                    if (this.type === 'work') {
                        if (!this.startTime) this.startTime = '09:00';
                        if (!this.endTime) this.endTime = '18:00';
                    }
                },

                async submitForm() {
                    const data = {
                        date: this.date, type: this.type, name: this.name, notes: this.notes,
                        location: this.workLocation, start_time: this.startTime, end_time: this.endTime,
                    };
                    try {
                        const response = await fetch('{{ route("schedule.updateOrClearDay") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }, body: JSON.stringify(data), });
                        if (!response.ok) { const errorData = await response.json(); let errorMessage = (errorData.message || '更新に失敗しました。') + '\n\n'; if (errorData.errors) { for (const key in errorData.errors) { errorMessage += `- ${errorData.errors[key].join(', ')}\n`; } } alert(errorMessage); return; }
                        window.location.reload();
                    } catch (error) { alert('更新中に予期せぬエラーが発生しました。'); }
                },

                async clearForm() {
                    if (!confirm(this.date + ' の設定をクリアしますか？')) return;
                    try {
                        const response = await fetch('{{ route("schedule.updateOrClearDay") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }, body: JSON.stringify({ date: this.date, type: 'clear' }), });
                        if (!response.ok) throw new Error('サーバーエラーが発生しました。');
                        window.location.reload();
                    } catch (error) { alert('クリアに失敗しました。'); }
                }
            }
        }

        function requestModal() {
            return {
                isOpen: false,
                requestDate: '',
                requestReason: '',
                requestType: 'work',
                requestWorkLocation: 'office',
                requestStartTime: '',
                requestEndTime: '',
                requestName: '',
                requestNotes: '',

                openModal(event) {
                    const data = event.detail;
                    this.requestDate = data.date;
                    this.requestType = data.type;
                    this.requestWorkLocation = data.location;
                    this.requestStartTime = data.startTime;
                    this.requestEndTime = data.endTime;
                    this.requestName = data.name;
                    this.requestNotes = data.notes;
                    this.requestReason = '';
                    this.handleRequestTypeChange();
                    this.isOpen = true;
                },

                handleRequestTypeChange() {
                    if (this.requestType === 'work') {
                        if (!this.requestStartTime) this.requestStartTime = '09:00';
                        if (!this.requestEndTime) this.requestEndTime = '18:00';
                    }
                },

                async submitRequest() {
                    if (!confirm('この内容で申請しますか？')) {
                        return;
                    }
                    if (!this.requestReason) { alert('申請理由を入力してください。'); return; }
                    const data = {
                        date: this.requestDate, reason: this.requestReason, type: this.requestType,
                        name: this.requestName, notes: this.requestNotes, location: this.requestWorkLocation,
                        start_time: this.requestStartTime, end_time: this.requestEndTime,
                    };
                    try {
                        const response = await fetch('{{ route("shift-change-requests.store") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }, body: JSON.stringify(data), });
                        const result = await response.json();
                        if (!response.ok) { let errorMessage = result.message || '申請に失敗しました。'; if (result.errors) { errorMessage += '\n\n' + Object.values(result.errors).map(e => `- ${e.join('\n')}`).join('\n'); } throw new Error(errorMessage); }
                        alert(result.message);
                        this.isOpen = false;
                        window.location.reload();
                    } catch (error) { alert('申請中にエラーが発生しました:\n' + error.message); }
                }
            }
        }
    </script>

    <div x-data="requestModal()" x-show="isOpen" @open-request-modal.window="openModal($event)" @keydown.escape.window="isOpen = false" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="isOpen = false" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl dark:bg-gray-800 rounded-2xl">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">シフト変更申請 (<span x-text="requestDate"></span>)</h3>
                <div class="mt-4">
                    <form @submit.prevent="submitRequest()">
                        <div class="space-y-4">
                            <div>
                                <label for="request_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">申請理由 (必須)</label>
                                <textarea id="request_reason" x-model="requestReason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500" required></textarea>
                            </div><hr class="dark:border-gray-600">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">申請内容</p>
                            <div class="space-y-3 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                                <div class="flex flex-wrap items-center gap-2">
                                    <select x-model="requestType" @change="handleRequestTypeChange()" class="form-select-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm">
                                        <option value="location_only">場所のみ変更</option><option value="work">時間変更</option><option value="full_day_off">全休</option><option value="am_off">午前休</option><option value="pm_off">午後休</option><option value="clear">設定クリア</option>
                                    </select>
                                    <template x-if="requestType === 'work'"><div class="flex items-center gap-1"><input type="time" x-model="requestStartTime" class="form-input-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm w-24"><span>-</span><input type="time" x-model="requestEndTime" class="form-input-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm w-24"></div></template>
                                    <template x-if="requestType.includes('off')"><input type="text" x-model="requestName" class="form-input-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm" placeholder="休日の名称"></template>
                                </div>
                                <div class="space-y-2">
                                    <template x-if="requestType === 'work' || requestType === 'location_only'"><div class="flex items-center gap-3 text-sm"><span class="font-medium text-gray-600 dark:text-gray-400">場所:</span><label class="inline-flex items-center gap-1"><input type="radio" value="office" x-model="requestWorkLocation" class="form-radio text-blue-600"><span>出勤</span></label><label class="inline-flex items-center gap-1"><input type="radio" value="remote" x-model="requestWorkLocation" class="form-radio text-blue-600"><span>在宅</span></label></div></template>
                                    <template x-if="requestType !== 'clear'"><div><input type="text" x-model="requestNotes" class="form-input-sm dark:bg-gray-700 dark:border-gray-600 rounded-md text-sm w-full" placeholder="メモ (共有カレンダーで表示)"></div></template>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="isOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-500">キャンセル</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">この内容で申請する</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endpush