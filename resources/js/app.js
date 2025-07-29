// resources/js/app.js
import "./bootstrap";

import Alpine from "alpinejs";
import collapse from "@alpinejs/collapse";
import Sortable from "sortablejs";
import focus from "@alpinejs/focus";

// 他のAlpine.jsプラグインや案件依頼ストアがあればここに

import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import listPlugin from "@fullcalendar/list";
import interactionPlugin from "@fullcalendar/interaction";
import TomSelect from "tom-select";
window.TomSelect = TomSelect;

window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.start();

// Global features
import {
    initializeTaskTooltips,
    initializeImagePreviewModal,
} from "./features/global-tooltips.js";
import "./features/file-deleter.js";

import { initializeWorkTimers } from "./features/work-timer.js";
import "./features/attendance-timer.js";

if (document.getElementById("feedback-table")) {
    import("./page-specific/admin-feedbacks-index.js").catch((error) =>
        console.error("Error loading admin-feedbacks-index.js:", error)
    );
}

document.addEventListener("DOMContentLoaded", () => {
    initializeTaskTooltips();
    initializeImagePreviewModal(
        "imagePreviewModalGlobal",
        "previewImageFullGlobal",
        "closePreviewModalBtnGlobal",
        "preview-image"
    );
    initializeWorkTimers();

    // --- Page-specific JavaScript ---
    if (
        (document.querySelector(".task-status-select") ||
            document.querySelector("[class*='editable-cell']")) &&
        !document.getElementById("ganttTable")
    ) {
        import("./page-specific/tasks-index.js")
            .then((module) => {
                if (module.default) {
                    module.default(); // initTasksIndex() を実行
                }
            })
            .catch((error) =>
                console.error(
                    "Error loading or initializing tasks-index.js:",
                    error
                )
            );
    }

    // ★修正箇所: 案件詳細ページ専用のJSを動的に読み込むように修正
    if (document.getElementById("project-show-main-container")) {
        import("./page-specific/project-show-main.js").catch((error) =>
            console.error("Error loading project-show-main.js:", error)
        );
    }

    if (document.getElementById("project-form-page")) {
        import("./page-specific/projects-form.js").catch((error) =>
            console.error("Error loading projects-form.js:", error)
        );
    }
    const taskFormPageElement = document.getElementById("task-form-page");
    if (taskFormPageElement) {
        import("./page-specific/tasks-form.js").catch((error) =>
            console.error("Error loading tasks-form.js:", error)
        );

        if (
            taskFormPageElement.dataset.taskId &&
            document.getElementById("file-upload-dropzone-edit")
        ) {
            import("./page-specific/tasks-edit-dropzone.js").catch((error) =>
                console.error("Error loading tasks-edit-dropzone.js:", error)
            );
        }

        if (document.getElementById("feedback-table")) {
            import("./page-specific/admin-feedbacks-index.js").catch((error) =>
                console.error("Error loading admin-feedbacks-index.js:", error)
            );
        }
    }

    if (document.getElementById("sortable-feedback-categories")) {
        import("./admin/feedback-categories-sortable.js").catch((error) =>
            console.error(
                "Error loading feedback-categories-sortable.js:",
                error
            )
        );
    }

    if (document.getElementById("ganttTable")) {
        import("./page-specific/gantt-chart.js").catch((error) =>
            console.error("Error loading gantt-chart.js:", error)
        );
    }

    if (document.getElementById("sortable-definitions")) {
        import("./admin/form-definitions-sortable.js")
            .then((module) => {
                if (module.initFormDefinitionSortable) {
                    module.initFormDefinitionSortable();
                } else {
                    console.error(
                        "initFormDefinitionSortable function not found in form-definitions-sortable.js module."
                    );
                }
            })
            .catch((error) =>
                console.error(
                    "Error loading form-definitions-sortable.js:",
                    error
                )
            );
    }

    const calendarEl = document.getElementById("calendar");

    if (calendarEl) {
        let events = [];
        const eventsData = calendarEl.dataset.events;

        if (eventsData) {
            try {
                events = JSON.parse(eventsData);
            } catch (e) {
                console.error(
                    "カレンダーのイベントデータ(JSON)の解析に失敗しました。",
                    e
                );
                console.error("受信したデータ:", eventsData);
            }
        }

        const calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, listPlugin, interactionPlugin],
            locale: "ja",
            dayMaxEvents: 2,
            moreLinkContent: function (args) {
                return "+ 他" + args.num + "件";
            },
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,listMonth",
            },
            buttonText: {
                today: "今日",
                month: "月",
                list: "リスト",
            },
            initialView: "dayGridMonth",
            events: events,
            eventTimeFormat: {
                hour: "numeric",
                minute: "2-digit",
                hour12: false,
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                if (window.Livewire) {
                    Livewire.dispatch("open-modal", {
                        name: "eventDetailModal",
                        eventData: info.event.toPlainObject(),
                    });
                } else {
                    window.dispatchEvent(
                        new CustomEvent("open-modal", {
                            detail: {
                                name: "eventDetailModal",
                                eventData: info.event.toPlainObject(),
                            },
                        })
                    );
                }
            },
            dayCellClassNames: function (arg) {
                if (arg.date.getDay() === 6) {
                    return ["fc-day-sat"];
                }
                if (arg.date.getDay() === 0) {
                    return ["fc-day-sun"];
                }
                return [];
            },
        });
        calendar.render();
    }

    document.body.addEventListener("click", async function (event) {
        const reworkButton = event.target.closest(".rework-task-btn");
        if (!reworkButton) {
            return; // 「直し」ボタン以外がクリックされた場合は何もしない
        }

        event.preventDefault();

        const taskId = reworkButton.dataset.taskId;
        const taskName = reworkButton.dataset.taskName;
        const projectId = reworkButton.dataset.projectId;

        if (
            !confirm(
                `工程「${taskName}」をコピーして「直し」工程を作成します。よろしいですか？`
            )
        ) {
            return;
        }

        reworkButton.disabled = true;
        reworkButton.innerHTML = '<i class="fas fa-spinner fa-spin fa-sm"></i>';

        try {
            const url = `/projects/${projectId}/tasks/${taskId}/rework`;
            const viewContext =
                reworkButton.dataset.viewContext || "project-show";
            const response = await axios.post(url, {}); // ボディは空

            if (response.data.success) {
                alert(response.data.message);

                if (viewContext === "tasks-index") {
                    // 【工程一覧ページの場合】画面をリロードして最新の状態を再描画
                    window.location.reload();
                } else {
                    // 【案件詳細ページなど、その他の場合】これまで通り動的に行を追加
                    const parentRow = reworkButton.closest("tr");
                    if (parentRow) {
                        // 親行のUIを更新
                        const statusIconWrapper = parentRow.querySelector(
                            ".task-status-icon-wrapper"
                        );
                        if (statusIconWrapper) {
                            statusIconWrapper.innerHTML =
                                '<i class="fas fa-wrench text-orange-500" title="直し"></i>';
                        }
                        reworkButton.style.display = "none"; // ボタンを非表示に

                        // 新しい子工程の行を親行の下に挿入
                        parentRow.insertAdjacentHTML(
                            "afterend",
                            response.data.newRowHtml
                        );

                        // 新しく追加された行のJS機能を再初期化
                        if (window.initTasksIndex) initTasksIndex();
                        if (window.initializeWorkTimers) initializeWorkTimers();
                    }
                }
            } else {
                throw new Error(
                    response.data.message || "処理に失敗しました。"
                );
            }
        } catch (error) {
            console.error("Rework request failed:", error);
            alert(error.response?.data?.message || "エラーが発生しました。");
        } finally {
            // 元のボタンの状態に戻す（エラー時やユーザーがページを離れない場合のため）
            if (reworkButton) {
                reworkButton.disabled = false;
                reworkButton.innerHTML = '<i class="fas fa-wrench fa-sm"></i>';
            }
        }
    });
});
