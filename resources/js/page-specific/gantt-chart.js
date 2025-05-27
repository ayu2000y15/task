// resources/js/page-specific/gantt-chart.js
import Dropzone from "dropzone";
import axios from "axios"; // bootstrap.jsでwindow.axiosが設定されている場合、またはここで明示的に使用する場合

// Dropzoneの自動検出をグローバルに一度だけ設定するのが望ましい
// もしapp.jsやbootstrap.jsで行っていない場合はここで設定
if (typeof Dropzone !== "undefined" && Dropzone.autoDiscover !== undefined) {
    Dropzone.autoDiscover = false;
}

const overlay = document.getElementById("upload-loading-overlay");

document.addEventListener("alpine:init", () => {
    Alpine.store("ganttDropzone", {
        instance: null,
        currentProjectId: null,
        currentTaskId: null,
        init(projectId, taskId) {
            this.currentProjectId = projectId;
            this.currentTaskId = taskId;
            const dropzoneElement = document.getElementById(
                "gantt-file-upload-dropzone-alpine"
            );
            if (!dropzoneElement) {
                console.warn(
                    "Dropzone element #gantt-file-upload-dropzone-alpine not found!"
                );
                return;
            }

            const csrfTokenEl = document.querySelector(
                'meta[name="csrf-token"]'
            );
            if (!csrfTokenEl || !csrfTokenEl.getAttribute("content")) {
                console.error("CSRF token not found for Gantt Dropzone.");
                return;
            }
            const uploadUrl = `/projects/${this.currentProjectId}/tasks/${this.currentTaskId}/files`;

            if (this.instance) {
                this.instance.destroy(); // 既存のインスタンスがあれば破棄
            }

            const clickableButton = dropzoneElement.querySelector(
                ".dz-button-bootstrap"
            );

            this.instance = new Dropzone(dropzoneElement, {
                url: uploadUrl,
                method: "post",
                clickable: clickableButton || true, // ボタンがなくても機能するようにフォールバック
                paramName: "file",
                maxFilesize: 100, // MB
                addRemoveLinks: true,
                dictRemoveFile: "×", // 削除ボタンのテキスト
                headers: {
                    "X-CSRF-TOKEN": csrfTokenEl.getAttribute("content"),
                },
                autoProcessQueue: false, // 手動でキューを処理
                init: function () {
                    this.on("success", (file, response) => {
                        Alpine.store("ganttDropzone").fetchFiles(); // ストアのIDを使用
                        this.removeFile(file); // アップロード成功後、プレビューからファイルを削除
                    });
                    this.on("error", (file, message) => {
                        let errorMessage = "アップロードに失敗しました。";
                        if (typeof message === "string") errorMessage = message;
                        else if (
                            message &&
                            message.errors &&
                            message.errors.file
                        )
                            errorMessage = message.errors.file[0];
                        else if (message && message.message)
                            errorMessage = message.message;

                        // エラーがオブジェクトで詳細なメッセージを持つ場合
                        if (
                            typeof message === "object" &&
                            message !== null &&
                            message.message
                        ) {
                            errorMessage = message.message;
                        }

                        alert("エラー: " + errorMessage);
                        this.removeFile(file); // エラー発生後、プレビューからファイルを削除
                        if (overlay) overlay.style.display = "none";
                    });
                    this.on("queuecomplete", () => {
                        if (overlay) overlay.style.display = "none";
                        // キュー完了時の追加ロジック (例: 全ファイル成功/失敗のアラート)
                        if (
                            this.getQueuedFiles().length === 0 &&
                            this.getUploadingFiles().length === 0
                        ) {
                            if (
                                this.getRejectedFiles().length > 0 ||
                                this.getFilesWithStatus(Dropzone.ERROR).length >
                                    0
                            ) {
                                // console.warn('一部のファイルのアップロードに失敗しました。');
                            } else {
                                // console.log('すべてのファイルのアップロードが完了しました。');
                            }
                        }
                    });
                    this.on("processingmultiple", () => {
                        if (overlay) overlay.style.display = "flex";
                    });
                    this.on("sendingmultiple", () => {
                        // 複数ファイル送信開始時
                        if (overlay) overlay.style.display = "flex";
                    });
                },
            });
        },
        fetchFiles(projectId, taskId) {
            const pId = projectId || this.currentProjectId;
            const tId = taskId || this.currentTaskId;
            if (!pId || !tId) {
                console.warn(
                    "Project ID or Task ID not available for fetching files."
                );
                return;
            }

            const fileListEl = document.getElementById(
                "gantt-uploaded-file-list-alpine"
            );
            if (!fileListEl) {
                console.warn(
                    "File list element #gantt-uploaded-file-list-alpine not found!"
                );
                return;
            }
            fileListEl.innerHTML =
                '<li class="p-3 text-center text-sm text-gray-500 dark:text-gray-400">ファイルを読み込み中...</li>';

            axios
                .get(`/projects/${pId}/tasks/${tId}/files`)
                .then((response) => {
                    fileListEl.innerHTML = response.data;
                })
                .catch((error) => {
                    fileListEl.innerHTML =
                        '<li class="p-3 text-center text-sm text-red-600 dark:text-red-400">ファイル一覧の取得に失敗しました。</li>';
                    console.error(
                        "Error fetching files for Gantt modal:",
                        error
                    );
                });
        },
        processQueue() {
            if (overlay) overlay.style.display = "flex";
            if (this.instance && this.instance.getQueuedFiles().length > 0) {
                this.instance.processQueue();
            } else {
                if (overlay) overlay.style.display = "none";
                alert("アップロードするファイルが選択されていません。");
            }
        },
        removeAllFiles() {
            if (this.instance) {
                this.instance.removeAllFiles(true); // Dropzoneからファイルを削除
            }
            // モーダル内のファイルリスト表示もクリア（任意で）
            const fileListEl = document.getElementById(
                "gantt-uploaded-file-list-alpine"
            );
            if (fileListEl) {
                fileListEl.innerHTML =
                    '<li class="p-3 text-center text-sm text-gray-500 dark:text-gray-400">ファイルがありません。</li>';
            }
        },
    });
});

