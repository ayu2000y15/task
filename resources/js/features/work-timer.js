// resources/js/features/work-timer.js

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");

/**
 * ▼▼▼【ここから新規作成】UI描画ロジックを分離・関数化 ▼▼▼
 * 特定のタスクのタイマーUIを描画する
 * @param {HTMLElement} container - UIを描画するコンテナ要素
 * @param {object|null} log - 表示対象の作業ログオブジェクト
 * @param {boolean} isPaused - タスクが（アクティブではないが）一時停止状態か
 * @param {string} taskStatus - タスク自体のステータス
 */
function renderTimerControls(container, log, isPaused, taskStatus) {
    const taskId = container.dataset.taskId;
    container.innerHTML = ""; // コンテナを初期化

    // 完了・キャンセル済みのタスク
    if (taskStatus === "completed" || taskStatus === "cancelled") {
        const statusDisplay = document.createElement("div");
        statusDisplay.className = "text-sm font-semibold";
        if (taskStatus === "completed") {
            statusDisplay.className += " text-green-600 dark:text-green-400";
            statusDisplay.innerHTML = `<i class="fas fa-check-circle mr-1"></i>完了済`;
        } else {
            statusDisplay.className += " text-gray-500 dark:text-gray-400";
            statusDisplay.innerHTML = `<i class="fas fa-times-circle mr-1"></i>キャンセル済`;
        }
        container.appendChild(statusDisplay);
        return;
    }

    // 実行中または一時停止中のログがある場合
    if (log && log.status === "active") {
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

        const buttonGroup = document.createElement("div");
        buttonGroup.className = "flex items-center space-x-2";

        const pauseButton = document.createElement("button");
        pauseButton.innerHTML = `<i class="fas fa-pause mr-1"></i> 一時停止`;
        pauseButton.className =
            "inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-yellow-500 hover:bg-yellow-600 rounded-md shadow-sm transition";
        pauseButton.onclick = () => handleTimerAction(taskId, "pause", log.id);
        buttonGroup.appendChild(pauseButton);

        const stopButton = document.createElement("button");
        stopButton.innerHTML = `<i class="fas fa-check-circle mr-1"></i> 完了`;
        stopButton.className =
            "inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-red-500 hover:bg-red-600 rounded-md shadow-sm transition";
        stopButton.onclick = () => handleTimerAction(taskId, "stop", log.id);
        buttonGroup.appendChild(stopButton);
        container.appendChild(buttonGroup);
    } else {
        // 未開始または一時停止後の状態
        if (isPaused || (log && log.status === "stopped")) {
            // isPausedフラグまたは、pause直後のstoppedログを考慮
            const displayContainer = document.createElement("div");
            displayContainer.className =
                "text-sm mb-2 text-yellow-600 dark:text-yellow-400 font-semibold";
            displayContainer.textContent = "[一時停止中]";
            container.appendChild(displayContainer);

            const resumeButton = document.createElement("button");
            resumeButton.innerHTML = `<i class="fas fa-play mr-1"></i> 再開`;
            resumeButton.className =
                "inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-green-500 hover:bg-green-600 rounded-md shadow-sm transition";
            resumeButton.onclick = () => handleTimerAction(taskId, "start");
            container.appendChild(resumeButton);
        } else {
            const startButton = document.createElement("button");
            startButton.innerHTML = `<i class="fas fa-play mr-1"></i> 開始`;
            startButton.className =
                "inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-md shadow-sm transition";
            startButton.onclick = () => handleTimerAction(taskId, "start");
            container.appendChild(startButton);
        }
    }
}

/**
 * ▼▼▼【ここを修正】handleTimerActionからリロード処理を削除し、UI更新処理を呼び出す ▼▼▼
 * タイマーアクション（開始・一時停止・完了）をサーバーに送信
 */
async function handleTimerAction(taskId, action, workLogId = null) {
    let url, body;
    const method = "POST";

    if (action === "start") {
        url = "/work-logs/start";
        body = { task_id: taskId };
    } else if (action === "pause") {
        url = `/work-logs/${workLogId}/stop`;
        body = { action_type: "pause" };
    } else if (action === "stop") {
        const memo = prompt("作業メモ（任意）:", "");
        if (memo === null) return;
        url = `/work-logs/${workLogId}/stop`;
        body = { action_type: "complete", memo: memo };
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

        const data = await response.json();
        if (!response.ok) {
            alert(`エラー: ${data.error || "操作に失敗しました。"}`);
            return;
        }

        // --- UIの動的更新 ---
        const container = document.querySelector(
            `.timer-controls[data-task-id="${taskId}"]`
        );
        if (container) {
            // isPausedフラグを更新
            const isNowPaused =
                data.work_log && data.work_log.status === "stopped";
            container.dataset.isPaused = isNowPaused ? "true" : "false";

            // タスクステータスが「完了」になった場合は更新
            if (action === "stop") {
                container.dataset.taskStatus = "completed";
            }

            // UIを再描画
            renderTimerControls(
                container,
                data.work_log,
                isNowPaused,
                container.dataset.taskStatus
            );
        }
    } catch (error) {
        console.error("Timer action failed:", error);
        alert("通信エラーが発生しました。");
    }
}

/**
 * ▼▼▼【ここを修正】初期化時に新しいUI描画関数を呼び出すようにする ▼▼▼
 * ページ読み込み時にタイマーを初期化
 */
export function initializeWorkTimers() {
    const timerContainers = document.querySelectorAll(".timer-controls");
    if (timerContainers.length === 0) return;

    const runningLogsElement = document.getElementById(
        "running-work-logs-data"
    );
    let activeWorkLogs = [];
    try {
        activeWorkLogs = JSON.parse(runningLogsElement.textContent);
    } catch (e) {
        console.error(
            "Failed to parse work log data:",
            e,
            runningLogsElement.textContent
        );
        return;
    }

    timerContainers.forEach((container) => {
        const taskId = container.dataset.taskId;
        const taskStatus = container.dataset.taskStatus;
        const isPaused = container.dataset.isPaused === "true";
        const logForThisTask = activeWorkLogs.find(
            (log) => String(log.task_id) === String(taskId)
        );

        // UI描画関数を呼び出す
        renderTimerControls(container, logForThisTask, isPaused, taskStatus);
    });
}
