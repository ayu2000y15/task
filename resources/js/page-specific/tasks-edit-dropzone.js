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
        maxFilesize: 20, // MB単位
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
            this.on("error", function (file, errorMessage) {
                console.error("ファイルアップロードエラー:", errorMessage);
                // ユーザーにエラーを通知
                const message =
                    typeof errorMessage.message === "string"
                        ? errorMessage.message
                        : errorMessage.error || "不明なエラーが発生しました。";
                alert(`エラー: ${message}`);
                // エラーになったプレビューを消去
                this.removeFile(file);
            });
        },
    });
}

// ページ読み込み完了時に初期化処理を実行
initializeEditDropzone();
