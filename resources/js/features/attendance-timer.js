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

        init() {
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
                alert(data.message);

                // 1. bodyのdata属性を更新
                document.body.dataset.attendanceStatus = data.new_status;
                // 2. グローバルイベントを発行して、他のコンポーネントに変更を通知
                window.dispatchEvent(
                    new CustomEvent("attendance-status-changed", {
                        detail: { newStatus: data.new_status },
                    })
                );

                // ドロップダウンを閉じる
                this.$data.open = false;
            } catch (error) {
                console.error("Attendance clock error:", error);
                alert("エラー: " + error.message);
            } finally {
                this.loading = false;
            }
        },
    }));
});
