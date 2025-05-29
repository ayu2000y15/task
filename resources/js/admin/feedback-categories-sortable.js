// resources/js/admin/feedback-categories-sortable.js
import Sortable from "sortablejs";
import axios from "axios";

function initFeedbackCategorySortable() {
    const sortableList = document.getElementById(
        "sortable-feedback-categories"
    );
    if (!sortableList) {
        return;
    }

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");
    // Blade側で data-reorder-url 属性を tbody に設定することを推奨
    const reorderUrl =
        sortableList.dataset.reorderUrl || "/admin/feedback-categories/reorder";

    new Sortable(sortableList, {
        animation: 150,
        handle: ".sortable-handle", // ドラッグハンドルのクラス
        ghostClass: "sortable-placeholder",
        onEnd: function (evt) {
            const newOrderIds = [];
            sortableList.querySelectorAll("tr").forEach((row) => {
                if (row.dataset.id) {
                    newOrderIds.push(row.dataset.id);
                }
            });

            axios
                .post(reorderUrl, {
                    ids: newOrderIds,
                    _token: csrfToken, // CSRFトークンはaxiosのグローバル設定で送信される場合、ここは不要なことも
                })
                .then((response) => {
                    if (response.data.success) {
                        // console.log(response.data.message || 'Order updated successfully.');
                        // 必要であれば成功通知 (例: 小さなトーストメッセージ)
                    } else {
                        console.error(
                            "Failed to reorder categories:",
                            response.data.message
                        );
                        alert(
                            "順序の更新に失敗しました: " +
                                (response.data.message || "不明なエラー")
                        );
                    }
                })
                .catch((error) => {
                    console.error(
                        "Error reordering categories:",
                        error.response || error
                    );
                    alert("順序の更新中にエラーが発生しました。");
                });
        },
    });
}

// このファイルが読み込まれた時点で初期化処理を実行
if (document.getElementById("sortable-feedback-categories")) {
    initFeedbackCategorySortable();
}

export default {
    initFeedbackCategorySortable, // 必要であればエクスポート
};
