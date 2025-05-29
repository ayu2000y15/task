import Sortable from "sortablejs";

export function initFormDefinitionSortable() {
    // console.log('[FORM DEFINITIONS SORTABLE JS] initFormDefinitionSortable() function called.');

    const sortableDefinitionsTableBody = document.getElementById(
        "sortable-definitions"
    );

    if (!sortableDefinitionsTableBody) {
        // console.log('[FORM DEFINITIONS SORTABLE JS] Target tbody element "sortable-definitions" not found. Sortable not initialized.');
        return;
    }

    if (typeof Sortable === "undefined") {
        console.error(
            "[FORM DEFINITIONS SORTABLE JS] Sortable object is undefined. Make sure it is installed and imported correctly."
        );
        return;
    }
    // console.log('[FORM DEFINITIONS SORTABLE JS] Sortable object is defined. Initializing...');

    try {
        new Sortable(sortableDefinitionsTableBody, {
            animation: 150,
            handle: ".drag-handle",
            onEnd: function (evt) {
                // console.log('[FORM DEFINITIONS SORTABLE JS] Sortable onEnd event triggered.');
                const orderedIds = Array.from(
                    sortableDefinitionsTableBody.children
                ).map((item) => item.dataset.id);

                const reorderUrl = "/admin/form-definitions/reorder"; // ルート名は 'admin.form-definitions.reorder'
                const csrfTokenEl = document.querySelector(
                    'meta[name="csrf-token"]'
                );

                if (!csrfTokenEl) {
                    console.error(
                        "[FORM DEFINITIONS SORTABLE JS] CSRF token meta tag not found."
                    );
                    alert(
                        "エラー: CSRFトークンが見つかりません。ページをリロードしてください。"
                    );
                    return;
                }
                const csrfToken = csrfTokenEl.getAttribute("content");

                fetch(reorderUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ orderedIds: orderedIds }),
                })
                    .then((response) => {
                        if (!response.ok) {
                            return response.json().then((errData) => {
                                let errorMessage =
                                    "サーバーエラーが発生しました。";
                                if (errData && errData.message)
                                    errorMessage = errData.message;
                                else if (errData && errData.error)
                                    errorMessage = errData.error;
                                throw new Error(
                                    errorMessage +
                                        ` (Status: ${response.status})`
                                );
                            });
                        }
                        return response.json();
                    })
                    .then((data) => {
                        if (data.success) {
                            // console.log('[FORM DEFINITIONS SORTABLE JS] Order updated successfully.');
                            // 画面上の「順序」セルの値を更新
                            orderedIds.forEach((id, index) => {
                                const row =
                                    sortableDefinitionsTableBody.querySelector(
                                        `tr[data-id="${id}"]`
                                    );
                                if (row) {
                                    const orderCell =
                                        row.querySelector("td:nth-child(2)"); // 2番目のセルが「順序」
                                    if (orderCell) {
                                        orderCell.textContent = index; // 0ベースのインデックスで更新 (必要なら index + 1)
                                    }
                                }
                            });
                            // ここでToast通知などを出すとより親切です
                            // 例: alert('並び順を更新しました。');
                        } else {
                            let errorMessage =
                                data.error ||
                                data.message ||
                                "並び順の更新に失敗しました。";
                            console.error(
                                "[FORM DEFINITIONS SORTABLE JS] Failed to update order:",
                                errorMessage
                            );
                            alert("エラー: " + errorMessage);
                        }
                    })
                    .catch((error) => {
                        console.error(
                            "[FORM DEFINITIONS SORTABLE JS] Error during fetch for reorder:",
                            error
                        );
                        alert(
                            "エラー: " + error.message ||
                                "並び順の更新中に技術的な問題が発生しました。"
                        );
                    });
            },
        });
        // console.log('[FORM DEFINITIONS SORTABLE JS] Sortable component initialized successfully on #sortable-definitions.');
    } catch (e) {
        console.error(
            "[FORM DEFINITIONS SORTABLE JS] Error initializing Sortable:",
            e
        );
    }
}
