// resources/js/features/work-timer.js

// ▼▼▼【ここから追加】タイマー表示関連のグローバル変数と関数 ▼▼▼
let activeTimerUpdaters = {};
let globalTimerInterval = null;

/**
 * 秒数を HH:MM:SS 形式の文字列にフォーマットする
 * @param {number} totalSeconds - 総秒数
 * @returns {string} フォーマットされた時間文字列
 */
function formatDuration(totalSeconds) {
    const isNegative = totalSeconds < 0;
    if (isNegative) {
        totalSeconds = -totalSeconds;
    }

    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = Math.floor(totalSeconds % 60);

    const formatted = [
        hours.toString().padStart(2, "0"),
        minutes.toString().padStart(2, "0"),
        seconds.toString().padStart(2, "0"),
    ].join(":");

    return (isNegative ? "-" : "") + formatted;
}

/**
 * すべてのアクティブなタイマー表示を更新する
 */
function updateAllRunningTimers() {
    const now = new Date();
    Object.keys(activeTimerUpdaters).forEach((taskId) => {
        const updater = activeTimerUpdaters[taskId];
        const displayCell = document.querySelector(
            `.task-actual-time-display[data-task-id="${taskId}"]`
        );

        if (displayCell && updater) {
            const currentSessionElapsedTime = (now - updater.startTime) / 1000; // 秒
            const totalElapsedTime =
                updater.totalPastWorkSeconds + currentSessionElapsedTime;
            const remainingTime =
                updater.durationMinutes * 60 - totalElapsedTime;

            displayCell.textContent = formatDuration(remainingTime);

            if (remainingTime < 0) {
                displayCell.classList.add("text-red-500", "font-bold");
            } else {
                displayCell.classList.remove("text-red-500", "font-bold");
            }
        } else {
            // 表示要素が見つからない場合はタイマーを停止
            stopActualTimeUpdater(taskId);
        }
    });
}

/**
 * 特定のタスクのリアルタイム時間表示を開始する
 * @param {string} taskId
 * @param {string} startTimeIso
 * @param {number} durationMinutes
 * @param {number} totalPastWorkSeconds -【追加】過去の総作業時間（秒）
 */
function startActualTimeUpdater(
    taskId,
    startTimeIso,
    durationMinutes,
    totalPastWorkSeconds
) {
    if (!taskId || !startTimeIso) return;

    activeTimerUpdaters[taskId] = {
        startTime: new Date(startTimeIso),
        durationMinutes: durationMinutes || 0,
        totalPastWorkSeconds: totalPastWorkSeconds || 0,
    };

    if (!globalTimerInterval) {
        globalTimerInterval = setInterval(updateAllRunningTimers, 1000);
    }
}

/**
 * 特定のタスクのリアルタイム時間表示を停止する
 */
function stopActualTimeUpdater(taskId) {
    const updater = activeTimerUpdaters[taskId];
    const now = new Date();

    delete activeTimerUpdaters[taskId];

    if (Object.keys(activeTimerUpdaters).length === 0 && globalTimerInterval) {
        clearInterval(globalTimerInterval);
        globalTimerInterval = null;
    }

    if (updater) {
        const displayCell = document.querySelector(
            `.task-actual-time-display[data-task-id="${taskId}"]`
        );
        if (displayCell) {
            const currentSessionElapsedTime = (now - updater.startTime) / 1000;
            const finalWorkSeconds =
                updater.totalPastWorkSeconds + currentSessionElapsedTime;

            const row = document.querySelector(`tr[data-task-id="${taskId}"]`);
            if (row) {
                // 次回開始時のためにデータ属性も更新
                row.dataset.totalWorkSeconds = finalWorkSeconds;
            }

            const durationMinutes = updater.durationMinutes;
            const remainingTime = durationMinutes * 60 - finalWorkSeconds;

            displayCell.textContent = formatDuration(remainingTime);

            if (remainingTime < 0) {
                displayCell.classList.add("text-red-500", "font-bold");
            } else {
                displayCell.classList.remove("text-red-500", "font-bold");
            }
        }
    }
}
// ▲▲▲【追加ここまで】▲▲▲

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");

