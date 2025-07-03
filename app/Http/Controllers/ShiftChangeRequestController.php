<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ShiftChangeRequest;
use App\Models\WorkShift;
use App\Models\User;
use App\Notifications\ShiftChangeRequested;
use App\Notifications\ShiftChangeRequestApproved;
use App\Notifications\ShiftChangeRequestRejected;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ShiftChangeRequestController extends Controller
{
    /**
     * シフト変更申請の一覧を表示 (承認者向け)
     */
    public function index()
    {
        $this->authorize('viewAny', ShiftChangeRequest::class);

        $requests = ShiftChangeRequest::where('status', 'pending')
            ->with('user') // 申請者情報も一緒に取得
            ->orderBy('date', 'asc')
            ->get();

        return view('shift-change-requests.index', compact('requests'));
    }

    /**
     * 新しいシフト変更申請を保存
     */
    public function store(Request $request)
    {
        $this->authorize('create', ShiftChangeRequest::class);

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'reason' => 'required|string|max:1000',
            'type' => 'required|string|in:work,full_day_off,am_off,pm_off,location_only,clear',
            'name' => 'nullable|string|max:255',
            'start_time' => 'nullable|required_if:type,work|date_format:H:i',
            'end_time' => 'nullable|required_if:type,work|date_format:H:i|after:start_time',
            'location' => 'nullable|string|in:office,remote',
            'notes' => 'nullable|string|max:1000',
        ]);

        $shiftRequest = ShiftChangeRequest::create([
            'user_id' => Auth::id(),
            'date' => $validated['date'],
            'reason' => $validated['reason'],
            'requested_type' => $validated['type'],
            'requested_name' => $validated['name'] ?? null,
            'requested_start_time' => $validated['start_time'] ?? null,
            'requested_end_time' => $validated['end_time'] ?? null,
            'requested_location' => $validated['location'] ?? null,
            'requested_notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        // 承認権限を持つユーザーを全員取得するロジックを元に戻す
        $approvers = User::all()->filter(function ($user) {
            return $user->can('approve', ShiftChangeRequest::class);
        });

        if ($approvers->isEmpty()) {
            Log::warning('シフト変更申請の承認者が見つかりませんでした。');
        } else {
            foreach ($approvers as $approver) {
                $approver->notify(new ShiftChangeRequested($shiftRequest));
            }
        }

        return response()->json(['success' => true, 'message' => '変更申請を送信しました。承認されるまでお待ちください。']);
    }

    /**
     * 申請を承認する
     */
    public function approve(ShiftChangeRequest $shiftChangeRequest)
    {
        $this->authorize('approve', $shiftChangeRequest);

        // 1. 申請ステータスを更新
        $shiftChangeRequest->update([
            'status' => 'approved',
            'approver_id' => Auth::id(),
            'processed_at' => now(),
        ]);

        // 2. WorkShiftテーブルにシフトを反映
        if ($shiftChangeRequest->requested_type === 'clear') {
            WorkShift::where('user_id', $shiftChangeRequest->user_id)
                ->where('date', $shiftChangeRequest->date)
                ->delete();
        } else {
            WorkShift::updateOrCreate(
                ['user_id' => $shiftChangeRequest->user_id, 'date' => $shiftChangeRequest->date],
                [
                    'type' => $shiftChangeRequest->requested_type,
                    'name' => $shiftChangeRequest->requested_name,
                    'start_time' => $shiftChangeRequest->requested_start_time,
                    'end_time' => $shiftChangeRequest->requested_end_time,
                    'location' => $shiftChangeRequest->requested_location,
                    'notes' => $shiftChangeRequest->requested_notes,
                ]
            );
        }

        // 3. 申請者に承認通知を送信
        $shiftChangeRequest->user->notify(new ShiftChangeRequestApproved($shiftChangeRequest));

        return redirect()->route('shift-change-requests.index')->with('success', '申請を承認しました。');
    }

    /**
     * 申請を否認する
     */
    public function reject(Request $request, ShiftChangeRequest $shiftChangeRequest)
    {
        $this->authorize('approve', $shiftChangeRequest);

        $validated = $request->validate(['rejection_reason' => 'required|string|max:1000']);

        // 1. 申請ステータスと否認理由を更新
        $shiftChangeRequest->update([
            'status' => 'rejected',
            'approver_id' => Auth::id(),
            'rejection_reason' => $validated['rejection_reason'],
            'processed_at' => now(),
        ]);

        // 2. 申請者に否認通知を送信
        $shiftChangeRequest->user->notify(new ShiftChangeRequestRejected($shiftChangeRequest));

        return redirect()->route('shift-change-requests.index')->with('success', '申請を否認しました。');
    }

    /**
     * ログインユーザー自身のシフト変更申請一覧を表示
     */
    public function myRequests()
    {
        $requests = Auth::user()->shiftChangeRequests()
            ->with('approver') // 処理者情報も一緒に取得
            ->orderBy('date', 'desc') // 対象日の降順で表示
            ->paginate(20); // 20件ずつページネーション

        return view('shift-change-requests.my_requests', compact('requests'));
    }

    /**
     * 申請を取り下げる (削除する)
     */
    public function destroy(ShiftChangeRequest $shiftChangeRequest)
    {
        // Policyで削除権限をチェック
        $this->authorize('delete', $shiftChangeRequest);

        $shiftChangeRequest->delete();

        return redirect()->route('shift-change-requests.my')->with('success', '申請を取り下げました。');
    }
}
