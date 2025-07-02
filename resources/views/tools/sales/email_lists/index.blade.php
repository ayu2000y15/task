@extends('layouts.tool')

@section('title', 'メールリスト管理')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">メールリスト管理</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">メールリスト管理</h1>
            <div>
                @can('tools.sales.access')
                    <x-primary-button as="a" href="{{ route('tools.sales.email-lists.create') }}">
                        <i class="fas fa-plus mr-1"></i> 新規メールリスト作成
                    </x-primary-button>
                @endcan
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky z-30">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            リスト名
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[200px] w-1/3">
                            説明
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            登録数
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            作成日
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[180px]">
                            操作
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($emailLists as $list)
                        {{-- ▼▼▼ trタグに data-href とカーソル、ホバー時のスタイルを追加 ▼▼▼ --}}
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                            data-href="{{ route('tools.sales.email-lists.show', $list) }}">
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{-- リスト名のリンクを削除し、単なるテキストに --}}
                                {{ $list->name }}
                            </td>
                            <td class="px-6 py-2 text-sm text-gray-500 dark:text-gray-400 ">
                                {!! nl2br(e(trim($list->description))) !!}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                {{ $list->subscribers_count }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $list->created_at->format('Y/m/d') }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    @can('tools.sales.access')
                                        <x-secondary-button as="a" href="{{ route('tools.sales.email-lists.edit', $list) }}"
                                            class="py-1 px-3 text-xs action-button"> {{-- action-buttonクラスを追加 (JS用) --}}
                                            <i class="fas fa-edit mr-1"></i>編集
                                        </x-secondary-button>
                                        <form action="{{ route('tools.sales.email-lists.destroy', $list) }}" method="POST"
                                            class="inline-block action-button" {{-- action-buttonクラスを追加 (JS用) --}}
                                            onsubmit="return confirm('本当に「{{ $list->name }}」を削除しますか？この操作は元に戻せません。');">
                                            @csrf
                                            @method('DELETE')
                                            <x-danger-button type="submit" class="py-1 px-3 text-xs">
                                                <i class="fas fa-trash mr-1"></i>削除
                                            </x-danger-button>
                                        </form>
                                        @if($list->subscribers_count > 0)
                                            <form action="{{ route('tools.sales.email-lists.subscribers.destroy-all', $list) }}"
                                                method="POST" class="inline-block action-button"
                                                onsubmit="return confirm('「{{ $list->name }}」の購読者 {{ $list->subscribers_count }} 件をすべて削除します。よろしいですか？\nこの操作は元に戻せません。');">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button type="submit" class="py-1 px-3 text-xs">
                                                    <i class="fas fa-users-slash mr-1"></i>購読者全件削除
                                                </x-danger-button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">(操作権限なし)</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                メールリストはまだ作成されていません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($emailLists->hasPages())
            <div class="mt-4">
                {{ $emailLists->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rows = document.querySelectorAll('tbody tr[data-href]');
            rows.forEach(row => {
                row.addEventListener('click', function (event) {
                    // クリックされた要素がボタン、リンク、フォーム、またはそれらの子要素であるかを確認
                    // .action-button クラスを持つ要素、またはその親に .action-button がある場合は遷移しない
                    if (event.target.closest('.action-button, a[href], button, input[type="submit"]')) {
                        // もしボタン/リンク自身やそのアイコンがクリックされた場合は、
                        // ボタン/リンクのデフォルト動作を優先し、行クリックでの遷移は行わない
                        return;
                    }
                    // 上記以外（セルの空きスペースなど）がクリックされた場合に遷移
                    if (this.dataset.href) {
                        window.location.href = this.dataset.href;
                    }
                });
            });

            // 編集ボタンや削除フォーム内のボタンがクリックされたときに、
            // 親のtr要素のクリックイベントが発火して画面遷移するのを防ぐ
            // (上記の closest('.action-button') で対応しているが、念のため個別にも設定可能)
            // const actionButtons = document.querySelectorAll('.action-button');
            // actionButtons.forEach(button => {
            //     button.addEventListener('click', function(event) {
            //         event.stopPropagation();
            //     });
            // });
        });
    </script>
@endpush