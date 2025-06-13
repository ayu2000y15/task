// resources/js/page-specific/tasks-index.js
import axios from "axios";

function getStatusIconHtml(status) {
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
    const iconWrapper = row.querySelector(".task-status-icon-wrapper");
    if (iconWrapper) {
        let isSpecialTask = false;
        if (row.tagName.toLowerCase() === "tr") {
            const taskNameCell = row.querySelector("td:nth-child(2)");
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
        selectElement.dataset.originalStatus = newStatus;
        selectElement.dataset.currentProgress = newProgress;
    }
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
            let currentProgressOnRow =
                parseInt(row.dataset.progress || "0") || 0;

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
            axios
                .post(`/projects/${projectId}/tasks/${taskId}/progress`, {
                    status: newStatus,
                    progress: newProgress,
                })
                .then((response) => {
                    if (response.data.success)
                        updateTaskRowUI(row, newStatus, newProgress);
                });
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
            let currentProgress =
                parseInt(this.closest("tr, li")?.dataset.progress || "0") || 0;

            if (newStatus === "completed") newProgress = 100;
            else if (newStatus === "in_progress")
                newProgress =
                    currentProgress > 0 && currentProgress < 100
                        ? currentProgress
                        : 10;

            axios
                .post(`/projects/${projectId}/tasks/${taskId}/progress`, {
                    status: newStatus,
                    progress: newProgress,
                })
                .then((response) => {
                    if (response.data.success) {
                        const row = this.closest("tr, li");
                        updateTaskRowUI(row, newStatus, newProgress);
                        const inProgressCheckbox = row?.querySelector(
                            ".task-status-in-progress"
                        );
                        const completedCheckbox = row?.querySelector(
                            ".task-status-completed"
                        );
                        if (inProgressCheckbox)
                            inProgressCheckbox.checked =
                                newStatus === "in_progress";
                        if (completedCheckbox)
                            completedCheckbox.checked =
                                newStatus === "completed";
                    }
                });
        });
    });
}

function initializeEditableMultipleAssignees() {
    // ▼▼▼【ここから修正】▼▼▼
    // IDが 'assignee-data-container-' で始まる全てのコンテナを取得
    const containers = document.querySelectorAll(
        '[id^="assignee-data-container"]'
    );

    if (containers.length === 0) {
        // コンテナが一つもなければ、警告を出して処理を終了
        console.warn("担当者選択肢のデータコンテナが見つかりません。");
        return;
    }

    // 見つかった各コンテナに対して処理を実行
    containers.forEach((container) => {
        let assigneeOptions = [];
        try {
            // 各コンテナから担当者オプションのデータを取得
            assigneeOptions = JSON.parse(container.dataset.assigneeOptions);
        } catch (e) {
            console.error(
                "担当者選択肢のJSON解析に失敗しました。",
                e,
                container
            );
            return; // このコンテナの処理をスキップ
        }

        // 現在のコンテナ内にある編集可能セルのみを対象にする
        const editableCells = container.querySelectorAll(
            ".editable-cell-assignees"
        );

        editableCells.forEach((cell) => {
            cell.addEventListener("click", function (event) {
                // 既に編集中の場合は何もしない
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

try {
    initializeTaskStatusUpdater();
    initializeTaskCheckboxes();
    initializeEditableMultipleAssignees();
    initializeFolderFileToggle();
} catch (e) {
    console.error("Error during tasks-index.js specific initialization:", e);
}
