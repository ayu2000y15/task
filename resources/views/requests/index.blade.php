@extends('layouts.app')
@section('title', '作業依頼一覧')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ tab: 'assigned' }">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">作業依頼一覧</h1>
            <x-primary-button as="a" href="{{ route('requests.create') }}">
                <i class="fas fa-plus mr-2"></i>新規依頼を作成
            </x-primary-button>
        </div>
        <div class="p-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500"
            role="alert">
            <i class="fas fa-info-circle mr-1"></i>
            各項目の日付を設定すると、その日のホーム画面「やることリスト」にタスクが表示され、日々の計画に役立ちます。
        </div>

        {{-- タブ切り替え --}}
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-6 sm:space-x-8 overflow-x-auto" aria-label="Tabs">
                <button @click="tab = 'assigned'"
                    :class="tab === 'assigned' ? 'font-semibold border-blue-600 text-blue-600 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500'"
                    class="py-4 px-1 inline-flex items-center gap-x-2 border-b-2 font-medium text-sm whitespace-nowrap focus:outline-none">
                    受信した依頼
                    @if($pendingAssigned->count() > 0)
                        <span
                            class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">{{ $pendingAssigned->count() }}</span>
                    @endif
                </button>
                <button @click="tab = 'personal'"
                    :class="tab === 'personal' ? 'font-semibold border-blue-600 text-blue-600 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500'"
                    class="py-4 px-1 inline-flex items-center gap-x-2 border-b-2 font-medium text-sm whitespace-nowrap focus:outline-none">
                    自分用
                    @if($pendingPersonal->count() > 0)
                        <span
                            class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">{{ $pendingPersonal->count() }}</span>
                    @endif
                </button>
                <button @click="tab = 'created'"
                    :class="tab === 'created' ? 'font-semibold border-blue-600 text-blue-600 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-500'"
                    class="py-4 px-1 inline-flex items-center gap-x-2 border-b-2 font-medium text-sm whitespace-nowrap focus:outline-none">
                    送信した依頼
                </button>
            </nav>
        </div>

        {{-- 受信依頼パネル --}}
        <div x-show="tab === 'assigned'" class="space-y-8">
            @include('requests.partials.request-list', ['title' => '未完了の受信依頼', 'requests' => $pendingAssigned, 'isEmptyMessage' => '未完了の受信依頼はありません。'])
            @include('requests.partials.request-list', ['title' => '完了済みの受信依頼', 'requests' => $completedAssigned, 'isEmptyMessage' => '完了済みの受信依頼はありません。', 'collapsible' => true])
        </div>

        {{-- ▼▼▼【ここから追加】「自分用」パネル ▼▼▼ --}}
        <div x-show="tab === 'personal'" x-cloak class="space-y-8">
            @include('requests.partials.request-list', ['title' => '未完了の自分用タスク', 'requests' => $pendingPersonal, 'isEmptyMessage' => '未完了のタスクはありません。'])
            @include('requests.partials.request-list', ['title' => '完了済みの自分用タスク', 'requests' => $completedPersonal, 'isEmptyMessage' => '完了済みのタスクはありません。', 'collapsible' => true])
        </div>
        {{-- ▲▲▲【追加ここまで】▲▲▲ --}}

        {{-- 送信依頼パネル --}}
        <div x-show="tab === 'created'" x-cloak class="space-y-8">
            @include('requests.partials.request-list', ['title' => '未完了の送信依頼', 'requests' => $pendingCreated, 'isEmptyMessage' => '未完了の送信依頼はありません。'])
            @include('requests.partials.request-list', ['title' => '完了済みの送信依頼', 'requests' => $completedCreated, 'isEmptyMessage' => '完了済みの送信依頼はありません。', 'collapsible' => true])
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // =================================================================
            // 機能1: 日付ピッカーで「やることリスト」の計画日を設定する
            // =================================================================

            // 計画日を設定/解除する共通関数
            function updateMyDayDate(itemId, dateValue) {
                fetch(`/requests/items/${itemId}/set-my-day`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ date: dateValue })
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Update failed');
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) {
                            alert('計画日の更新に失敗しました。');
                        }
                        // 日付をクリアした場合、クリアボタンを消すためにページをリロードするのが簡単で確実です
                        if (dateValue === null) {
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        alert('計画日の更新中にエラーが発生しました。');
                        console.error('Error:', error);
                    });
            }

            // 日付ピッカーの変更を監視
            document.querySelectorAll('.my-day-date-input').forEach(input => {
                input.addEventListener('change', function () {
                    const itemId = this.dataset.itemId;
                    const newDate = this.value;
                    // 日付が設定されたら、ページをリロードしてクリアボタンを表示させる
                    if (newDate) {
                        updateMyDayDate(itemId, newDate);
                        // 成功を待たずにUIを即時変更したい場合はここに記述
                        // ただし、リロードした方が確実です
                    }
                });
            });

            // 日付クリアボタンのクリックを監視
            document.querySelectorAll('.my-day-clear-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const itemId = this.dataset.itemId;
                    const dateInput = document.querySelector(`.my-day-date-input[data-item-id="${itemId}"]`);
                    if (dateInput) {
                        dateInput.value = ''; // 視覚的に入力欄を空にする
                    }
                    updateMyDayDate(itemId, null); // サーバーにnullを送って日付をクリア
                });
            });


            // =================================================================
            // 機能2: チェックボックスで項目の完了/未完了を切り替える
            // =================================================================
            const checkboxes = document.querySelectorAll('.request-item-checkbox');
            checkboxes.forEach(checkbox => {
                // 重複してイベントリスナーが登録されるのを防ぐ
                if (checkbox.dataset.initialized) return;
                checkbox.dataset.initialized = true;

                checkbox.addEventListener('change', function () {
                    const itemId = this.dataset.itemId;
                    const isCompleted = this.checked;
                    const label = this.closest('li').querySelector('label');
                    const statusSpan = document.getElementById(`status-${itemId}`);

                    fetch(`/requests/items/${itemId}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            is_completed: isCompleted
                        })
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                if (isCompleted) {
                                    label.classList.add('line-through', 'text-gray-500');
                                    if (statusSpan && data.item.completed_by) {
                                        const completedDate = new Date(data.item.completed_at).toLocaleString('ja-JP', { month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric' });
                                        statusSpan.innerHTML = ` - ${data.item.completed_by.name}が完了 (${completedDate})`;
                                        statusSpan.classList.remove('hidden');
                                    }
                                } else {
                                    label.classList.remove('line-through', 'text-gray-500');
                                    if (statusSpan) {
                                        statusSpan.classList.add('hidden');
                                    }
                                }
                            } else {
                                this.checked = !isCompleted; // 失敗したらチェックボックスを元に戻す
                            }
                        })
                        .catch(error => {
                            console.error('There was a problem with the fetch operation:', error);
                            alert('更新に失敗しました。');
                            this.checked = !isCompleted;
                        });
                });
            });

            // 終了予定日時を更新する共通関数
            function updateDueDate(itemId, dateValue) {
                fetch(`/requests/items/${itemId}/set-due-date`, { // ★ ルート変更
                    method: 'PATCH', // ★ PATCHに変更
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ due_date: dateValue }) // ★ キーを due_date に変更
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Update failed');
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) {
                            alert('終了予定日時の更新に失敗しました。');
                        }
                        // 成功時は何もしない（画面はそのまま）
                    })
                    .catch(error => {
                        alert('終了予定日時の更新中にエラーが発生しました。');
                        console.error('Error:', error);
                    });
            }

            // 終了予定日時ピッカーの変更を監視
            document.querySelectorAll('.due-date-input').forEach(input => {
                input.addEventListener('change', function () {
                    const itemId = this.dataset.itemId;
                    const newDate = this.value;
                    updateDueDate(itemId, newDate);
                });
            });
        });
    </script>
@endpush