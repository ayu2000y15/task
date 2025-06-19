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
     * 作業を開始する
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

            $logsToStop = WorkLog::where('task_id', $task->id)
                ->whereIn('user_id', $targetUserIds)
                ->where('status', 'active')
                ->get();

            if ($logsToStop->isEmpty()) {
                return response()->json(['error' => '停止対象の実行中の作業記録が見つかりません。'], 404);
            }

            $updateData = [
                'end_time' => Carbon::now(),
                'status' => 'stopped',
            ];
            if ($request->filled('memo')) {
                $updateData['memo'] = $request->memo;
            }

            WorkLog::whereIn('id', $logsToStop->pluck('id'))->update($updateData);

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

            // ▼▼▼【変更】フロントエンド連携のため、最新の実行中ログを返す ▼▼▼
            $responsePayload['running_logs'] = WorkLog::where('user_id', Auth::user()->id)->where('status', 'active')->get();
            // ▲▲▲【変更ここまで】▲▲▲

            return response()->json($responsePayload);
        });
    }
}
