// resources/js/page-specific/projects-show.js
import axios from "axios";

// --- 定義: フラグとステータスの表示用マッピング ---
const deliveryFlagData = {
    0: { tooltip: "未納品", icon: "fa-truck", colorClass: "text-yellow-500" },
    1: {
        tooltip: "納品済み",
        icon: "fa-check-circle",
        colorClass: "text-green-500",
    },
};
const paymentFlagData = {
    Pending: {
        tooltip: "未払い",
        icon: "fa-clock",
        colorClass: "text-yellow-500",
    },
    Processing: {
        tooltip: "支払い中",
        icon: "fa-spinner fa-spin",
        colorClass: "text-blue-500",
    },
    Completed: {
        tooltip: "支払完了",
        icon: "fa-check-circle",
        colorClass: "text-green-500",
    },
    "Partially Paid": {
        tooltip: "一部支払い",
        icon: "fa-adjust",
        colorClass: "text-orange-500",
    },
    Overdue: {
        tooltip: "期限切れ",
        icon: "fa-exclamation-triangle",
        colorClass: "text-red-500",
    },
    Cancelled: {
        tooltip: "キャンセル",
        icon: "fa-ban",
        colorClass: "text-gray-500",
    },
    Refunded: {
        tooltip: "返金済み",
        icon: "fa-undo",
        colorClass: "text-purple-500",
    },
    "On Hold": {
        tooltip: "保留中",
        icon: "fa-pause-circle",
        colorClass: "text-indigo-500",
    },
    "": {
        tooltip: "未設定",
        icon: "fa-question-circle",
        colorClass: "text-gray-400",
    }, // 未選択/空の場合
};
const projectStatusData = {
    not_started: {
        tooltip: "未着手",
        icon: "fa-minus-circle",
        colorClass: "text-gray-500",
    },
    in_progress: {
        tooltip: "進行中",
        icon: "fa-play-circle",
        colorClass: "text-blue-500",
    },
    completed: {
        tooltip: "完了",
        icon: "fa-check-circle",
        colorClass: "text-green-500",
    },
    on_hold: {
        tooltip: "保留中",
        icon: "fa-pause-circle",
        colorClass: "text-yellow-500",
    },
    cancelled: {
        tooltip: "キャンセル",
        icon: "fa-times-circle",
        colorClass: "text-red-500",
    },
    "": {
        tooltip: "未設定",
        icon: "fa-question-circle",
        colorClass: "text-gray-400",
    }, // 未選択/空の場合
};
// --- 定義ここまで ---

/**
 * 指定されたアイコン要素のアイコンクラスとツールチップを更新する
 * @param {HTMLElement} iconElement - アイコンを表示するspan要素
 * @param {string} value - 新しいフラグ/ステータスの値
 * @param {object} map - 値と表示データ（tooltip, icon, colorClass）のマッピングオブジェクト
 */
function updateIconAndTooltip(iconElement, value, map) {
    if (iconElement) {
        const defaultValueKey = Object.keys(map).includes("")
            ? ""
            : Object.keys(map)[0]; // デフォルトのキー
        const mapping = map[value] ||
            map[defaultValueKey] || {
                tooltip: value || "-",
                icon: "fa-question-circle",
                colorClass: "text-gray-400",
            };
        iconElement.title = mapping.tooltip;
        const iTag = iconElement.querySelector("i");
        if (iTag) {
            iTag.className = `fas ${mapping.icon} ${mapping.colorClass}`;
        } else {
            // iタグがない場合は作成して追加
            iconElement.innerHTML = `<i class="fas ${mapping.icon} ${mapping.colorClass}"></i>`;
        }
    }
}

/**
 * プロジェクトのフラグまたはステータスをAJAXで更新し、関連する表示も更新する
 * @param {HTMLSelectElement} selectElement - 変更されたselect要素
 * @param {string} csrfToken - CSRFトークン
 */
