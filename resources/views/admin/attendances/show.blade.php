@extends('layouts.app')

@section('title', $user->name . 'さんの勤怠明細')

@push('styles')
    <style>
        .details-row {
            display: none;
        }

        .details-row.is-open {
            display: table-row;
        }

        .summary-row {
            cursor: pointer;
        }

        .details-icon {
            transition: transform 0.2s;
        }

        .summary-row.is-open .details-icon {
            transform: rotate(180deg);
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div x-data="attendancePage()">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
            {{-- ヘッダーと月ナビゲーション --}}
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    <a href="{{ route('admin.work-records.index') }}" class="text-blue-600 hover:underline">作業実績一覧</a>
                    <i class="fas fa-chevron-right fa-xs mx-2"></i>
                    {{ $user->name }}さんの勤怠明細
                </h1>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('admin.attendances.show', ['user' => $user, 'month' => $targetMonth->copy()->subMonth()->format('Y-m')]) }}"
                        class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"><i
                            class="fas fa-chevron-left"></i> 前月</a>
                    <span class="font-semibold text-lg">{{ $targetMonth->format('Y年n月') }}</span>
                    <a href="{{ route('admin.attendances.show', ['user' => $user, 'month' => $targetMonth->copy()->addMonth()->format('Y-m')]) }}"
                        class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">次月
                        <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4 border-b dark:border-gray-700 pb-3">
                    {{ $targetMonth->format('Y年n月') }} サマリー
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-y-4 gap-x-6">
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">総勤務時間 (拘束時間)</span>
                        <span class="text-xl font-bold text-gray-800 dark:text-gray-200 block">
                            {{ format_seconds_to_hms($monthTotalDetentionSeconds) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">総休憩時間</span>
                        <span class="text-xl font-bold text-gray-800 dark:text-gray-200 block">
                            {{ format_seconds_to_hms($monthTotalBreakSeconds) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">支払対象時間 (勤務 - 休憩)</span>
                        <span class="text-xl font-bold text-blue-600 dark:text-blue-400 block">
                            {{ format_seconds_to_hms($monthTotalActualWorkSeconds) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">実働時間 (作業ログ合計)</span>
                        <span class="text-xl font-bold text-green-600 dark:text-green-500 block">
                            {{ format_seconds_to_hms($monthTotalWorkLogSeconds) }}
                        </span>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t dark:border-gray-700">
                    <span class="text-base font-medium text-gray-500 dark:text-gray-400">月の給与合計</span>
                    <span class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 block mt-1">
                        ¥{{ number_format($monthTotalSalary, 0) }}
                    </span>
                </div>
            </div>

            {{-- 適用時給表示エリア --}}
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg mb-4 border dark:border-gray-600">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center"><i
                        class="fas fa-yen-sign mr-2 text-gray-500"></i>この月に適用される時給</h4>
                @forelse($applicableRates as $rate)
                    <li class="text-sm text-gray-700 dark:text-gray-300 list-disc list-inside mt-2 space-y-1 pl-2">
                        <strong class="font-bold text-blue-600 dark:text-blue-400">¥{{ number_format($rate->rate) }}</strong>
                        <span class="text-xs ml-2">({{ $rate->effective_date->format('Y/m/d') }} から適用)</span>
                    </li>
                @empty
                    <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-300"><i
                            class="fas fa-exclamation-circle mr-2"></i>適用される有効な時給が登録されていません。</p>
                @endforelse
            </div>

            {{-- 凡例エリア --}}
            <div class="flex items-center gap-2 text-xs flex-wrap mb-4">
                <span class="font-semibold mr-2">凡例:</span>
                <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded">土曜</span>
                <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/40 rounded">日曜</span>
                <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/40 rounded">祝日</span>
                <span class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/40 rounded">登録休日</span>
            </div>

            {{-- 計算方法の注釈エリア --}}
            <div
                class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg mb-4 text-xs border border-blue-200 dark:border-blue-800">
                <h4 class="font-semibold text-gray-800 dark:text-gray-200 flex items-center mb-1">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>各項目の計算方法について
                </h4>
                <ul class="list-disc list-inside pl-2 space-y-1 text-gray-700 dark:text-gray-300">
                    <li><strong>拘束時間:</strong> 「退勤時刻」から「出勤時刻」を引いた合計時間です。</li>
                    <li><strong>実働時間:</strong> 実際にPCを操作した作業ログ(WorkLog)の合計時間です。</li>
                    <li><strong>支払対象:</strong> 「拘束時間」から「休憩等」を引いた、日給計算の基準となる時間です。</li>
                    <li><strong>日給合計:</strong> 「支払対象」の時間に時給を掛けて算出されます。</li>
                </ul>
            </div>

            {{-- 勤怠テーブル --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="overflow-x-auto h-[75vh] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                            <tr>
                                <th class="w-10 px-2"></th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">日付</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">状態</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">出勤</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">退勤</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">拘束時間</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">休憩等</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">支払対象</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">実働時間</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">日給合計</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @php $weekMap = ['日', '月', '火', '水', '木', '金', '土']; @endphp
                            @foreach ($monthlyReport as $report)
                                @php
                                    $date = $report['date'];
                                    $rowClass = '';
                                    if (isset($report['work_shift']) && in_array($report['work_shift']->type, ['full_day_off', 'am_off', 'pm_off'])) {
                                        $rowClass = 'bg-yellow-50 dark:bg-yellow-900/30';
                                    } elseif ($date->isSaturday()) {
                                        $rowClass = 'bg-blue-50 dark:bg-blue-900/30';
                                    } elseif ($date->isSunday()) {
                                        $rowClass = 'bg-red-50 dark:bg-red-900/30';
                                    } elseif ($report['public_holiday']) {
                                        $rowClass = 'bg-green-50 dark:bg-green-900/30';
                                    }
                                @endphp

                                @if($report['type'] === 'edited')
                                    @include('admin.attendances.partials.row-edited', ['report' => $report, 'date' => $date, 'rowClass' => $rowClass, 'weekMap' => $weekMap])
                                @elseif($report['type'] === 'workday')
                                    @include('admin.attendances.partials.row-workday', ['report' => $report, 'date' => $date, 'rowClass' => $rowClass, 'weekMap' => $weekMap])
                                @else
                                    @include('admin.attendances.partials.row-dayoff', ['report' => $report, 'date' => $date, 'rowClass' => $rowClass, 'weekMap' => $weekMap])
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100 dark:bg-gray-700 sticky bottom-0">
                            <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                                <td class="px-2 py-3 text-left" colspan="7">月の合計</td>
                                <td class="px-2 py-3 text-left whitespace-nowrap text-blue-600 dark:text-blue-400">
                                    {{ format_seconds_to_hms($monthTotalActualWorkSeconds) }}
                                </td>
                                <td></td>
                                <td class="px-2 py-3 text-left whitespace-nowrap">¥{{ number_format($monthTotalSalary, 0) }}
                                </td>
                                <td class="px-2 py-3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- 編集用モーダルウィンドウ --}}
        <div x-show="editModalOpen" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
            @keydown.escape.window="closeEditModal()">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-lg"
                @click.outside="closeEditModal()">
                <h3 class="text-lg font-semibold mb-4" x-text="`勤怠編集 (${modal.date})`"></h3>
                <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                    {{-- 休日として登録するチェックボックス --}}
                    <div
                        class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" x-model="modal.is_day_off"
                                class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                <i class="fas fa-calendar-times mr-1 text-yellow-600"></i>休日として登録する
                            </span>
                        </label>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 ml-6">
                            ※作業ログがない日を休日として登録できます。交通費がある場合は自動的にクリアされます。
                        </p>
                    </div>

                    {{-- 出勤場所選択 --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            <i class="fas fa-map-marker-alt mr-1 text-gray-500"></i>出勤場所
                        </label>
                        <div class="flex gap-3">
                            <label class="flex items-center space-x-2 cursor-pointer p-3 border rounded-md flex-1"
                                :class="modal.location === 'remote' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'">
                                <input type="radio" x-model="modal.location" value="remote"
                                    class="text-blue-600 focus:ring-blue-500">
                                <span class="text-sm font-medium">
                                    <i class="fas fa-home mr-1"></i>在宅
                                </span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer p-3 border rounded-md flex-1"
                                :class="modal.location === 'office' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600'">
                                <input type="radio" x-model="modal.location" value="office"
                                    class="text-green-600 focus:ring-green-500">
                                <span class="text-sm font-medium">
                                    <i class="fas fa-building mr-1"></i>出勤
                                </span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                            ※在宅→出勤に変更した場合、デフォルト交通費が自動登録されます。出勤→在宅に変更した場合、交通費は削除されます。
                        </p>
                    </div>

                    <div x-show="!modal.is_day_off">
                        <label class="block text-sm font-medium">出勤時間</label>
                        <input type="time" x-model="modal.start_time" class="mt-1 w-full dark:bg-gray-700 rounded-md">
                    </div>
                    <div x-show="!modal.is_day_off">
                        <label class="block text-sm font-medium">退勤時間</label>
                        <input type="time" x-model="modal.end_time" class="mt-1 w-full dark:bg-gray-700 rounded-md">
                        <p class="mt-1 text-red-500 text-xs">※出勤時間より早い時刻を選択した場合、翌日の退勤時間となります。</p>
                    </div>
                    <div x-show="!modal.is_day_off">
                        <label class="block text-sm font-medium mb-1">休憩 / 中抜け</label>
                        <div class="space-y-2">
                            <template x-for="(br, index) in modal.breaks" :key="index">
                                <div class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                                    <select x-model="br.type" class="mt-1 block w-1/4 dark:bg-gray-600 rounded-md text-sm">
                                        <option value="break">休憩</option>
                                        <option value="away">中抜け</option>
                                    </select>
                                    <input type="time" x-model="br.start_time"
                                        class="mt-1 w-full dark:bg-gray-600 rounded-md text-sm">
                                    <span class="dark:text-gray-400">-</span>
                                    <input type="time" x-model="br.end_time"
                                        class="mt-1 w-full dark:bg-gray-600 rounded-md text-sm">
                                    <button @click="modal.breaks.splice(index, 1)"
                                        class="p-2 text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <button @click="modal.breaks.push({type: 'break', start_time: '', end_time: ''})"
                            class="mt-2 text-sm text-blue-600 hover:underline">
                            <i class="fas fa-plus-circle mr-1"></i>休憩/中抜けを追加
                        </button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">備考</label>
                        <textarea x-model="modal.note" rows="3" class="mt-1 w-full dark:bg-gray-700 rounded-md"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <x-secondary-button @click="closeEditModal()">キャンセル</x-secondary-button>
                    <x-primary-button @click="saveEdit()">保存</x-primary-button>
                    <button @click="clearData()" class="text-red-500 hover:underline text-sm">データをクリア</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('attendancePage', () => ({
                editModalOpen: false,
                modal: {
                    date: '',
                    start_time: '',
                    end_time: '',
                    breaks: [],
                    note: '',
                    is_day_off: false,
                    location: 'remote'
                },
                openEditModal(date, startTime, endTime, breaksJson, note, location) {
                    this.modal.date = date;
                    this.modal.start_time = startTime;
                    this.modal.end_time = endTime;
                    this.modal.breaks = breaksJson ? JSON.parse(breaksJson) : [];
                    this.modal.note = note;
                    this.modal.is_day_off = false; // デフォルトはfalse
                    this.modal.location = location || 'remote'; // デフォルトは在宅
                    this.editModalOpen = true;
                },
                closeEditModal() {
                    this.editModalOpen = false;
                },
                saveEdit() {
                    this.submitData(this.modal);
                },
                clearData() {
                    if (confirm(`${this.modal.date} のデータをクリアします。よろしいですか？`)) {
                        this.submitData({
                            start_time: '',
                            end_time: '',
                            breaks: [],
                            note: '',
                            is_day_off: false,
                            location: 'remote'
                        });
                        this.closeEditModal();
                    }
                },
                submitData(payload) {
                    const url = `/admin/attendances/{{ $user->id }}/${this.modal.date}`;
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(res => {
                            if (res.ok) {
                                return res.json();
                            }
                            return Promise.reject(res);
                        })
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || '保存に失敗しました。');
                            }
                        })
                        .catch(err => {
                            if (err instanceof Response && err.status === 422) {
                                err.json().then(errorData => {
                                    const messages = Object.values(errorData.errors).flat();
                                    const errorMessage = messages.join('\n');
                                    alert(errorMessage);
                                });
                            } else {
                                alert('エラーが発生しました。');
                                console.error(err);
                            }
                        });
                }
            }));
        });

        // アコーディオン機能
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.summary-row').forEach(row => {
                row.addEventListener('click', (e) => {
                    if (e.target.closest('button, a')) return;

                    const targetId = row.dataset.detailsTarget;
                    const detailsRow = document.getElementById(targetId);
                    if (detailsRow) {
                        detailsRow.classList.toggle('is-open');
                        row.classList.toggle('is-open');
                    }
                });
            });
        });
    </script>
@endpush