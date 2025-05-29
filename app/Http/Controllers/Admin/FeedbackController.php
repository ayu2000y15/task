<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\FeedbackCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = Feedback::with(['user', 'category', 'files']);

        $defaultSortField = 'created_at';
        $defaultSortDirection = 'desc';

        // ビューに渡す全パラメータ（フィルター＋ソート）
        $allParamsForView = $request->all();
        $allParamsForView['sort_field'] = $request->input('sort_field', $defaultSortField);
        $allParamsForView['sort_direction'] = $request->input('sort_direction', $defaultSortDirection);

        // 実際のフィルター入力値のみを抽出 (新しいキー名に対応)
        $actualFilterInputs = $request->only([
            'submitter_name', // ★追加
            'category_id',
            'status',
            'assignee_text',
            'created_at_from', // ★'start_date' から変更
            'created_at_to',   // ★'end_date' から変更
            'keyword'
        ]);

        $activeFilterCount = count(array_filter($actualFilterInputs, function ($value) {
            return !is_null($value) && $value !== '';
        }));

        // フィルター処理 (新しいキー名に対応)
        if (!empty($actualFilterInputs['submitter_name'])) { // ★追加
            $query->whereHas('user', function ($q) use ($actualFilterInputs) {
                $q->where('name', 'like', '%' . $actualFilterInputs['submitter_name'] . '%');
            });
            // もしFeedbackモデルのuser_nameカラム (送信者名が固定で保存されている場合) を検索するなら以下
            // $query->where('user_name', 'like', '%' . $actualFilterInputs['submitter_name'] . '%');
        }
        if (!empty($actualFilterInputs['category_id'])) {
            $query->where('feedback_category_id', $actualFilterInputs['category_id']);
        }
        if (!empty($actualFilterInputs['status'])) {
            $query->where('status', $actualFilterInputs['status']);
        }
        if (!empty($actualFilterInputs['assignee_text'])) {
            $query->where('assignee_text', 'like', '%' . $actualFilterInputs['assignee_text'] . '%');
        }
        if (!empty($actualFilterInputs['created_at_from'])) { // ★'start_date' から変更
            $query->whereDate('created_at', '>=', $actualFilterInputs['created_at_from']);
        }
        if (!empty($actualFilterInputs['created_at_to'])) { // ★'end_date' から変更
            $query->whereDate('created_at', '<=', $actualFilterInputs['created_at_to']);
        }
        if (!empty($actualFilterInputs['keyword'])) {
            $keyword = $actualFilterInputs['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('content', 'like', '%' . $keyword . '%')
                    ->orWhere('admin_memo', 'like', '%' . $keyword . '%');
            });
        }

        // ソート処理 (変更なし)
        $currentSortField = $allParamsForView['sort_field'];
        $currentSortDirection = $allParamsForView['sort_direction'];
        $allowedSortFields = ['id', 'title', 'feedback_category_id', 'status', 'created_at', 'updated_at'];
        if (!in_array($currentSortField, $allowedSortFields)) {
            $currentSortField = $defaultSortField;
            $allParamsForView['sort_field'] = $currentSortField; // 不正な値はデフォルトに戻す
        }
        if (!in_array($currentSortDirection, ['asc', 'desc'])) {
            $currentSortDirection = $defaultSortDirection;
            $allParamsForView['sort_direction'] = $currentSortDirection;
        }

        if ($currentSortField === 'feedback_category_id') {
            $query->leftJoin('feedback_categories', 'feedbacks.feedback_category_id', '=', 'feedback_categories.id')
                ->orderBy('feedback_categories.name', $currentSortDirection)
                ->select('feedbacks.*');
        } else {
            $query->orderBy($currentSortField, $currentSortDirection);
        }

        $feedbacks = $query->paginate(15)->appends($allParamsForView);

        $feedbackCategoriesForFilter = FeedbackCategory::where('is_active', true)->orderBy('display_order')->pluck('name', 'id'); // 変数名変更
        $statusOptions = Feedback::STATUS_OPTIONS;
        $unreadFeedbackCount = Feedback::where('status', Feedback::STATUS_UNREAD)->count();

        return view('admin.feedbacks.index', [
            'feedbacks' => $feedbacks,
            'feedbackCategories' => $feedbackCategoriesForFilter, // ★ 変数名変更
            'statusOptions' => $statusOptions,
            'filters' => $allParamsForView,
            'activeFilterCount' => $activeFilterCount,
            'unreadFeedbackCount' => $unreadFeedbackCount
        ]);
    }

    // updateStatus, updateMemo, updateAssignee メソッドは変更なし
    // ... (これらのメソッドは前回の回答のものをそのまま使用) ...
    public function updateStatus(Request $request, Feedback $feedback)
    {
        $allowedStatuses = array_keys(Feedback::STATUS_OPTIONS);
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in($allowedStatuses)],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $newStatus = $request->input('status');
        $feedback->status = $newStatus;

        if ($newStatus === Feedback::STATUS_COMPLETED) {
            if (is_null($feedback->completed_at)) {
                $feedback->completed_at = now();
            }
        } else {
            $feedback->completed_at = null;
        }

        $feedback->save();
        $feedback->load('category');

        $feedback->status_label_display = Feedback::STATUS_OPTIONS[$feedback->status] ?? $feedback->status;
        $feedback->status_badge_class_display = Feedback::getStatusColorClass($feedback->status, 'badge');
        $feedback->completed_at_display = $feedback->completed_at ? $feedback->completed_at->format('Y/m/d H:i') : '-';
        $feedback->updated_at_display = $feedback->updated_at ? $feedback->updated_at->format('Y/m/d H:i') : '-';

        return response()->json([
            'success' => true,
            'message' => '対応ステータスを更新しました。',
            'feedback' => $feedback,
        ]);
    }

    public function updateMemo(Request $request, Feedback $feedback)
    {
        $validator = Validator::make($request->all(), [
            'admin_memo' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $feedback->admin_memo = $request->input('admin_memo');
        $feedback->save();

        $feedback->updated_at_display = $feedback->updated_at ? $feedback->updated_at->format('Y/m/d H:i') : '-';

        return response()->json([
            'success' => true,
            'message' => 'メモを更新しました。',
            'feedback' => $feedback
        ]);
    }

    public function updateAssignee(Request $request, Feedback $feedback)
    {
        $validator = Validator::make($request->all(), [
            'assignee_text' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $feedback->assignee_text = $request->input('assignee_text');
        $feedback->save();

        $feedback->updated_at_display = $feedback->updated_at ? $feedback->updated_at->format('Y/m/d H:i') : '-';

        return response()->json([
            'success' => true,
            'message' => '担当者を更新しました。',
            'feedback' => $feedback->only(['assignee_text', 'updated_at_display', 'id'])
        ]);
    }
}
