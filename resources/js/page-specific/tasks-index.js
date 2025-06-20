// resources/js/page-specific/tasks-index.js
import axios from "axios";
import TomSelect from "tom-select";

function getStatusIconHtml(status) {
    switch (status) {
        case "completed":
            return '<i class="fas fa-check-circle text-green-500" title="完了"></i>';
        case "in_progress":
            return '<i class="fas fa-play-circle text-blue-500" title="進行中"></i>';
        case "on_hold":
            return '<i class="fas fa-pause-circle text-yellow-500" title="一時停止中"></i>';
        case "cancelled":
            return '<i class="fas fa-times-circle text-red-500" title="キャンセル"></i>';
        case "not_started":
        default:
            return '<i class="far fa-circle text-gray-400" title="未着手"></i>';
    }
}

function updateTaskRowUI(row, newStatus, newProgress) {
    if (!row) return;

    const iconWrapper = row.querySelector(".task-status-icon-wrapper");
    if (iconWrapper) {
        let isSpecialTask = false;
        if (row.tagName.toLowerCase() === "tr") {
            const taskNameCell = row.querySelector(
                "td:nth-child(2), td:nth-child(1)"
            );
            if (taskNameCell) {
                isSpecialTask = !!(
                    taskNameCell.querySelector("i.fa-flag") ||
                    taskNameCell.querySelector("i.fa-folder")
                );
            }
        } else if (row.tagName.toLowerCase() === "li") {
            isSpecialTask = !!(
                iconWrapper.querySelector("i.fa-flag") ||
                iconWrapper.querySelector("i.fa-folder")
            );
        }

        if (!isSpecialTask) {
            iconWrapper.innerHTML = getStatusIconHtml(newStatus);
        }
    }
    row.dataset.progress = newProgress;

    const selectElement = row.querySelector(".task-status-select");
    if (selectElement) {
        selectElement.value = newStatus;
    }

    // ▼▼▼【新機能】タイマーUIの更新 ▼▼▼
    updateTimerDisplayForTask(row.dataset.taskId, newStatus, newProgress);
    // ▲▲▲【新機能ここまで】▲▲▲
}

// ▼▼▼【新機能】タイマー表示の更新 ▼▼▼
function updateTimerDisplayForTask(taskId, newStatus, newProgress) {
    const timerContainers = document.querySelectorAll(
        `.timer-controls[data-task-id="${taskId}"], .timer-display-only[data-task-id="${taskId}"]`
    );

    timerContainers.forEach((container) => {
        container.dataset.taskStatus = newStatus;

        // タイマーUIの再描画をトリガー
        if (window.initializeWorkTimers) {
            // work-timer.jsの関数を呼び出してUIを更新
            const event = new CustomEvent("timer-ui-update", {
                detail: { taskId, newStatus, newProgress },
            });
            window.dispatchEvent(event);
        }
    });
}
// ▲▲▲【新機能ここまで】▲▲▲

// ▼▼▼【新機能】確認ダイアログ付きステータス更新 ▼▼▼
async function updateTaskStatus(
    taskId,
    projectId,
    newStatus,
    newProgress,
    element
) {
    try {
        const response = await axios.post(
            `/projects/${projectId}/tasks/${taskId}/progress`,
            {
                status: newStatus,
                progress: newProgress,
            }
        );

        if (response.data.success) {
            handleSuccessfulStatusUpdate(
                element,
                newStatus,
                newProgress,
                response.data
            );
        }
    } catch (error) {
        if (error.response?.data?.requires_confirmation) {
            // 警告ダイアログの処理
            const confirmed = await showConfirmationDialogs(
                error.response.data.warnings
            );
            if (confirmed) {
                // ユーザーが全ての警告に「はい」と答えた場合、強制実行
                try {
                    const forceResponse = await axios.post(
                        `/projects/${projectId}/tasks/${taskId}/progress`,
                        {
                            status: newStatus,
                            progress: newProgress,
                            force_update: true, // 強制実行フラグ
                        }
                    );

                    if (forceResponse.data.success) {
                        handleSuccessfulStatusUpdate(
                            element,
                            newStatus,
                            newProgress,
                            forceResponse.data
                        );
                    }
                } catch (forceError) {
                    console.error("Force update failed:", forceError);
                    alert("ステータスの更新に失敗しました。");
                    revertStatusChange(element); // 失敗したら元に戻す
                }
            } else {
                // ユーザーが「いいえ」を選んだ場合、元の値に戻す
                revertStatusChange(element);
            }
        } else {
            console.error("Status update failed:", error);
            const errorMessage =
                error.response?.data?.message ||
                "ステータスの更新に失敗しました。";
            alert(errorMessage);
            revertStatusChange(element);
        }
    }
}

