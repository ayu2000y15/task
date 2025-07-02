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

        function updateItemDate(itemId, dateValue, field, element) {
            const originalValue = element.defaultValue; // ロード時の値を保持
            const url = field === 'start_at' ? `/requests/items/${itemId}/set-start-at` : `/requests/items/${itemId}/set-end-at`;

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ [field]: dateValue })
            })
                .then(res => {
                    if (!res.ok) {
                        // レスポンスが正常でない場合 (422バリデーションエラーなど) は、Promiseをrejectしてcatchブロックに渡す
                        return Promise.reject(res);
                    }
                    return res.json();
                })
                .then(data => {
                    // 成功したら、デフォルト値を更新
                    element.defaultValue = element.value;
                    console.log(data.message || '日時を更新しました。');
                })
                .catch(response => {
                    // rejectされたレスポンスをここで処理
                    response.json().then(errorData => {
                        if (response.status === 422 && errorData.errors) {
                            // Laravelからのバリデーションエラーの場合
                            const messages = Object.values(errorData.errors).flat().join('\n');
                            alert('入力エラー:\n' + messages);
                        } else {
                            // その他のサーバーエラー
                            alert(errorData.message || '日時の更新に失敗しました。');
                        }
                        // エラー発生時は入力値を元に戻す
                        element.value = originalValue;
                    }).catch(jsonParseError => {
                        // レスポンスがJSON形式でなかった場合
                        alert('サーバーとの通信に失敗しました。');
                        element.value = originalValue;
                    });
                });
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
                        alert('担当者ではないため、予定・依頼項目を更新できません。');
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
                        alert(data.message || '予定・依頼の並び順を更新しました。');
                    })
                    .catch(errorData => {
                        // 失敗した場合: エラーメッセージをアラートで表示
                        alert('エラー: ' + (errorData.message || '並び順の更新に失敗しました。'));
                    });
            }
        });

        document.body.addEventListener('change', function (e) {
            if (e.target.matches('.start-at-input')) {
                updateItemDate(e.target.dataset.itemId, e.target.value, 'start_at', e.target);
            }
            if (e.target.matches('.end-at-input')) {
                updateItemDate(e.target.dataset.itemId, e.target.value, 'end_at', e.target);
            }
            if (e.target.matches('.request-item-checkbox')) {
                handleCheckboxChange(e.target);
            }
        });
    });
</script>