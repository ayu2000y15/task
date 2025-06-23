// resources/js/page-specific/project-show-tasks.js

import axios from "axios";
import { handleFormError, getCsrfToken } from "./project-show-utils"; // 既存のユーティリティを再利用

function initializeTaskForms(container) {
    const forms = container.querySelectorAll('form[id^="task-form-"]');

    forms.forEach((form) => {
        form.addEventListener("submit", (event) => {
            event.preventDefault();

            const characterIdInput = form.querySelector(
                'input[name="character_id"]'
            );
            const characterId = characterIdInput ? characterIdInput.value : "";

            const errorDivId = `task-form-errors-${characterId || "project"}`;
            const errorDiv = document.getElementById(errorDivId);
            if (errorDiv) errorDiv.innerHTML = "";

            const projectId = document.getElementById(
                "project-show-main-container"
            ).dataset.projectId;
            const actionUrl = `/projects/${projectId}/tasks`;
            const formData = new FormData(form);

            axios
                .post(actionUrl, formData, {
                    headers: {
                        "X-CSRF-TOKEN": getCsrfToken(),
                        Accept: "application/json",
                    },
                })
                .then((response) => {
                    if (response.data.success) {
                        const tableId = characterId
                            ? `character-tasks-table-${characterId}`
                            : "project-tasks-table";
                        const tbody = document.querySelector(
                            `#${tableId} tbody`
                        );

                        if (tbody) {
                            const emptyRow = tbody.querySelector("td[colspan]");
                            if (emptyRow) emptyRow.parentElement.remove();
                            tbody.insertAdjacentHTML(
                                "beforeend",
                                response.data.html
                            );
                        }
                        form.reset();
                    }
                })
                .catch((error) => {
                    handleFormError(
                        error.response,
                        "登録に失敗しました。",
                        errorDivId,
                        error.response?.data?.errors
                    );
                });
        });
    });
}

/**
 * 案件詳細ページのモーダルから呼ばれる一括登録フォームの初期化関数
 * (★ データ収集方法を FormData を使う安定した方式に修正)
 */
function initializeBatchTaskRegistration() {
    const form = document.getElementById("batch-task-form");
    if (!form) return;

    // Alpineコンポーネントのルート要素はモーダルを閉じるために取得しておく
    const alpineComponent = form.closest("[x-data]");

    form.addEventListener("submit", (event) => {
        event.preventDefault();

        const errorDiv = document.getElementById("batch-task-form-errors");
        if (errorDiv) errorDiv.innerHTML = "";

        const projectId = document.getElementById("project-show-main-container")
            .dataset.projectId;
        const actionUrl = `/projects/${projectId}/tasks/batch`;

        // ★★★ 修正箇所 ★★★
        // Alpine.jsの内部プロパティ(__x)に依存せず、標準のFormData APIを使用します。
        // これにより、JavaScriptエラーが解消され、コードが安定します。
        const formData = new FormData(form);

        axios
            .post(actionUrl, formData, {
                // formDataを直接送信
                headers: {
                    "X-CSRF-TOKEN": getCsrfToken(),
                    Accept: "application/json",
                },
            })
            .then((response) => {
                if (response.data.success) {
                    const createdTasks = response.data.tasks || [];
                    const taskHtmlMap = {};

                    if (response.data.html) {
                        createdTasks.forEach((task) => {
                            const regex = new RegExp(
                                `<tr id="task-row-${task.id}"[\\s\\S]*?<\\/tr>`
                            );
                            const match = response.data.html.match(regex);
                            if (match) {
                                taskHtmlMap[task.id] = match[0];
                            }
                        });
                    }

                    createdTasks.forEach((task) => {
                        const tableId = task.character_id
                            ? `character-tasks-table-${task.character_id}`
                            : "project-tasks-table";
                        const tbody = document.querySelector(
                            `#${tableId} tbody`
                        );

                        if (tbody && taskHtmlMap[task.id]) {
                            const emptyRow = tbody.querySelector("td[colspan]");
                            if (emptyRow) emptyRow.parentElement.remove();
                            tbody.insertAdjacentHTML(
                                "beforeend",
                                taskHtmlMap[task.id]
                            );
                        }
                    });

                    if (alpineComponent) {
                        // モーダルを閉じるイベントを発行
                        alpineComponent.dispatchEvent(
                            new CustomEvent("close-modal", {
                                detail: "batch-task-modal",
                                bubbles: true,
                            })
                        );
                    }
                    alert(response.data.message);
                }
            })
            .catch((error) => {
                handleFormError(
                    error.response,
                    "一括登録に失敗しました。",
                    "batch-task-form-errors",
                    error.response?.data?.errors
                );
            });
    });
}

// project-show-main.js から呼び出せるように export
export { initializeTaskForms, initializeBatchTaskRegistration };
