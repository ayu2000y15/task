// resources/js/page-specific/tasks-index.js
import axios from "axios";

function getStatusIconHtml(status) {
    // (既存の関数 - 変更なし)
    switch (status) {
        case "completed":
            return '<i class="fas fa-check-circle text-green-500" title="完了"></i>';
        case "in_progress":
            return '<i class="fas fa-play-circle text-blue-500" title="進行中"></i>';
        case "on_hold":
            return '<i class="fas fa-pause-circle text-yellow-500" title="保留中"></i>';
        case "cancelled":
            return '<i class="fas fa-times-circle text-red-500" title="キャンセル"></i>';
        case "not_started":
        default:
            return '<i class="far fa-circle text-gray-400" title="未着手"></i>';
    }
}

function updateTaskRowUI(row, newStatus, newProgress) {
    if (!row) {
        return;
    }
    // アイコン更新
    const iconWrapper = row.querySelector(".task-status-icon-wrapper");
    if (iconWrapper) {
        const taskNameCell = row.querySelector("td:nth-child(2)"); // 工程名セル
        // マイルストーンやフォルダは専用アイコンなので、通常のステータスアイコンは更新しない
        const isMilestone = iconWrapper.querySelector("i.fa-flag");
        const isFolder = iconWrapper.querySelector("i.fa-folder");
        if (!isMilestone && !isFolder) {
            iconWrapper.innerHTML = getStatusIconHtml(newStatus);
        }
    }

    // data-progress属性の更新
    row.dataset.progress = newProgress;

    // ステータスセレクトボックスの値と関連属性を更新
    const selectElement = row.querySelector(".task-status-select");
    if (selectElement) {
        selectElement.value = newStatus;
        selectElement.dataset.originalStatus = newStatus;
        selectElement.dataset.currentProgress = newProgress;
    }
}

function initializeTaskCheckboxes() {
    const checkboxes = document.querySelectorAll(".task-status-checkbox");

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", function () {
            const row = this.closest("tr");
            if (!row) return;

            const taskId = row.dataset.taskId;
            const projectId = row.dataset.projectId;
            const action = this.dataset.action;
            const isChecked = this.checked;

            const inProgressCheckbox = row.querySelector(
                ".task-status-in-progress"
            );
            const completedCheckbox = row.querySelector(
                ".task-status-completed"
            );

            let newStatus = "not_started";
            let newProgress = 0;
            let currentProgressOnRow = parseInt(row.dataset.progress || "0");
            if (isNaN(currentProgressOnRow)) currentProgressOnRow = 0;

            // 1. UI上のチェックボックスの状態をまず確定させる
            if (isChecked) {
                if (action === "set-in-progress") {
                    if (completedCheckbox) completedCheckbox.checked = false;
                } else if (action === "set-completed") {
                    if (inProgressCheckbox) inProgressCheckbox.checked = false;
                }
            }

            // 2. newStatus と newProgress を決定
            if (inProgressCheckbox && inProgressCheckbox.checked) {
                newStatus = "in_progress";
                if (currentProgressOnRow > 0 && currentProgressOnRow < 100) {
                    newProgress = currentProgressOnRow;
                } else if (currentProgressOnRow === 100) {
                    // 完了から進行中に戻した場合
                    newProgress = 90;
                } else {
                    // 未着手から進行中、または不明な状態から
                    newProgress = 10;
                }
            } else if (completedCheckbox && completedCheckbox.checked) {
                newStatus = "completed";
                newProgress = 100;
            } else {
                // 両方チェックされていない場合
                newStatus = "not_started";
                newProgress = 0;
            }

            // 元のチェック状態を保存（エラー時用）
            const originalInProgressChecked = inProgressCheckbox
                ? inProgressCheckbox.checked
                : false;
            const originalCompletedChecked = completedCheckbox
                ? completedCheckbox.checked
                : false;
            // 送信直前に、現在のチェック状態を UI に反映
            if (inProgressCheckbox)
                inProgressCheckbox.checked = newStatus === "in_progress";
            if (completedCheckbox)
                completedCheckbox.checked = newStatus === "completed";

            axios
                .post(`/projects/${projectId}/tasks/${taskId}/progress`, {
                    status: newStatus,
                    progress: newProgress,
                })
                .then((response) => {
                    if (response.data.success) {
                        updateTaskRowUI(row, newStatus, newProgress);
                    } else {
                        alert(
                            "更新に失敗: " +
                                (response.data.message || "不明なエラー")
                        );
                        // UIを元に戻す
                        if (inProgressCheckbox)
                            inProgressCheckbox.checked =
                                originalInProgressChecked;
                        if (completedCheckbox)
                            completedCheckbox.checked =
                                originalCompletedChecked;
                        // row UIも元のステータスに戻す必要がある場合があるが、一旦チェックボックスのみ
                    }
                })
                .catch((error) => {
                    console.error("Error updating task via checkbox:", error);
                    alert("更新中にエラーが発生しました。");
                    // UIを元に戻す
                    if (inProgressCheckbox)
                        inProgressCheckbox.checked = originalInProgressChecked;
                    if (completedCheckbox)
                        completedCheckbox.checked = originalCompletedChecked;
                });
        });
    });
}

