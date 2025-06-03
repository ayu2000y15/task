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
        // マイルストーンやフォルダかどうかの判定をより堅牢に
        let isSpecialTask = false;
        // tr の場合、td経由でアイコンをチェック
        if (row.tagName.toLowerCase() === "tr") {
            const taskNameCell = row.querySelector("td:nth-child(2)"); // 工程名セルを想定
            if (taskNameCell) {
                isSpecialTask = !!(
                    taskNameCell.querySelector("i.fa-flag") ||
                    taskNameCell.querySelector("i.fa-folder")
                );
            }
        } else if (row.tagName.toLowerCase() === "li") {
            // li の場合、iconWrapper 自体が特殊アイコンを持っているか、または別の方法で判定
            isSpecialTask = !!(
                iconWrapper.querySelector("i.fa-flag") ||
                iconWrapper.querySelector("i.fa-folder")
            );
        }

        if (!isSpecialTask) {
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
            // ★ 修正点: 'tr' に加えて 'li' も検索対象にする
            const row = this.closest("tr, li");
            if (!row) {
                console.warn(
                    "Checkbox event couldn't find parent <tr> or <li>",
                    this
                );
                return;
            }

            const taskId = row.dataset.taskId;
            const projectId = row.dataset.projectId;
            const action = this.dataset.action;
            const isChecked = this.checked;

            // taskId や projectId がない場合は処理を中断
            if (!taskId || !projectId) {
                console.warn("Missing taskId or projectId on row:", row);
                // チェックボックスの状態を元に戻す (任意)
                this.checked = !isChecked;
                return;
            }

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

            if (isChecked) {
                if (action === "set-in-progress") {
                    if (completedCheckbox) completedCheckbox.checked = false;
                } else if (action === "set-completed") {
                    if (inProgressCheckbox) inProgressCheckbox.checked = false;
                }
            }

            if (inProgressCheckbox && inProgressCheckbox.checked) {
                newStatus = "in_progress";
                if (currentProgressOnRow > 0 && currentProgressOnRow < 100) {
                    newProgress = currentProgressOnRow;
                } else if (currentProgressOnRow === 100) {
                    newProgress = 90;
                } else {
                    newProgress = 10;
                }
            } else if (completedCheckbox && completedCheckbox.checked) {
                newStatus = "completed";
                newProgress = 100;
            } else {
                newStatus = "not_started";
                newProgress = 0;
            }

            const originalInProgressChecked = inProgressCheckbox
                ? inProgressCheckbox.checked
                : false;
            const originalCompletedChecked = completedCheckbox
                ? completedCheckbox.checked
                : false;

            // サーバー送信前のUI状態確定
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
                        // ホーム画面（ToDoリストの見出しがあるページ）かどうかを判定
                        const isHomePage =
                            !!document.querySelector("h1.text-2xl") &&
                            (!!document.querySelector("h5.text-lg") ||
                                !!document.querySelector("h2.text-xl")); // より汎用的なホーム判定
                        if (
                            isHomePage &&
                            (newStatus === "completed" ||
                                newStatus === "cancelled" ||
                                newStatus === "on_hold" ||
                                newStatus === "not_started")
                        ) {
                            // ToDoリストや期限間近のリストから消えたり移動したりする可能性のあるステータス変更時はリロード
                            location.reload();
                        }
                    } else {
                        alert(
                            "更新に失敗: " +
                                (response.data.message || "不明なエラー")
                        );
                        if (inProgressCheckbox)
                            inProgressCheckbox.checked =
                                originalInProgressChecked;
                        if (completedCheckbox)
                            completedCheckbox.checked =
                                originalCompletedChecked;
                        // アイコンも元に戻す必要があれば、元のステータスを保持しておき updateTaskRowUI を呼ぶ
                    }
                })
                .catch((error) => {
                    console.error("Error updating task via checkbox:", error);
                    alert("更新中にエラーが発生しました。");
                    if (inProgressCheckbox)
                        inProgressCheckbox.checked = originalInProgressChecked;
                    if (completedCheckbox)
                        completedCheckbox.checked = originalCompletedChecked;
                });
        });
    });
}

function initializeTaskStatusUpdater() {
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
                newProgress = currentProgress;
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
                        const isHomePage =
                            !!document.querySelector("h1.text-2xl") &&
                            (!!document.querySelector("h5.text-lg") ||
                                !!document.querySelector("h2.text-xl"));
                        if (
                            isHomePage &&
                            (newStatusFromSelect === "completed" ||
                                newStatusFromSelect === "cancelled" ||
                                newStatusFromSelect === "on_hold" || // 保留中もリスト移動の可能性
                                newStatusFromSelect === "not_started") // 未着手もリスト移動の可能性
                        ) {
                            location.reload();
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
// initializeEditableAssignee と initializeFolderFileToggle は変更なし
function initializeEditableAssignee() {
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
            const taskId = this.dataset.taskId; // trから取得するよう変更の必要性検討
            const projectId = this.dataset.projectId; // trから取得するよう変更の必要性検討

            if (!taskId || !projectId) {
                // taskId, projectId が cell の dataset になければ row から取得
                const parentRow = this.closest("tr");
                if (parentRow) {
                    if (!taskId) this.dataset.taskId = parentRow.dataset.taskId;
                    if (!projectId)
                        this.dataset.projectId = parentRow.dataset.projectId;
                }
            }
            const finalTaskId = this.dataset.taskId;
            const finalProjectId = this.dataset.projectId;

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

                if (
                    newAssignee !== currentAssignee &&
                    finalTaskId &&
                    finalProjectId
                ) {
                    axios
                        .post(
                            `/projects/${finalProjectId}/tasks/${finalTaskId}/assignee`,
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
    initializeTaskStatusUpdater();
    initializeTaskCheckboxes(); // ★ 修正箇所
    initializeEditableAssignee();
    initializeFolderFileToggle();
} catch (e) {
    console.error("Error during tasks-index.js specific initialization:", e);
}

export default {
    // ...
};
