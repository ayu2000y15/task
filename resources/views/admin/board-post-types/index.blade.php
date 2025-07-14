@extends('layouts.app')

@section('title', '投稿タイプ管理')

@section('breadcrumbs')
    <a href="{{ route('home.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">ホーム</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">投稿タイプ管理</span>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableBody = document.getElementById('sortable-table-body');

            if (tableBody) {
                // 並び替え機能の初期化
                new Sortable(tableBody, {
                    animation: 150,
                    handle: '.drag-handle', // ドラッグハンドルのクラスを指定
                    onEnd: function (evt) {
                        updateOrderNumbers();
                        updateOrder();
                    }
                });

                // 行クリックによるページ遷移の処理
                tableBody.addEventListener('click', function(e) {
                    // 操作ボタン、リンク、フォーム要素、ドラッグハンドル内でのクリックは無視
                    const ignoreElements = 'A, BUTTON, FORM, INPUT, .drag-handle, .fa-grip-vertical';
                    if (e.target.closest(ignoreElements)) {
                        return;
                    }

                    // data-href属性を持つ行を探してページ遷移
                    const row = e.target.closest('tr[data-href]');
                    if (row && row.dataset.href) {
                        window.location.href = row.dataset.href;
                    }
                });
            }

            // 並び替え後に順序番号を画面上で更新する関数
            function updateOrderNumbers() {
                const rows = Array.from(tableBody.querySelectorAll('tr[data-id]'));
                rows.forEach((row, index) => {
                    const orderCell = row.querySelector('.order-cell');
                    if (orderCell) {
                        orderCell.textContent = index + 1;
                    }
                });
            }

            // サーバーに新しい順序を保存する関数
            function updateOrder() {
                const rows = Array.from(tableBody.querySelectorAll('tr[data-id]'));
                const items = rows.map((row, index) => ({
                    id: parseInt(row.dataset.id),
                    order: index + 1
                }));

                fetch('{{ route("admin.board-post-types.update-order") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ items })
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok.');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        console.log('順序を更新しました');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    </script>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">投稿タイプ管理</h1>
            @can('create', App\Models\BoardPostType::class)
                <x-primary-button as="a" href="{{ route('admin.board-post-types.create') }}">
                    <i class="fas fa-plus mr-2"></i>新規作成
                </x-primary-button>
            @endcan
        </div>

        <div
            class="mb-4 p-3 text-sm bg-blue-50 text-blue-800 border border-blue-200 rounded-lg dark:bg-gray-700 dark:text-blue-300 dark:border-blue-600">
            <h4 class="font-bold mb-1"><i class="fas fa-info-circle mr-1"></i>投稿タイプとカスタム項目の連携</h4>
            <ul class="list-disc list-inside text-xs">
                <li>投稿タイプを作成すると、同じ名前のカスタム項目カテゴリが自動作成されます。</li>
                <li>投稿タイプの名前や表示名を変更すると、対応するカスタム項目カテゴリも同期されます。</li>
                <li>投稿タイプを削除すると、対応するカスタム項目カテゴリも削除されます（※カスタム項目が存在しない場合のみ）。</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto w-full">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 w-12"></th> {{-- ドラッグハンドル用 --}}
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">順序</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">表示名</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">システム名</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">説明</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ステータス</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($boardPostTypes as $type)
                            {{-- isDeletable() はコントローラ側で定義・渡すことを想定 --}}
                            @php $isDeletable = $type->posts_count === 0; @endphp
                            <tr data-id="{{ $type->id }}" data-href="{{ route('admin.board-post-types.show', $type) }}" class="hover:bg-gray-100 dark:hover:bg-gray-700/50 cursor-pointer">
                                <td class="px-4 py-4 text-gray-400 dark:text-gray-500 text-center drag-handle"><i class="fas fa-grip-vertical"></i></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 order-cell">{{ $type->order }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $type->display_name }}
                                    @if($type->is_default)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            <i class="fas fa-star mr-1"></i>デフォルト
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $type->name }}</code>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate" title="{{ $type->description }}">
                                    {{ $type->description ?: '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($type->is_active)
                                        <i class="fas fa-check-circle text-green-500" title="有効"></i>
                                    @else
                                        <i class="fas fa-times-circle text-gray-400" title="無効"></i>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        @can('update', $type)
                                            <x-icon-button :href="route('admin.board-post-types.edit', $type)" icon="fas fa-edit" title="編集" color="blue" />
                                        @endcan

                                        @can('delete', $type)
                                            @if($isDeletable)
                                                <form action="{{ route('admin.board-post-types.destroy', $type) }}" method="POST" onsubmit="return confirm('本当に投稿タイプ「{{ $type->display_name }}」を削除しますか？');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-icon-button icon="fas fa-trash" title="削除" color="red" type="submit" />
                                                </form>
                                            @else
                                                 <x-icon-button icon="fas fa-trash" title="この投稿タイプには投稿が存在するため削除できません" color="gray" disabled="true" />
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    投稿タイプが登録されていません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
