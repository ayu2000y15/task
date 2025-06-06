<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\FeedbackCategory;
use App\Models\FeedbackFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Feedback::class);

        $query = Feedback::with(['user', 'category', 'files']);

        $defaultSortField = 'created_at';
        $defaultSortDirection = 'desc';

        // リクエストから全てのパラメータを取得し、$allParamsForView 配列として初期化
        // ソートパラメータが存在しない場合はデフォルト値を設定
        $allParamsForView = $request->all();
        $currentSortField = $request->input('sort_field', $defaultSortField);
        $currentSortDirection = $request->input('sort_direction', $defaultSortDirection);
        $allParamsForView['sort_field'] = $currentSortField; // ビューでのリンク生成用に現在のソート条件を格納
        $allParamsForView['sort_direction'] = $currentSortDirection;

        // 実際のフィルター入力値のみを抽出 (新しいキー名に対応)
        $actualFilterInputs = $request->only([
            'submitter_name',
            'category_id',
            'status',
            'priority',
            'assignee_text',
            'created_at_from',
            'created_at_to',
            'keyword'
        ]);

        $activeFilterCount = count(array_filter($actualFilterInputs, function ($value) {
            return !is_null($value) && $value !== '';
        }));

        // フィルター処理 (新しいキー名に対応)
        if (!empty($actualFilterInputs['submitter_name'])) {
            // Feedbackモデルのuser_nameカラム (送信時に固定で保存されている名前) を検索
            $query->where('user_name', 'like', '%' . $actualFilterInputs['submitter_name'] . '%');
        }
        if (!empty($actualFilterInputs['category_id'])) {
            $query->where('feedback_category_id', $actualFilterInputs['category_id']);
        }
        if (!empty($actualFilterInputs['status'])) {
            $query->where('status', $actualFilterInputs['status']);
        }
        if (!empty($actualFilterInputs['priority'])) {
            $query->where('priority', $actualFilterInputs['priority']);
        }
        if (!empty($actualFilterInputs['assignee_text'])) {
            $query->where('assignee_text', 'like', '%' . $actualFilterInputs['assignee_text'] . '%');
        }
        if (!empty($actualFilterInputs['created_at_from'])) {
            $query->whereDate('created_at', '>=', $actualFilterInputs['created_at_from']);
        }
        if (!empty($actualFilterInputs['created_at_to'])) {
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

        // ソート処理
        $allowedSortFields = ['id', 'title', 'feedback_category_id', 'status', 'priority', 'created_at', 'updated_at'];
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
                ->orderBy('feedback_categories.display_order', $currentSortDirection) // カテゴリの表示順も考慮
                ->orderBy('feedback_categories.name', $currentSortDirection)      // 同順なら名前順
                ->select('feedbacks.*');
        } else {
            $query->orderBy($currentSortField, $currentSortDirection);
        }

        $feedbacks = $query->paginate(15)->appends($allParamsForView);

        $feedbackCategoriesForFilter = FeedbackCategory::where('is_active', true)->orderBy('display_order')->pluck('name', 'id');
        $statusOptions = Feedback::STATUS_OPTIONS;
        $priorityOptions = Feedback::PRIORITY_OPTIONS;
        $unreadFeedbackCount = Feedback::where('status', Feedback::STATUS_UNREAD)->count();

        return view('admin.feedbacks.index', [
            'feedbacks' => $feedbacks,
            'feedbackCategories' => $feedbackCategoriesForFilter,
            'statusOptions' => $statusOptions,
            'priorityOptions' => $priorityOptions,
            'filters' => $allParamsForView, // 全てのGETパラメータを渡す
            'activeFilterCount' => $activeFilterCount,
            'unreadFeedbackCount' => $unreadFeedbackCount // グローバルではなくこの画面専用の未読件数も渡す
        ]);
    }

    /**
     * Show the form for editing the specified feedback.
     */
    public function edit(Feedback $feedback)
    {
        $this->authorize('update', $feedback);

        $feedback->load('files'); // 添付ファイルをEager load
        $categories = ['' => '選択してください'] + FeedbackCategory::where('is_active', true)->orderBy('display_order')->pluck('name', 'id')->all();
        $priorities = ['' => '選択してください'] + Feedback::PRIORITY_OPTIONS;
        $statuses = Feedback::STATUS_OPTIONS;

        return view('admin.feedbacks.edit', compact('feedback', 'categories', 'priorities', 'statuses'));
    }

    /**
     * Update the specified feedback in storage.
     */
    public function update(Request $request, Feedback $feedback)
    {
        $this->authorize('update', $feedback);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'feedback_category_id' => 'required|exists:feedback_categories,id',
            'priority' => ['required', Rule::in(array_keys(Feedback::PRIORITY_OPTIONS))],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'content' => 'required|string|max:5000',
            'status' => ['required', Rule::in(array_keys(Feedback::STATUS_OPTIONS))],
            'assignee_text' => 'nullable|string|max:255',
            'admin_memo' => 'nullable|string|max:5000',
            'images' => 'nullable|array', // 新規アップロード用
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // 各5MBまで
            'delete_images' => 'nullable|array', // 削除対象の既存ファイルID用
            'delete_images.*' => 'integer|exists:feedback_files,id',
        ], [
            'title.required' => 'タイトルは必須です。',
            'feedback_category_id.required' => 'カテゴリを選択してください。',
            'priority.required' => '優先度を選択してください。',
            'content.required' => '内容は必須です。',
            'status.required' => '対応ステータスを選択してください。',
            'images.max' => 'アップロードできる画像は合計5枚までです。',
            'images.*.image' => '画像ファイルを選択してください。',
            'images.*.mimes' => '画像ファイルは jpg, jpeg, png, gif 形式のみ有効です。',
            'images.*.max' => '各画像ファイルのサイズは5MBまでです。',
        ]);

        // 画像の合計枚数バリデーション
        $existingFileCount = $feedback->files()->count();
        $deletedFileCount = count($request->input('delete_images', []));
        $newFileCount = count($request->file('images', []));
        $totalFilesAfterUpdate = $existingFileCount - $deletedFileCount + $newFileCount;

        if ($totalFilesAfterUpdate > 5) {
            $validator->after(function ($validator) {
                $validator->errors()->add('images', 'アップロードできる画像の合計枚数は5枚までです。');
            });
        }

        if ($validator->fails()) {
            return redirect()->route('admin.feedbacks.edit', $feedback)
                ->withErrors($validator)
                ->withInput();
        }
        $validatedData = $validator->validated();

        // フィードバック本体の情報を更新
        $feedback->title = $validatedData['title'];
        $feedback->feedback_category_id = $validatedData['feedback_category_id'];
        $feedback->priority = $validatedData['priority'];
        $feedback->email = $validatedData['email'] ?? null;
        $feedback->phone = $validatedData['phone'] ?? null;
        $feedback->content = $validatedData['content']; // 内容も更新可能とする
        $feedback->assignee_text = $validatedData['assignee_text'] ?? null;
        $feedback->admin_memo = $validatedData['admin_memo'] ?? null;

        // ステータスと完了日の更新
        $newStatus = $validatedData['status'];
        if ($feedback->status !== $newStatus) {
            $feedback->status = $newStatus;
            if ($newStatus === Feedback::STATUS_COMPLETED && is_null($feedback->completed_at)) {
                $feedback->completed_at = now();
            } elseif ($newStatus !== Feedback::STATUS_COMPLETED) {
                $feedback->completed_at = null;
            }
        }
        $feedback->save(); // FeedbackモデルのLogsActivityが発火 (updatedイベント)

        $deletedFileLogDetails = [];
        $uploadedFileLogDetails = [];

        // 既存ファイルの削除処理
        if ($request->has('delete_images')) {
            foreach ($request->input('delete_images') as $fileIdToDelete) {
                $fileRecord = $feedback->files()->find($fileIdToDelete);
                if ($fileRecord) {
                    $originalNameForLog = $fileRecord->original_name;
                    $filePathForLog = $fileRecord->file_path;

                    Storage::disk('public')->delete($fileRecord->file_path);
                    $fileRecord->delete(); // FeedbackFileモデルのLogsActivityが発火 (deletedイベント)

                    $deletedFileLogDetails[] = [
                        'feedback_title' => $feedback->title,
                        'feedback_id' => $feedback->id,
                        'original_name' => $originalNameForLog,
                        'path' => $filePathForLog,
                    ];
                }
            }
        }

        // 新規ファイルのアップロード処理
        if ($request->hasFile('images')) {
            $currentFileCount = $feedback->files()->count(); // 削除後のファイル数を再取得
            foreach ($request->file('images') as $file) {
                if ($currentFileCount >= 5) break; // 5枚制限

                $originalName = $file->getClientOriginalName();
                $directory = "feedbacks/{$feedback->id}/images";
                $path = $file->store($directory, 'public');

                $feedbackFile = FeedbackFile::create([ // FeedbackFileモデルのLogsActivityが発火 (createdイベント)
                    'feedback_id' => $feedback->id,
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
                $currentFileCount++;

                $uploadedFileLogDetails[] = [
                    'feedback_title' => $feedback->title,
                    'feedback_id' => $feedback->id,
                    'original_name' => $feedbackFile->original_name,
                    'path' => $feedbackFile->file_path,
                    'size' => $feedbackFile->size,
                ];
            }
        }

        // ファイル削除操作のログを手動で記録
        if (!empty($deletedFileLogDetails)) {
            foreach ($deletedFileLogDetails as $logDetail) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($feedback)
                    ->withProperties($logDetail)
                    ->log("フィードバック「{$logDetail['feedback_title']}」からファイル「{$logDetail['original_name']}」が削除されました。");
            }
        }
        // 新規ファイルアップロード操作のログを手動で記録
        if (!empty($uploadedFileLogDetails)) {
            foreach ($uploadedFileLogDetails as $logDetail) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($feedback)
                    ->withProperties($logDetail)
                    ->log("フィードバック「{$logDetail['feedback_title']}」にファイル「{$logDetail['original_name']}」が添付されました。");
            }
        }

        return redirect()->route('admin.feedbacks.index')->with('success', 'フィードバック ID:' . $feedback->id . ' を更新しました。');
    }

    /**
     * Update the status of the specified feedback. (AJAX)
     */
    public function updateStatus(Request $request, Feedback $feedback)
    {
        $this->authorize('update', $feedback); // Ensure user can update
        $allowedStatuses = array_keys(Feedback::STATUS_OPTIONS);
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in($allowedStatuses)],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $newStatus = $request->input('status');
        $feedback->status = $newStatus;

        // Update completed_at based on new status
        if ($newStatus === Feedback::STATUS_COMPLETED) {
            if (is_null($feedback->completed_at)) { // Set completed_at only if not already set
                $feedback->completed_at = now();
            }
        } else {
            $feedback->completed_at = null; // Clear completed_at if status is not completed
        }

        $feedback->save(); // This will trigger LogsActivity trait if status or completed_at is in $logAttributes
        $feedback->load('category'); // To get category name if needed for display

        // Prepare data for JSON response
        $feedback->status_label_display = Feedback::STATUS_OPTIONS[$feedback->status] ?? $feedback->status;
        $feedback->status_badge_class_display = Feedback::getStatusColorClass($feedback->status, 'badge');
        $feedback->completed_at_display = $feedback->completed_at ? $feedback->completed_at->format('Y/m/d H:i') : '-';
        $feedback->updated_at_display = $feedback->updated_at ? $feedback->updated_at->format('Y/m/d H:i') : '-';


        return response()->json([
            'success' => true,
            'message' => '対応ステータスを更新しました。',
            'feedback' => $feedback, // Return updated feedback model for client-side UI update
        ]);
    }

    /**
     * Update the admin memo of the specified feedback. (AJAX)
     */
    public function updateMemo(Request $request, Feedback $feedback)
    {
        $this->authorize('update', $feedback);
        $validator = Validator::make($request->all(), [
            'admin_memo' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $feedback->admin_memo = $request->input('admin_memo');
        $feedback->save(); // Triggers LogsActivity

        $feedback->updated_at_display = $feedback->updated_at ? $feedback->updated_at->format('Y/m/d H:i') : '-';


        return response()->json([
            'success' => true,
            'message' => 'メモを更新しました。',
            'feedback' => $feedback // Return relevant parts of feedback
        ]);
    }

    /**
     * Update the assignee of the specified feedback. (AJAX)
     */
    public function updateAssignee(Request $request, Feedback $feedback)
    {
        $this->authorize('update', $feedback);
        $validator = Validator::make($request->all(), [
            'assignee_text' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $feedback->assignee_text = $request->input('assignee_text');
        $feedback->save(); // Triggers LogsActivity

        $feedback->updated_at_display = $feedback->updated_at ? $feedback->updated_at->format('Y/m/d H:i') : '-';

        return response()->json([
            'success' => true,
            'message' => '担当者を更新しました。',
            'feedback' => $feedback->only(['assignee_text', 'updated_at_display', 'id'])
        ]);
    }
}