/**
 * ステータスに応じたアイコンのHTMLを返す
 */
function getStatusIconHtml(status) {
    switch (status) {
        case "completed":
            return '<i class="fas fa-check-circle text-green-500" title="完了"></i>';
        case "in_progress":
            return '<i class="fas fa-play-circle text-blue-500" title="進行中"></i>';
        case "rework": // ← ★ この case を追加
            return '<i class="fas fa-wrench text-orange-500" title="直し"></i>';
        case "on_hold":
            return '<i class="fas fa-pause-circle text-yellow-500" title="一時停止中"></i>';
        case "cancelled":
            return '<i class="fas fa-times-circle text-red-500" title="キャンセル"></i>';
        case "not_started":
        default:
            return '<i class="far fa-circle text-gray-400" title="未着手"></i>';
    }
}

/**
 * タスク行のUI（アイコン、進捗、セレクトボックス）を更新する
 */
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
}

/**
 * タイマーなど外部からのタスクステータス更新を監視するリスナー
 */
function listenForExternalTaskUpdates() {
    window.addEventListener("task-status-updated", (event) => {
        const { taskId, newStatus, newProgress } = event.detail;
        if (!taskId || !newStatus) return;

        const rows = document.querySelectorAll(
            `tr[data-task-id="${taskId}"], li[data-task-id="${taskId}"]`
        );
        if (rows.length > 0) {
            rows.forEach((row) => {
                updateTaskRowUI(row, newStatus, newProgress);
            });
        }
    });
}

/**
 * 表示専用UIを描画する関数
 */
