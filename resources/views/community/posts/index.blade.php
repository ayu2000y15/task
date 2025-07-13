@extends('layouts.app')

@section('title', '社内掲示板')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- ヘッダー：タイトルと新規作成ボタン --}}
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">社内掲示板</h1>
            <div>
                @can('create', App\Models\BoardPost::class)
                    <x-primary-button as="a" href="{{ route('community.posts.create') }}">
                        <i class="fas fa-plus mr-1"></i> 新規投稿作成
                    </x-primary-button>
                @endcan
            </div>
        </div>

        {{-- 絞り込み表示 --}}
        @if(request('tag') || request('post_type'))
            <div class="mb-6 space-y-4">
                {{-- タグ絞り込み --}}
                @if(request('tag'))
                    <div class="p-4 bg-purple-50 dark:bg-purple-900/50 border-l-4 border-purple-400 dark:border-purple-500">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-tag text-purple-500 dark:text-purple-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-purple-700 dark:text-purple-200">
                                    タグ「<strong>{{ request('tag') }}</strong>」で絞り込み中。
                                    <a href="{{ route('community.posts.index', array_filter(['post_type' => request('post_type')])) }}"
                                        class="font-medium underline hover:text-purple-600 dark:hover:text-purple-100 ml-2">タグ絞り込みを解除</a>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- 投稿タイプ絞り込み --}}
                @if(request('post_type'))
                    <div class="p-4 bg-indigo-50 dark:bg-indigo-900/50 border-l-4 border-indigo-400 dark:border-indigo-500">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-tag text-indigo-500 dark:text-indigo-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-indigo-700 dark:text-indigo-200">
                                    投稿タイプ「<strong>{{ \App\Models\BoardPostType::where('name', request('post_type'))->first()->display_name ?? request('post_type') }}</strong>」で絞り込み中。
                                    <a href="{{ route('community.posts.index', array_filter(['tag' => request('tag')])) }}"
                                        class="font-medium underline hover:text-indigo-600 dark:hover:text-indigo-100 ml-2">投稿タイプ絞り込みを解除</a>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- 全ての絞り込みを解除 --}}
                @if(request('tag') && request('post_type'))
                    <div class="text-center">
                        <a href="{{ route('community.posts.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md transition">
                            <i class="fas fa-times mr-2"></i>すべての絞り込みを解除
                        </a>
                    </div>
                @endif
            </div>
        @endif

        {{-- 投稿一覧テーブル --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            タイトル
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            閲覧範囲
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            投稿タイプ
                        </th>
                        {{-- ▼▼▼【ここから追加】▼▼▼ --}}
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            タグ
                        </th>
                        {{-- ▲▲▲【ここまで】▲▲▲ --}}
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            投稿者
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            最終更新日時
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            コメント
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[180px]">
                            操作
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($posts as $post)
                        @can('view', $post)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100 relative">
                                    {{-- 未読または既読の状態に応じてバッジを表示 --}}
                                    @if ($unreadPostIds->contains($post->id))
                                        <span
                                            class="absolute top-1.5 left-2 inline-block px-1.5 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full"
                                            style="font-size: 0.6rem; line-height: 1;">NEW</span>
                                    @elseif ($readPostIds->contains($post->id))
                                        <span
                                            class="absolute top-1.5 left-2 inline-block px-1.5 py-0.5 text-xs font-semibold text-gray-600 bg-gray-200 dark:text-gray-300 dark:bg-gray-600 rounded-full"
                                            style="font-size: 0.6rem; line-height: 1;">既読</span>
                                    @endif

                                    {{-- タイトル本体。バッジの分だけ左に余白を設ける --}}
                                    <a href="{{ route('community.posts.show', $post) }}"
                                        class="block pl-10 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 hover:underline">
                                        {{ Str::limit($post->title, 50) }}
                                    </a>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-wrap items-center gap-1">
                                        @if ($post->role)
                                            <span
                                                class="px-2 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200"
                                                title="ロール: {{ $post->role->display_name ?? $post->role->name }}">
                                                <i class="fas fa-users mr-1 mt-0.5"></i>
                                                {{ Str::limit($post->role->display_name ?? $post->role->name, 10) }}
                                            </span>
                                        @endif

                                        @if ($post->readableUsers->isNotEmpty())
                                            <span
                                                class="px-2 y-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                                title="指定ユーザー: {{ $post->readableUsers->pluck('name')->join(', ') }}">
                                                <i class="fas fa-user-check mr-1 mt-0.5"></i>
                                                個別指定 ({{ $post->readableUsers->count() }})
                                            </span>
                                        @endif

                                        @if (!$post->role && $post->readableUsers->isEmpty())
                                            <span
                                                class="px-2 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">
                                                <i class="fas fa-globe-asia mr-1 mt-0.5"></i>
                                                全公開
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($post->boardPostType)
                                        <a href="{{ route('community.posts.index', ['post_type' => $post->boardPostType->name]) }}"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium hover:shadow transition no-underline
                                                                                            @if($post->boardPostType->name === 'announcement')
                                                                                                bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800
                                                                                            @else
                                                                                                bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300 hover:bg-indigo-200 dark:hover:bg-indigo-800
                                                                                            @endif">
                                            <i class="fas fa-tag mr-1"></i>
                                            {{ $post->boardPostType->display_name }}
                                        </a>
                                    @endif
                                </td>

                                {{-- ▼▼▼【ここから追加】▼▼▼ --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($post->tags->isNotEmpty())
                                        <div class="flex flex-wrap items-center gap-1">
                                            @foreach($post->tags->take(2) as $tag) {{-- 表示数を2つに制限 --}}
                                                <a href="{{ route('community.posts.index', ['tag' => $tag->name]) }}"
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-800 transition no-underline">
                                                    <i class="fas fa-tag mr-1"></i>
                                                    {{ Str::limit($tag->name, 10) }}
                                                </a>
                                            @endforeach
                                            @if($post->tags->count() > 2)
                                                <span class="px-2 py-0.5 text-xs text-gray-400 dark:text-gray-500"
                                                    title="{{ $post->tags->slice(2)->pluck('name')->join(', ') }}">+{{ $post->tags->count() - 2 }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                {{-- ▲▲▲【ここまで】▲▲▲ --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $post->user->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $post->updated_at->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <i class="far fa-comment-dots mr-1"></i>
                                    {{ $post->comments_count ?? $post->comments->count() }}
                                    @if($unreadCommentPostIds->contains($post->id))
                                        <span class="px-2 py-0.5 text-xs font-bold text-white bg-orange-500 rounded-full">未読あり</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <x-secondary-button as="a" href="{{ route('community.posts.show', $post) }}"
                                            class="py-1 px-3 text-xs">
                                            <i class="fas fa-eye mr-1"></i>詳細
                                        </x-secondary-button>

                                        @if(auth()->check() && auth()->id() === $post->user_id)
                                            <x-secondary-button as="a" href="{{ route('community.posts.edit', $post) }}"
                                                class="py-1 px-3 text-xs">
                                                <i class="fas fa-edit mr-1"></i>編集
                                            </x-secondary-button>
                                        @endif

                                        @can('delete', $post)
                                            <form action="{{ route('community.posts.destroy', $post) }}" method="POST"
                                                class="inline-block" onsubmit="return confirm('本当に投稿「{{ $post->title }}」を削除しますか？');">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button type="submit" class="py-1 px-3 text-xs">
                                                    <i class="fas fa-trash mr-1"></i>削除
                                                </x-danger-button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endcan
                    @empty
                        <tr>
                            {{-- ▼▼▼【変更】colspanを7に修正 ▼▼▼ --}}
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                投稿はまだありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ページネーションリンク --}}
        @if ($posts->hasPages())
            <div class="mt-4">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
@endsection