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

        $query = BoardPost::with('user', 'role', 'comments', 'readableUsers', 'tags');

        if ($request->has('tag')) {
            $tag = $request->input('tag');
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', $tag);
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
        $roles = Auth::user()->roles;
        // 全てのアクティブなユーザーを取得
        $allActiveUsers = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->pluck('name', 'id');

        return view('community.posts.create', compact('roles', 'allActiveUsers'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', BoardPost::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'role_id' => 'nullable|exists:roles,id',
            'readable_user_ids' => 'nullable|array', // ユーザー選択用のバリデーション
            'readable_user_ids.*' => 'exists:users,id', // 配列内の各IDが存在するか
        ]);

        $post = Auth::user()->boardPosts()->create($validated);

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
        $post->load('user', 'role', 'readableUsers', 'tags', 'reactions.user');

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
        $roles = Auth::user()->roles;
        // 全てのアクティブなユーザーを取得
        $allActiveUsers = User::where('status', User::STATUS_ACTIVE)->orderBy('name')->pluck('name', 'id');
        // この投稿で既に選択されている閲覧可能ユーザーのIDを取得
        $selectedReadableUserIds = $post->readableUsers()->pluck('id')->toArray();


        return view('community.posts.edit', compact('post', 'roles', 'allActiveUsers', 'selectedReadableUserIds'));
    }

    /**
     * 投稿を更新する
     */
    public function update(Request $request, BoardPost $post)
    {
        // ポリシーで更新権限をチェック
        $this->authorize('update', $post);

        // バリデーション
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'role_id' => 'nullable|exists:roles,id',
            'readable_user_ids' => 'nullable|array', // ユーザー選択用のバリデーション
            'readable_user_ids.*' => 'exists:users,id',
        ]);

        // 投稿を更新
        $post->update($validated);

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
}