function initializeTaskStatusUpdater() {
    // (既存の関数 - 変更なし)
    const selects = document.querySelectorAll(".task-status-select");
    selects.forEach((select) => {
        const row = select.closest("tr, li");
        if (!row) {
            return;
        }
        select.dataset.originalStatus = select.value;
        select.dataset.currentProgress = row.dataset.progress || "0";

        select.addEventListener("change", function () {
            const taskId = this.dataset.taskId;
            const projectId = this.dataset.projectId;
            const newStatusFromSelect = this.value;
            let newProgress = 0;
            let currentProgress = parseInt(this.dataset.currentProgress);
            if (isNaN(currentProgress)) currentProgress = 0;

            if (newStatusFromSelect === "completed") {
                newProgress = 100;
            } else if (
                newStatusFromSelect === "not_started" ||
                newStatusFromSelect === "cancelled"
            ) {
                newProgress = 0;
            } else if (newStatusFromSelect === "in_progress") {
                if (currentProgress === 0) newProgress = 10;
                else if (currentProgress === 100) newProgress = 90;
                else newProgress = currentProgress > 0 ? currentProgress : 10;
            } else {
                // on_hold など
                newProgress = currentProgress; // 基本的には現在の進捗を維持
            }

            axios
                .post(`/projects/${projectId}/tasks/${taskId}/progress`, {
                    status: newStatusFromSelect,
                    progress: newProgress,
                })
                .then((response) => {
                    if (response.data.success) {
                        const currentRow = this.closest("tr, li");
                        if (currentRow) {
                            updateTaskRowUI(
                                currentRow,
                                newStatusFromSelect,
                                newProgress
                            );
                            // チェックボックスの状態も同期
                            const inProgressCheckbox = currentRow.querySelector(
                                ".task-status-in-progress"
                            );
                            const completedCheckbox = currentRow.querySelector(
                                ".task-status-completed"
                            );
                            if (inProgressCheckbox) {
                                inProgressCheckbox.checked =
                                    newStatusFromSelect === "in_progress";
                            }
                            if (completedCheckbox) {
                                completedCheckbox.checked =
                                    newStatusFromSelect === "completed";
                            }
                        }
                        // ホーム画面（ToDoリストの見出しがあるページ）かどうかを判定
                        const isHomePage =
                            !!document.querySelector("h2.text-xl");
                        if (
                            isHomePage &&
                            (newStatusFromSelect === "completed" ||
                                newStatusFromSelect === "cancelled")
                        ) {
                            location.reload(); // 完了やキャンセル時はホームのカンバンから消える可能性があるのでリロード
                        }
                    } else {
                        alert(
                            "ステータスの更新に失敗しました: " +
                                (response.data.message || "")
                        );
                        this.value =
                            this.dataset.originalStatus || "not_started";
                    }
                })
                .catch((error) => {
                    console.error("Error updating task status:", error);
                    alert("ステータス更新中にエラーが発生しました。");
                    this.value = this.dataset.originalStatus || "not_started";
                });
        });
    });
}

