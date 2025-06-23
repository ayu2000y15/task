{{-- resources/views/requests/partials/request-card-scripts.blade.php --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.requestCardScriptsInitialized) {
            return;
        }
        window.requestCardScriptsInitialized = true;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.querySelectorAll('.sortable-list').forEach(el => {
            if (el) {
                new Sortable(el, {
                    handle: '.drag-handle',
                    animation: 150,
                });
            }
        });

        function updateItemDate(itemId, dateValue, field) {
            const url = field === 'start_at' ? `/requests/items/${itemId}/set-start-at` : `/requests/items/${itemId}/set-end-at`;
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ [field]: dateValue })
            })
                .then(res => res.json())
                .catch(error => console.error('Date update error:', error));
        }

        function handleCheckboxChange(checkbox) {
            const itemId = checkbox.dataset.itemId;
            const isCompleted = checkbox.checked;
            const label = checkbox.closest('li').querySelector('label');
            const statusSpan = document.getElementById(`status-${itemId}`);

            fetch(`/requests/items/${itemId}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ is_completed: isCompleted })
            })
                .then(response => {
                    if (!response.ok) {
                        return Promise.reject(response);
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
                        checkbox.checked = !isCompleted;
                    }
                })
                .catch(response => {
                    if (response.status === 403) {
                        alert('担当者ではないため、依頼項目を更新できません。');
                    } else {
                        alert('更新に失敗しました。');
                    }
                    checkbox.checked = !isCompleted;
                });
        }

        document.body.addEventListener('click', function (e) {
            const saveOrderButton = e.target.closest('.save-request-item-order-btn');
            if (saveOrderButton) {
                const targetListSelector = saveOrderButton.dataset.targetList;
                const url = saveOrderButton.dataset.url;
                const listElement = document.querySelector(targetListSelector);

                if (!listElement) {
                    console.error('Target list for sorting not found:', targetListSelector);
                    return;
                }

                const itemIds = Array.from(listElement.children).map(row => row.dataset.id);

                if (!itemIds || itemIds.length === 0 || itemIds.some(id => id === undefined)) {
                    alert('並び替え対象の項目が見つかりません。');
                    return;
                }

                // ▼▼▼【ここから修正】メッセージ表示のロジックをアラートに変更 ▼▼▼
                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ item_ids: itemIds })
                })
                    .then(res => {
                        // サーバーからのレスポンスをJSONとして解析
                        return res.json().then(data => {
                            //レスポンスが成功(2xx)でなければ、エラーとして扱う
                            if (!res.ok) {
                                return Promise.reject(data);
                            }
                            return data;
                        });
                    })
                    .then(data => {
                        // 成功した場合: サーバーからのメッセージをアラートで表示
                        alert(data.message || '作業依頼の並び順を更新しました。');
                    })
                    .catch(errorData => {
                        // 失敗した場合: エラーメッセージをアラートで表示
                        alert('エラー: ' + (errorData.message || '並び順の更新に失敗しました。'));
                    });
                // ▲▲▲【修正ここまで】▲▲▲
            }
        });

        document.body.addEventListener('change', function (e) {
            if (e.target.matches('.start-at-input')) {
                updateItemDate(e.target.dataset.itemId, e.target.value, 'start_at');
            }
            if (e.target.matches('.end-at-input')) {
                updateItemDate(e.target.dataset.itemId, e.target.value, 'end_at');
            }
            if (e.target.matches('.request-item-checkbox')) {
                handleCheckboxChange(e.target);
            }
        });
    });
</script>