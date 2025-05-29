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

        return view('user_feedbacks.create', ['categories' => $categoryOptions]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Feedback::class);

        $validator = Validator::make($request->all(), [
            // 'submitter_name' のバリデーションを削除
            'title' => 'required|string|max:255',
            'feedback_category_id' => 'required|exists:feedback_categories,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'content' => 'required|string|max:5000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ], [
            // 'submitter_name.required' のメッセージを削除
            'title.required' => 'タイトルは必須です。',
            'feedback_category_id.required' => 'カテゴリを選択してください。',
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

        $validatedData = $validator->validated(); // ここには submitter_name は含まれない

        $feedback = new Feedback();
        $feedback->user_id = Auth::id();
        $feedback->user_name = Auth::user()->name; // ★ 常にログインユーザーの名前に戻す
        $feedback->title = $validatedData['title'];
        $feedback->feedback_category_id = $validatedData['feedback_category_id'];
        $feedback->email = $validatedData['email'] ?? null;
        $feedback->phone = $validatedData['phone'] ?? null;
        $feedback->content = $validatedData['content'];
        $feedback->status = Feedback::STATUS_UNREAD;
        $feedback->save();

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $originalName = $file->getClientOriginalName();
                $path = $file->store("feedbacks/{$feedback->id}/images", 'public');

                FeedbackFile::create([
                    'feedback_id' => $feedback->id,
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('home.index')->with('success', 'フィードバックを送信しました。ありがとうございました。');
    }
}
