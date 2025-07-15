<?php
// app/Http/Controllers/WorkLogController.php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\WorkLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;


class WorkLogController extends Controller
{

    /**
     * 作業ログ手修正申請画面表示(GET)
     */
    public function showManualEditRequestForm(WorkLog $log)
    {
        $this->authorize('update', $log);
        return view('work_logs.manual_edit_request', compact('log'));
    }

    /**
     * 作業ログの手修正申請
     */
    public function requestManualEdit(Request $request, WorkLog $log)
    {
        $this->authorize('update', $log);
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'memo' => 'nullable|string|max:2000',
        ]);
        $manualLog = WorkLog::create([
            'user_id' => $log->user_id,
            'task_id' => $log->task_id,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'stopped',
            'memo' => $validated['memo'],
            'parent_log_id' => $log->id,
            'edit_type' => 'manual',
            'edit_status' => 'pending',
        ]);
        // 承認権限者にメール通知（例: "worklog-approver"権限を持つ全ユーザー）
        $approvers = \App\Models\User::whereHas('roles.permissions', function($q){ $q->where('name', 'worklog-approve'); })->get();
        foreach ($approvers as $approver) {
            \Mail::to($approver->email)->send(new \App\Mail\WorkLogManualEditRequestMail($manualLog));
        }
        return response()->json(['success' => true, 'message' => '手修正申請を送信しました。', 'manual_log_id' => $manualLog->id]);
    }

    /**
     * 手修正申請の承認
     */
    public function approveManualEdit(Request $request, WorkLog $manualLog)
    {
        $this->authorize('approve', $manualLog);
        if ($manualLog->edit_type !== 'manual' || $manualLog->edit_status !== 'pending') {
            return response()->json(['success' => false, 'message' => '承認できる状態ではありません。'], 400);
        }
        $manualLog->edit_status = 'approved';
        $manualLog->save();
        // 申請者に承認メール通知
        \Mail::to($manualLog->user->email)->send(new \App\Mail\WorkLogManualEditResultMail($manualLog, true));
        return response()->json(['success' => true, 'message' => '手修正を承認しました。']);
    }

    /**
     * 手修正申請の拒否
     */
    public function rejectManualEdit(Request $request, WorkLog $manualLog)
    {
        $this->authorize('approve', $manualLog);
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        if ($manualLog->edit_type !== 'manual' || $manualLog->edit_status !== 'pending') {
            return response()->json(['success' => false, 'message' => '拒否できる状態ではありません。'], 400);
        }
        $manualLog->edit_status = 'rejected';
        $manualLog->edit_reject_reason = $validated['reason'];
        $manualLog->save();
        // 申請者に拒否メール通知
        \Mail::to($manualLog->user->email)->send(new \App\Mail\WorkLogManualEditResultMail($manualLog, false));
        return response()->json(['success' => true, 'message' => '手修正申請を拒否しました。']);
    }
    /**
     * 作業を開始する (このメソッドは変更なし)
     */
    public function start(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
        ]);

        return DB::transaction(function () use ($request) {
            $user = Auth::user();
            $task = Task::with('assignees')->findOrFail($request->task_id);
            $usersToLog = collect();

            // --- 記録対象ユーザーの決定ロジック (修正版) ---
            $assigneeIds = $request->input('assignee_ids');
            $validAssigneeIds = is_array($assigneeIds) ? array_filter($assigneeIds, 'is_numeric') : [];

            if (!empty($validAssigneeIds)) {
                $usersToLog = User::findMany($validAssigneeIds);
            } else {
                if ($user->status !== User::STATUS_SHARED) {
                    $usersToLog->push($user);
                } elseif ($task->assignees->isNotEmpty()) {
                    $usersToLog = $task->assignees;
                } else {
                    $usersToLog->push($user);
                }
            }
            // --- ロジックここまで ---

            if ($usersToLog->isEmpty()) {
                return response()->json(['error' => '記録対象の担当者が見つかりませんでした。'], 400);
            }

            foreach ($usersToLog as $userToCheck) {
                if ($userToCheck->getCurrentAttendanceStatus() !== 'working') {
                    return response()->json([
                        'error' => '担当者「' . $userToCheck->name . '」が出勤中ではありません。作業を開始する前に出勤打刻をしてください。'
                    ], 403);
                }
            }

            // 対象者の中に一人でも作業中の人がいればエラーにする
            foreach ($usersToLog as $userToLog) {
                if (WorkLog::where('task_id', $task->id)->where('user_id', $userToLog->id)->where('status', 'active')->exists()) {
                    return response()->json(['error' => '担当者「' . $userToLog->name . '」は既にこのタスクの作業を開始しています。'], 409);
                }
            }

            // 全員のログを作成
            foreach ($usersToLog as $userToLog) {
                WorkLog::create([
                    'user_id'    => $userToLog->id,
                    'task_id'    => $task->id,
                    'start_time' => Carbon::now(),
                    'status'     => 'active',
                ]);
            }

            // 工程ステータスとis_pausedフラグを更新
            if ($task->status !== 'in_progress' && $task->status !== 'completed') {
                $task->status = 'in_progress';
            }
            $task->is_paused = false;
            $task->save();

            return response()->json([
                'success' => true,
                'message' => '作業を開始しました。',
                'task_status' => $task->status,
                'is_paused' => $task->is_paused,
                'running_logs' => WorkLog::where('user_id', Auth::id())->where('status', 'active')->get()
            ]);
        });
    }

    /**
     * タスクIDに基づいて作業を停止・完了する
     */
    public function stopByTask(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'action_type' => 'required|string|in:pause,complete',
            'memo' => 'nullable|string|max:2000',
        ]);

        return DB::transaction(function () use ($request) {
            $user = Auth::user();
            $task = Task::with('assignees')->findOrFail($request->task_id);

            $targetUserIds = collect();

            if ($user->status === User::STATUS_SHARED && $task->assignees->isNotEmpty()) {
                $targetUserIds = $task->assignees->pluck('id');
            } else {
                $targetUserIds->push($user->id);
            }

            $activeLogs = WorkLog::where('task_id', $task->id)
                ->whereIn('user_id', $targetUserIds)
                ->where('status', 'active')
                ->get();

            if ($activeLogs->isEmpty()) {
                return response()->json(['error' => '停止対象の実行中の作業記録が見つかりません。'], 404);
            }

            // ▼▼▼【ここから修正】日付またぎ処理を追加 ▼▼▼
            $now = Carbon::now();
            $finalLogsToStop = collect(); // 最終的に停止するログを格納するコレクション

            foreach ($activeLogs as $log) {
                if ($log->start_time->isBefore($now->copy()->startOfDay())) {
                    // 日付をまたいでいる場合、ログを分割し、新しくできた当日分のログを停止対象にする
                    $newLog = $this->handleOvernightWorkLog($log);
                    $finalLogsToStop->push($newLog);
                } else {
                    // 日付をまたいでいない場合は、元のログをそのまま停止対象にする
                    $finalLogsToStop->push($log);
                }
            }
            // ▲▲▲【修正ここまで】▲▲▲

            $updateData = [
                'end_time' => $now,
                'status' => 'stopped',
            ];
            if ($request->filled('memo')) {
                $updateData['memo'] = $request->memo;
            }

            // 最終的な停止対象のログIDに対して更新を実行
            WorkLog::whereIn('id', $finalLogsToStop->pluck('id'))->update($updateData);

            $otherActiveLogsCount = WorkLog::where('task_id', $task->id)
                ->where('status', 'active')
                ->count();

            $taskIsPaused = false;
            $finalTaskStatus = $task->status;
            $message = '';
            $responsePayload = ['success' => true];

            if ($request->action_type === 'complete') {
                if ($otherActiveLogsCount > 0) {
                    $message = 'ご自身の作業を完了しました。他の担当者が作業中のため、工程は「進行中」のままです。';
                    $taskIsPaused = false;
                    $finalTaskStatus = 'in_progress';
                    $responsePayload['log_only'] = true;
                } else {
                    $message = '作業を終了し、工程を完了にしました。';
                    $finalTaskStatus = 'completed';
                    $taskIsPaused = false;
                }
            } elseif ($request->action_type === 'pause') {
                $message = '作業を一時停止しました。';
                if ($otherActiveLogsCount === 0) {
                    $finalTaskStatus = 'on_hold';
                    $taskIsPaused = true;
                } else {
                    $finalTaskStatus = 'in_progress';
                    $taskIsPaused = false;
                }
            }

            if ($task->status !== $finalTaskStatus || $task->is_paused !== $taskIsPaused) {
                $task->status = $finalTaskStatus;
                $task->is_paused = $taskIsPaused;
                if ($finalTaskStatus === 'completed') {
                    $task->progress = 100;
                }
                $task->save();
            }

            $responsePayload['message'] = $message;
            $responsePayload['task_status'] = $finalTaskStatus;
            $responsePayload['is_paused'] = $taskIsPaused;
            $responsePayload['running_logs'] = WorkLog::where('user_id', Auth::user()->id)->where('status', 'active')->get();

            return response()->json($responsePayload);
        });
    }

    /**
     * ▼▼▼【ここから追加】日付をまたいだ作業ログを分割するプライベートメソッド ▼▼▼
     */
    private function handleOvernightWorkLog(WorkLog $log): WorkLog
    {
        $endOfStartDay = $log->start_time->copy()->endOfDay(); // 開始日の23:59:59
        $startOfNextDay = $endOfStartDay->copy()->addSecond();  // 翌日の00:00:00

        // 元のログ（前日分）を更新して終了させる
        $log->update([
            'end_time' => $endOfStartDay,
            'status'   => 'stopped',
        ]);

        // 新しいログ（当日分）を作成し、これを新たな「アクティブなログ」として返す
        // この後、呼び出し元のstopByTaskでend_timeが設定される
        $newLog = WorkLog::create([
            'user_id'    => $log->user_id,
            'task_id'    => $log->task_id,
            'start_time' => $startOfNextDay,
            'status'     => 'active',
            'memo'       => '（日跨ぎ自動継続）',
        ]);

        return $newLog;
    }
    // ▲▲▲【追加ここまで】▲▲▲
}
