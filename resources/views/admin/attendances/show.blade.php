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
    </style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- ヘッダーと月ナビゲーション --}}
        <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                <a href="{{ route('admin.work-records.index') }}" class="text-blue-600 hover:underline">作業実績一覧</a>
                <i class="fas fa-chevron-right fa-xs mx-2"></i>
                {{ $user->name }}さんの勤怠明細
            </h1>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.attendances.show', ['user' => $user, 'month' => $targetMonth->copy()->subMonth()->format('Y-m')]) }}" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"><i class="fas fa-chevron-left"></i> 前月</a>
                <span class="font-semibold text-lg">{{ $targetMonth->format('Y年n月') }}</span>
                <a href="{{ route('admin.attendances.show', ['user' => $user, 'month' => $targetMonth->copy()->addMonth()->format('Y-m')]) }}" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">次月 <i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        {{-- ▼▼▼【ここに追加】適用時給表示エリア ▼▼▼ --}}
        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg mb-4 border dark:border-gray-600">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                <i class="fas fa-yen-sign mr-2 text-gray-500"></i>
                この月に適用される時給
            </h4>
            @if($applicableRates->isNotEmpty())
                <ul class="list-disc list-inside mt-2 space-y-1 pl-2">
                    @foreach($applicableRates as $rate)
                        <li class="text-sm text-gray-700 dark:text-gray-300">
                            <strong class="font-bold text-blue-600 dark:text-blue-400">¥{{ number_format($rate->rate) }}</strong>
                            <span class="text-xs ml-2">({{ $rate->effective_date->format('Y/m/d') }} から適用)</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    適用される有効な時給が登録されていません。
                </p>
            @endif
        </div>
    {{-- ▲▲▲ 追加ここまで ▲▲▲ --}}

        {{-- アクションボタンと凡例 --}}
        <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
            <div class="flex items-center gap-2 text-xs flex-wrap">
                <span class="font-semibold mr-2">凡例:</span>
                <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded">土曜</span>
                <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/40 rounded">日曜</span>
                <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/40 rounded">祝日</span>
                <span class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/40 rounded">登録休日</span>
            </div>
            <form action="{{ route('admin.attendances.generate', ['user' => $user, 'month' => $targetMonth->format('Y-m')]) }}" method="POST" class="flex-shrink-0">
                @csrf
                <x-secondary-button type="submit" onclick="return confirm('作業ログから勤怠データを再計算します。手動で編集した行は上書きされません。よろしいですか？')">
                    <i class="fas fa-sync-alt mr-2"></i>作業ログから自動計算
                </x-secondary-button>
            </form>
        </div>

        {{-- 勤怠テーブル --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto h-[75vh] overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">日付</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">状態</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">出勤</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">退勤</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">休憩(分)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">実働時間</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">日給合計</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">備考</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                        </tr>
                    </thead>
                    <tbody id="attendance-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @php $weekMap = ['日', '月', '火', '水', '木', '金', '土']; @endphp
                        @foreach ($monthlyReport as $day)
                            @php
                                $date = $day['date'];
                                $attendance = $day['attendance'];
                                $userHoliday = $day['user_holiday'];
                                $publicHolidayName = $day['public_holiday_name'];
                                $logs = $day['logs'];
                                $hasLogs = $logs->isNotEmpty();

                                $rowClass = '';
                                if ($userHoliday) { $rowClass = 'bg-yellow-50 dark:bg-yellow-900/30'; }
                                elseif ($date->isSaturday()) { $rowClass = 'bg-blue-50 dark:bg-blue-900/30'; }
                                elseif ($date->isSunday()) { $rowClass = 'bg-red-50 dark:bg-red-900/30'; }
                                elseif ($publicHolidayName) { $rowClass = 'bg-green-50 dark:bg-green-900/30'; }
                            @endphp

                            {{-- ▼▼▼【ここから修正】tbodyのループ内を単一の構造に統一 ▼▼▼ --}}
                            <tr class="{{ $rowClass }} attendance-row @if($hasLogs) summary-row hover:bg-gray-50 dark:hover:bg-gray-700/50 @endif"
                                @if($hasLogs) data-details-target="details-{{ $date->format('Y-m-d') }}" @endif
                                data-date="{{ $date->format('Y-m-d') }}">

                                {{-- 日付セル --}}
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="font-medium">{{ $date->format('n/j') }} ({{ $weekMap[$date->dayOfWeek] }})</div>
                                    @if($publicHolidayName) <div class="text-xs text-green-700 dark:text-green-300">{{ $publicHolidayName }}</div>
                                    @elseif($userHoliday) <div class="text-xs text-yellow-700 dark:text-yellow-300">{{ $userHoliday->name }}</div>
                                    @endif
                                </td>

                                {{-- 状態セル --}}
                                <td class="px-4 py-2 text-center">
                                    @if($attendance->status === 'calculated')
                                        <span title="自動計算"><i class="fas fa-magic text-blue-500"></i></span>
                                    @elseif($attendance->exists)
                                        <span title="手動編集"><i class="fas fa-pencil-alt text-gray-500"></i></span>
                                    @else
                                        <span title="新規入力"><i class="far fa-square text-gray-400"></i></span>
                                    @endif

                                    {{-- 作業ログがある場合のみ詳細表示アイコンを表示 --}}
                                    @if($hasLogs)
                                        <i class="fas fa-chevron-down details-icon ml-2"></i>
                                    @endif
                                </td>

                                {{-- 入力フォームのセル（共通） --}}
                                <td class="px-4 py-2"><input type="time" name="start_time" value="{{ optional($attendance->start_time)->format('H:i') }}" class="attendance-input start-time-input w-24 bg-white dark:bg-gray-700 border-gray-300 rounded-md text-sm"></td>
                                <td class="px-4 py-2"><input type="time" name="end_time" value="{{ optional($attendance->end_time)->format('H:i') }}" class="attendance-input end-time-input w-24 bg-white dark:bg-gray-700 border-gray-300 rounded-md text-sm"></td>
                                <td class="px-4 py-2"><input type="number" name="break_minutes" value="{{ floor($attendance->break_seconds / 60) }}" placeholder="分" class="attendance-input break-minutes-input w-20 bg-white dark:bg-gray-700 border-gray-300 rounded-md text-sm"></td>
                                <td class="px-4 py-2 font-semibold whitespace-nowrap actual-work-display">{{ $attendance->exists ? gmdate('H:i:s', $attendance->actual_work_seconds) : '-' }}</td>
                                <td class="px-4 py-2 whitespace-nowrap daily-salary-display">{{ $attendance->exists ? '¥' . number_format($attendance->daily_salary, 0) : '-' }}</td>
                                <td class="px-4 py-2"><input type="text" name="note" value="{{ $attendance->note }}" class="attendance-input w-full bg-white dark:bg-gray-700 border-gray-300 rounded-md text-sm"></td>
                                <td class="px-4 py-2"><x-primary-button type="button" class="save-row-btn text-xs !py-1 !px-2" data-user-id="{{ $user->id }}">保存</x-primary-button></td>
                            </tr>

                            {{-- 詳細表示行（作業ログがある場合のみレンダリング） --}}
                            @if($hasLogs)
                                <tr class="details-row" id="details-{{ $date->format('Y-m-d') }}">
                                    <td colspan="9" class="p-0">
                                        <div class="bg-gray-100 dark:bg-gray-900/50 p-4">
                                            <h6 class="font-semibold text-sm mb-2">作業ログ詳細</h6>
                                            <table class="min-w-full text-sm">
                                                <thead class="border-b-2 border-gray-300 dark:border-gray-600">
                                                    <tr>
                                                        <th class="py-2 px-3 text-left">案件</th>
                                                        <th class="py-2 px-3 text-left">キャラクター</th>
                                                        <th class="py-2 px-3 text-left">工程</th>
                                                        <th class="py-2 px-3 text-left">開始</th>
                                                        <th class="py-2 px-3 text-left">終了</th>
                                                        <th class="py-2 px-3 text-left">作業時間</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($logs as $log)
                                                        <tr class="border-b dark:border-gray-600/50">
                                                            <td class="py-2 px-3">{{ $log->task->project->title ?? '-' }}</td>
                                                            <td class="py-2 px-3">{{ optional($log->task->character)->name ?? '-' }}</td>
                                                            <td class="py-2 px-3">{{ $log->task->name ?? '-' }}</td>
                                                            <td class="py-2 px-3">{{ $log->start_time->format('H:i') }}</td>
                                                            <td class="py-2 px-3">{{ optional($log->end_time)->format('H:i') }}</td>
                                                            <td class="py-2 px-3">{{ gmdate('H:i:s', $log->effective_duration) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="5" class="py-4 px-3 text-center text-gray-500">この日の作業ログはありません。</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                            {{-- ▲▲▲ 修正ここまで ▲▲▲ --}}
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-700 sticky bottom-0">
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                            <td class="px-4 py-3 text-left" colspan="5">月の合計</td>
                            <td class="px-4 py-3 text-left whitespace-nowrap">{{ gmdate('H:i:s', $monthTotalActualWorkSeconds) }}</td>
                            <td class="px-4 py-3 text-left whitespace-nowrap">¥{{ number_format($monthTotalSalary, 0) }}</td>
                            <td class="px-4 py-3" colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // PHPから渡された時給履歴をパース
        const hourlyRates = JSON.parse('{!! $hourlyRatesJson !!}');

        const attendanceTable = document.getElementById('attendance-table');
        if (!attendanceTable) return;

        // --- リアルタイム計算ロジック ---
        function recalculateRow(row) {
            const startTimeStr = row.querySelector('.start-time-input').value;
            const endTimeStr = row.querySelector('.end-time-input').value;
            const breakMinutes = parseInt(row.querySelector('.break-minutes-input').value) || 0;

            const actualWorkDisplay = row.querySelector('.actual-work-display');
            const dailySalaryDisplay = row.querySelector('.daily-salary-display');

            if (!startTimeStr || !endTimeStr) {
                actualWorkDisplay.textContent = '-';
                dailySalaryDisplay.textContent = '-';
                return;
            }

            let start = new Date(`1970-01-01T${startTimeStr}:00`);
            let end = new Date(`1970-01-01T${endTimeStr}:00`);

            // 日付をまたぐ場合
            if (end <= start) {
                end.setDate(end.getDate() + 1);
            }

            const attendanceSeconds = (end - start) / 1000;
            const breakSeconds = breakMinutes * 60;
            const actualWorkSeconds = Math.max(0, attendanceSeconds - breakSeconds);

            // 実働時間のフォーマット
            const h = String(Math.floor(actualWorkSeconds / 3600)).padStart(2, '0');
            const m = String(Math.floor((actualWorkSeconds % 3600) / 60)).padStart(2, '0');
            const s = String(actualWorkSeconds % 60).padStart(2, '0');
            actualWorkDisplay.textContent = `${h}:${m}:${s}`;

            // 日給の計算
            const rowDate = row.dataset.date;
            let rateForDay = 0;
            // 時給履歴は適用日の降順でソートされていると仮定
            for (const rate of hourlyRates) {
                if (rowDate >= rate.effective_date) {
                    rateForDay = parseFloat(rate.rate);
                    break; // 最初に見つかったものがその日に適用される最新のレート
                }
            }

            if (rateForDay > 0) {
                const salary = Math.round((actualWorkSeconds / 3600) * rateForDay);
                dailySalaryDisplay.textContent = `¥${salary.toLocaleString()}`;
            } else {
                dailySalaryDisplay.textContent = '¥0';
            }
        }

        // --- 行単位での保存ロジック ---
        function saveRow(button) {
            const row = button.closest('.attendance-row');
            const date = row.dataset.date;
            const userId = button.dataset.userId;

            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            const data = {
                start_time: row.querySelector('[name="start_time"]').value,
                end_time: row.querySelector('[name="end_time"]').value,
                break_minutes: row.querySelector('[name="break_minutes"]').value,
                note: row.querySelector('[name="note"]').value,
            };

            fetch(`/admin/attendances/${userId}/${date}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(res => res.ok ? res.json() : Promise.reject(res))
            .then(data => {
                if(data.success) {
                    button.innerHTML = '<i class="fas fa-check"></i> 保存済';
                    if(data.attendance) {
                        row.querySelector('.actual-work-display').textContent = data.attendance.actual_work_seconds_formatted;
                        row.querySelector('.daily-salary-display').textContent = data.attendance.daily_salary_formatted;
                    }
                    setTimeout(() => {
                        button.innerHTML = '保存';
                        button.disabled = false;
                        // 保存成功後、ページをリロードして状態アイコンなどを正しく反映させる
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || '保存に失敗しました。');
                }
            })
            .catch(err => {
                err.json().then(jsonErr => {
                    alert(jsonErr.message || 'エラーが発生しました。');
                }).catch(() => {
                    alert('エラーが発生しました。');
                });
                button.innerHTML = '保存';
                button.disabled = false;
            });
        }

        // --- イベントリスナーの設定 ---
        attendanceTable.addEventListener('input', function(e) {
            if (e.target.closest('.attendance-input')) {
                recalculateRow(e.target.closest('.attendance-row'));
            }
        });

        attendanceTable.addEventListener('click', function(e) {
            if (e.target.closest('.save-row-btn')) {
                saveRow(e.target.closest('.save-row-btn'));
            }
        });

        // アコーディオン機能
        document.querySelectorAll('.summary-row').forEach(row => {
            row.addEventListener('click', (e) => {
                if (e.target.closest('input, button')) return;
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