function renderTimerDisplay(container) {
    container.innerHTML = ""; // コンテナをクリア
    const taskStatus = container.dataset.taskStatus;
    const isPaused = container.dataset.isPaused === "true";

    let text = "未定義";
    let iconClass = "fas fa-question-circle";
    let badgeClass =
        "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";

    let effectiveStatus = taskStatus;
    if ((taskStatus === "in_progress" || taskStatus === "rework") && isPaused) {
        effectiveStatus = "on_hold";
    }

    switch (effectiveStatus) {
        case "completed":
            text = "完了済";
            iconClass = "fas fa-check-circle";
            badgeClass =
                "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300";
            break;
        case "in_progress":
            text = "作業中";
            iconClass = "fas fa-play-circle";
            badgeClass =
                "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300";
            break;
        case "on_hold":
            text = "一時停止中";
            iconClass = "fas fa-pause-circle";
            badgeClass =
                "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300";
            break;
        case "rework":
            text = "直し";
            iconClass = "fas fa-wrench";
            badgeClass =
                "bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300";
            break;
        case "cancelled":
            text = "キャンセル";
            iconClass = "fas fa-times-circle";
            badgeClass =
                "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300";
            break;
        case "not_started":
        default:
            text = "未着手";
            iconClass = "far fa-circle";
            badgeClass =
                "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";
            break;
    }

    const badge = document.createElement("div");
    badge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass}`;
    badge.innerHTML = `<i class="${iconClass} mr-1.5"></i> ${text}`;

    container.appendChild(badge);
}

/**
 * 実行中の作業ログがあるかを確認し、グローバルイベントを発行する
 */
function dispatchWorkLogStatus() {
    const runningLogsElement = document.getElementById(
        "running-work-logs-data"
    );
    const userDataEl = document.getElementById("user-data-container");
    if (!runningLogsElement || !userDataEl) return;

    const userData = JSON.parse(userDataEl.dataset.user);
    if (!userData) return;

    let activeWorkLogs = [];
    try {
        activeWorkLogs = JSON.parse(runningLogsElement.textContent);
    } catch (e) {
        /* データが空の場合など */
    }

    const currentUserId = userData.id;
    const hasActiveLog = activeWorkLogs.some(
        (log) => log.user_id === currentUserId && log.status === "active"
    );

    window.dispatchEvent(
        new CustomEvent("work-log-status-changed", {
            detail: { hasActiveWorkLog: hasActiveLog },
        })
    );
}

/**
 * 特定のタスクのタイマーUIを描画する
 */
function renderTimerControls(container, log, isPaused, taskStatus, assignees) {
    const attendanceStatus = document.body.dataset.attendanceStatus;
    const isWorking = attendanceStatus === "working";

    const taskId = container.dataset.taskId,
        viewMode = container.dataset.viewMode || "full";
    const userDataEl = document.getElementById("user-data-container");
    if (!userDataEl) return;

    const userData = JSON.parse(userDataEl.dataset.user);
    const isSharedAccount = userData && userData.status === "shared";
    const hasMultipleAssignees = assignees && assignees.length > 1;
    container.innerHTML = "";

    if (
        taskStatus === "completed" ||
        taskStatus === "cancelled" ||
        taskStatus === "rework"
    ) {
        const statusDisplay = document.createElement("div");
        statusDisplay.className = "text-sm font-semibold";
        if (taskStatus === "completed") {
            statusDisplay.className += " text-green-600 dark:text-green-400";
            statusDisplay.innerHTML = `<i class="fas fa-check-circle mr-1"></i>${
                viewMode === "full" ? "完了済" : ""
            }`;
        } else {
            statusDisplay.className += " text-gray-500 dark:text-gray-400";
            statusDisplay.innerHTML = `<i class="fas fa-times-circle mr-1"></i>${
                viewMode === "full" ? "キャンセル済" : ""
            }`;
        }
        container.appendChild(statusDisplay);
        return;
    }

    if (log && log.status === "active") {
        if (viewMode === "full") {
            const displayContainer = document.createElement("div");
            displayContainer.className = "text-sm mb-2";
            const startTime = new Date(log.start_time);
            const formattedStartTime = `${
                startTime.getMonth() + 1
            }/${startTime.getDate()} ${startTime
                .getHours()
                .toString()
                .padStart(2, "0")}:${startTime
                .getMinutes()
                .toString()
                .padStart(2, "0")}`;
            displayContainer.innerHTML = `作業中 (開始: <span class="font-semibold text-gray-800 dark:text-gray-200">${formattedStartTime}</span>)`;
            container.appendChild(displayContainer);
        }

        const buttonGroup = document.createElement("div");
        buttonGroup.className = "flex items-center space-x-2";

        const pauseButton = document.createElement("button");
        if (viewMode === "compact") {
            pauseButton.innerHTML = `<i class="fas fa-pause"></i>`;
            pauseButton.className =
                "inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-white bg-yellow-500 hover:bg-yellow-600 rounded-md shadow-sm transition";
            pauseButton.title = "一時停止";
        } else {
            pauseButton.innerHTML = `<i class="fas fa-pause mr-1"></i> 一時停止`;
            pauseButton.className =
                "inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-yellow-500 hover:bg-yellow-600 rounded-md shadow-sm transition";
        }
        pauseButton.onclick = () => handleTimerAction(taskId, "pause");
        buttonGroup.appendChild(pauseButton);

        const stopButton = document.createElement("button");
        if (viewMode === "compact") {
            stopButton.innerHTML = `<i class="fas fa-check-circle"></i>`;
            stopButton.className =
                "inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-white bg-red-500 hover:bg-red-600 rounded-md shadow-sm transition";
            stopButton.title = "完了";
        } else {
            stopButton.innerHTML = `<i class="fas fa-check-circle mr-1"></i> 完了`;
            stopButton.className =
                "inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-red-500 hover:bg-red-600 rounded-md shadow-sm transition";
        }
        stopButton.onclick = () => handleTimerAction(taskId, "stop");
        buttonGroup.appendChild(stopButton);
        container.appendChild(buttonGroup);
    } else {
        const buttonContainer = document.createElement("div");
        buttonContainer.className = "flex items-center space-x-2";

        if (isPaused || (log && log.status === "stopped")) {
            if (viewMode === "full") {
                const displayContainer = document.createElement("div");
                displayContainer.className =
                    "text-sm mb-2 text-yellow-600 dark:text-yellow-400 font-semibold";
                displayContainer.textContent = "[一時停止中]";
                container.appendChild(displayContainer);
            }

            const resumeButton = document.createElement("button");
            if (viewMode === "compact") {
                resumeButton.innerHTML = `<i class="fas fa-play"></i>`;
                resumeButton.className = `inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-white rounded-md shadow-sm transition ${
                    isWorking
                        ? "bg-green-500 hover:bg-green-600"
                        : "bg-gray-400 cursor-not-allowed"
                }`;
                resumeButton.title = isWorking
                    ? "再開"
                    : "出勤中のみ作業を再開できます";
            } else {
                resumeButton.innerHTML = `<i class="fas fa-play mr-1"></i> 再開`;
                resumeButton.className = `inline-flex items-center px-3 py-1 text-xs font-medium text-white rounded-md shadow-sm transition ${
                    isWorking
                        ? "bg-green-500 hover:bg-green-600"
                        : "bg-gray-400 cursor-not-allowed"
                }`;
            }
            if (isWorking) {
                resumeButton.onclick = () => {
                    isSharedAccount && hasMultipleAssignees
                        ? openAssigneeSelectionModal(taskId, assignees)
                        : handleTimerAction(taskId, "start");
                };
            } else {
                resumeButton.disabled = true;
            }
            buttonContainer.appendChild(resumeButton);
        } else {
            const startButton = document.createElement("button");
            if (viewMode === "compact") {
                startButton.innerHTML = `<i class="fas fa-play"></i>`;
                startButton.className = `inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-white rounded-md shadow-sm transition ${
                    isWorking
                        ? "bg-blue-500 hover:bg-blue-600"
                        : "bg-gray-400 cursor-not-allowed"
                }`;
                startButton.title = isWorking
                    ? "開始"
                    : "出勤中のみ作業を開始できます";
            } else {
                startButton.innerHTML = `<i class="fas fa-play mr-1"></i> 開始`;
                startButton.className = `inline-flex items-center px-3 py-1 text-xs font-medium text-white rounded-md shadow-sm transition ${
                    isWorking
                        ? "bg-blue-500 hover:bg-blue-600"
                        : "bg-gray-400 cursor-not-allowed"
                }`;
            }
            if (isWorking) {
                startButton.onclick = () => {
                    isSharedAccount && hasMultipleAssignees
                        ? openAssigneeSelectionModal(taskId, assignees)
                        : handleTimerAction(taskId, "start");
                };
            } else {
                startButton.disabled = true;
            }
            buttonContainer.appendChild(startButton);
        }
        container.appendChild(buttonContainer);
    }
}

function openAssigneeSelectionModal(taskId, assignees) {
    window.dispatchEvent(
        new CustomEvent("open-assignee-modal", {
            detail: { taskId, assignees },
        })
    );
}

window.handleStartTimerWithSelection = (taskId, assigneeIds) => {
    if (!assigneeIds || assigneeIds.length === 0) {
        alert("担当者を少なくとも1人選択してください。");
        return;
    }
    handleTimerAction(taskId, "start", assigneeIds);
};

async function handleTimerAction(taskId, action, assigneeIds = []) {
    let url, body;
    const method = "POST";

    if (action === "start") {
        url = "/work-logs/start";
        body = { task_id: taskId, assignee_ids: assigneeIds };
    } else if (action === "pause" || action === "stop") {
        url = `/work-logs/stop-by-task`;
        body = {
            task_id: taskId,
            action_type: action === "pause" ? "pause" : "complete",
        };
        if (action === "stop") {
            const memo = prompt("作業メモ（任意）:", "");
            if (memo === null) return;
            body.memo = memo;
        }
    } else {
        return;
    }

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: JSON.stringify(body),
        });

        if (!response.ok) {
            let errorMessage = `エラー (コード: ${response.status}): サーバーがリクエストを拒否しました。`;
            try {
                const errorData = await response.json();
                if (errorData.message)
                    errorMessage += `\n\nメッセージ: ${errorData.message}`;
                if (errorData.error)
                    errorMessage += `\n\nメッセージ: ${errorData.error}`;
                if (errorData.errors)
                    errorMessage += `\n\n詳細:\n${Object.values(
                        errorData.errors
                    )
                        .flat()
                        .join("\n")}`;
            } catch (e) {
                errorMessage += `\nサーバーからの応答が予期した形式ではありませんでした。`;
            }
            alert(errorMessage);
            return;
        }

        const data = await response.json();

        if (action === "start") {
            const row = document.querySelector(`tr[data-task-id="${taskId}"]`);
            if (row && data.running_logs) {
                // `running_logs` から今開始したログを見つける
                const newLog = data.running_logs.find(
                    (log) =>
                        String(log.task_id) === String(taskId) &&
                        log.status === "active"
                );
                if (newLog) {
                    const durationMinutes =
                        parseInt(row.dataset.duration, 10) || 0;
                    const totalPastWorkSeconds =
                        parseInt(row.dataset.totalWorkSeconds, 10) || 0;
                    startActualTimeUpdater(
                        taskId,
                        newLog.start_time,
                        durationMinutes,
                        totalPastWorkSeconds
                    );
                }
            }
        } else if (action === "pause" || action === "stop") {
            stopActualTimeUpdater(taskId);
        }

        if (data.running_logs) {
            const runningLogsElement = document.getElementById(
                "running-work-logs-data"
            );
            if (runningLogsElement) {
                runningLogsElement.textContent = JSON.stringify(
                    data.running_logs
                );
            }
        }
        dispatchWorkLogStatus();

        document
            .querySelectorAll(`.timer-controls[data-task-id="${taskId}"]`)
            .forEach((container) => {
                if (data.task_status)
                    container.dataset.taskStatus = data.task_status;
                if (typeof data.is_paused !== "undefined")
                    container.dataset.isPaused = data.is_paused
                        ? "true"
                        : "false";

                const logForRender =
                    action === "start"
                        ? {
                              status: "active",
                              start_time: new Date().toISOString(),
                          }
                        : null;
                const isPausedForRender =
                    action === "pause" || data.is_paused === true;

                renderTimerControls(
                    container,
                    logForRender,
                    isPausedForRender,
                    container.dataset.taskStatus,
                    JSON.parse(container.dataset.assignees || "[]")
                );
            });

        document
            .querySelectorAll(`.timer-display-only[data-task-id="${taskId}"]`)
            .forEach((container) => {
                if (data.task_status)
                    container.dataset.taskStatus = data.task_status;
                if (typeof data.is_paused !== "undefined")
                    container.dataset.isPaused = data.is_paused
                        ? "true"
                        : "false";
                renderTimerDisplay(container);
            });

        if (data.message) alert(data.message);

        const finalStatus = data.task_status;
        if (finalStatus) {
            window.dispatchEvent(
                new CustomEvent("task-status-updated", {
                    detail: {
                        taskId: taskId,
                        newStatus: finalStatus,
                        newProgress:
                            finalStatus === "completed"
                                ? 100
                                : finalStatus === "in_progress"
                                ? 10
                                : 0,
                    },
                })
            );
        }
    } catch (error) {
        console.error("Timer action failed:", error);
        alert(
            "通信中に予期せぬエラーが発生しました。コンソールを確認してください。"
        );
    }
}

window.addEventListener("timer-ui-update", (event) => {
    const { taskId, newStatus } = event.detail;
    const timerContainers = document.querySelectorAll(
        `.timer-controls[data-task-id="${taskId}"], .timer-display-only[data-task-id="${taskId}"]`
    );

    timerContainers.forEach((container) => {
        container.dataset.taskStatus = newStatus;
        if (container.classList.contains("timer-controls")) {
            const runningLogsElement = document.getElementById(
                "running-work-logs-data"
            );
            let activeWorkLogs = [];
            if (runningLogsElement) {
                try {
                    activeWorkLogs = JSON.parse(runningLogsElement.textContent);
                } catch (e) {}
            }
            const logForThisTask = activeWorkLogs.find(
                (log) => String(log.task_id) === String(taskId)
            );
            const isPaused = container.dataset.isPaused === "true";
            const assignees = JSON.parse(container.dataset.assignees || "[]");
            renderTimerControls(
                container,
                logForThisTask,
                isPaused,
                newStatus,
                assignees
            );
        } else if (container.classList.contains("timer-display-only")) {
            renderTimerDisplay(container);
        }
    });
});

export function initializeWorkTimers() {
    const timerContainers = document.querySelectorAll(".timer-controls");
    const displayOnlyContainers = document.querySelectorAll(
        ".timer-display-only"
    );

    if (timerContainers.length === 0 && displayOnlyContainers.length === 0)
        return;

    const runningLogsElement = document.getElementById(
        "running-work-logs-data"
    );
    let activeWorkLogs = [];
    if (runningLogsElement) {
        try {
            activeWorkLogs = JSON.parse(runningLogsElement.textContent);
        } catch (e) {
            console.error(
                "Failed to parse work log data:",
                e,
                runningLogsElement.textContent
            );
        }
    }
    dispatchWorkLogStatus();

    activeWorkLogs.forEach((log) => {
        if (log.status === "active") {
            const row = document.querySelector(
                `tr[data-task-id="${log.task_id}"]`
            );
            if (row) {
                const durationMinutes = parseInt(row.dataset.duration, 10) || 0;
                const totalPastWorkSeconds =
                    parseInt(row.dataset.totalWorkSeconds, 10) || 0;
                startActualTimeUpdater(
                    log.task_id,
                    log.start_time,
                    durationMinutes,
                    totalPastWorkSeconds
                );
            }
        }
    });

    timerContainers.forEach((container) => {
        const taskId = container.dataset.taskId;
        const taskStatus = container.dataset.taskStatus;
        const isPaused = container.dataset.isPaused === "true";
        const assignees = JSON.parse(container.dataset.assignees || "[]");
        const logForThisTask = activeWorkLogs.find(
            (log) => String(log.task_id) === String(taskId)
        );
        renderTimerControls(
            container,
            logForThisTask,
            isPaused,
            taskStatus,
            assignees
        );
    });

    displayOnlyContainers.forEach((container) => {
        renderTimerDisplay(container);
    });

    window.addEventListener("attendance-status-changed", () => {
        const timerContainers = document.querySelectorAll(".timer-controls");
        const runningLogsElement = document.getElementById(
            "running-work-logs-data"
        );
        let activeWorkLogs = [];
        if (runningLogsElement) {
            try {
                activeWorkLogs = JSON.parse(runningLogsElement.textContent);
            } catch (e) {}
        }
        timerContainers.forEach((container) => {
            const taskId = container.dataset.taskId;
            const taskStatus = container.dataset.taskStatus;
            const isPaused = container.dataset.isPaused === "true";
            const assignees = JSON.parse(container.dataset.assignees || "[]");
            const logForThisTask = activeWorkLogs.find(
                (log) => String(log.task_id) === String(taskId)
            );
            renderTimerControls(
                container,
                logForThisTask,
                isPaused,
                taskStatus,
                assignees
            );
        });
    });

    listenForExternalTaskUpdates();
}

// ▼▼▼【ここから変更】外部から呼び出せるように関数をグローバルに公開 ▼▼▼
window.initializeWorkTimers = initializeWorkTimers;