function handleProjectFlagOrStatusUpdate(selectElement, csrfToken) {
    const projectId = selectElement.dataset.projectId;
    const updateUrl = selectElement.dataset.url;
    const newValue = selectElement.value;
    const fieldName = selectElement.name; // 'delivery_flag', 'payment_flag', 'status'
    const iconTargetId = selectElement.dataset.iconTarget;

    if (!projectId || !updateUrl || !fieldName) {
        console.error(
            "Update select element is missing required data attributes: projectId, url, or name."
        );
        return;
    }

    const payload = { [fieldName]: newValue };

    // エラー時に値を戻すため、変更前の値を保持（初回はselectedオプションから取得）
    if (selectElement.dataset.originalValueInitialized !== "true") {
        const initiallySelectedOption = Array.from(selectElement.options).find(
            (opt) => opt.selected
        );
        selectElement.dataset.originalValue = initiallySelectedOption
            ? initiallySelectedOption.value
            : "";
        selectElement.dataset.originalValueInitialized = "true";
    }
    const originalSelectValue = selectElement.dataset.originalValue;

    axios
        .patch(updateUrl, payload, {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
        })
        .then((response) => {
            if (response.data.success) {
                selectElement.dataset.originalValue = newValue; // 更新成功

                const currentIconElement =
                    document.getElementById(iconTargetId);
                let currentMap;

                // 更新されたフラグ/ステータスに応じてアイコンとツールチップを更新
                if (fieldName === "delivery_flag") {
                    currentMap = deliveryFlagData;
                    updateIconAndTooltip(
                        currentIconElement,
                        response.data.delivery_flag,
                        currentMap
                    );
                } else if (fieldName === "payment_flag") {
                    currentMap = paymentFlagData;
                    updateIconAndTooltip(
                        currentIconElement,
                        response.data.payment_flag,
                        currentMap
                    );
                } else if (fieldName === "status") {
                    // プロジェクトステータス自体を変更した場合
                    currentMap = projectStatusData;
                    updateIconAndTooltip(
                        currentIconElement,
                        response.data.new_status,
                        currentMap
                    );
                }

                // ProjectObserverによって変更された可能性のあるプロジェクト全体のステータスも更新
                if (response.data.new_status) {
                    const projectStatusIcon = document.getElementById(
                        `project_status_icon_${projectId}`
                    );
                    const projectStatusSelect = document.getElementById(
                        `project_status_select_${projectId}`
                    );

                    updateIconAndTooltip(
                        projectStatusIcon,
                        response.data.new_status,
                        projectStatusData
                    );
                    if (projectStatusSelect) {
                        projectStatusSelect.value = response.data.new_status;
                        projectStatusSelect.dataset.originalValue =
                            response.data.new_status;
                    }
                }
                // console.log(response.data.message || '更新しました。'); // 必要なら通知
            } else {
                alert(
                    "更新に失敗しました: " +
                        (response.data.message || "不明なエラー")
                );
                selectElement.value = originalSelectValue; // エラー時は元の値に戻す
            }
        })
        .catch((error) => {
            console.error(
                "Error updating project flag/status:",
                error.response || error
            );
            alert("更新中にエラーが発生しました。");
            selectElement.value = originalSelectValue; // エラー時は元の値に戻す
            if (
                error.response &&
                error.response.data &&
                error.response.data.errors
            ) {
                let errorMessages = [];
                for (const key in error.response.data.errors) {
                    errorMessages.push(
                        error.response.data.errors[key].join("\n")
                    );
                }
                alert("エラー詳細:\n" + errorMessages.join("\n"));
            }
        });
}

/**
 * 材料ステータス更新 (既存の User_Uploaded_File_40 より)
 * @param {HTMLInputElement} checkbox - チェックボックス要素
 * @param {string} projectId - プロジェクトID
 * @param {string} csrfToken - CSRFトークン
 */
