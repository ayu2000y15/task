@extends('layouts.app')

@section('title', '投稿タイプ管理')

@section('breadcrumbs')
    <a href="{{ route('home.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">ホーム</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">投稿タイプ管理</span>
@endsection

@push('styles')
    <style>
        .sortable {
            /* 行自体はデフォルト */
        }

        .drag-handle {
            cursor: move;
        }

        .sortable:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }

        .sortable.dragging {
            opacity: 0.5;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableBody = document.getElementById('sortable-table-body');

            if (tableBody) {
                new Sortable(tableBody, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    handle: '.drag-handle', // ドラッグハンドルを限定
                    onStart: function (evt) {
                        evt.item.classList.add('dragging');
                    },
                    onEnd: function (evt) {
                        evt.item.classList.remove('dragging');
                        updateOrder();
                        updateOrderNumbers();
                    }
                });
            }

            // 並び替え後に順序番号を即時で更新
            function updateOrderNumbers() {
                const rows = Array.from(tableBody.querySelectorAll('tr[data-id]'));
                rows.forEach((row, index) => {
                    // 順序番号を表示している要素を取得
                    const orderCell = row.querySelector('td');
                    if (orderCell) {
                        // drag-handleのspanの次のテキストノードを更新
                        const handle = orderCell.querySelector('.drag-handle');
                        if (handle && handle.nextSibling) {
                            // テキストノードを探して更新
                            let next = handle.nextSibling;
                            while (next && next.nodeType !== 3) { // 3: Text Node
                                next = next.nextSibling;
                            }
                            if (next) {
                                next.textContent = ' ' + (index + 1);
                            }
                        }
                    }
                });
            }

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
                    .then(response => response.json())
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
            <div>
                @can('create', App\Models\BoardPost::class)
                    <x-primary-button as="a" href="{{ route('admin.board-post-types.create') }}">
                        <i class="fas fa-plus mr-1"></i> 新規作成
                    </x-primary-button>
                @endcan
            </div>
        </div>

        <div
            class="mb-2 p-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>投稿タイプとカスタム項目の連携について：</strong><br>
            • 投稿タイプを作成すると、同じ名前のカスタム項目カテゴリが自動作成されます<br>
            • 投稿タイプの名前や表示名を変更すると、対応するカスタム項目カテゴリも同期されます<br>
            • 投稿タイプを削除すると、対応するカスタム項目カテゴリも削除されます（カスタム項目が存在しない場合）
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto w-full">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                順序
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                表示名
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                システム名
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                説明
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                ステータス
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                操作
                            </th>
                        </tr>
                    </thead>
                    <tbody id="sortable-table-body"
                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($boardPostTypes as $type)
                            <tr data-id="{{ $type->id }}" class="sortable hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <span class="drag-handle"><i class="fas fa-grip-vertical text-gray-400 mr-2"></i></span>
                                    {{ $type->order }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $type->display_name }}
                                                @if($type->is_default)
                                                    <span
                                                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                        <i class="fas fa-star mr-1"></i>デフォルト
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <code
                                        class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $type->name }}</code>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs">
                                    <div class="truncate" title="{{ $type->description }}">
                                        {{ $type->description ?: '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($type->is_active)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                            <i class="fas fa-check-circle mr-1"></i>有効
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                            <i class="fas fa-times-circle mr-1"></i>無効
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        @can('view', $type)
                                            <x-secondary-button as="a" href="{{ route('admin.board-post-types.show', $type) }}"
                                                class="py-1 px-3 text-xs">
                                                <i class="fas fa-eye mr-1"></i>詳細
                                            </x-secondary-button>
                                        @endcan

                                        @can('update', $type)
                                            <x-secondary-button as="a" href="{{ route('admin.board-post-types.edit', $type) }}"
                                                class="py-1 px-3 text-xs">
                                                <i class="fas fa-edit mr-1"></i>編集
                                            </x-secondary-button>
                                        @endcan

                                        @can('delete', $type)
                                            <form action="{{ route('admin.board-post-types.destroy', $type) }}" method="POST"
                                                class="inline-block"
                                                onsubmit="return confirm('本当に投稿タイプ「{{ $type->display_name }}」を削除しますか？\n\n使用中の投稿がある場合は削除できません。');">
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
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    投稿タイプが登録されていません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    </div>
@endsection
