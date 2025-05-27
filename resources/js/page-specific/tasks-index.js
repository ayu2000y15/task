// resources/js/page-specific/tasks-index.js
import axios from "axios";

function getStatusIconHtml(status) {
    // ... (変更なし)
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
        // console.warn("updateTaskRowUI: row is null");
        return;
    }
    // ... (アイコン更新ロジックは変更なし) ...
    const iconWrapper = row.querySelector(".task-status-icon-wrapper");
    if (iconWrapper) {
        const taskNameCell = row.querySelector("td:nth-child(2)");
        if (taskNameCell && taskNameCell.querySelector("i.fa-flag")) {
        } else if (taskNameCell && taskNameCell.querySelector("i.fa-folder")) {
        } else {
            iconWrapper.innerHTML = getStatusIconHtml(newStatus);
        }
    }
    const selectElement = row.querySelector(".task-status-select");
    if (selectElement) {
        selectElement.dataset.originalStatus = newStatus;
        selectElement.dataset.currentProgress = newProgress;
    }
}

function initializeTaskStatusUpdater() {
    // console.log('TaskStatusUpdater: Initializing...');
    const selects = document.querySelectorAll(".task-status-select");
    // console.log('TaskStatusUpdater: Found selects:', selects.length);

    selects.forEach((select) => {
        const row = select.closest("tr, li"); // liも対象に
        if (!row) {
            // console.warn('TaskStatusUpdater: Select element is not inside a <tr> or <li>. Skipping event listener attachment for:', select);
            return;
        }
        // console.log('TaskStatusUpdater: Attaching listener to select in row:', row);
        select.dataset.originalStatus = select.value;
        select.dataset.currentProgress = row.dataset.progress || "0";

        select.addEventListener("change", function () {
            const taskId = this.dataset.taskId;
            const projectId = this.dataset.projectId;
            const newStatus = this.value;
            let newProgress = 0;

            if (newStatus === "completed") newProgress = 100;
            else if (newStatus === "not_started" || newStatus === "cancelled")
                newProgress = 0;
            else if (newStatus === "in_progress") {
                let currentProgress = parseInt(this.dataset.currentProgress);
                if (isNaN(currentProgress)) currentProgress = 0;
                if (currentProgress === 0 && newStatus === "in_progress")
                    newProgress = 10;
                else if (currentProgress === 100 && newStatus === "in_progress")
                    newProgress = 90;
                else newProgress = currentProgress > 0 ? currentProgress : 10;
            }

            axios
                .post(`/projects/${projectId}/tasks/${taskId}/progress`, {
                    status: newStatus,
                    progress: newProgress,
                })
                .then((response) => {
                    if (response.data.success) {
                        // ホーム画面（ToDoリストの見出しがあるページ）かどうかを判定
                        const isHomePage =
                            !!document.querySelector("h2.text-xl");

                        if (isHomePage) {
                            // ホーム画面の場合は、ページをリロードしてカンバンボード全体を更新
                            location.reload();
                        } else {
                            // それ以外のページでは、行のUIを直接更新
                            const currentRow = this.closest("tr, li");
                            if (currentRow) {
                                updateTaskRowUI(
                                    currentRow,
                                    newStatus,
                                    newProgress
                                );
                            }
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
    // console.log('TaskStatusUpdater: Initialized.');
}

function initializeEditableAssignee() {
    // console.log('EditableAssignee: Initializing...');
    const cells = document.querySelectorAll(
        '.editable-cell[data-field="assignee"]'
    );
    // console.log('EditableAssignee: Found cells:', cells.length);

    cells.forEach((cell) => {
        const row = cell.closest("tr"); // editable-cell が tr の中にあることを確認
        if (!row) {
            // console.warn('EditableAssignee: Cell is not inside a <tr>. Skipping event listener attachment for:', cell);
            return; // <tr> の中にない cell はスキップ
        }
        // console.log('EditableAssignee: Attaching listener to cell in row:', row);

        const originalText = cell.textContent.trim();
        cell.dataset.originalValue = originalText === "-" ? "" : originalText;

        cell.addEventListener("click", function handleAssigneeCellClick() {
            if (this.querySelector("input.assignee-edit-input")) return;

            const currentAssignee = this.dataset.originalValue || "";
            const taskId = this.dataset.taskId;
            const projectId = this.dataset.projectId;

            this.innerHTML = `<input type="text" value="${currentAssignee}" class="assignee-edit-input form-input w-full text-sm p-1 border border-blue-500 rounded dark:bg-gray-700 dark:text-gray-200" placeholder="担当者名">`;
            const input = this.querySelector("input.assignee-edit-input");
            if (!input) return; // inputが作成されなかった場合は終了
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
                // イベントリスナーを削除
                currentInput.removeEventListener("blur", saveAssignee); // blurリスナーを削除
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

            input.addEventListener("blur", saveAssignee, { once: true }); // blurは一度だけに
            input.addEventListener("keydown", handleAssigneeKeydownOnce); // keydownも名前付き関数で登録
        });
    });
    // console.log('EditableAssignee: Initialized.');
}

function initializeFolderFileToggle() {
    const buttons = document.querySelectorAll(".toggle-folder-files");

    buttons.forEach((button) => {
        const targetId = button.dataset.target; // data-target="folder-files-{{ $task->id }}" を想定

        if (!targetId) {
            console.warn(
                "FolderFileToggle: Button found without data-target attribute."
            );
            return;
        }

        button.addEventListener("click", function () {
            const targetElement = document.getElementById(targetId); // IDで直接要素を取得
            const icon = this.querySelector("i");

            if (!targetElement) {
                console.warn(
                    "FolderFileToggle: Target element not found with ID:",
                    targetId
                );
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
    initializeEditableAssignee();
    initializeFolderFileToggle();
} catch (e) {
    console.error("Error during tasks-index.js specific initialization:", e);
}

export default {
    // Only export functions if they need to be called from elsewhere,
    // otherwise, their direct execution is enough.
};
