@extends('layouts.app')

@section('title', 'フォーム管理')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">フォーム管理</h1>
            <x-primary-button
                onclick="location.href='{{ route('admin.form-categories.create') }}'">
                <i class="fas fa-plus mr-2"></i>新規フォームを作成
            </x-primary-button>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">
                            </th> {{-- ドラッグハンドル用 --}}
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
                                納期目安
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                フィールド数
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                外部フォーム
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                状態
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                操作
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="sortable-categories">
                        @forelse ($categories as $category)
                            {{-- 行クリックで詳細ページに遷移するための属性を追加 --}}
                            <tr data-id="{{ $category->id }}"
                                data-href="{{ route('admin.form-categories.show', $category) }}"
                                class="hover:bg-gray-100 dark:hover:bg-gray-700/50 cursor-pointer">
                                <td
                                    class="px-4 py-4 whitespace-nowrap text-sm text-gray-400 dark:text-gray-500 cursor-move drag-handle text-center">
                                    <i class="fas fa-grip-vertical"></i>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 order-cell">
                                    {{ $category->order }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $category->display_name }}
                                    </div>
                                    @if($category->description)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ Str::limit($category->description, 50) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($category->delivery_estimate_text)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ $category->delivery_estimate_text }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            なし
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $category->form_field_definitions_count }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($category->is_external_form)
                                        <div class="flex flex-col space-y-1">
                                            <div>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    公開中
                                                </span>
                                            </div>
                                            @if($category->slug)
                                                {{-- 外部リンクのクリックはページ遷移を妨げないようにする --}}
                                                <a href="{{ $category->external_form_url }}" target="_blank"
                                                   class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                                    /{{ $category->slug }}
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            非公開
                                        </span>
                                    </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    @if($category->is_enabled)
                                        <i class="fas fa-check-circle text-green-500"></i>
                                    @else
                                        <i class="fas fa-times-circle text-gray-400"></i>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    {{-- 操作ボタンのクリックはページ遷移を妨げないようにJSで制御 --}}
                                    <div class="flex items-center justify-end space-x-2">
                                        <x-icon-button :href="route('admin.form-categories.edit', $category)"
                                            icon="fas fa-edit" title="編集" color="blue" />
                                        @if($category->isBeingUsed())
                                            <x-icon-button icon="fas fa-trash"
                                                title="このカテゴリは {{ $category->form_field_definitions_count }} 件の項目定義を持つため削除できません" color="gray"
                                                disabled="true" />
                                        @else
                                            <form action="{{ route('admin.form-categories.destroy', $category) }}" method="POST"
                                                onsubmit="return confirm('本当に削除しますか？この操作は取り消せません。');">
                                                @csrf
                                                @method('DELETE')
                                                <x-icon-button icon="fas fa-trash" title="削除" color="red" type="submit" />
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    フォームがありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('sortable-categories');

    if (tbody) {
        // 並べ替え機能の初期化
        const sortable = Sortable.create(tbody, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                updateOrderNumbers();
                saveOrder();
            }
        });

        // 行クリックによるページ遷移の処理
        tbody.addEventListener('click', function(e) {
            // クリックされた要素が操作ボタン、リンク、フォーム要素、またはドラッグハンドル内ではないかチェック
            const ignoreElements = 'A, BUTTON, FORM, INPUT, .drag-handle, .fa-grip-vertical';
            if (e.target.closest(ignoreElements)) {
                return; // これらの要素がクリックされた場合は何もしない
            }

            // クリックされた行（tr）を探し、data-href属性があればページ遷移する
            const row = e.target.closest('tr[data-href]');
            if (row && row.dataset.href) {
                window.location.href = row.dataset.href;
            }
        });
    }

    function updateOrderNumbers() {
        const rows = document.querySelectorAll('#sortable-categories tr');
        rows.forEach((row, index) => {
            const orderCell = row.querySelector('.order-cell');
            if (orderCell) {
                orderCell.textContent = index + 1;
            }
        });
    }

    function saveOrder() {
        const rows = document.querySelectorAll('#sortable-categories tr[data-id]');
        const orderedIds = Array.from(rows).map(row => row.dataset.id);

        fetch('{{ route('admin.form-categories.reorder') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ orderedIds: orderedIds })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                console.error('順序の保存に失敗しました:', data.message || '不明なエラー');
            }
        })
        .catch(error => {
            console.error('順序の保存中にエラーが発生しました:', error);
            // location.reload(); // 必要に応じてページをリロードして状態をリセット
        });
    }
});
</script>
@endpush
