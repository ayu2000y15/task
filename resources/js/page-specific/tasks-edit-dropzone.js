// resources/js/page-specific/tasks-edit-dropzone.js

import Dropzone from "dropzone";
import axios from "axios";

/**
 * 工程編集ページ用のDropzoneを初期化します。
 */
function initializeEditDropzone() {
    const dropzoneElement = document.getElementById(
        "file-upload-dropzone-edit"
    );
    const formPageElement = document.getElementById("task-form-page");
    const fileListElement = document.getElementById("file-list-edit");
    const loaderOverlay = document.getElementById("upload-loader-overlay");

    // 必要な要素がページに存在しない場合は処理を中断
    if (!dropzoneElement || !formPageElement || !fileListElement) {
        return;
    }

    // Dropzoneの自動検出を無効化
    Dropzone.autoDiscover = false;

    // フォームからプロジェクトIDとタスクIDを取得
    const projectId = formPageElement.dataset.projectId;
    const taskId = formPageElement.dataset.taskId;

    if (!projectId || !taskId) {
        console.error(
            "Project ID or Task ID is missing from the page data attributes."
        );
        return;
    }

    const uploadUrl = `/projects/${projectId}/tasks/${taskId}/files`;
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    const myDropzone = new Dropzone(dropzoneElement, {
        url: uploadUrl,
        headers: {
            "X-CSRF-TOKEN": csrfToken,
        },
        paramName: "file", // サーバーサイドでファイルを受け取るためのキー
        maxFilesize: 100, // MB単位
        addRemoveLinks: true,
        dictDefaultMessage:
            "ここにファイルをドラッグ＆ドロップ<br>またはクリックしてファイルを選択",
        dictRemoveFile: "×",
        dictCancelUpload: "-",
        dictFileTooBig:
            "ファイルが大きすぎます ({{filesize}}MB)。最大サイズ: {{maxFilesize}}MB。",
        dictInvalidFileType: "このファイル形式はアップロードできません。",

        // 初期化処理
        init: function () {
            // ファイル送信開始時のイベント
            this.on("sending", function (file) {
                if (loaderOverlay) {
                    loaderOverlay.classList.remove("hidden");
                }
            });

            // 全てのファイル処理完了時のイベント
            this.on("queuecomplete", function () {
                if (loaderOverlay) {
                    loaderOverlay.classList.add("hidden");
                }
            });
            // アップロード成功時のイベント
            this.on("success", function (file, response) {
                // サーバーからのレスポンスに成功フラグとHTMLが含まれていると仮定
                if (response.success && response.html) {
                    // ファイルリスト部分をサーバーから返却されたHTMLで更新
                    fileListElement.innerHTML = response.html;
                } else {
                    console.error(
                        "ファイルのアップロードには成功しましたが、サーバーからの応答が不正です。",
                        response
                    );
                    alert(
                        "ファイルの反映に失敗しました。ページをリロードしてください。"
                    );
                }
                // 処理が完了したらプレビューを消去
                this.removeFile(file);
            });

            // アップロード失敗時のイベント
            this.on("error", function (file, error) {
                console.error("ファイルアップロードエラー:", {
                    file: file.name,
                    error: error,
                });

                let message = `「${file.name}」のアップロードに失敗しました。\n\n`;

                if (typeof error === "string") {
                    // Dropzoneが単純な文字列エラーを返す場合 (例: ファイルサイズ超過など)
                    message += `理由: ${error}`;
                } else if (error.error) {
                    // サーバーが { "error": "メッセージ" } 形式のJSONを返した場合
                    message += `理由: ${error.error}`;
                } else if (file.xhr && file.xhr.statusText) {
                    // サーバーからのHTTPエラーステータスがある場合 (500 Internal Server Errorなど)
                    message += `サーバーエラー: ${file.xhr.status} ${file.xhr.statusText}`;
                } else {
                    // 上記のいずれにも当てはまらない場合
                    message +=
                        "サーバーに接続できないか、予期せぬエラーが発生しました。";
                }

                alert(message);
                this.removeFile(file);
            });
        },
    });
}

// ページ読み込み完了時に初期化処理を実行
initializeEditDropzone();
