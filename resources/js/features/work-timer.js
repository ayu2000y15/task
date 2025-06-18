// resources/js/features/work-timer.js

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");

/**
 * 特定のタスクのタイマーUIを描画する
 */
function renderTimerControls(container, log, isPaused, taskStatus, assignees) {
    const taskId = container.dataset.taskId;
    const viewMode = container.dataset.viewMode || "full";

    const userData = JSON.parse(
        document.getElementById("user-data-container").dataset.user
    );
    const isSharedAccount = userData && userData.status === "shared";
    const hasMultipleAssignees = assignees && assignees.length > 1;

    container.innerHTML = ""; // コンテナを初期化

    // 完了・キャンセル済みのタスク
    if (taskStatus === "completed" || taskStatus === "cancelled") {
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

    // 実行中のログがある場合
    if (log && log.status === "active") {
        // 通常表示のときだけ「作業中」テキストを表示
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
        // 未開始または一時停止後の状態
        const buttonContainer = document.createElement("div"); // ボタンをdivで囲む
        buttonContainer.className = "flex items-center space-x-2";

        if (isPaused || (log && log.status === "stopped")) {
            // 通常表示のときだけ「一時停止中」テキストを表示
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
                resumeButton.className =
                    "inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-white bg-green-500 hover:bg-green-600 rounded-md shadow-sm transition";
                resumeButton.title = "再開";
            } else {
                resumeButton.innerHTML = `<i class="fas fa-play mr-1"></i> 再開`;
                resumeButton.className =
                    "inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-green-500 hover:bg-green-600 rounded-md shadow-sm transition";
            }
            resumeButton.onclick = () => {
                if (isSharedAccount && hasMultipleAssignees) {
                    openAssigneeSelectionModal(taskId, assignees);
                } else {
                    handleTimerAction(taskId, "start");
                }
            };
            buttonContainer.appendChild(resumeButton);
        } else {
            // 完全な未開始状態
            const startButton = document.createElement("button");
            if (viewMode === "compact") {
                startButton.innerHTML = `<i class="fas fa-play"></i>`;
                startButton.className =
                    "inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-md shadow-sm transition";
                startButton.title = "開始";
            } else {
                startButton.innerHTML = `<i class="fas fa-play mr-1"></i> 開始`;
                startButton.className =
                    "inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-md shadow-sm transition";
            }
            startButton.onclick = () => {
                if (isSharedAccount && hasMultipleAssignees) {
                    openAssigneeSelectionModal(taskId, assignees);
                } else {
                    handleTimerAction(taskId, "start");
                }
            };
            buttonContainer.appendChild(startButton);
        }
        container.appendChild(buttonContainer);
    }
}
/**
 * 担当者選択モーダルを表示する
 */
function openAssigneeSelectionModal(taskId, assignees) {
    // Alpine.js コンポーネントにイベントをディスパッチしてモーダルを開く
    window.dispatchEvent(
        new CustomEvent("open-assignee-modal", {
            detail: { taskId, assignees },
        })
    );
}

/**
 * 選択された担当者でタイマーを開始する (Alpine.jsから呼び出せるようにグローバルに公開)
 */
window.handleStartTimerWithSelection = function (taskId, assigneeIds) {
    if (!assigneeIds || assigneeIds.length === 0) {
        alert("担当者を少なくとも1人選択してください。");
        return;
    }
    // 同じファイルスコープ内の handleTimerAction を呼び出す
    handleTimerAction(taskId, "start", assigneeIds);
};

/**
 * タイマーアクション（開始・一時停止・完了）をサーバーに送信
 */
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
                if (errorData.message) {
                    errorMessage += `\n\nメッセージ: ${errorData.message}`;
                }
                if (errorData.error) {
                    errorMessage += `\n\nメッセージ: ${errorData.error}`;
                }
                if (errorData.errors) {
                    const errorDetails = Object.values(errorData.errors)
                        .flat()
                        .join("\n");
                    errorMessage += `\n\n詳細:\n${errorDetails}`;
                }
            } catch (e) {
                errorMessage += `\nサーバーからの応答が予期した形式ではありませんでした。`;
            }
            alert(errorMessage);
            return;
        }

        const data = await response.json();

        // 1. タイマー自身のUIを更新する
        const containers = document.querySelectorAll(
            `.timer-controls[data-task-id="${taskId}"]`
        );

        if (containers.length > 0) {
            containers.forEach((container) => {
                let logForRender = null;
                let isPausedForRender = false;

                // ▼▼▼【ここから変更】▼▼▼
                // バックエンドから返された最新のタスクステータスをデータ属性に反映
                if (data.task_status) {
                    container.dataset.taskStatus = data.task_status;
                }

                if (action === "start") {
                    logForRender = {
                        status: "active",
                        start_time: new Date().toISOString(),
                    };
                    isPausedForRender = false;
                    // ステータスが完了でない限り、進行中に設定
                    if (container.dataset.taskStatus !== "completed") {
                        container.dataset.taskStatus = "in_progress";
                    }
                } else if (action === "pause") {
                    logForRender = null;
                    isPausedForRender = true;
                } else if (action === "stop") {
                    logForRender = null;
                    isPausedForRender = false;

                    // 自分の作業ログだけが停止した場合、タイマーは「開始」状態に戻る
                    // タスク全体が完了した場合にのみ、データ属性が'completed'に更新される
                    if (data.log_only) {
                        // UIを「未開始」の状態に戻す（renderTimerControlsが開始ボタンを表示）
                    } else {
                        container.dataset.taskStatus = "completed";
                    }
                }
                // ▲▲▲【変更ここまで】▲▲▲

                container.dataset.isPaused = isPausedForRender
                    ? "true"
                    : "false";

                const assigneesData = JSON.parse(
                    container.dataset.assignees || "[]"
                );

                renderTimerControls(
                    container,
                    logForRender,
                    isPausedForRender,
                    container.dataset.taskStatus,
                    assigneesData
                );
            });
        }

        // ▼▼▼【変更】アラートメッセージを常にサーバーからの応答で表示▼▼▼
        if (data.message) {
            alert(data.message);
        }

        // 2. 工程一覧のステータスアイコンを更新するためのイベントを発行する
        // ▼▼▼【変更】サーバーからの応答に基づいてイベントを発行▼▼▼
        if (data.task_status) {
            window.dispatchEvent(
                new CustomEvent("task-status-updated", {
                    detail: {
                        taskId: taskId,
                        newStatus: data.task_status,
                        newProgress:
                            data.task_status === "completed"
                                ? 100
                                : data.task_status === "in_progress"
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

/**
 * ページ読み込み時にタイマーを初期化
 */
export function initializeWorkTimers() {
    const timerContainers = document.querySelectorAll(".timer-controls");
    if (timerContainers.length === 0) return;

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
}
