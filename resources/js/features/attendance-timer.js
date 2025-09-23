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
                    // 出勤時は直接出勤場所選択モーダルを表示する
                    this.openLocationModal();
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

        async confirmAndClockIn() {
            // ここでは簡易的に予定場所を取得する方法がないため、画面上に data 属性などで
            // 予定場所が埋め込まれていることを想定します。body に data-scheduled-location がある場合それを使います。
            const scheduled = document.body.dataset.scheduledLocation || null; // 'office'|'home' など

            const scheduledLabel =
                scheduled === "remote"
                    ? "在宅"
                    : scheduled === "office"
                    ? "出勤"
                    : "（予定なし）";

            const confirmed = confirm(
                `現在の予定では ${scheduledLabel} となっています。出勤場所を選択しますか？\n「はい」で出勤場所を選択します。\n「いいえ」はキャンセル（何もしません）。`
            );

            if (!confirmed) {
                // ユーザーが「いいえ」を選んだ場合はキャンセル（何もしない）
                return;
            }

            // (旧動作) 確認ダイアログを表示してからモーダルを開くフローは現在使用しない。
            // 直接モーダルを開くユーティリティを呼び出す。
            await this.openLocationModal();
        },

        async openLocationModal() {
            const scheduled = document.body.dataset.scheduledLocation || null; // 'office'|'remote' など

            const modal = document.getElementById("attendance-location-modal");
            const form = document.getElementById("attendance-location-form");
            const cancelBtn = document.getElementById(
                "attendance-location-cancel"
            );

            if (!modal || !form) {
                // フォールバック: prompt を使う
                const newLocation = prompt(
                    "出勤する場所を入力してください（例: office または remote）",
                    scheduled || "office"
                );
                if (!newLocation) return;
                await this.postChangeLocationAndClockIn(newLocation);
                return;
            }

            // 開く
            modal.style.display = "flex";

            const closeModal = () => {
                modal.style.display = "none";
                form.removeEventListener("submit", onSubmit);
                cancelBtn.removeEventListener("click", onCancel);
            };

            const onCancel = (e) => {
                e.preventDefault();
                closeModal();
            };

            const onSubmit = async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                const chosen = fd.get("attendance_location");
                if (!chosen) return;
                closeModal();
                await this.postChangeLocationAndClockIn(chosen);
            };

            form.addEventListener("submit", onSubmit);
            cancelBtn.addEventListener("click", onCancel);
        },

        async postChangeLocationAndClockIn(newLocation) {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");
            try {
                const today = new Date().toISOString().slice(0, 10); // YYYY-MM-DD
                const res = await fetch(
                    "/attendance/change-location-on-clockin",
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": csrfToken,
                            Accept: "application/json",
                        },
                        body: JSON.stringify({
                            date: today,
                            location: newLocation,
                        }),
                    }
                );

                const data = await res.json();
                if (!res.ok)
                    throw new Error(data.message || "場所変更に失敗しました");

                // 成功メッセージは一つにまとめて表示する
                const messages = [];
                if (data.message) messages.push(data.message);
                if (data.transportation_created) {
                    // 交通費が作成された場合はその旨を追加
                    messages.push("交通費の登録を行いました。");
                } else if (data.note) {
                    // 交通費作成がなかった場合に注意書きがあれば表示
                    messages.push(data.note);
                }
                if (messages.length) {
                    alert(messages.join("\n"));
                }
                await this.clock("clock_in");
            } catch (e) {
                console.error("change-location error", e);
                alert("場所変更中にエラーが発生しました: " + e.message);
            }
        },
    }));
});