// jQuery依存のコードは、bootstrap.jsで window.$ が正しく設定された後に実行されることを想定しています。
// もし `$` is not a function のエラーが継続する場合、`bootstrap.js`でのjQueryのグローバル登録を再度確認してください。
if (typeof $ !== "undefined") {
    $(document).ready(function () {
        const ganttTable = $("#ganttTable");
        if (ganttTable.length === 0) {
            // console.log('Gantt table not found, exiting Gantt specific jQuery initializations.');
            return;
        }

        // 子要素の開閉ロジック
        $(document).on("click", ".toggle-children", function () {
            const $toggleSpan = $(this);
            const $icon = $toggleSpan.find("i");
            const isExpanded = $icon.hasClass("fa-chevron-down");
            const $parentRow = $toggleSpan.closest("tr");
            let $directChildrenToToggle;

            if ($parentRow.hasClass("project-header")) {
                const projectId = $toggleSpan.data("project-id");
                $directChildrenToToggle = $(
                    `tr.project-${projectId}-tasks.task-level-0:not(.project-header)`
                );
            } else if ($parentRow.hasClass("character-header")) {
                const characterId = $toggleSpan.data("character-id");
                const projectIdOfChar = $toggleSpan.data("project-id-of-char");
                $directChildrenToToggle = $(
                    `tr.project-${projectIdOfChar}-tasks.task-parent-char-${characterId}.task-level-1`
                );
            } else {
                const taskId = $toggleSpan.data("task-id");
                if (taskId) {
                    $directChildrenToToggle = $(`tr.task-parent-${taskId}`);
                }
            }

            if (
                !$directChildrenToToggle ||
                $directChildrenToToggle.length === 0
            ) {
                if (isExpanded)
                    $icon
                        .removeClass("fa-chevron-down")
                        .addClass("fa-chevron-right");
                else
                    $icon
                        .removeClass("fa-chevron-right")
                        .addClass("fa-chevron-down");
                return;
            }

            if (isExpanded) {
                $icon
                    .removeClass("fa-chevron-down")
                    .addClass("fa-chevron-right");
                function closeAllDescendants($elements) {
                    $elements.each(function () {
                        const $currentElement = $(this);
                        $currentElement.hide();
                        const $toggleSpanOnCurrentElement = $currentElement
                            .find(".toggle-children")
                            .first();
                        if ($toggleSpanOnCurrentElement.length > 0) {
                            $toggleSpanOnCurrentElement
                                .find("i")
                                .removeClass("fa-chevron-down")
                                .addClass("fa-chevron-right");
                            let $grandChildren;
                            const characterId =
                                $toggleSpanOnCurrentElement.data(
                                    "character-id"
                                );
                            const taskId =
                                $toggleSpanOnCurrentElement.data("task-id");
                            if (characterId) {
                                const projectIdOfChar =
                                    $toggleSpanOnCurrentElement.data(
                                        "project-id-of-char"
                                    );
                                $grandChildren = $(
                                    `tr.project-${projectIdOfChar}-tasks.task-parent-char-${characterId}.task-level-1`
                                );
                            } else if (taskId) {
                                $grandChildren = $(`tr.task-parent-${taskId}`);
                            }
                            if ($grandChildren && $grandChildren.length > 0) {
                                closeAllDescendants($grandChildren);
                            }
                        }
                    });
                }
                closeAllDescendants($directChildrenToToggle);
            } else {
                $icon
                    .removeClass("fa-chevron-right")
                    .addClass("fa-chevron-down");
                $directChildrenToToggle.show();
            }
        });

        // 「今日」へスクロール
        function scrollToToday() {
            const $todayCell = $(".gantt-cell.today").first();
            if ($todayCell.length) {
                const ganttContainer = $(".gantt-container");
                if (ganttContainer.length === 0) return;
                const stickyColWidth =
                    $(".gantt-sticky-col").first().outerWidth(true) || 0; // trueでマージン含む
                const cellOffsetLeft = $todayCell.position().left;
                // 中央ではなく、スティッキー列のすぐ右側に来るように調整
                const targetScroll = cellOffsetLeft - stickyColWidth - 20; // 20pxのオフセット
                ganttContainer.animate(
                    { scrollLeft: ganttContainer.scrollLeft() + targetScroll },
                    300
                );
            }
        }
        $("#todayBtn").on("click", scrollToToday);

        // ステータス変更時のAJAX処理
        $(document).on("change", ".status-select", function () {
            const taskId = $(this).data("task-id");
            const projectId = $(this).data("project-id");
            const status = $(this).val();
            let progress = 0;
            if (status === "completed") progress = 100;
            else if (status === "not_started" || status === "cancelled")
                progress = 0;

            axios
                .post(
                    `/projects/${projectId}/tasks/${taskId}/progress`,
                    {
                        status: status,
                        progress: progress,
                    },
                    {
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content,
                        },
                    }
                )
                .then((response) => {
                    if (!response.data.success) {
                        console.error(
                            "ステータス更新に失敗しました: " +
                                (response.data.message || "")
                        );
                        // 必要に応じてユーザーにエラー通知やUIのロールバック
                    }
                    location.reload(); // UI全体を更新（より洗練された方法も検討可）
                })
                .catch((error) => {
                    console.error(
                        "ステータス更新中にエラーが発生しました。",
                        error
                    );
                    // 必要に応じてユーザーにエラー通知
                });
        });

        // 担当者のインライン編集
        $(document).on(
            "click",
            '.editable-cell[data-field="assignee"]',
            function () {
                const cell = $(this);
                if (cell.find("input").length) return; // 既に編集中なら何もしない

                const originalValue =
                    cell.find("span").text().trim() === "-"
                        ? ""
                        : cell.find("span").text().trim();
                const taskId = cell.data("task-id");
                const projectId = cell.data("project-id");

                cell.find("span").hide(); // 元のテキストを隠す
                const input = $(
                    `<input type="text" class="form-input text-sm p-1 border border-blue-500 rounded dark:bg-gray-700 dark:text-gray-200 w-full" value="${originalValue}">`
                );
                cell.append(input);
                input.focus().select();

                function saveChanges() {
                    const newValue = input.val().trim();
                    input.remove(); // inputを削除
                    cell.find("span")
                        .text(newValue || "-")
                        .show(); // 新しい値でテキストを更新して表示

                    if (newValue !== originalValue) {
                        axios
                            .post(
                                `/projects/${projectId}/tasks/${taskId}/assignee`,
                                {
                                    assignee: newValue,
                                },
                                {
                                    headers: {
                                        "X-CSRF-TOKEN": document.querySelector(
                                            'meta[name="csrf-token"]'
                                        ).content,
                                    },
                                }
                            )
                            .then((response) => {
                                if (!response.data.success) {
                                    console.error(
                                        "担当者の更新に失敗しました: " +
                                            (response.data.message || "")
                                    );
                                    cell.find("span").text(
                                        originalValue || "-"
                                    ); // 失敗時は元に戻す
                                } else {
                                    cell.data("current-value", newValue); // data属性も更新
                                }
                            })
                            .catch((error) => {
                                console.error(
                                    "担当者更新中にエラーが発生しました。",
                                    error
                                );
                                cell.find("span").text(originalValue || "-"); // エラー時も元に戻す
                            });
                    }
                }
                input.on("blur", saveChanges);
                input.on("keydown", function (e) {
                    if (e.key === "Enter") {
                        e.preventDefault();
                        saveChanges();
                    } else if (e.key === "Escape") {
                        input.val(originalValue);
                        saveChanges();
                    } // Escapeでキャンセル
                });
            }
        );

        // モーダル内のファイル削除ボタンの処理
        $(document).on(
            "click",
            "#gantt-uploaded-file-list-alpine .delete-file-btn",
            function (e) {
                e.preventDefault();
                const button = $(this);
                const url = button.data("url");
                if (!url) {
                    console.error(
                        "Delete URL not found on Gantt delete button"
                    );
                    return;
                }
                if (confirm("本当にこのファイルを削除しますか？")) {
                    axios
                        .delete(url, {
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector(
                                    'meta[name="csrf-token"]'
                                ).content,
                            },
                        })
                        .then(function (response) {
                            if (response.data.success) {
                                const itemToRemove = button.closest("li");
                                if (itemToRemove) itemToRemove.remove();
                                const fileListEl = document.getElementById(
                                    "gantt-uploaded-file-list-alpine"
                                );
                                if (
                                    fileListEl &&
                                    fileListEl.children.length === 0
                                ) {
                                    fileListEl.innerHTML =
                                        '<li class="p-3 text-center text-sm text-gray-500 dark:text-gray-400">アップロードされたファイルはありません。</li>';
                                }
                            } else {
                                alert(
                                    "ファイルの削除に失敗しました。\n" +
                                        (response.data.message || "")
                                );
                            }
                        })
                        .catch(function (error) {
                            alert("ファイルの削除中にエラーが発生しました。");
                            console.error(error);
                        });
                }
            }
        );

        // モーダルが閉じたときのイベント (Alpine.jsのイベントをリッスン)
        window.addEventListener("close-modal", (event) => {
            if (event.detail.name === "ganttFileUploadModal") {
                if (
                    Alpine.store("ganttDropzone") &&
                    Alpine.store("ganttDropzone").instance
                ) {
                    // Dropzoneのファイルをクリアする（removeAllFilesはファイルプレビューとキューをクリア）
                    Alpine.store("ganttDropzone").removeAllFiles();
                }
            }
        });
    });
} else {
    console.log(
        "jQuery is not loaded. Some Gantt chart interactions might not work. Ensure jQuery is globally available before this script runs, typically via bootstrap.js."
    );
}