function handleSuccessfulStatusUpdate(
    element,
    newStatus,
    newProgress,
    responseData
) {
    const row = element.closest("tr, li");
    updateTaskRowUI(row, newStatus, newProgress);

    if (responseData.work_log_message) {
        showWorkLogNotification(responseData.work_log_message);
    }

    if (responseData.running_logs) {
        const runningLogsElement = document.getElementById(
            "running-work-logs-data"
        );
        if (runningLogsElement) {
            runningLogsElement.textContent = JSON.stringify(
                responseData.running_logs
            );
        }
        window.dispatchEvent(
            new CustomEvent("work-log-status-changed", {
                detail: {
                    hasActiveWorkLog: responseData.running_logs.length > 0,
                },
            })
        );
    }
}

function revertStatusChange(element) {
    // セレクトボックスの場合、元の値に戻す
    if (element.tagName.toLowerCase() === "select") {
        const row = element.closest("tr, li");
        const currentStatus = row.dataset.taskStatus || "not_started";
        element.value = currentStatus;
    }
    // チェックボックスの場合、チェックを外す
    else if (element.type === "checkbox") {
        element.checked = false;
    }
}

/**
 * 複数の警告ダイアログを順番に表示する関数
 * @param {Array} warnings サーバーから受け取った警告の配列
 * @returns {Promise<boolean>} ユーザーが全てに同意した場合はtrue、途中でキャンセルした場合はfalse
 */
async function showConfirmationDialogs(warnings) {
    for (const warning of warnings) {
        // warning.message にはサーバーで生成されたメッセージが入っています
        const confirmed = confirm(
            warning.message + "\n\nこのまま続行しますか？"
        );
        if (!confirmed) {
            return false; // ユーザーが「キャンセル」を選んだら、即座にfalseを返す
        }
    }
    return true; // 全ての警告ダイアログで「OK」が押された
}
// ▲▲▲【新機能ここまで】▲▲▲

function initializeTaskCheckboxes() {
    document.querySelectorAll(".task-status-checkbox").forEach((checkbox) => {
        checkbox.addEventListener("change", function () {
            const row = this.closest("tr, li");
            if (!row) return;

            const taskId = row.dataset.taskId;
            const projectId = row.dataset.projectId;
            const action = this.dataset.action;
            if (!taskId || !projectId) return;

            const inProgressCheckbox = row.querySelector(
                ".task-status-in-progress"
            );
            const completedCheckbox = row.querySelector(
                ".task-status-completed"
            );

            let newStatus = "not_started";
            let newProgress = 0;
            const currentProgressOnRow =
                Number.parseInt(row.dataset.progress || "0") || 0;

            if (this.checked) {
                if (action === "set-in-progress" && completedCheckbox)
                    completedCheckbox.checked = false;
                if (action === "set-completed" && inProgressCheckbox)
                    inProgressCheckbox.checked = false;
            }

            if (inProgressCheckbox?.checked) {
                newStatus = "in_progress";
                newProgress =
                    currentProgressOnRow > 0 && currentProgressOnRow < 100
                        ? currentProgressOnRow
                        : 10;
            } else if (completedCheckbox?.checked) {
                newStatus = "completed";
                newProgress = 100;
            }

            // ▼▼▼【変更】新しい確認ダイアログ付き更新関数を使用 ▼▼▼
            updateTaskStatus(taskId, projectId, newStatus, newProgress, this);
            // ▲▲▲【変更ここまで】▲▲▲
        });
    });
}

function initializeTaskStatusUpdater() {
    document.querySelectorAll(".task-status-select").forEach((select) => {
        select.addEventListener("change", function () {
            const taskId = this.dataset.taskId;
            const projectId = this.dataset.projectId;
            const newStatus = this.value;
            let newProgress = 0;
            const currentProgress =
                Number.parseInt(
                    this.closest("tr, li")?.dataset.progress || "0"
                ) || 0;

            if (newStatus === "completed") newProgress = 100;
            else if (newStatus === "in_progress")
                newProgress =
                    currentProgress > 0 && currentProgress < 100
                        ? currentProgress
                        : 10;

            // ▼▼▼【変更】新しい確認ダイアログ付き更新関数を使用 ▼▼▼
            updateTaskStatus(taskId, projectId, newStatus, newProgress, this);
            // ▲▲▲【変更ここまで】▲▲▲
        });
    });
}

