<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\FeedbackCategory;
use App\Models\FeedbackFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserFeedbackController extends Controller
{
    public function create()
    {
        $this->authorize('create', Feedback::class);

        $activeCategories = FeedbackCategory::where('is_active', true)->orderBy('display_order')->pluck('name', 'id');
        $categoryOptions = ['' => '選択してください'] + $activeCategories->all();
        $priorityOptions = Feedback::PRIORITY_OPTIONS;

        return view('user_feedbacks.create', [
            'categories' => $categoryOptions,
            'priorities' => $priorityOptions
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Feedback::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'feedback_category_id' => 'required|exists:feedback_categories,id',
            'priority' => ['required', Rule::in(array_keys(Feedback::PRIORITY_OPTIONS))],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'content' => 'required|string|max:5000',
            'images' => 'nullable|array|max:5', // 最大5ファイルまで
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // 各5MBまで
        ], [
            'title.required' => 'タイトルは必須です。',
            'feedback_category_id.required' => 'カテゴリを選択してください。',
            'priority.required' => '優先度を選択してください。',
            'priority.in' => '選択された優先度は無効です。',
            'content.required' => '内容は必須です。',
            'images.max' => 'アップロードできる画像は5枚までです。',
            'images.*.image' => '画像ファイルを選択してください。',
            'images.*.mimes' => '画像ファイルは jpg, jpeg, png, gif 形式のみ有効です。',
            'images.*.max' => '各画像ファイルのサイズは5MBまでです。',
        ]);

        if ($validator->fails()) {
            return redirect()->route('user_feedbacks.create')
                ->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated();

        $feedback = new Feedback();
        $feedback->user_id = Auth::id();
        $feedback->user_name = Auth::user()->name;
        $feedback->title = $validatedData['title'];
        $feedback->feedback_category_id = $validatedData['feedback_category_id'];
        $feedback->priority = $validatedData['priority'];
        $feedback->email = $validatedData['email'] ?? null;
        $feedback->phone = $validatedData['phone'] ?? null;
        $feedback->content = $validatedData['content'];
        $feedback->status = Feedback::STATUS_UNREAD;
        $feedback->save(); // FeedbackモデルのLogsActivityが発火 (createdイベント)

        $uploadedFileLogDetails = []; // ★ ログ用のファイル詳細情報を格納する配列

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $originalName = $file->getClientOriginalName();
                // ★ 保存パスをフィードバックIDに基づいて一意にする
                $directory = "feedbacks/{$feedback->id}/images";
                $path = $file->store($directory, 'public'); // publicディスクに保存

                $feedbackFile = FeedbackFile::create([ // FeedbackFileモデルのLogsActivityが発火 (createdイベント)
                    'feedback_id' => $feedback->id,
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);

                // ★ ログ用の情報を収集
                $uploadedFileLogDetails[] = [
                    'feedback_title' => $feedback->title,
                    'feedback_id' => $feedback->id,
                    'original_name' => $feedbackFile->original_name,
                    'path' => $feedbackFile->file_path, // file_pathを使用
                    'size' => $feedbackFile->size,
                ];
            }
        }

        // ★ ファイルアップロード操作のログを手動で記録
        if (!empty($uploadedFileLogDetails)) {
            foreach ($uploadedFileLogDetails as $logDetail) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($feedback) // 操作対象はフィードバック
                    ->withProperties($logDetail) // ファイルごとの詳細情報をプロパティに
                    ->log("フィードバック「{$logDetail['feedback_title']}」にファイル「{$logDetail['original_name']}」が添付されました。");
            }
        }

        return redirect()->route('home.index')->with('success', 'フィードバックを送信しました。ありがとうございました。');
    }
}
