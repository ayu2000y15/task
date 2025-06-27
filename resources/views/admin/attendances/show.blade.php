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
                    <li><strong>実働時間:</strong> 作業ログ(WorkLog)に記録された、純粋な作業時間の合計です。</li>
                    <li><strong>日給合計:</strong> (拘束時間 - 休憩等)
                        の時間に時給を掛けて算出されます。<strong><u>実働時間とは異なる基準</u></strong>で計算される点にご注意ください。
                    </li>
                </ul>
            </div>

            {{-- 勤怠テーブル --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="overflow-x-auto h-[75vh] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                            <tr>
                                {{-- ▼▼▼【追加】トグルアイコン用の列を追加 ▼▼▼ --}}
                                <th class="w-10 px-2"></th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">日付</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">状態</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">出勤</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">退勤</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">拘束時間</th>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">休憩等</th>
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
                                <td class="px-2 py-3 text-left whitespace-nowrap">
                                    {{ gmdate('H:i:s', $monthTotalActualWorkSeconds) }}
                                </td>
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
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md"
                @click.outside="closeEditModal()">
                <h3 class="text-lg font-semibold mb-4" x-text="`勤怠編集 (${modal.date})`"></h3>
                <div>
                    <label class="block text-sm font-medium">出勤時間</label>
                    <input type="time" x-model="modal.start_time" class="mt-1 w-full dark:bg-gray-700 rounded-md">
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium">退勤時間</label>
                    <input type="time" x-model="modal.end_time" class="mt-1 w-full dark:bg-gray-700 rounded-md">
                    <p class="mt-1 text-red-500 text-xs">※出勤時間より早い時刻を選択した場合、翌日の退勤時間となります。</p>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium">休憩（分）</label>
                    <input type="number" x-model="modal.break_minutes" class="mt-1 w-full dark:bg-gray-700 rounded-md">
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium">備考</label>
                    <textarea x-model="modal.note" rows="3" class="mt-1 w-full dark:bg-gray-700 rounded-md"></textarea>
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
                modal: { date: '', start_time: '', end_time: '', break_minutes: 0, note: '' },
                openEditModal(date, startTime, endTime, breakMinutes, note) {
                    this.modal.date = date;
                    this.modal.start_time = startTime;
                    this.modal.end_time = endTime;
                    this.modal.break_minutes = breakMinutes;
                    this.modal.note = note;
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
                        this.submitData({ start_time: '', end_time: '' });
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
                        .then(res => res.ok ? res.json() : Promise.reject(res))
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || '保存に失敗しました。');
                            }
                        })
                        .catch(err => {
                            alert('エラーが発生しました。');
                            console.error(err);
                        });
                }
            }));
        });

        // アコーディオン機能
        document.addEventListener('DOMContentLoaded', function () {
            // このリスナーはtbodyに委譲する方が動的に追加される要素にも対応できるが、
            // 今回はページリロードが前提のため、このままでも機能する
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