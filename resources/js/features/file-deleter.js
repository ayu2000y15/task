// resources/js/features/file-deleter.js

import axios from "axios";

/**
 * ファイル削除ボタンのクリックイベントを処理する
 * イベントデリゲーションを使い、動的に追加された要素にも対応
 */
function initializeFileDeleteHandler() {
    document.body.addEventListener("click", function (e) {
        // クリックされた要素が削除ボタン（またはその中身）かを確認
        const deleteButton = e.target.closest(".folder-file-delete-btn");

        if (deleteButton) {
            e.preventDefault();
            const url = deleteButton.dataset.url;
            const fileId = deleteButton.dataset.fileId;

            if (!url || !fileId) {
                console.error(
                    "Delete button is missing data-url or data-file-id attribute."
                );
                return;
            }

            if (confirm("本当にこのファイルを削除しますか？")) {
                axios
                    .delete(url)
                    .then((response) => {
                        if (response.data.success) {
                            // 成功したら画面からファイル要素を削除
                            const fileItem = document.getElementById(
                                `folder-file-item-${fileId}`
                            );
                            if (fileItem) {
                                fileItem.remove();
                            }
                        } else {
                            alert(
                                "ファイルの削除に失敗しました: " +
                                    (response.data.message || "")
                            );
                        }
                    })
                    .catch((error) => {
                        console.error("Error deleting file:", error);
                        alert("ファイル削除中にエラーが発生しました。");
                    });
            }
        }
    });
}

// 実行
initializeFileDeleteHandler();