function initializeEditableAssignee() {
    // (既存の関数 - 変更なし)
    const cells = document.querySelectorAll(
        '.editable-cell[data-field="assignee"]'
    );
    cells.forEach((cell) => {
        const row = cell.closest("tr");
        if (!row) {
            return;
        }
        const originalText = cell.textContent.trim();
        cell.dataset.originalValue = originalText === "-" ? "" : originalText;

        cell.addEventListener("click", function handleAssigneeCellClick() {
            if (this.querySelector("input.assignee-edit-input")) return;

            const currentAssignee = this.dataset.originalValue || "";
            const taskId = this.dataset.taskId;
            const projectId = this.dataset.projectId;

            this.innerHTML = `<input type="text" value="${currentAssignee}" class="assignee-edit-input form-input w-full text-sm p-1 border border-blue-500 rounded dark:bg-gray-700 dark:text-gray-200" placeholder="担当者名">`;
            const input = this.querySelector("input.assignee-edit-input");
            if (!input) return;
            input.focus();
            input.select();

            const saveAssignee = () => {
                const currentInput = cell.querySelector(
                    "input.assignee-edit-input"
                );
                if (!currentInput) return;

                const newAssignee = currentInput.value.trim();
                cell.innerHTML = newAssignee || "-";
                cell.dataset.originalValue = newAssignee;

                if (newAssignee !== currentAssignee) {
                    axios
                        .post(
                            `/projects/${projectId}/tasks/${taskId}/assignee`,
                            {
                                assignee: newAssignee,
                            }
                        )
                        .then((response) => {
                            if (!response.data.success) {
                                cell.innerHTML = currentAssignee || "-";
                                cell.dataset.originalValue = currentAssignee;
                                alert(
                                    "担当者の更新に失敗しました: " +
                                        (response.data.message || "")
                                );
                            }
                        })
                        .catch((error) => {
                            console.error("Error updating assignee:", error);
                            cell.innerHTML = currentAssignee || "-";
                            cell.dataset.originalValue = currentAssignee;
                            alert("担当者更新中にエラーが発生しました。");
                        });
                }
                currentInput.removeEventListener("blur", saveAssignee);
            };

            const handleAssigneeKeydownOnce = (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    saveAssignee();
                } else if (e.key === "Escape") {
                    const currentInput = cell.querySelector(
                        "input.assignee-edit-input"
                    );
                    if (currentInput) currentInput.value = currentAssignee;
                    saveAssignee();
                }
            };

            input.addEventListener("blur", saveAssignee, { once: true });
            input.addEventListener("keydown", handleAssigneeKeydownOnce);
        });
    });
}

function initializeFolderFileToggle() {
    // (既存の関数 - 変更なし)
    const buttons = document.querySelectorAll(".toggle-folder-files");
    buttons.forEach((button) => {
        const targetId = button.dataset.target;
        if (!targetId) {
            return;
        }
        button.addEventListener("click", function () {
            const targetElement = document.getElementById(targetId);
            const icon = this.querySelector("i");
            if (!targetElement) {
                return;
            }
            targetElement.classList.toggle("hidden");
            if (targetElement.classList.contains("hidden")) {
                icon.classList.remove("fa-chevron-up");
                icon.classList.add("fa-chevron-down");
            } else {
                icon.classList.remove("fa-chevron-down");
                icon.classList.add("fa-chevron-up");
            }
        });
    });
}

// Initialize features specific to tasks index page
try {
    initializeTaskStatusUpdater(); // 既存のセレクトボックス用
    initializeTaskCheckboxes(); // ★ 新しいチェックボックス用を追加
    initializeEditableAssignee();
    initializeFolderFileToggle();
} catch (e) {
    console.error("Error during tasks-index.js specific initialization:", e);
}

export default {
    // ...
};
