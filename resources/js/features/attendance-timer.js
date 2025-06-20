// resources/js/features/attendance-timer.js

document.addEventListener("alpine:init", () => {
    Alpine.data("attendanceTimer", ({ initialStatus }) => ({
        status: initialStatus,
        loading: false,
        hasActiveWorkLog: false,
        statusTexts: {
            clocked_out: "未出勤",
            working: "出勤中",
            on_break: "休憩中",
            on_away: "中抜け中",
        },

        get statusText() {
            return this.statusTexts[this.status] || "不明";
        },

        // ▼▼▼【ここから追加】UIを制御するためのプロパティ▼▼▼
        /**
         * メインアクションボタンのテキストを返します。
         */
        get primaryActionText() {
            switch (this.status) {
                case "clocked_out":
                    return "出勤";
                case "working":
                    return "休憩開始";
                case "on_break":
                    return "休憩終了";
                case "on_away":
                    return "業務再開";
                default:
                    return "";
            }
        },

        /**
         * メインアクションボタンのアイコンを返します。
         */
        get primaryActionIcon() {
            switch (this.status) {
                case "clocked_out":
                    return "fa-sign-in-alt";
                case "working":
                    return "fa-mug-hot";
                case "on_break":
                    return "fa-play";
                case "on_away":
                    return "fa-play";
                default:
                    return "";
            }
        },

        /**
         * 現在のステータスを示すアイコンを返します。（主にスマホ表示用）
         */
        get statusIcon() {
            switch (this.status) {
                case "clocked_out":
                    return "fa-door-open";
                case "working":
                    return "fa-briefcase";
                case "on_break":
                    return "fa-mug-hot";
                case "on_away":
                    return "fa-walking";
                default:
                    return "fa-clock";
            }
        },

        /**
         * メインアクションボタンのツールチップテキストを返します。
         */
        get primaryActionTooltip() {
            if (this.hasActiveWorkLog && this.status === "working") {
                return "実行中の作業があるため操作できません";
            }
            return this.primaryActionText;
        },

        /**
         * メインアクションボタンがクリックされたときに実行される関数です。
         */
        performPrimaryAction() {
            switch (this.status) {
                case "clocked_out":
                    this.clock("clock_in");
                    break;
                case "working":
                    this.clock("break_start");
                    break;
                case "on_break":
                    this.clock("break_end");
                    break;
                case "on_away":
                    this.clock("away_end");
                    break;
            }
        },
        // ▲▲▲【追加ここまで】▲▲▲

        init() {
            // `running-work-logs-data`スクリプトタグから初期データを読み込む
            const runningLogsDataElement = document.getElementById(
                "running-work-logs-data"
            );
            if (runningLogsDataElement) {
                const runningLogs = JSON.parse(
                    runningLogsDataElement.textContent
                );
                this.hasActiveWorkLog = runningLogs.length > 0;
            }

            window.addEventListener("work-log-status-changed", (event) => {
                this.hasActiveWorkLog = event.detail.hasActiveWorkLog;
            });
        },

        async clock(type) {
            if (
                ["clock_out", "break_start", "away_start"].includes(type) &&
                this.hasActiveWorkLog
            ) {
                alert(
                    "実行中の作業があるため、この操作はできません。先に作業を一時停止してください。"
                );
                return;
            }

            if (this.loading) return;
            this.loading = true;

            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");

            try {
                const response = await fetch("/attendance/clock", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ type: type }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || "打刻に失敗しました。");
                }

                this.status = data.new_status;
                // alert(data.message); // 打刻のたびにアラートが出るのはUXを損なうためコメントアウト。代わりにToastなどの通知を推奨。

                // 1. bodyのdata属性を更新
                document.body.dataset.attendanceStatus = data.new_status;
                // 2. グローバルイベントを発行して、他のコンポーネントに変更を通知
                window.dispatchEvent(
                    new CustomEvent("attendance-status-changed", {
                        detail: { newStatus: data.new_status },
                    })
                );

                // ドロップダウンを閉じる（存在すれば）
                if (this.$data.open) {
                    this.$data.open = false;
                }
            } catch (error) {
                console.error("Attendance clock error:", error);
                alert("エラー: " + error.message);
            } finally {
                this.loading = false;
            }
        },
    }));
});
