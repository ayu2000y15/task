// resources/js/features/work-timer.js

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");

// 実行中タスクのタイマーを更新する関数は不要になるため削除
// function startElapsedTimeTimer(...) { ... }

async function handleTimerAction(taskId, action, workLogId = null) {
    let url, body;
    const method = "POST";

    if (action === "start") {
        url = "/work-logs/start";
        body = { task_id: taskId };
    } else if (action === "pause") {
        // 「一時停止」はstop APIを 'pause' タイプで呼び出す
        url = `/work-logs/${workLogId}/stop`;
        body = { action_type: "pause" };
    } else if (action === "stop") {
        // 「完了」はstop APIを 'complete' タイプで呼び出す
        const memo = prompt("作業メモ（任意）:", "");
        if (memo === null) {
            return; // ユーザーがキャンセルしたら何もしない
        }
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
        // 成功したらページをリロードして表示を更新する
        window.location.reload();
    } catch (error) {
        console.error("Timer action failed:", error);
        alert("通信エラーが発生しました。");
    }
}

export function initializeWorkTimers() {
    const timerContainers = document.querySelectorAll(".timer-controls");
    if (timerContainers.length === 0) {
        return;
    }

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
        const isPaused = container.dataset.isPaused === "true"; // data-is-paused属性を取得
        const logForThisTask = activeWorkLogs.find(
            (log) => String(log.task_id) === String(taskId)
        );
        container.innerHTML = ""; // コンテナを初期化

        if (taskStatus === "completed" || taskStatus === "cancelled") {
            const statusDisplay = document.createElement("div");
            statusDisplay.className = "text-sm font-semibold";
            if (taskStatus === "completed") {
                statusDisplay.className +=
                    " text-green-600 dark:text-green-400";
                statusDisplay.innerHTML = `<i class="fas fa-check-circle mr-1"></i>完了済`;
            } else {
                statusDisplay.className += " text-gray-500 dark:text-gray-400";
                statusDisplay.innerHTML = `<i class="fas fa-times-circle mr-1"></i>キャンセル済`;
            }
            container.appendChild(statusDisplay);
            return;
        }

        if (logForThisTask) {
            // 作業中の表示 (変更なし)
            const displayContainer = document.createElement("div");
            displayContainer.className = "text-sm mb-2";
            const startTime = new Date(logForThisTask.start_time);
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
            pauseButton.onclick = () =>
                handleTimerAction(taskId, "pause", logForThisTask.id);
            buttonGroup.appendChild(pauseButton);

            const stopButton = document.createElement("button");
            stopButton.innerHTML = `<i class="fas fa-check-circle mr-1"></i> 完了`;
            stopButton.className =
                "inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-red-500 hover:bg-red-600 rounded-md shadow-sm transition";
            stopButton.onclick = () =>
                handleTimerAction(taskId, "stop", logForThisTask.id);
            buttonGroup.appendChild(stopButton);

            container.appendChild(buttonGroup);
        } else {
            // ▼▼▼【ここから修正】isPausedフラグで表示を分岐 ▼▼▼
            if (isPaused) {
                // 一時停止中の表示
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
                // 未開始の表示
                const startButton = document.createElement("button");
                startButton.innerHTML = `<i class="fas fa-play mr-1"></i> 開始`;
                startButton.className =
                    "inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-md shadow-sm transition";
                startButton.onclick = () => handleTimerAction(taskId, "start");
                container.appendChild(startButton);
            }
            // ▲▲▲【修正ここまで】▲▲▲
        }
    });
}
