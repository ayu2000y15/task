<?php

namespace App\Http\Controllers;

use App\Models\BoardPost;
use App\Models\User;
use App\Models\Role;
use App\Models\BoardPostRead;
use App\Models\BoardCommentRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\Tag;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BoardPostType;
use App\Models\FormFieldDefinition;
use App\Models\BoardPostCustomFieldValue;

class BoardPostController extends Controller
{
    // index, create, store, showメソッドは変更なし（前の回答を参照）
    // ...

    public function index(Request $request)
    {
        $this->authorize('viewAny', BoardPost::class); // 権限チェックのモデルを修正

        // 未読の投稿IDリストを取得
        $unreadPostIds = BoardPostRead::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->pluck('board_post_id');

        // ▼▼▼【ここから追加】既読の投稿IDリストを取得 ▼▼▼
        $readPostIds = BoardPostRead::where('user_id', Auth::id())
            ->whereNotNull('read_at')
            ->pluck('board_post_id');

        // ▲▲▲【ここまで】▲▲▲

        $unreadCommentPostIds = \App\Models\BoardComment::whereIn(
            'id',
            \App\Models\BoardCommentRead::where('user_id', auth()->id())->whereNull('read_at')->pluck('comment_id')
        )->pluck('board_post_id')->unique();

        $query = BoardPost::with('user', 'role', 'comments', 'readableUsers', 'tags', 'boardPostType');

        if ($request->has('tag')) {
            $tag = $request->input('tag');
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', $tag);
            });
        }

        if ($request->has('post_type')) {
            $postType = $request->input('post_type');
            $query->whereHas('boardPostType', function ($q) use ($postType) {
                $q->where('name', $postType);
            });
        }

        $posts = $query->latest()->paginate(20);

        // ▼▼▼【変更】compactに $readPostIds を追加 ▼▼▼
        return view('community.posts.index', compact('posts', 'unreadPostIds', 'readPostIds', 'unreadCommentPostIds'));
    }

    public function create()
    {
        $this->authorize('create', BoardPost::class);
        // ログインユーザーが所属するロールのみを取得
        $userRoles = Auth::user()->roles;

        // everyoneロールを追加（全ユーザー公開用）
        $everyoneRole = (object)[
            'id' => 'everyone',
            'name' => 'everyone',
            'display_name' => '全ユーザー'
        ];

        // ユーザーロールとeveryoneロールを結合
        $roles = $userRoles->push($everyoneRole);

        // 全てのアクティブなユーザーを取得
        $allActiveUsers = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->pluck('name', 'id');
        // アクティブな投稿タイプを取得
        $boardPostTypes = BoardPostType::active()->ordered()->get();

        return view('community.posts.create', compact('roles', 'allActiveUsers', 'boardPostTypes'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', BoardPost::class);

        // 基本項目のバリデーション
        $baseValidation = [
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'role_id' => 'required', // 必須に変更（everyoneまたは既存ロールのID）
            'board_post_type_id' => 'nullable|exists:board_post_types,id',
            'readable_user_ids' => 'nullable|array',
            'readable_user_ids.*' => 'exists:users,id',
        ];

        // 投稿タイプが指定されていない場合はデフォルトを設定
        $postTypeId = $request->input('board_post_type_id');
        if (empty($postTypeId)) {
            $defaultType = BoardPostType::getDefault();
            $postTypeId = $defaultType ? $defaultType->id : null;
            $request->merge(['board_post_type_id' => $postTypeId]);
        }

        // カスタム項目のバリデーションルールを追加
        $customFieldRules = [];
        if ($postTypeId) {
            $postType = BoardPostType::find($postTypeId);
            if ($postType) {
                $formFields = FormFieldDefinition::where('category', $postType->name)
                    ->where('is_enabled', true)
                    ->get();

                foreach ($formFields as $field) {
                    $fieldName = "custom_field_{$field->id}";
                    $rules = [];

                    if ($field->is_required) {
                        $rules[] = 'required';
                    } else {
                        $rules[] = 'nullable';
                    }

                    switch ($field->type) {
                        case 'email':
                            $rules[] = 'email';
                            break;
                        case 'url':
                            $rules[] = 'url';
                            break;
                        case 'number':
                            $rules[] = 'numeric';
                            break;
                        case 'date':
                            $rules[] = 'date';
                            break;
                        case 'text':
                        case 'textarea':
                            $rules[] = 'string|max:1000';
                            break;
                    }

                    $customFieldRules[$fieldName] = implode('|', $rules);
                }
            }
        }

        $validated = $request->validate(array_merge($baseValidation, $customFieldRules));

        // everyoneロールの特別処理
        if ($validated['role_id'] === 'everyone') {
            $validated['role_id'] = null; // everyoneの場合はnullに設定（全公開）
        }

        // 投稿タイプに応じたバリデーション
        $postType = BoardPostType::find($validated['board_post_type_id']);
        if ($postType && $postType->name === 'announcement' && empty($validated['body'])) {
            throw ValidationException::withMessages([
                'body' => 'お知らせの場合、本文は必須です。'
            ]);
        }

        $post = Auth::user()->boardPosts()->create($validated);

        // カスタム項目の値を保存
        if ($post->board_post_type_id) {
            $this->saveCustomFieldValues($post, $request);
        }

        // 閲覧可能ユーザーを中間テーブルに保存
        if (isset($validated['readable_user_ids'])) {
            $post->readableUsers()->sync($validated['readable_user_ids']);
        }

        $this->processTagsAndMentions($post, $request);

        return redirect()->route('community.posts.show', $post)->with('success', '投稿を作成しました。');
    }

    public function show(BoardPost $post)
    {
        // 1. 投稿の閲覧権限をチェック
        $this->authorize('view', $post);

        // 2. この投稿の全コメントを、必要なリレーションを含めて一度に取得
        $allComments = $post->comments()->with(['user', 'reactions.user'])->get();

        // 3. 親IDでコメントをグループ化
        $commentsGroupedByParent = $allComments->groupBy('parent_id');

        // 4. 再帰的にソートするための空のコレクションを準備
        $sortedComments = collect();

        // 5. 再帰的にコメントを追加していく内部関数を定義
        $appendRepliesRecursively = function ($parentId) use (&$appendRepliesRecursively, $commentsGroupedByParent, &$sortedComments) {
            $replies = $commentsGroupedByParent->get($parentId, collect())->sortBy('created_at');

            foreach ($replies as $reply) {
                $sortedComments->push($reply);
                $appendRepliesRecursively($reply->id);
            }
        };

        // 6. トップレベルのコメント（parent_idがnull）から再帰処理を開始
        $appendRepliesRecursively(null);

        // 7. 投稿本体の関連データを読み込む
        $post->load('user', 'role', 'readableUsers', 'tags', 'reactions.user', 'boardPostType', 'customFieldValues.formFieldDefinition');

        $authUserId = auth()->id();

        // 8.【重要】既読にする「前」に、どのコメントが未読であるかのIDリストを取得
        $commentIdsOnPage = $sortedComments->pluck('id');
        $unreadCommentIds = collect();
        if ($commentIdsOnPage->isNotEmpty()) {
            $unreadCommentIds = BoardCommentRead::where('user_id', $authUserId)
                ->whereIn('comment_id', $commentIdsOnPage)
                ->whereNull('read_at')
                ->pluck('comment_id');
        }

        // 9. この投稿自体と、表示されている全てのコメントを「既読」にする
        BoardPostRead::updateOrCreate(
            ['user_id' => $authUserId, 'board_post_id' => $post->id],
            ['read_at' => now()]
        );
        if ($commentIdsOnPage->isNotEmpty()) {
            BoardCommentRead::where('user_id', $authUserId)
                ->whereIn('comment_id', $commentIdsOnPage)
                ->whereNull('read_at') // 未読のものだけを更新
                ->update(['read_at' => now()]);
        }

        // 10. この投稿を閲覧済みのユーザーリストを取得する (表示用)
        $readByUsers = BoardPostRead::where('board_post_id', $post->id)
            ->whereNotNull('read_at')
            ->with('user')
            ->latest('read_at')
            ->get();

        // 11. 必要な全てのデータをビューに渡す
        return view('community.posts.show', compact('post', 'readByUsers', 'unreadCommentIds', 'sortedComments'));
    }

    /**
     * 投稿編集フォームを表示する
     */
    public function edit(BoardPost $post)
    {
        // ポリシーで更新権限をチェック
        $this->authorize('update', $post);

        // ログインユーザーが所属するロールのみを取得
        $userRoles = Auth::user()->roles;

        // everyoneロールを追加（全ユーザー公開用）
        $everyoneRole = (object)[
            'id' => 'everyone',
            'name' => 'everyone',
            'display_name' => '全ユーザー'
        ];

        // ユーザーロールとeveryoneロールを結合
        $roles = $userRoles->push($everyoneRole);

        // 全てのアクティブなユーザーを取得
        $allActiveUsers = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->pluck('name', 'id');
        // この投稿で既に選択されている閲覧可能ユーザーのIDを取得
        $selectedReadableUserIds = $post->readableUsers()->pluck('id')->toArray();
        // アクティブな投稿タイプを取得
        $boardPostTypes = BoardPostType::active()->ordered()->get();

        return view('community.posts.edit', compact('post', 'roles', 'allActiveUsers', 'selectedReadableUserIds', 'boardPostTypes'));
    }

    /**
     * 投稿を更新する
     */
    public function update(Request $request, BoardPost $post)
    {
        // ポリシーで更新権限をチェック
        $this->authorize('update', $post);

        // 基本項目のバリデーション
        $baseValidation = [
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'role_id' => 'required', // 必須に変更
            'board_post_type_id' => 'nullable|exists:board_post_types,id',
            'readable_user_ids' => 'nullable|array',
            'readable_user_ids.*' => 'exists:users,id',
        ];

        // カスタム項目のバリデーションルールを追加
        $customFieldRules = [];
        $postTypeId = $request->input('board_post_type_id');
        if ($postTypeId) {
            $postType = BoardPostType::find($postTypeId);
            if ($postType) {
                $formFields = FormFieldDefinition::where('category', $postType->name)
                    ->where('is_enabled', true)
                    ->get();

                foreach ($formFields as $field) {
                    $fieldName = "custom_field_{$field->id}";
                    $rules = [];

                    if ($field->is_required) {
                        $rules[] = 'required';
                    } else {
                        $rules[] = 'nullable';
                    }

                    switch ($field->type) {
                        case 'email':
                            $rules[] = 'email';
                            break;
                        case 'url':
                            $rules[] = 'url';
                            break;
                        case 'number':
                            $rules[] = 'numeric';
                            break;
                        case 'date':
                            $rules[] = 'date';
                            break;
                        case 'text':
                        case 'textarea':
                            $rules[] = 'string|max:1000';
                            break;
                    }

                    $customFieldRules[$fieldName] = implode('|', $rules);
                }
            }
        }

        $validated = $request->validate(array_merge($baseValidation, $customFieldRules));

        // everyoneロールの特別処理
        if ($validated['role_id'] === 'everyone') {
            $validated['role_id'] = null; // everyoneの場合はnullに設定（全公開）
        }

        // 投稿タイプに応じたバリデーション
        $postType = BoardPostType::find($validated['board_post_type_id']);
        if ($postType && $postType->name === 'announcement' && empty($validated['body'])) {
            throw ValidationException::withMessages([
                'body' => 'お知らせの場合、本文は必須です。'
            ]);
        }

        // 投稿を更新
        $post->update($validated);

        // カスタム項目の値を保存
        if ($post->board_post_type_id) {
            $this->saveCustomFieldValues($post, $request);
        }

        // 閲覧可能ユーザーを中間テーブルに保存（syncで差分を自動更新）
        if (isset($validated['readable_user_ids'])) {
            $post->readableUsers()->sync($validated['readable_user_ids']);
        } else {
            // 配列が送信されなかった場合は、すべての関連を削除
            $post->readableUsers()->sync([]);
        }

        // タグとメンションの再処理
        $this->processTagsAndMentions($post, $request);

        return redirect()->route('community.posts.show', $post)->with('success', '投稿を更新しました。');
    }

    /**
     * 投稿を削除する
     */
    public function destroy(BoardPost $post)
    {
        $this->authorize('delete', $post);
        $post->delete();
        return redirect()->route('community.posts.index')->with('success', '投稿を削除しました。');
    }


    /**
     * 本文からタグとメンションを解析して保存する
     * (メンションとタグの処理を強化)
     */
    protected function processTagsAndMentions(BoardPost $post, Request $request)
    {
        $body = $request->input('body');
        $cleanBody = strip_tags($body);

        // ▼▼▼【ここから変更】@all の処理を追加 ▼▼▼
        if (preg_match('/@all\b/', $cleanBody)) {

            // --- 1. 通知処理 ---
            // 投稿者を除く全アクティブユーザーのIDを取得
            $allUserIdsForNotification = User::where('status', User::STATUS_ACTIVE)
                ->where('id', '!=', auth()->id())
                ->pluck('id')
                ->all();

            // 全員に通知（BoardPostReadレコードを作成）
            foreach ($allUserIdsForNotification as $userId) {
                BoardPostRead::firstOrCreate(['user_id' => $userId, 'board_post_id' => $post->id]);
            }

            // --- 2. 閲覧範囲を「全公開」に設定 ---
            $post->role_id = null; // ロール指定を解除
            $post->save();
            $post->readableUsers()->sync([]); // 個別ユーザー指定を解除

            // --- 3. アクティビティログに記録 ---
            activity()
                ->performedOn($post)
                ->causedBy(auth()->user())
                ->log("投稿「{$post->title}」で@allメンションを使用し、全ユーザーに通知しました。");
        } else {
            // --- @all がない場合は、これまで通りの個別メンション処理 ---
            preg_match_all('/@([\p{L}\p{N}_-]+)/u', $cleanBody, $mentionMatches);
            $mentionedUserIds = [];
            if (!empty($mentionMatches[1])) {
                $mentionedAccessIds = array_unique($mentionMatches[1]);
                $mentionedUsers = User::whereIn('access_id', $mentionedAccessIds)->get();

                foreach ($mentionedUsers as $user) {
                    $mentionedUserIds[] = $user->id;
                    if ($user->id !== auth()->id()) {
                        BoardPostRead::firstOrCreate(['user_id' => $user->id, 'board_post_id' => $post->id]);
                    }
                }
            }

            $selectedUserIds = $request->input('readable_user_ids', []);
            $allReadableUserIds = array_unique(array_merge($mentionedUserIds, $selectedUserIds));
            $syncedUsers = $post->readableUsers()->sync($allReadableUserIds);

            if (!empty($syncedUsers['attached']) || !empty($syncedUsers['detached'])) {
                activity()
                    ->performedOn($post)
                    ->causedBy(auth()->user())
                    ->log("投稿「{$post->title}」の閲覧範囲（個別ユーザー）が更新されました。");
            }
        }

        // === タグ処理 (#タグ名) ===
        preg_match_all('/\[([^\]]+?)\]/u', strip_tags($body), $tagMatches);
        $tagIds = [];
        if (!empty($tagMatches[1])) {
            $tagNames = array_unique($tagMatches[1]);
            foreach ($tagNames as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
        }

        $syncedTags = $post->tags()->sync($tagIds);
        // ▼▼▼【追加】タグの変更を手動でログに記録 ▼▼▼
        if (!empty($syncedTags['attached']) || !empty($syncedTags['detached'])) {
            activity()
                ->performedOn($post)
                ->causedBy(auth()->user())
                ->log("投稿「{$post->title}」のタグが更新されました。");
        }
    }

    /**
     * TinyMCE用の画像アップロード
     */
    /**
     * TinyMCEエディタからの画像アップロードを処理します。
     * (SalesToolControllerを参考に、堅牢な実装に修正)
     */
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            // 投稿作成権限を持つユーザーのみアップロードを許可
            $this->authorize('create', BoardPost::class);

            if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
                return response()->json(['error' => ['message' => '無効なファイルがアップロードされました。']], 400);
            }

            // バリデーションルールにwebpを追加
            $validatedData = $request->validate([
                'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // 2MBまでの画像ファイル
            ]);

            $file = $validatedData['file'];

            // ファイルを 'public' ディスクの 'board_images' ディレクトリに保存
            $path = $file->store('board_images', 'public');

            if (!$path) {
                Log::error('Board Post image upload: File storage failed.', ['file' => $file->getClientOriginalName()]);
                return response()->json(['error' => ['message' => 'ファイルの保存に失敗しました。サーバーログを確認してください。']], 500);
            }

            // publicディスクのURLを取得
            $url = Storage::disk('public')->url($path);
            Log::info('Board Post image uploaded successfully.', ['url' => $url, 'original_name' => $file->getClientOriginalName()]);

            // TinyMCEが期待するJSONレスポンス形式
            return response()->json(['location' => $url]);
        } catch (ValidationException $e) {
            // バリデーション例外をキャッチ
            Log::warning('Board Post image upload validation failed.', ['errors' => $e->errors()]);
            return response()->json(['error' => ['message' => 'バリデーションエラー: ' . $e->getMessage(), 'details' => $e->errors()]], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // 権限例外をキャッチ
            Log::warning('Board Post image upload authorization failed.', ['user_id' => Auth::id()]);
            return response()->json(['error' => ['message' => 'この操作を行う権限がありません。']], 403);
        } catch (\Throwable $e) { // その他のすべての例外をキャッチ
            // 詳細なエラー情報をログに出力
            Log::error('Board Post image upload critical error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            // クライアントには汎用的なエラーメッセージを返す
            return response()->json(['error' => ['message' => '画像のアップロード中に予期せぬサーバーエラーが発生しました。']], 500);
        }
    }

    /**
     * メンション候補のユーザーを検索してJSONで返す
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $term = strtolower($request->query('query', ''));

        // 通常のユーザー検索
        $users = User::where('status', User::STATUS_ACTIVE)
            ->where('name', 'LIKE', "%{$term}%")
            ->select('id', 'name', 'access_id')
            ->take(10)
            ->get();

        $results = $users->map(function ($user) {
            return ['id' => $user->access_id, 'text' => $user->name];
        });

        // ▼▼▼【ここから追加】@all の候補を追加するロジック ▼▼▼
        // "all" という文字列が検索語で始まっている場合 (例: "a", "al", "all")
        if (Str::startsWith('all', $term)) {
            $allMention = collect([
                ['id' => 'all', 'text' => '全員にメンション']
            ]);
            // ユーザーリストの先頭に @all を追加
            $results = $allMention->concat($results);
        }
        // ▲▲▲【ここまで】▲▲▲

        return response()->json($results);
    }

    public function toggleReaction(Request $request, BoardPost $post)
    {
        $this->authorize('view', $post); // 投稿を閲覧できるユーザーならリアクション可能

        $validated = $request->validate([
            'emoji' => 'required|string|max:8',
        ]);

        $userId = auth()->id();
        $emoji = $validated['emoji'];

        // 既存のリアクションを検索
        $existingReaction = $post->reactions()
            ->where('user_id', $userId)
            ->where('emoji', $emoji)
            ->first();

        if ($existingReaction) {
            // あれば削除
            $existingReaction->delete();
        } else {
            // なければ作成
            $post->reactions()->create([
                'user_id' => $userId,
                'emoji' => $emoji,
            ]);
        }

        // 最新のリアクション情報を取得して、部分ビューを再レンダリング
        $reactions = $post->reactions()->with('user')->get();
        $updatedHtml = view('community.posts.partials._reactions', compact('post', 'reactions'))->render();

        return response()->json([
            'success' => true,
            'html' => $updatedHtml,
        ]);
    }

    /**
     * 【追加】投稿の閲覧権限を持つユーザーをメンション候補として検索する
     *
     * @param Request $request
     * @param BoardPost $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchMentionableUsers(Request $request, BoardPost $post): \Illuminate\Http\JsonResponse
    {
        $term = strtolower($request->query('query', ''));
        $mentionableUserIds = collect();

        // 1. 投稿者を常に候補に追加
        $mentionableUserIds->push($post->user_id);

        // 2. 閲覧権限に応じて候補ユーザーのIDリストを作成
        if ($post->role_id) {
            // ロールが指定されている場合、そのロールに所属するユーザーIDを取得
            $roleUserIds = \App\Models\Role::find($post->role_id)->users()->pluck('id');
            $mentionableUserIds = $mentionableUserIds->merge($roleUserIds);
        } elseif ($post->readableUsers->isNotEmpty()) {
            // 個別ユーザーが指定されている場合、そのユーザーIDを取得
            $mentionableUserIds = $mentionableUserIds->merge($post->readableUsers->pluck('id'));
        } else {
            // 全公開の場合は、すべてのアクティブユーザーを対象とする
            $allUserIds = \App\Models\User::where('status', \App\Models\User::STATUS_ACTIVE)->pluck('id');
            $mentionableUserIds = $mentionableUserIds->merge($allUserIds);
        }

        // 3. 検索クエリを構築
        $query = \App\Models\User::whereIn('id', $mentionableUserIds->unique())
            ->where('status', \App\Models\User::STATUS_ACTIVE); // 念のためアクティブユーザーに限定

        // 検索語（名前またはaccess_id）でさらに絞り込み
        if ($term) {
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('access_id', 'LIKE', "%{$term}%");
            });
        }

        $users = $query->select('id', 'name', 'access_id')->take(10)->get();

        // 4. フロントエンドが要求する形式にフォーマット
        $results = $users->map(function ($user) {
            return ['id' => $user->access_id, 'text' => $user->name];
        });

        // 5. `@all` の候補を追加するロジック
        if (\Illuminate\Support\Str::startsWith('all', $term)) {
            $allMention = collect([
                ['id' => 'all', 'text' => '全員にメンション (@all)']
            ]);
            // ユーザーリストの先頭に @all を追加
            $results = $allMention->concat($results);
        }

        return response()->json($results);
    }

    /**
     * カスタム項目の値を保存する
     */
    protected function saveCustomFieldValues(BoardPost $post, Request $request)
    {
        if (!$post->boardPostType) {
            Log::warning('BoardPost has no boardPostType', ['post_id' => $post->id]);
            return;
        }

        // 投稿タイプのnameと一致するcategoryのFormFieldDefinitionを取得
        $formFields = FormFieldDefinition::where('category', $post->boardPostType->name)
            ->where('is_enabled', true)
            ->orderBy('order')
            ->orderBy('label')
            ->get();

        Log::info('Saving custom field values', [
            'post_id' => $post->id,
            'post_type' => $post->boardPostType->name,
            'form_fields_count' => $formFields->count(),
            'request_data' => $request->only(
                $formFields->map(fn($field) => "custom_field_{$field->id}")->toArray()
            )
        ]);

        $validationErrors = [];

        foreach ($formFields as $field) {
            $fieldName = "custom_field_{$field->id}";
            $value = $request->input($fieldName);

            Log::debug('Processing custom field', [
                'field_id' => $field->id,
                'field_name' => $fieldName,
                'field_label' => $field->label,
                'field_type' => $field->type,
                'is_required' => $field->is_required,
                'value' => $value
            ]);

            // バリデーション
            if ($field->is_required && empty($value)) {
                $validationErrors[$fieldName] = ["{$field->label}は必須です。"];
                continue;
            }

            // 値を保存または削除
            if ($value !== null && $value !== '') {
                $post->setCustomFieldValue($field->id, $value);
                Log::info('Custom field value saved', [
                    'post_id' => $post->id,
                    'field_id' => $field->id,
                    'value' => $value
                ]);
            } else {
                // 空の値の場合は削除
                $deleted = $post->customFieldValues()
                    ->where('form_field_definition_id', $field->id)
                    ->delete();

                if ($deleted) {
                    Log::info('Custom field value deleted', [
                        'post_id' => $post->id,
                        'field_id' => $field->id
                    ]);
                }
            }
        }

        // バリデーションエラーがある場合は例外を投げる
        if (!empty($validationErrors)) {
            Log::warning('Custom field validation errors', $validationErrors);
            throw ValidationException::withMessages($validationErrors);
        }
    }

    /**
     * 投稿タイプに応じたカスタム項目を取得（AJAX用）
     */
    public function getCustomFields(Request $request)
    {
        $postTypeId = $request->input('post_type_id');

        if (!$postTypeId) {
            return response()->json(['fields' => []]);
        }

        $postType = BoardPostType::find($postTypeId);

        if (!$postType) {
            return response()->json(['fields' => []]);
        }

        // BoardPostTypeのnameと一致するcategoryのFormFieldDefinitionを取得
        $fields = FormFieldDefinition::where('category', $postType->name)
            ->where('is_enabled', true)
            ->orderBy('order')
            ->orderBy('label')
            ->get()
            ->map(function ($field) {
                return [
                    'id' => $field->id,
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'options' => $field->options,
                    'placeholder' => $field->placeholder,
                    'is_required' => $field->is_required,
                    'order' => $field->order,
                ];
            });

        return response()->json(['fields' => $fields]);
    }
}
