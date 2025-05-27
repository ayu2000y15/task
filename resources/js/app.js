// resources/js/app.js
import "./bootstrap";

import Alpine from "alpinejs";
import collapse from "@alpinejs/collapse"; // x-collapseのため
// 他のAlpine.jsプラグインやカスタムストアがあればここに

import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import listPlugin from "@fullcalendar/list";
import interactionPlugin from "@fullcalendar/interaction";

window.Alpine = Alpine;
Alpine.plugin(collapse); // x-collapseのため
Alpine.start();

// Global features (例)
import {
    initializeTaskTooltips,
    initializeImagePreviewModal,
} from "./features/global-tooltips.js";
import "./features/file-deleter.js";

document.addEventListener("DOMContentLoaded", () => {
    initializeTaskTooltips();
    initializeImagePreviewModal(/* ...args... */);

    // --- Page-specific JavaScript ---
    if (
        document.querySelector(".task-status-select, .editable-cell") &&
        !document.getElementById("ganttTable")
    ) {
        // ガントページではtasks-indexをロードしないように調整
        import("./page-specific/tasks-index.js").catch((error) =>
            console.error("Error loading tasks-index.js:", error)
        );
    }
    if (document.getElementById("project-show-main-container")) {
        import("./page-specific/projects-show.js").catch((error) =>
            console.error("Error loading projects-show.js:", error)
        );
    }
    if (document.getElementById("project-form-page")) {
        import("./page-specific/projects-form.js").catch((error) =>
            console.error("Error loading projects-form.js:", error)
        );
    }
    // 編集済み: 工程作成・編集ページ用の tasks-form.js を修正
    const taskFormPageElement = document.getElementById("task-form-page");
    if (taskFormPageElement) {
        import("./page-specific/tasks-form.js")
            .then((module) => {
                // tasks-form.js がエクスポートする初期化関数がある場合 (例: initializeTaskFormEventListeners)
                // module.default.initializeTaskFormEventListeners(); // または module.initializeTaskFormEventListeners();
            })
            .catch((error) =>
                console.error("Error loading tasks-form.js:", error)
            );

        // 工程編集ページで、かつファイルアップロード要素が存在する場合のみDropzoneのJSを読み込む
        if (
            taskFormPageElement.dataset.taskId &&
            document.getElementById("file-upload-dropzone-edit")
        ) {
            // data-task-id があるかで編集ページかを判定
            import("./page-specific/tasks-edit-dropzone.js").catch((error) =>
                console.error("Error loading tasks-edit-dropzone.js:", error)
            );
        }
    }

    // ★★★ ガントチャート専用JSの読み込みを追加 ★★★
    if (document.getElementById("ganttTable")) {
        // ガントチャートテーブルのIDをトリガーにする
        import("./page-specific/gantt-chart.js").catch((error) =>
            console.error("Error loading gantt-chart.js:", error)
        );
    }

    // カレンダー機能の初期化
    // 1. イベントリスナーが動作しているか確認

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

            locale: "ja", // 1. 表示を日本語化
            dayMaxEvents: 2, // 2. １日に表示するイベント数を2件に制限（それ以上は「+他n件」と表示）

            moreLinkContent: function (args) {
                return "+ 他" + args.num + "件";
            },

            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,listMonth",
            },
            buttonText: {
                // ボタンのテキストも明示的に日本語に設定
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
});
