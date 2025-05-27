// resources/js/page-specific/projects-show.js
import axios from "axios";

/**
 * プロジェクト詳細ページのインタラクティブ機能を初期化
 */
function initializeProjectShowPage() {
    const mainContainer = document.getElementById(
        "project-show-main-container"
    );
    if (!mainContainer) return;

    const projectId = mainContainer.dataset.projectId;

    // --- イベントリスナー (イベントデリゲーション) ---

    // 材料ステータス更新
    mainContainer.addEventListener("change", function (event) {
        const checkbox = event.target.closest(".material-status-checkbox");
        if (!checkbox) return;

        const url = checkbox.dataset.url;
        const newStatus = checkbox.checked ? "購入済" : "未購入";
        const characterId = checkbox.closest("[data-character-id]")?.dataset
            .characterId;

        if (!url || !characterId || !projectId) {
            console.error(
                "Missing data attributes for material status update."
            );
            return;
        }

        axios
            .patch(url, { status: newStatus })
            .then((response) => {
                if (response.data.success) {
                    // 材料更新がコストに影響するため、コストタブも更新
                    refreshCharacterCosts(projectId, characterId);
                } else {
                    checkbox.checked = !checkbox.checked;
                    alert(
                        "材料ステータスの更新に失敗しました: " +
                            (response.data.message || "")
                    );
                }
            })
            .catch((error) => {
                console.error("Error updating material status:", error);
                checkbox.checked = !checkbox.checked;
                alert("材料ステータス更新中にエラーが発生しました。");
            });
    });

    // コスト追加・削除フォームの送信
    mainContainer.addEventListener("submit", function (event) {
        const addForm = event.target.closest(".add-cost-form");
        const deleteForm = event.target.closest(".delete-cost-form");

        if (addForm) {
            event.preventDefault();
            handleCostAdd(addForm, projectId);
        } else if (deleteForm) {
            event.preventDefault();
            handleCostDelete(deleteForm, projectId);
        }
    });
}

/**
 * コスト追加処理
 * @param {HTMLFormElement} form - 送信されたフォーム
 * @param {string} projectId - プロジェクトID
 */
function handleCostAdd(form, projectId) {
    const characterId = form.dataset.characterId;
    const formData = new FormData(form);

    if (!characterId) {
        console.error("Missing data attributes for cost form.");
        return;
    }

    axios
        .post(form.action, formData)
        .then((response) => {
            if (
                response.status === 200 ||
                response.status === 201 ||
                (response.data && response.data.success)
            ) {
                refreshCharacterCosts(projectId, characterId);
            } else {
                handleFormError(response);
            }
        })
        .catch((error) => {
            console.error("Error adding cost:", error.response || error);
            handleFormError(
                error.response,
                "コスト追加中にエラーが発生しました。"
            );
        });
}

/**
 * コスト削除処理
 * @param {HTMLFormElement} form - 送信されたフォーム
 * @param {string} projectId - プロジェクトID
 */
function handleCostDelete(form, projectId) {
    const characterId = form.closest("[data-character-id]")?.dataset
        .characterId;

    if (!characterId) {
        console.error("Missing data for cost deletion.");
        return;
    }

    if (confirm("このコストを削除しますか？")) {
        axios
            .post(form.action, new FormData(form))
            .then((response) => {
                if (
                    response.status === 200 ||
                    (response.data && response.data.success)
                ) {
                    refreshCharacterCosts(projectId, characterId);
                } else {
                    alert(
                        "コストの削除に失敗しました: " +
                            (response.data.message || "")
                    );
                }
            })
            .catch((error) => {
                console.error("Error deleting cost:", error);
                alert("コスト削除中にエラーが発生しました。");
            });
    }
}

/**
 * キャラクターのコスト表示エリアを非同期で更新
 * @param {string} projectId
 * @param {string} characterId
 */
function refreshCharacterCosts(projectId, characterId) {
    const costsTabContainer = document.getElementById(
        `costs-content-${characterId}`
    );
    if (!costsTabContainer) {
        console.warn(
            `Could not find the costs tab container for character: ${characterId}`
        );
        return;
    }

    const costsPartialUrl = `/projects/${projectId}/characters/${characterId}/costs-partial`;

    axios
        .get(costsPartialUrl)
        .then((response) => {
            costsTabContainer.innerHTML = response.data;
        })
        .catch((error) => {
            console.error("Error refreshing costs list:", error);
            alert(
                "コスト情報エリアの更新に失敗しました。サーバー側の処理が実装されているか確認してください。(404 Not Found)"
            );
        });
}

/**
 * フォーム送信時のAPIエラーを処理してアラート表示
 * @param {object} response - Axiosのエラーレスポンス
 * @param {string} defaultMessage - デフォルトのエラーメッセージ
 */
function handleFormError(response, defaultMessage = "処理に失敗しました。") {
    let errorMessage = defaultMessage;
    if (response && response.data) {
        if (response.data.message) {
            errorMessage = response.data.message;
        } else if (response.data.errors) {
            errorMessage = Object.values(response.data.errors)
                .flat()
                .join("\n");
        }
    }
    alert(errorMessage);
}

// このスクリプトは `app.js` の `DOMContentLoaded` 後に動的に読み込まれるため、
// ここではイベントを待たずに初期化関数を即時実行する。
initializeProjectShowPage();
