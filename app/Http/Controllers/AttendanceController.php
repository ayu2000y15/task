<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceLog;
use App\Models\WorkLog; // WorkLogモデルを追加
use App\Models\User; // Userモデルを追加
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB; // DBファサードを追加
use Illuminate\Support\Facades\Log; // Logファサードを追加
use App\Models\WorkShift;
use App\Models\DefaultShiftPattern;
use App\Models\TransportationExpense;

class AttendanceController extends Controller
{
    /**
     * 勤怠を打刻する
     */
    public function clock(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:clock_in,clock_out,break_start,break_end,away_start,away_end'],
            'memo' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        // デバッグ用ログ: リクエストされた場所とサーバーで計算した日付を残す
        $appTz = config('app.timezone') ?: 'UTC';
        $computedDate = Carbon::now($appTz)->toDateString();
        Log::info('changeLocationOnClockIn requested', ['user_id' => $user->id, 'request_date' => $request->input('date'), 'computed_date' => $computedDate, 'location' => $request->input('location')]);
        $type = $request->input('type');
        $now = Carbon::now();

        // ▼▼▼ 状態チェックロジックはそのまま活かします ▼▼▼
        $currentStatus = $user->getCurrentAttendanceStatus();

        $allowedActions = [
            'clocked_out' => ['clock_in'],
            'working'     => ['clock_out', 'break_start', 'away_start'],
            'on_break'    => ['break_end'],
            'on_away'     => ['away_end'],
        ];

        if (!isset($allowedActions[$currentStatus]) || !in_array($type, $allowedActions[$currentStatus])) {
            $currentStatusJapanese = ['clocked_out' => '未出勤', 'working' => '出勤中', 'on_break' => '休憩中', 'on_away' => '中抜け中'][$currentStatus] ?? '不明な状態';
            $requestedActionJapanese = $this->getJapaneseActionName($type);
            return response()->json(['success' => false, 'message' => "現在のステータスは「{$currentStatusJapanese}」です。「{$requestedActionJapanese}」はできません。画面を更新してから再度ボタンを押してください。"], 409);
        }

        if (in_array($type, ['clock_out', 'break_start', 'away_start']) && $user->hasActiveWorkLog()) {
            return response()->json(['success' => false, 'message' => "実行中の作業があります。この操作を続ける前に、すべての作業を停止または完了してください。\n実行中の作業はホームから確認できます。"], 409);
        }
        // ▲▲▲ 状態チェックロジックここまで ▲▲▲

        DB::beginTransaction();
        try {
            // 再利用用に date をここで決める
            $appTz = config('app.timezone') ?: 'UTC';
            $date = Carbon::now($appTz)->toDateString();
            // ▼▼▼ 日跨ぎ処理ここから ▼▼▼
            $lastAttendanceLog = AttendanceLog::where('user_id', $user->id)
                ->orderByDesc('timestamp')
                ->first();

            // 最後のログが昨日以前の場合、日跨ぎ処理を実行
            if ($lastAttendanceLog && $lastAttendanceLog->timestamp->isBefore($now->copy()->startOfDay())) {
                $this->handleOvernightShift($user, $lastAttendanceLog);
            }
            // ▲▲▲ 日跨ぎ処理ここまで ▲▲▲

            // 本来の打刻を記録
            AttendanceLog::create([
                'user_id' => $user->id,
                'type' => $type,
                'timestamp' => $now,
                'memo' => $request->input('memo'),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Clocking error for user ' . $user->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'サーバーエラーが発生しました。'], 500);
        }


        // 状態を更新するためにキャッシュをクリア
        Cache::forget('attendance_status_' . $user->id);

        $response = [
            'success' => true,
            'message' => $this->getJapaneseActionName($type) . 'しました。',
            'new_status' => $user->fresh()->getCurrentAttendanceStatus(),
        ];
        return response()->json($response);
    }

    /**
     * 日付をまたいだ勤務と作業ログを分割するプライベートメソッド
     */
    private function handleOvernightShift(User $user, AttendanceLog $lastLog)
    {
        $lastLogTimestamp = $lastLog->timestamp;
        $endOfLastDay = $lastLogTimestamp->copy()->endOfDay(); // 前日の23:59:59
        $startOfNextDay = $endOfLastDay->copy()->addSecond(); // 翌日の00:00:00

        // 最後のログが "出勤" または "復帰" 系の場合（稼働中だった場合）
        if (in_array($lastLog->type, ['clock_in', 'break_end', 'away_end'])) {
            // 1. 勤怠ログの分割（退勤と翌日の出勤）
            AttendanceLog::create([
                'user_id' => $user->id,
                'type' => 'clock_out',
                'timestamp' => $endOfLastDay,
                'memo' => '日跨ぎ自動処理',
            ]);
            AttendanceLog::create([
                'user_id' => $user->id,
                'type' => 'clock_in',
                'timestamp' => $startOfNextDay,
                'memo' => '日跨ぎ自動処理',
            ]);

            // 2. 進行中の作業ログの分割
            $activeWorkLog = WorkLog::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('start_time', '<=', $endOfLastDay)
                ->first();

            if ($activeWorkLog) {
                // 既存のログを停止
                $activeWorkLog->update([
                    'end_time' => $endOfLastDay,
                    'status' => 'stopped',
                    'effective_duration' => $endOfLastDay->diffInSeconds($activeWorkLog->start_time),
                ]);

                // 翌日分として新しいログを開始
                WorkLog::create([
                    'user_id' => $user->id,
                    'task_id' => $activeWorkLog->task_id,
                    'start_time' => $startOfNextDay,
                    'end_time' => null,
                    'status' => 'active',
                    'memo' => '（日跨ぎ自動継続）',
                ]);
            }
        }
        // 最後のログが "休憩開始" または "中抜け開始" の場合
        elseif (in_array($lastLog->type, ['break_start', 'away_start'])) {
            // 1. 対応する終了タイプを決定
            $endType = ($lastLog->type === 'break_start') ? 'break_end' : 'away_end';

            // 2. 休憩/中抜けを前日付けで終了
            AttendanceLog::create([
                'user_id' => $user->id,
                'type' => $endType,
                'timestamp' => $endOfLastDay,
                'memo' => '日跨ぎ自動処理',
            ]);

            // 3. 前日付けで退勤
            AttendanceLog::create([
                'user_id' => $user->id,
                'type' => 'clock_out',
                'timestamp' => $endOfLastDay,
                'memo' => '日跨ぎ自動処理',
            ]);

            // 4. 翌日付けで出勤
            AttendanceLog::create([
                'user_id' => $user->id,
                'type' => 'clock_in',
                'timestamp' => $startOfNextDay,
                'memo' => '日跨ぎ自動処理',
            ]);

            // 5. 翌日付けで元の休憩/中抜けを再開
            AttendanceLog::create([
                'user_id' => $user->id,
                'type' => $lastLog->type, // 元の'break_start' or 'away_start'
                'timestamp' => $startOfNextDay,
                'memo' => '日跨ぎ自動処理',
            ]);
            // 注意: このシナリオではアクティブな作業ログはないはずなので、WorkLogの分割は行いません。
        }
    }

    private function getJapaneseActionName(string $type): string
    {
        // (このメソッドは変更なし)
        return [
            'clock_in' => '出勤',
            'clock_out' => '退勤',
            'break_start' => '休憩開始',
            'break_end' => '休憩終了',
            'away_start' => '中抜け開始',
            'away_end' => '中抜け終了',
        ][$type] ?? '打刻';
    }

    /**
     * 自分の勤怠履歴を表示する
     */
    public function index(Request $request)
    {
        // (このメソッドは変更なし)
        $user = Auth::user();
        $month = $request->input('month', now()->format('Y-m'));
        try {
            $currentMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception $e) {
            $currentMonth = now()->startOfMonth();
        }
        $logs = AttendanceLog::where('user_id', $user->id)
            ->whereYear('timestamp', $currentMonth->year)
            ->whereMonth('timestamp', $currentMonth->month)
            ->orderBy('timestamp', 'asc')
            ->get()
            ->groupBy(function ($log) {
                return $log->timestamp->format('Y-m-d');
            });
        return view('my-attendance.index', [
            'logsByDate' => $logs,
            'currentMonth' => $currentMonth,
        ]);
    }

    /**
     * 出勤ボタンで予定と違う場所を選んだときに work_shifts を更新または作成します。
     * 期待するパラメータ: `date` (YYYY-MM-DD), `location` (string: 'office'|'home' 等)
     */
    public function changeLocationOnClockIn(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'location' => ['required', 'string', 'max:64'],
        ]);

        $user = Auth::user();
        // Ensure date uses server/app timezone (avoid client UTC off-by-one)
        // Ignore client's raw date and derive date from server's current date in app timezone.
        $appTz = config('app.timezone') ?: 'UTC';
        $date = Carbon::now($appTz)->toDateString();
        $location = $request->input('location');

        try {
            // デフォルトシフトの場所と異なる場合のみ WorkShift を作成/更新する
            $dayOfWeek = Carbon::parse($date, $appTz)->dayOfWeek; // 0=Sun .. 6=Sat
            $defaultPattern = DefaultShiftPattern::where('user_id', $user->id)
                ->where('day_of_week', $dayOfWeek)
                ->first();
            $defaultLocation = $defaultPattern ? $defaultPattern->location : null;

            // 既存の WorkShift を先に取得しておく
            $workShift = WorkShift::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->first();

            if ($defaultLocation !== $location) {
                // デフォルトと異なる場合は作成または更新
                if ($workShift) {
                    $workShift->update([
                        'location' => $location,
                        'notes' => '出勤時に変更（自動）',
                    ]);
                } else {
                    WorkShift::create([
                        'user_id' => $user->id,
                        'date' => $date,
                        'type' => 'location_only',
                        'location' => $location,
                        'notes' => '出勤時に変更（自動）',
                    ]);
                }
            } else {
                // デフォルトと同じ場所の場合でも、既に WorkShift が存在して別の場所が入っていれば更新する
                if ($workShift && $workShift->location !== $location) {
                    $workShift->update([
                        'location' => $location,
                        'notes' => '出勤時に変更（自動）',
                    ]);
                }
                // それ以外は何もしない（デフォルト通りなので追加作成不要）
            }

            // 出勤を選択した場合、当日の交通費が未登録であればデフォルト交通費を自動登録する
            $extraMessage = null;
            $transportationCreated = false;
            if ($location === 'office') {
                $exists = TransportationExpense::where('user_id', $user->id)
                    ->whereDate('date', $date)
                    ->exists();

                if (!$exists) {
                    // ユーザーにデフォルト交通費設定があるか参照
                    $departure = $user->default_transportation_departure ?? null;
                    $destination = $user->default_transportation_destination ?? null;
                    $amount = $user->default_transportation_amount ?? null;

                    // 必須のデフォルト設定が揃っているか確認する
                    if (is_null($amount) || $amount === 0) {
                        $extraMessage = 'デフォルト交通費が設定されていなかったため、交通費の登録ができませんでした。';
                    } elseif (empty($departure) || empty($destination)) {
                        $extraMessage = 'デフォルトの出発地または到着地が設定されていなかったため、交通費の登録ができませんでした。';
                        Log::warning('Missing default departure/destination for user ' . $user->id . ' when auto-creating transportation.');
                    } else {
                        try {
                            TransportationExpense::create([
                                'user_id' => $user->id,
                                'date' => $date,
                                'departure' => $departure,
                                'destination' => $destination,
                                'amount' => $amount,
                                'notes' => '出勤時に自動作成',
                            ]);
                            $transportationCreated = true;
                        } catch (\Exception $e) {
                            Log::warning('Auto create transportation expense failed for user ' . $user->id . ' on ' . $date . ': ' . $e->getMessage());
                            $extraMessage = '交通費の自動登録に失敗しましたが、出勤処理は完了しました。';
                        }
                    }
                }
            }

            $response = ['success' => true, 'message' => '出勤場所を登録しました。'];
            if (!empty($extraMessage)) {
                $response['note'] = $extraMessage;
            }
            if ($transportationCreated) {
                $response['transportation_created'] = true;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('changeLocationOnClockIn error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => '場所の更新に失敗しました。'], 500);
        }
    }
}