function showWorkLogNotification(message) {
    const notification = document.createElement("div");
    notification.className =
        "fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-md shadow-lg z-50 transition-opacity duration-300";
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = "0";
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

function initializeEditableMultipleAssignees() {
    const containers = document.querySelectorAll(
        '[id^="assignee-data-container"]'
    );

    if (containers.length === 0) {
        console.warn("担当者選択肢のデータコンテナが見つかりません。");
        return;
    }

    containers.forEach((container) => {
        let assigneeOptions = [];
        try {
            assigneeOptions = JSON.parse(container.dataset.assigneeOptions);
        } catch (e) {
            console.error(
                "担当者選択肢のJSON解析に失敗しました。",
                e,
                container
            );
            return;
        }

        const editableCells = container.querySelectorAll(
            ".editable-cell-assignees"
        );

        editableCells.forEach((cell) => {
            cell.addEventListener("click", (event) => {
                if (cell.querySelector(".ts-control")) {
                    return;
                }
                const parentRow = cell.closest("tr");
                if (!parentRow) return;
                const taskId = parentRow.dataset.taskId;
                const projectId = parentRow.dataset.projectId;
                const currentAssigneeIds = JSON.parse(
                    cell.dataset.currentAssignees || "[]"
                );
                const badgeContainer = cell.querySelector(
                    ".assignee-badge-container"
                );

                if (badgeContainer) badgeContainer.style.display = "none";

                const select = document.createElement("select");
                select.setAttribute("multiple", "multiple");
                cell.appendChild(select);

                const tomSelectInstance = new TomSelect(select, {
                    options: assigneeOptions,
                    items: currentAssigneeIds,
                    valueField: "id",
                    labelField: "name",
                    searchField: "name",
                    plugins: ["remove_button"],
                    create: false,
                    placeholder: "担当者を選択...",
                    onBlur: function () {
                        setTimeout(() => {
                            saveChanges(this);
                        }, 150);
                    },
                });

                const saveChanges = (instance) => {
                    if (!instance.wrapper) return;
                    const newAssigneeIds = instance.items;
                    const sortedOld = [...currentAssigneeIds].sort();
                    const sortedNew = [...newAssigneeIds].sort();
                    if (
                        JSON.stringify(sortedOld) === JSON.stringify(sortedNew)
                    ) {
                        if (badgeContainer)
                            badgeContainer.style.display = "flex";
                        instance.destroy();
                        select.remove();
                        return;
                    }
                    axios
                        .post(
                            `/projects/${projectId}/tasks/${taskId}/assignee`,
                            {
                                assignees: newAssigneeIds,
                            }
                        )
                        .then((response) => {
                            if (response.data.success) {
                                if (badgeContainer) {
                                    badgeContainer.innerHTML =
                                        response.data.assigneesHtml;
                                    badgeContainer.style.display = "flex";
                                }
                                cell.dataset.currentAssignees =
                                    JSON.stringify(newAssigneeIds);
                            } else {
                                if (badgeContainer)
                                    badgeContainer.style.display = "flex";
                                alert(
                                    response.data.message ||
                                        "更新に失敗しました。"
                                );
                            }
                        })
                        .catch((error) => {
                            console.error(
                                "担当者の更新中にエラーが発生しました:",
                                error
                            );
                            alert("担当者の更新中にエラーが発生しました。");
                            if (badgeContainer)
                                badgeContainer.style.display = "flex";
                        })
                        .finally(() => {
                            if (instance.wrapper) instance.destroy();
                            if (select.parentNode) select.remove();
                        });
                };
                tomSelectInstance.focus();
            });
        });
    });
}

function initializeFolderFileToggle() {
    document.querySelectorAll(".toggle-folder-files").forEach((button) => {
        button.addEventListener("click", function () {
            const targetElement = document.getElementById(this.dataset.target);
            const icon = this.querySelector("i");
            if (!targetElement || !icon) return;
            targetElement.classList.toggle("hidden");
            icon.classList.toggle("fa-chevron-down");
            icon.classList.toggle("fa-chevron-up");
        });
    });
}

// 初期化関数をエクスポート
export default function initTasksIndex() {
    try {
        initializeTaskStatusUpdater();
        initializeTaskCheckboxes();
        initializeEditableMultipleAssignees();
        initializeFolderFileToggle();
    } catch (e) {
        console.error(
            "Error during tasks-index.js specific initialization:",
            e
        );
    }
}
