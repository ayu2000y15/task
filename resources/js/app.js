// resources/js/app.js
import "./bootstrap";

import Alpine from "alpinejs";
import collapse from "@alpinejs/collapse";

// 他のAlpine.jsプラグインやカスタムストアがあればここに

// ★ Sortable.jsのような外部ライブラリをここでグローバルにインポートするか、
// ★ または各専用モジュール（下記参照）で個別にインポートします。
// import Sortable from 'sortablejs'; // もしグローバルに使いたい場合
// window.Sortable = Sortable; // 更にグローバルにする場合

import { Calendar } from "@fullcalendar/core"; //
import dayGridPlugin from "@fullcalendar/daygrid"; //
import listPlugin from "@fullcalendar/list"; //
import interactionPlugin from "@fullcalendar/interaction"; //

window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.start();

// Global features (例)
import {
    initializeTaskTooltips,
    initializeImagePreviewModal,
} from "./features/global-tooltips.js"; //
import "./features/file-deleter.js"; //
if (document.getElementById("feedback-table")) {
    // feedback-table IDを持つ要素が存在するか
    console.log("Attempting to load admin-feedbacks-index.js"); // このログが出るか
    import("./page-specific/admin-feedbacks-index.js")
        .then((module) => {
            console.log("admin-feedbacks-index.js loaded successfully."); // このログが出るか
        })
        .catch((error) =>
            console.error("Error loading admin-feedbacks-index.js:", error)
        );
}
document.addEventListener("DOMContentLoaded", () => {
    // console.log('[APP.JS] DOMContentLoaded event fired.');

    initializeTaskTooltips(); //
    initializeImagePreviewModal(
        //
        "imagePreviewModalGlobal",
        "previewImageFullGlobal",
        "closePreviewModalBtnGlobal",
        "preview-image"
    );

    // --- Page-specific JavaScript ---
    if (
        document.querySelector(".task-status-select, .editable-cell") &&
        !document.getElementById("ganttTable")
    ) {
        import("./page-specific/tasks-index.js").catch(
            (
                error //
            ) => console.error("Error loading tasks-index.js:", error)
        );
    }
    if (document.getElementById("project-show-main-container")) {
        import("./page-specific/projects-show.js").catch(
            (
                error //
            ) => console.error("Error loading projects-show.js:", error)
        );
    }
    if (document.getElementById("project-form-page")) {
        import("./page-specific/projects-form.js").catch(
            (
                error //
            ) => console.error("Error loading projects-form.js:", error)
        );
    }
    const taskFormPageElement = document.getElementById("task-form-page"); //
    if (taskFormPageElement) {
        import("./page-specific/tasks-form.js") //
            .catch((error) =>
                console.error("Error loading tasks-form.js:", error)
            );

        if (
            taskFormPageElement.dataset.taskId &&
            document.getElementById("file-upload-dropzone-edit")
        ) {
            import("./page-specific/tasks-edit-dropzone.js").catch(
                (
                    error //
                ) =>
                    console.error(
                        "Error loading tasks-edit-dropzone.js:",
                        error
                    )
            );
        }

        // Admin Feedbacks Index Page
        if (document.getElementById("feedback-table")) {
            import("./page-specific/admin-feedbacks-index.js").catch(
                (
                    error //
                ) =>
                    console.error(
                        "Error loading admin-feedbacks-index.js:",
                        error
                    )
            );
        }
    }

    // Admin Feedback Categories Sortable
    if (document.getElementById("sortable-feedback-categories")) {
        import("./admin/feedback-categories-sortable.js").catch((error) =>
            console.error(
                "Error loading feedback-categories-sortable.js:",
                error
            )
        );
    }

    if (document.getElementById("ganttTable")) {
        //
        import("./page-specific/gantt-chart.js").catch(
            (
                error //
            ) => console.error("Error loading gantt-chart.js:", error)
        );
    }

    // ★★★ カスタム項目定義の並び替え機能の初期化 ★★★
    if (document.getElementById("sortable-definitions")) {
        // console.log('[APP.JS] Found sortable-definitions element, attempting to load module.');
        import("./admin/form-definitions-sortable.js")
            .then((module) => {
                if (module.initFormDefinitionSortable) {
                    // console.log('[APP.JS] form-definitions-sortable.js module loaded, calling initFormDefinitionSortable.');
                    module.initFormDefinitionSortable();
                } else {
                    console.error(
                        "[APP.JS] initFormDefinitionSortable function not found in form-definitions-sortable.js module."
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
    // ★★★ ここまで追加 ★★★

    const calendarEl = document.getElementById("calendar"); //

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
            //
            plugins: [dayGridPlugin, listPlugin, interactionPlugin], //
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
                //
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
                //
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
});
