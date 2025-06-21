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
    row.dataset.taskStatus = newStatus;

    const selectElement = row.querySelector(".task-status-select");
    if (selectElement) {
        selectElement.value = newStatus;
    }

    updateTimerDisplayForTask(row.dataset.taskId, newStatus, newProgress);
}

function updateTimerDisplayForTask(taskId, newStatus, newProgress) {
    const timerContainers = document.querySelectorAll(
        `.timer-controls[data-task-id="${taskId}"], .timer-display-only[data-task-id="${taskId}"]`
    );

    timerContainers.forEach((container) => {
        container.dataset.taskStatus = newStatus;
        if (window.initializeWorkTimers) {
            const event = new CustomEvent("timer-ui-update", {
                detail: { taskId, newStatus, newProgress },
            });
            window.dispatchEvent(event);
        }
    });
}

async function updateTaskStatus(
    taskId,
    projectId,
    newStatus,
    newProgress,
    element
) {
    const row = element.closest("tr, li");
    if (!row) return;

    const originalStatus = row.dataset.taskStatus;
    if (newStatus === "in_progress" && originalStatus !== "in_progress") {
        if (
            !confirm(
                "タスクを「進行中」にしますか？\nこの操作は作業タイマーを開始します。"
            )
        ) {
            revertStatusChange(element);
            return;
        }
    }

    try {
        const response = await axios.post(
            `/projects/${projectId}/tasks/${taskId}/progress`,
            { status: newStatus, progress: newProgress }
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
            const confirmed = await showConfirmationDialogs(
                error.response.data.warnings
            );
            if (confirmed) {
                try {
                    const forceResponse = await axios.post(
                        `/projects/${projectId}/tasks/${taskId}/progress`,
                        {
                            status: newStatus,
                            progress: newProgress,
                            force_update: true,
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
                    revertStatusChange(element);
                }
            } else {
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
    if (row) {
        updateTaskRowUI(row, newStatus, newProgress);
    }

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
    const row = element.closest("tr, li");
    if (!row) return;
    const currentStatus = row.dataset.taskStatus || "not_started";

    if (element.tagName.toLowerCase() === "select") {
        element.value = currentStatus;
    } else if (element.type === "checkbox") {
        const inProgressCheckbox = row.querySelector(
            ".task-status-in-progress"
        );
        const completedCheckbox = row.querySelector(".task-status-completed");
        if (inProgressCheckbox)
            inProgressCheckbox.checked = currentStatus === "in_progress";
        if (completedCheckbox)
            completedCheckbox.checked = currentStatus === "completed";
    }
}

async function showConfirmationDialogs(warnings) {
    for (const warning of warnings) {
        const confirmed = confirm(
            warning.message + "\n\nこのまま続行しますか？"
        );
        if (!confirmed) return false;
    }
    return true;
}

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
            updateTaskStatus(taskId, projectId, newStatus, newProgress, this);
        });
    });
}

function initializeTaskStatusUpdater() {
    document.querySelectorAll(".task-status-select").forEach((select) => {
        select.addEventListener("change", function () {
            const row = this.closest("tr, li");
            if (!row || row.dataset.taskStatus === this.value) return;
            const taskId = row.dataset.taskId;
            const projectId = row.dataset.projectId;
            const newStatus = this.value;
            let newProgress = 0;
            const currentProgress =
                Number.parseInt(row.dataset.progress || "0") || 0;
            if (newStatus === "completed") newProgress = 100;
            else if (newStatus === "in_progress")
                newProgress =
                    currentProgress > 0 && currentProgress < 100
                        ? currentProgress
                        : 10;
            updateTaskStatus(taskId, projectId, newStatus, newProgress, this);
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
            if (notification.parentNode)
                notification.parentNode.removeChild(notification);
        }, 300);
    }, 3000);
}

function initializeEditableMultipleAssignees() {
    document
        .querySelectorAll('[id^="assignee-data-container"]')
        .forEach((container) => {
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
            container.addEventListener("click", (event) => {
                const cell = event.target.closest(".editable-cell-assignees");
                if (!cell || cell.querySelector(".ts-control")) return;
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
                            saveChanges(this, cell, badgeContainer);
                        }, 150);
                    },
                });

                const saveChanges = (
                    instance,
                    targetCell,
                    targetBadgeContainer
                ) => {
                    if (!instance.wrapper || !instance.wrapper.parentNode)
                        return;
                    const newAssigneeIds = instance.items;
                    const sortedOld = [...currentAssigneeIds].sort();
                    const sortedNew = [...newAssigneeIds].sort();

                    if (
                        JSON.stringify(sortedOld) === JSON.stringify(sortedNew)
                    ) {
                        if (targetBadgeContainer)
                            targetBadgeContainer.style.display = "flex";
                        instance.destroy();
                        select.remove();
                        return;
                    }
                    axios
                        .post(
                            `/projects/${projectId}/tasks/${taskId}/assignee`,
                            { assignees: newAssigneeIds }
                        )
                        .then((response) => {
                            if (response.data.success) {
                                if (targetBadgeContainer)
                                    targetBadgeContainer.innerHTML =
                                        response.data.assigneesHtml;
                                targetCell.dataset.currentAssignees =
                                    JSON.stringify(newAssigneeIds);
                            } else {
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
                        })
                        .finally(() => {
                            if (targetBadgeContainer)
                                targetBadgeContainer.style.display = "flex";
                            instance.destroy();
                            if (select.parentNode) select.remove();
                        });
                };
                tomSelectInstance.focus();
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

function initTasksIndex() {
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

window.initTasksIndex = initTasksIndex;

export default initTasksIndex;
