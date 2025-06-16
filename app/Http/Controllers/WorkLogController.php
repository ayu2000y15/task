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
            // Case 1: モーダルで担当者が選択された場合
            $assigneeIds = $request->input('assignee_ids');
            // リクエストで送られてきたIDが、有効なID（数字）であるかを最初にチェックします
            $validAssigneeIds = is_array($assigneeIds) ? array_filter($assigneeIds, 'is_numeric') : [];

            // 1. モーダル等で有効な担当者IDが1つ以上選択されていた場合
            if (!empty($validAssigneeIds)) {
                $usersToLog = User::findMany($validAssigneeIds);
            }
            // 2. 有効な担当者IDが選択されていない場合（非共有アカウントでの開始など）
            else {
                // 2a. ログインユーザーが「共有アカウント」でなければ、ログインユーザー自身を対象とする
                if ($user->status !== User::STATUS_SHARED) {
                    $usersToLog->push($user);
                }
                // 2b. ログインユーザーが「共有アカウント」で、タスクに担当者が割り当てられている場合
                elseif ($task->assignees->isNotEmpty()) {
                    $usersToLog = $task->assignees;
                }
                // 2c. 上記以外（共有アカウントでタスクに担当者未設定など）の最終的なフォールバック
                else {
                    $usersToLog->push($user);
                }
            }
            // --- ロジックここまで ---

            if ($usersToLog->isEmpty()) {
                // このエラーは、通常発生しないはずですが、万が一のために残します
                return response()->json(['error' => '記録対象の担当者が見つかりませんでした。'], 400);
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

            // 工程ステータスを「進行中」に更新
            if ($task->status !== 'in_progress' && $task->status !== 'completed') {
                $task->status = 'in_progress';
                $task->save();
            }

            return response()->json(['success' => true, 'message' => '作業を開始しました。']);
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

            // 停止対象のユーザーを決定
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

            $message = '作業を一時停止しました。';
            $responsePayload = ['success' => true, 'message' => $message];

            // "完了" の場合
            if ($request->action_type === 'complete') {
                $task->status = 'completed';
                $task->progress = 100;
                $task->save();
                $message = '作業を終了し、工程を完了にしました。';
                $responsePayload['message'] = $message;
            }

            return response()->json($responsePayload);
        });
    }
}