function handleMaterialStatusUpdate(checkbox, projectId, csrfToken) {
    const url = checkbox.dataset.url;
    const newStatus = checkbox.checked ? "購入済" : "未購入";
    const characterId = checkbox.closest("[data-character-id]")?.dataset
        .characterId;

    if (!url || !characterId) {
        console.error("Missing data attributes for material status update.");
        return;
    }

    axios
        .patch(
            url,
            { status: newStatus },
            { headers: { "X-CSRF-TOKEN": csrfToken } }
        )
        .then((response) => {
            if (response.data.success) {
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
}

/**
 * コスト追加処理 (既存の User_Uploaded_File_40 より)
 * @param {HTMLFormElement} form - 送信されたフォーム
 * @param {string} projectId - プロジェクトID
 * @param {string} csrfToken - CSRFトークン
 */
function handleCostAdd(form, projectId, csrfToken) {
    const characterId = form.dataset.characterId;
    const formData = new FormData(form);

    if (!characterId) {
        console.error("Missing data attributes for cost form.");
        return;
    }

    axios
        .post(form.action, formData, { headers: { "X-CSRF-TOKEN": csrfToken } })
        .then((response) => {
            if (
                response.status === 200 ||
                response.status === 201 ||
                (response.data && response.data.success)
            ) {
                refreshCharacterCosts(projectId, characterId);
                form.reset(); // フォームをリセット
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
 * コスト削除処理 (既存の User_Uploaded_File_40 より)
 * @param {HTMLFormElement} form - 送信されたフォーム
 * @param {string} projectId - プロジェクトID
 * @param {string} csrfToken - CSRFトークン
 */
function handleCostDelete(form, projectId, csrfToken) {
    const characterId = form.closest("[data-character-id]")?.dataset
        .characterId;

    if (!characterId) {
        console.error("Missing data for cost deletion.");
        return;
    }

    if (confirm("このコストを削除しますか？")) {
        // FormData を使ってDELETEリクエストをエミュレート (実際にはPOSTとして送信される)
        const formData = new FormData(form);
        // formData.append('_method', 'DELETE'); // これをサーバ側で処理できるようにするか、axios.delete を使う

        axios
            .post(form.action, formData, {
                headers: { "X-CSRF-TOKEN": csrfToken },
            }) // method="POST" @method('DELETE') のため POST で送信
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
 * キャラクターのコスト表示エリアを非同期で更新 (既存の User_Uploaded_File_40 より)
 * @param {string} projectId
 * @param {string} characterId
 */
function refreshCharacterCosts(projectId, characterId) {
    const costsTabContainer = document.getElementById(
        `costs-content-${characterId}`
    );
    if (!costsTabContainer) {
        console.warn(
            `Could not find costs tab container for character: ${characterId}`
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
            // alert("コスト情報エリアの更新に失敗しました。"); // 頻繁に出ると邪魔なのでコメントアウトも検討
        });
}

/**
 * フォーム送信時のAPIエラーを処理 (既存の User_Uploaded_File_40 より)
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

/**
 * プロジェクト詳細ページのインタラクティブ機能を初期化
 */
function initializeProjectShowPage() {
    const mainContainer = document.getElementById(
        "project-show-main-container"
    );
    if (!mainContainer) return;

    const projectId = mainContainer.dataset.projectId;
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    if (!csrfTokenElement) {
        console.error("CSRF token meta tag not found.");
        return;
    }
    const csrfToken = csrfTokenElement.getAttribute("content");

    mainContainer.addEventListener("change", function (event) {
        const materialCheckbox = event.target.closest(
            ".material-status-checkbox"
        );
        if (materialCheckbox) {
            handleMaterialStatusUpdate(materialCheckbox, projectId, csrfToken);
            return;
        }

        const flagSelect = event.target.closest(
            ".project-flag-select, .project-status-select"
        );
        if (flagSelect) {
            if (!flagSelect.dataset.originalValueInitialized) {
                const initiallySelectedOption = Array.from(
                    flagSelect.options
                ).find((opt) => opt.selected);
                flagSelect.dataset.originalValue = initiallySelectedOption
                    ? initiallySelectedOption.value
                    : "";
                flagSelect.dataset.originalValueInitialized = "true";
            }
            handleProjectFlagOrStatusUpdate(flagSelect, csrfToken);
        }
    });

    mainContainer.addEventListener("submit", function (event) {
        const addForm = event.target.closest(".add-cost-form");
        const deleteForm = event.target.closest(".delete-cost-form");

        if (addForm) {
            event.preventDefault();
            handleCostAdd(addForm, projectId, csrfToken);
        } else if (deleteForm) {
            event.preventDefault();
            handleCostDelete(deleteForm, projectId, csrfToken);
        }
    });
}

// DOMContentLoaded 後に初期化関数を呼び出す
// (app.js で projects-show.js が動的に読み込まれる場合は、このファイルが読み込まれた時点で実行される)
if (document.getElementById("project-show-main-container")) {
    initializeProjectShowPage();
}
