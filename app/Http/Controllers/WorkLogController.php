<?php
// app/Http/Controllers/WorkLogController.php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\WorkLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkLogController extends Controller
{
    /**
     * 作業を開始する（再開もこのアクションを使用）
     */
    public function start(Request $request)
    {
        $request->validate(['task_id' => 'required|exists:tasks,id']);

        return DB::transaction(function () use ($request) {
            $user = Auth::user();
            $task = Task::findOrFail($request->task_id);

            // このタスクで既に実行中のログがあればエラー
            if (WorkLog::where('task_id', $task->id)->where('user_id', $user->id)->where('status', 'active')->exists()) {
                return response()->json(['error' => 'このタスクは既に実行中です。'], 409);
            }

            // 新規開始処理
            $newLog = WorkLog::create([
                'user_id'    => $user->id,
                'task_id'    => $task->id,
                'start_time' => Carbon::now(),
                'status'     => 'active', // 'active'ステータスで開始
            ]);

            // 工程ステータスを「進行中」に更新
            if ($task->status !== 'in_progress' && $task->status !== 'completed') {
                $task->status = 'in_progress';
                $task->save();
            }

            return response()->json(['success' => true, 'message' => '作業を開始しました。', 'work_log' => $newLog->load('task')]);
        });
    }

    /**
     * 作業を終了する（一時停止または完了）
     */
    public function stop(WorkLog $workLog, Request $request)
    {
        $this->authorize('update', $workLog);

        $request->validate(['action_type' => 'required|string|in:pause,complete']);

        return DB::transaction(function () use ($workLog, $request) {
            if ($workLog->status !== 'active') {
                return response()->json(['error' => 'この作業記録は実行中ではありません。'], 400);
            }

            // 作業ログを終了
            $workLog->end_time = Carbon::now();
            $workLog->status = 'stopped';
            $workLog->memo = $request->input('memo');
            $workLog->save();

            $message = '作業を一時停止しました。';

            // 「完了」の場合のみ、工程のステータスを更新
            if ($request->action_type === 'complete') {
                $task = $workLog->task;
                $task->status = 'completed';
                $task->progress = 100;
                $task->save();
                $message = '作業を終了し、工程を完了にしました。';
                // 完了時はログ情報を返さない（UIを「開始」状態にするため）
                return response()->json(['success' => true, 'message' => $message, 'work_log' => null]);
            }

            // 「一時停止」の場合は更新されたログ情報を返す
            return response()->json(['success' => true, 'message' => $message, 'work_log' => $workLog->load('task')]);
        });
    }
}
