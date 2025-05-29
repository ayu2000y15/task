// resources/js/page-specific/admin-feedbacks-index.js
import axios from "axios";

// STATUS_OPTIONS と getStatusColorClass は Blade 側で既に解決されているか、
// もしJS側でも必要ならサーバーから渡すか、ここで定義します。
// 今回はステータスバッジ更新のためにJS側にも定義します。
const FEEDBACK_STATUS_OPTIONS = {
    unread: "未読",
    not_started: "未着手",
    in_progress: "対応中",
    completed: "対応済み",
    cancelled: "キャンセル",
    on_hold: "保留",
};

function getFeedbackStatusColorClass(status) {
    // この関数は app/Models/Feedback.php の getStatusColorClass と同じロジックを想定
    switch (status) {
        case "unread":
            return "bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200";
        case "not_started":
            return "bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100";
        case "in_progress":
            return "bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100";
        case "completed":
            return "bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100";
        case "cancelled":
            return "bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100";
        case "on_hold":
            return "bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-100";
        default:
            return "bg-gray-100 text-gray-700 dark:bg-gray-500 dark:text-gray-300";
    }
}

function initializeAdminFeedbacksPage() {
    const feedbackTable = document.getElementById("feedback-table");
    if (!feedbackTable) {
        return;
    }

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    // ステータス変更処理
    feedbackTable.addEventListener("change", function (event) {
        if (event.target.classList.contains("feedback-status-select")) {
            const selectElement = event.target;
            const feedbackId =
                selectElement.closest(".status-cell")?.dataset.feedbackId ||
                selectElement.closest("tr.feedback-main-row")?.dataset
                    .feedbackId;
            const url = selectElement.dataset.url;
            const newStatus = selectElement.value;
            const originalStatus = selectElement.dataset.originalStatus;

            if (!url || !feedbackId) {
                console.error(
                    "Status Update: Missing data attributes (feedbackId or url). FID:",
                    feedbackId,
                    "URL:",
                    url
                );
                return;
            }

            axios
                .patch(
                    url,
                    { status: newStatus },
                    {
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            Accept: "application/json",
                        },
                    }
                )
                .then((response) => {
                    if (response.data.success && response.data.feedback) {
                        const updatedFeedback = response.data.feedback;
                        selectElement.dataset.originalStatus =
                            updatedFeedback.status; // 成功時に originalStatus を更新

                        const statusCell =
                            selectElement.closest(".status-cell");
                        const badge = statusCell.querySelector(".status-badge");
                        if (badge) {
                            badge.textContent =
                                updatedFeedback.status_label_display ||
                                FEEDBACK_STATUS_OPTIONS[
                                    updatedFeedback.status
                                ] ||
                                updatedFeedback.status;
                            badge.className = `status-badge px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                updatedFeedback.status_badge_class_display ||
                                getFeedbackStatusColorClass(
                                    updatedFeedback.status
                                )
                            }`;
                        }

                        const mainRow = document.getElementById(
                            `feedback-row-${feedbackId}`
                        );
                        const detailsRow = mainRow
                            ? mainRow.nextElementSibling
                            : null;

                        if (
                            detailsRow &&
                            detailsRow.classList.contains("details-row") &&
                            detailsRow.style.display !== "none"
                        ) {
                            const completedAtSpan = detailsRow.querySelector(
                                ".feedback-completed-at-display"
                            );
                            if (completedAtSpan) {
                                completedAtSpan.textContent =
                                    updatedFeedback.completed_at_display || "-";
                            }
                            const updatedAtSpan = detailsRow.querySelector(
                                ".feedback-updated-at-cell"
                            );
                            if (updatedAtSpan) {
                                updatedAtSpan.textContent =
                                    updatedFeedback.updated_at_display || "-";
                            }
                        }
                        window.dispatchEvent(
                            new CustomEvent("feedback-status-updated")
                        );
                    } else {
                        alert(
                            "ステータスの更新に失敗しました: " +
                                (response.data.message || "不明なエラー")
                        );
                        selectElement.value = originalStatus;
                    }
                })
                .catch((error) => {
                    console.error(
                        "Error updating status:",
                        error.response || error
                    );
                    alert("ステータス更新中にエラーが発生しました。");
                    selectElement.value = originalStatus;
                });
        }
    });

    // 汎用インライン編集セットアップ関数
    function setupInlineEdit(
        cellClass,
        displayClass,
        editingClass,
        inputSelector,
        saveBtnClass,
        cancelBtnClass,
        dataAttrUrlKey,
        dataAttrFullKey,
        patchKey
    ) {
        feedbackTable.addEventListener("click", function (event) {
            const displayTarget = event.target.closest(`.${displayClass}`);
            if (displayTarget) {
                const cell = displayTarget.closest(`.${cellClass}`);
                if (
                    cell &&
                    cell.contains(displayTarget) &&
                    !cell.classList.contains("editing")
                ) {
                    const editingDiv = cell.querySelector(`.${editingClass}`);
                    const inputField = editingDiv.querySelector(inputSelector);
                    const fullValue =
                        displayTarget.dataset[dataAttrFullKey] || "";

                    cell.classList.add("editing");
                    displayTarget.style.display = "none";
                    editingDiv.style.display = "block";
                    inputField.value = fullValue;
                    inputField.focus();
                    inputField.dataset.originalValue = fullValue;
                }
            }

            const saveBtn = event.target.closest(`.${saveBtnClass}`);
            if (saveBtn) {
                event.preventDefault();
                const editingDiv = saveBtn.closest(`.${editingClass}`);
                const cell = editingDiv.closest(`.${cellClass}`);
                const localDisplayDiv = cell.querySelector(`.${displayClass}`);
                const inputField = editingDiv.querySelector(inputSelector);
                const feedbackId = cell.dataset.feedbackId;
                const url = cell.dataset[dataAttrUrlKey];
                const newValue = inputField.value;

                if (!feedbackId || !url) {
                    console.error(
                        `Missing data attributes for ${patchKey} update. FID: ${feedbackId}, URL: ${url}`
                    );
                    editingDiv.style.display = "none";
                    localDisplayDiv.style.display = "block";
                    cell.classList.remove("editing");
                    return;
                }

                axios
                    .patch(
                        url,
                        { [patchKey]: newValue },
                        {
                            headers: {
                                "X-CSRF-TOKEN": csrfToken,
                                Accept: "application/json",
                            },
                        }
                    )
                    .then((response) => {
                        if (response.data.success && response.data.feedback) {
                            const updatedFeedback = response.data.feedback;
                            const currentVal = updatedFeedback[patchKey] || "";
                            const valueToShow =
                                patchKey === "admin_memo" &&
                                currentVal.length > 50
                                    ? currentVal.substring(0, 50) + "..."
                                    : currentVal || "-";

                            localDisplayDiv.textContent = valueToShow;
                            localDisplayDiv.dataset[dataAttrFullKey] =
                                currentVal;
                            localDisplayDiv.title =
                                currentVal || "クリックして編集";

                            const mainRow = document.getElementById(
                                `feedback-row-${feedbackId}`
                            );
                            const detailsRow = mainRow
                                ? mainRow.nextElementSibling
                                : null;
                            if (
                                detailsRow &&
                                detailsRow.classList.contains("details-row") &&
                                detailsRow.style.display !== "none"
                            ) {
                                const updatedAtSpan = detailsRow.querySelector(
                                    ".feedback-updated-at-cell"
                                );
                                if (
                                    updatedAtSpan &&
                                    updatedFeedback.updated_at_display
                                ) {
                                    updatedAtSpan.textContent =
                                        updatedFeedback.updated_at_display;
                                }
                            }
                            window.dispatchEvent(
                                new CustomEvent("feedback-status-updated")
                            );
                        } else {
                            alert(
                                `${
                                    patchKey === "admin_memo"
                                        ? "メモ"
                                        : "担当者"
                                }の更新に失敗しました: ` +
                                    (response.data.message || "不明なエラー")
                            );
                        }
                    })
                    .catch((error) => {
                        console.error(
                            `Error updating ${patchKey}:`,
                            error.response || error
                        );
                        alert(
                            `${
                                patchKey === "admin_memo" ? "メモ" : "担当者"
                            }更新中にエラーが発生しました。`
                        );
                    })
                    .finally(() => {
                        editingDiv.style.display = "none";
                        localDisplayDiv.style.display = "block";
                        cell.classList.remove("editing");
                    });
            }

            const cancelBtn = event.target.closest(`.${cancelBtnClass}`);
            if (cancelBtn) {
                event.preventDefault();
                const editingDiv = cancelBtn.closest(`.${editingClass}`);
                const cell = editingDiv.closest(`.${cellClass}`);
                const localDisplayDiv = cell.querySelector(`.${displayClass}`);
                editingDiv.style.display = "none";
                localDisplayDiv.style.display = "block";
                cell.classList.remove("editing");
            }
        });

        feedbackTable.addEventListener("focusout", function (event) {
            const inputField = event.target;
            // inputSelector を使って、正しい入力フィールドかどうかを確認
            if (
                inputField.matches(inputSelector) &&
                inputField.closest(`.${editingClass}`)
            ) {
                const cell = inputField.closest(`.${cellClass}`); // focusoutはcellを特定する必要がある
                setTimeout(() => {
                    const editingDiv = inputField.closest(`.${editingClass}`);
                    // ボタンへのフォーカス移動でないことを確認
                    if (
                        editingDiv &&
                        editingDiv.style.display === "block" &&
                        !editingDiv.contains(document.activeElement)
                    ) {
                        const localDisplayDiv = cell.querySelector(
                            `.${displayClass}`
                        );
                        const feedbackId = cell.dataset.feedbackId;
                        const url = cell.dataset[dataAttrUrlKey];
                        const newValue = inputField.value;
                        const originalValue = inputField.dataset.originalValue;

                        if (!feedbackId || !url) {
                            console.error(
                                `Missing data attributes for ${patchKey} auto-save.`
                            );
                            editingDiv.style.display = "none";
                            localDisplayDiv.style.display = "block";
                            cell.classList.remove("editing");
                            return;
                        }

                        if (newValue !== originalValue) {
                            axios
                                .patch(
                                    url,
                                    { [patchKey]: newValue },
                                    {
                                        headers: {
                                            "X-CSRF-TOKEN": csrfToken,
                                            Accept: "application/json",
                                        },
                                    }
                                )
                                .then((response) => {
                                    if (
                                        response.data.success &&
                                        response.data.feedback
                                    ) {
                                        const updatedFeedback =
                                            response.data.feedback;
                                        const currentVal =
                                            updatedFeedback[patchKey] || "";
                                        const valueToShow =
                                            patchKey === "admin_memo" &&
                                            currentVal.length > 50
                                                ? currentVal.substring(0, 50) +
                                                  "..."
                                                : currentVal || "-";

                                        localDisplayDiv.textContent =
                                            valueToShow;
                                        localDisplayDiv.dataset[
                                            dataAttrFullKey
                                        ] = currentVal;
                                        localDisplayDiv.title =
                                            currentVal || "クリックして編集";

                                        const mainRow = document.getElementById(
                                            `feedback-row-${feedbackId}`
                                        );
                                        const detailsRow = mainRow
                                            ? mainRow.nextElementSibling
                                            : null;
                                        if (
                                            detailsRow &&
                                            detailsRow.classList.contains(
                                                "details-row"
                                            ) &&
                                            detailsRow.style.display !== "none"
                                        ) {
                                            const updatedAtSpan =
                                                detailsRow.querySelector(
                                                    ".feedback-updated-at-cell"
                                                );
                                            if (
                                                updatedAtSpan &&
                                                updatedFeedback.updated_at_display
                                            ) {
                                                updatedAtSpan.textContent =
                                                    updatedFeedback.updated_at_display;
                                            }
                                        }
                                        window.dispatchEvent(
                                            new CustomEvent(
                                                "feedback-status-updated"
                                            )
                                        );
                                    } else {
                                        // 更新失敗時も表示を元に戻す
                                        localDisplayDiv.textContent =
                                            originalValue
                                                ? patchKey === "admin_memo" &&
                                                  originalValue.length > 50
                                                    ? originalValue.substring(
                                                          0,
                                                          50
                                                      ) + "..."
                                                    : originalValue
                                                : "-";
                                        localDisplayDiv.dataset[
                                            dataAttrFullKey
                                        ] = originalValue || "";
                                        localDisplayDiv.title =
                                            originalValue || "クリックして編集";
                                        alert(
                                            `${
                                                patchKey === "admin_memo"
                                                    ? "メモ"
                                                    : "担当者"
                                            }の自動保存に失敗しました: ` +
                                                (response.data.message ||
                                                    "不明なエラー")
                                        );
                                    }
                                })
                                .catch((error) => {
                                    // AJAXエラー時も表示を元に戻す
                                    console.error(
                                        `Error auto-saving ${patchKey}:`,
                                        error.response || error
                                    );
                                    localDisplayDiv.textContent = originalValue
                                        ? patchKey === "admin_memo" &&
                                          originalValue.length > 50
                                            ? originalValue.substring(0, 50) +
                                              "..."
                                            : originalValue
                                        : "-";
                                    localDisplayDiv.dataset[dataAttrFullKey] =
                                        originalValue || "";
                                    localDisplayDiv.title =
                                        originalValue || "クリックして編集";
                                    alert(
                                        `${
                                            patchKey === "admin_memo"
                                                ? "メモ"
                                                : "担当者"
                                        }の自動保存中にエラーが発生しました。`
                                    );
                                });
                        }
                        editingDiv.style.display = "none";
                        localDisplayDiv.style.display = "block";
                        cell.classList.remove("editing");
                    }
                }, 150); // 150msの遅延はボタンクリックを優先させるため
            }
        });
    }

    // メモ編集の初期化
    // HTML側 data属性: data-memo-url, data-full-memo
    setupInlineEdit(
        "memo-cell",
        "memo-display",
        "memo-editing",
        "textarea",
        "memo-save-btn",
        "memo-cancel-btn",
        "memoUrl",
        "fullMemo",
        "admin_memo"
    );
    // 担当者編集の初期化
    // HTML側 data属性: data-assignee-url, data-full-assignee
    setupInlineEdit(
        "assignee-cell",
        "assignee-display",
        "assignee-editing",
        'input[type="text"]',
        "assignee-save-btn",
        "assignee-cancel-btn",
        "assigneeUrl",
        "fullAssignee",
        "assignee_text"
    );
}

// DOMが読み込まれた後、かつfeedback-tableが存在する場合に初期化
if (document.getElementById("feedback-table")) {
    initializeAdminFeedbacksPage();
}

export default {
    initializeAdminFeedbacksPage,
};
