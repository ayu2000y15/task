// ファイルアップロード処理
function initFileUpload() {
    const fileInputs = document.querySelectorAll(".file-upload-input");
    if (!fileInputs.length) return;

    fileInputs.forEach((input) => {
        const fieldName = input.id;
        const uploadArea = document.getElementById("upload-area-" + fieldName);
        const previewContainer = document.getElementById(
            "preview-" + fieldName
        );
        const isMultiple = input.hasAttribute("multiple");

        // required属性がある場合、データ属性に保存
        if (input.hasAttribute("required")) {
            input.setAttribute("data-required", "true");
        }

        // アップロードエリアをクリックしたらファイル選択ダイアログを開く
        if (uploadArea) {
            uploadArea.addEventListener("click", (e) => {
                e.stopPropagation();
                input.click();
            });

            // ドラッグ&ドロップイベント - イベント委任を使用
            const dropEvents = {
                dragenter: (e) => {
                    preventDefaults(e);
                    uploadArea.classList.add("drag-over");
                },
                dragover: (e) => {
                    preventDefaults(e);
                    uploadArea.classList.add("drag-over");
                },
                dragleave: (e) => {
                    preventDefaults(e);
                    uploadArea.classList.remove("drag-over");
                },
                drop: (e) => {
                    preventDefaults(e);
                    uploadArea.classList.remove("drag-over");

                    const dt = e.dataTransfer;
                    const files = dt.files;

                    if (isMultiple) {
                        input.files = dt.files;
                        handleFiles(files);
                    } else if (files.length > 0) {
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(files[0]);
                        input.files = dataTransfer.files;
                        handleFiles([files[0]]);
                    }
                },
            };

            // イベントリスナーを一括で設定
            Object.keys(dropEvents).forEach((event) => {
                uploadArea.addEventListener(event, dropEvents[event], false);
            });
        }

        // ファイル選択処理
        input.addEventListener("change", function () {
            if (!this.files.length) return;

            if (isMultiple) {
                handleFiles(this.files);
            } else {
                handleFiles([this.files[0]]);
            }

            // ファイルが選択されたらrequired属性を削除
            if (this.hasAttribute("data-required")) {
                this.removeAttribute("required");
            }
        });

        function handleFiles(files) {
            if (!files.length) return;

            // メモリ使用量を削減するため、一度に処理するファイル数を制限
            const maxFilesToProcess = isMultiple
                ? Math.min(files.length, 5)
                : 1;

            if (!isMultiple) {
                // 単一ファイルの場合は新しいプレビューをクリア
                const newPreviews = previewContainer.querySelectorAll(
                    ".file-preview-item.new-file"
                );
                newPreviews.forEach((preview) => preview.remove());
            }

            // ファイルを順次処理
            for (let i = 0; i < maxFilesToProcess; i++) {
                const file = files[i];
                if (!file.type.match("image.*")) continue;

                // ファイルサイズの制限（15MB以上の場合は処理しない）
                if (file.size > 15 * 1024 * 1024) {
                    alert(
                        `ファイル「${file.name}」のサイズが大きすぎます（15MB以下にしてください）`
                    );
                    continue;
                }

                createPreview(file);
            }

            // 残りのファイルがある場合は通知
            if (files.length > maxFilesToProcess) {
                alert(
                    `一度に${maxFilesToProcess}個までのファイルを処理します。残りは選択し直してください。`
                );
            }
        }

        function createPreview(file) {
            const reader = new FileReader();

            reader.onload = (e) => {
                // DOMフラグメントを使用してパフォーマンスを向上
                const fragment = document.createDocumentFragment();

                const preview = document.createElement("div");
                preview.className = "file-preview-item new-file";
                preview.dataset.filename = file.name;

                const img = document.createElement("img");
                img.src = e.target.result;
                img.className = "file-preview-image";

                const info = document.createElement("div");
                info.className = "file-preview-info";
                info.textContent = file.name;

                const size = document.createElement("div");
                size.className = "file-preview-size";
                size.textContent = formatFileSize(file.size);

                const removeBtn = document.createElement("button");
                removeBtn.className =
                    "btn btn-sm btn-danger file-preview-remove";
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.type = "button";

                // イベントリスナーを追加
                removeBtn.addEventListener("click", (e) => {
                    e.preventDefault();
                    preview.remove();

                    if (!isMultiple) {
                        input.value = "";
                        if (input.hasAttribute("data-required")) {
                            input.setAttribute("required", "");
                        }
                    } else {
                        const fileName = preview.dataset.filename;
                        const dt = new DataTransfer();
                        for (let i = 0; i < input.files.length; i++) {
                            const file = input.files[i];
                            if (file.name !== fileName) {
                                dt.items.add(file);
                            }
                        }
                        input.files = dt.files;
                        if (
                            input.files.length === 0 &&
                            input.hasAttribute("data-required")
                        ) {
                            input.setAttribute("required", "");
                        }
                    }
                });

                // 要素を追加
                preview.appendChild(img);
                preview.appendChild(info);
                preview.appendChild(size);
                preview.appendChild(removeBtn);

                fragment.appendChild(preview);
                previewContainer.appendChild(fragment);
            };

            // 画像の読み込みを開始
            reader.readAsDataURL(file);
        }
    });

    // 既存ファイル削除ボタンの処理 - イベント委任を使用
    document.addEventListener("click", (e) => {
        if (!e.target.closest(".file-delete-btn")) return;

        const button = e.target.closest(".file-delete-btn");
        e.preventDefault();

        const fieldName = button.dataset.field;
        const dataId = button.dataset.dataId;
        const index = button.dataset.index;
        const previewItem = button.closest(".file-preview-item");

        // 削除確認
        if (
            !confirm(
                "ファイルを削除してもよろしいですか？この操作は元に戻せません。"
            )
        ) {
            return;
        }

        if (!dataId) {
            // データIDがない場合は単純にプレビュー要素を削除
            previewItem.remove();
            return;
        }

        let path = location.pathname;
        path = path.substr(0, path.indexOf("/admin"));
        let url = path ? path : "";

        // APIエンドポイントを構築
        url += `/admin/content-data/delete-file/${dataId}/${fieldName}`;
        if (index !== undefined) {
            url += `/${index}`;
        }

        // ファイル削除APIを呼び出す
        fetch(url, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
                Accept: "application/json",
                "Content-Type": "application/json",
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    previewItem.remove();
                    showNotification(data.message);

                    const input = document.getElementById(fieldName);
                    if (input && input.hasAttribute("data-required")) {
                        input.setAttribute("required", "");
                    }
                } else {
                    showNotification(data.message, true);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                showNotification(
                    "ファイル削除中にエラーが発生しました。",
                    true
                );
            });
    });
}

// 配列フィールドの処理
function initArrayFields() {
    const arrayFieldContainers = document.querySelectorAll(
        ".array-field-container"
    );
    if (!arrayFieldContainers.length) return;

    // イベント委任を使用
    document.addEventListener("click", (e) => {
        // 追加ボタンの処理
        if (e.target.closest(".add-array-item")) {
            const addButton = e.target.closest(".add-array-item");
            const container = addButton.closest(".array-field-container");
            const fieldName = container.dataset.field;
            const itemsContainer = document.getElementById(
                `array-items-${fieldName}`
            );

            if (!itemsContainer) return;

            const arrayItems = JSON.parse(addButton.dataset.arrayItems || "[]");
            const itemIndex =
                itemsContainer.querySelectorAll(".array-item").length;

            let itemHtml = `
          <div class="array-item card p-3 mb-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">項目 #${itemIndex + 1}</h6>
              <button type="button" class="btn btn-sm btn-danger remove-array-item">
                <i class="fas fa-times"></i> 削除
              </button>
            </div>
        `;

            arrayItems.forEach((arrayItem) => {
                itemHtml += `
            <div class="mb-2">
              <label class="form-label">${arrayItem.name}</label>
          `;

                if (arrayItem.type === "text") {
                    itemHtml += `
              <input type="text"
                name="${fieldName}[${itemIndex}][${arrayItem.name}]"
                class="form-control"
                value="">
            `;
                } else if (arrayItem.type === "number") {
                    itemHtml += `
              <input type="number"
                name="${fieldName}[${itemIndex}][${arrayItem.name}]"
                class="form-control"
                value="">
            `;
                } else if (arrayItem.type === "boolean") {
                    itemHtml += `
              <div class="form-check">
                <input type="checkbox"
                  class="form-check-input"
                  id="${fieldName}_${itemIndex}_${arrayItem.name}"
                  name="${fieldName}[${itemIndex}][${arrayItem.name}]"
                  value="1">
                <label class="form-check-label" for="${fieldName}_${itemIndex}_${arrayItem.name}">
                  有効
                </label>
              </div>
            `;
                } else if (arrayItem.type === "date") {
                    itemHtml += `
              <input type="date"
                name="${fieldName}[${itemIndex}][${arrayItem.name}]"
                class="form-control"
                value="">
            `;
                } else if (arrayItem.type === "url") {
                    itemHtml += `
              <input type="url"
                name="${fieldName}[${itemIndex}][${arrayItem.name}]"
                class="form-control"
                value=""
                placeholder="https://example.com">
            `;
                }

                itemHtml += `
            </div>
          `;
            });

            itemHtml += `</div>`;
            itemsContainer.insertAdjacentHTML("beforeend", itemHtml);
        }

        // 削除ボタンの処理
        if (e.target.closest(".remove-array-item")) {
            const removeButton = e.target.closest(".remove-array-item");
            const arrayItem = removeButton.closest(".array-item");
            const container = arrayItem.closest(".array-field-container");

            if (container) {
                const fieldName = container.dataset.field;
                arrayItem.remove();
                updateArrayItemIndexes(fieldName);
            } else {
                // コンテナが見つからない場合は、親要素から探す
                const parentContainer = arrayItem.closest("[data-field]");
                if (parentContainer) {
                    const fieldName = parentContainer.dataset.field;
                    arrayItem.remove();
                    updateArrayItemIndexes(fieldName);
                }
            }
        }
    });
}

// ソート機能の初期化
function initSortable() {
    if (typeof window.Sortable === "undefined") return;

    const sortableList = document.getElementById("sortable-items");
    if (!sortableList) return;

    const sortDataInput = document.getElementById("sort-data");

    // Sortable.jsの初期化
    new window.Sortable(sortableList, {
        handle: ".sort-handle",
        animation: 150,
        onEnd: () => {
            updateSortOrder();
        },
    });

    // 表示順の更新
    function updateSortOrder() {
        const items = sortableList.querySelectorAll("tr");
        items.forEach((item, index) => {
            const orderSpan = item.querySelector(".sort-order");
            if (orderSpan) {
                orderSpan.textContent = index + 1;
                item.dataset.sortOrder = index + 1;
            }
        });

        updateSortData();
    }

    // ソートデータを更新する関数
    function updateSortData() {
        if (!sortDataInput) return;

        const items = sortableList.querySelectorAll("tr");
        const sortData = [];

        items.forEach((item) => {
            const id = item.dataset.id;
            const order = item.dataset.sortOrder;
            if (id && order) {
                sortData.push({ id: id, order: order });
            }
        });

        sortDataInput.value = JSON.stringify(sortData);
    }

    // 移動ボタンのイベント委任
    sortableList.addEventListener("click", (e) => {
        // 上に移動ボタン
        if (e.target.closest(".move-up-btn")) {
            const row = e.target.closest("tr");
            const prevRow = row.previousElementSibling;
            if (prevRow) {
                sortableList.insertBefore(row, prevRow);
                updateSortOrder();
            }
        }

        // 下に移動ボタン
        if (e.target.closest(".move-down-btn")) {
            const row = e.target.closest("tr");
            const nextRow = row.nextElementSibling;
            if (nextRow) {
                sortableList.insertBefore(nextRow, row);
                updateSortOrder();
            }
        }
    });

    // 初期化時にソートデータを設定
    updateSortData();
}

// 通知表示
function showNotification(message, isError = false) {
    if (!message) return;

    const notificationModalElement =
        document.getElementById("notificationModal");
    if (notificationModalElement && window.bootstrap) {
        const bootstrapModal = new window.bootstrap.Modal(
            notificationModalElement
        );
        const notificationModalBody = document.getElementById(
            "notificationModalBody"
        );

        if (notificationModalBody) {
            notificationModalBody.innerHTML = message;
            notificationModalBody.className = isError
                ? "text-danger"
                : "text-success";
            bootstrapModal.show();
        }
    } else {
        // モーダルが利用できない場合はアラートを使用
        alert(message);
    }
}

// ユーティリティ関数
function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (
        Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
    );
}

function updateArrayItemIndexes(fieldName) {
    if (!fieldName) return;

    const items = document.querySelectorAll(
        `#array-items-${fieldName} .array-item`
    );
    if (!items.length) return;

    items.forEach((item, index) => {
        // タイトルを更新
        const title = item.querySelector("h6");
        if (title) {
            title.textContent = `項目 #${index + 1}`;
        }

        // 入力フィールドの名前属性を更新
        const inputs = item.querySelectorAll("input");
        inputs.forEach((input) => {
            const name = input.name;
            if (!name) return;

            // 正規表現で現在のインデックスを抽出
            const pattern = new RegExp(`${fieldName}\\[(\\d+)\\]`);
            const match = name.match(pattern);

            if (match) {
                const oldIndex = match[1];
                const newName = name.replace(
                    `${fieldName}[${oldIndex}]`,
                    `${fieldName}[${index}]`
                );
                input.name = newName;

                // チェックボックスのIDも更新
                if (input.type === "checkbox") {
                    const id = input.id;
                    if (!id) return;

                    const newId = id.replace(
                        `${fieldName}_${oldIndex}`,
                        `${fieldName}_${index}`
                    );
                    input.id = newId;

                    // ラベルのforも更新
                    const label = item.querySelector(`label[for="${id}"]`);
                    if (label) {
                        label.setAttribute("for", newId);
                    }
                }
            }
        });
    });
}

// 遅延読み込み関数
function lazyInit() {
    // 必要な機能だけを初期化
    const path = window.location.pathname;

    // 共通の初期化
    initCommonFeatures();

    // ページ固有の初期化
    if (path.includes("/photo")) {
        // 画像管理ページ
        initPhotoPage();
    } else if (path.includes("/content-data")) {
        // コンテンツデータページ
        initContentDataPage();
    }
}

// 共通機能の初期化
function initCommonFeatures() {
    // お知らせの展開・折りたたみ機能
    document.addEventListener("click", (e) => {
        if (e.target.closest(".news-item-row")) {
            const item = e.target.closest(".news-list-item");
            if (item) {
                item.classList.toggle("expanded");
            }
        }
    });

    // 公開状態切り替え機能
    document.addEventListener("click", (e) => {
        if (!e.target.closest(".toggle-public-btn")) return;

        const button = e.target.closest(".toggle-public-btn");
        const dataId = button.dataset.id;
        const currentStatus = button.dataset.status;
        const newStatus = currentStatus === "1" ? "0" : "1";

        // ボタンを無効化して処理中を表示
        button.disabled = true;
        button.innerHTML =
            '<i class="fas fa-spinner fa-spin me-1"></i> 処理中...';

        // Ajaxリクエスト
        fetch("/admin/content-data/toggle-public", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                data_id: dataId,
                public_flg: newStatus,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    // ボタンの状態を更新
                    button.dataset.status = newStatus;
                    button.classList.remove(
                        newStatus === "1" ? "btn-secondary" : "btn-success"
                    );
                    button.classList.add(
                        newStatus === "1" ? "btn-success" : "btn-secondary"
                    );
                    button.innerHTML = `<i class="fas ${
                        newStatus === "1" ? "fa-eye" : "fa-eye-slash"
                    } me-1"></i> ${newStatus === "1" ? "公開" : "非公開"}`;
                    button.title =
                        newStatus === "1"
                            ? "公開中（クリックで非公開に切り替え）"
                            : "非公開（クリックで公開に切り替え）";

                    // 成功メッセージを表示
                    const alertDiv = document.createElement("div");
                    alertDiv.className =
                        "alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3";
                    alertDiv.setAttribute("role", "alert");
                    alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i> ${data.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
          `;
                    document.body.appendChild(alertDiv);

                    // 3秒後にアラートを自動的に閉じる
                    setTimeout(() => {
                        if (window.bootstrap) {
                            const bsAlert = new window.bootstrap.Alert(
                                alertDiv
                            );
                            bsAlert.close();
                        } else {
                            alertDiv.remove();
                        }
                    }, 3000);
                } else {
                    // エラーメッセージを表示
                    alert("エラー: " + data.message);
                    // ボタンを元の状態に戻す
                    button.innerHTML = `<i class="fas ${
                        currentStatus === "1" ? "fa-eye" : "fa-eye-slash"
                    } me-1"></i> ${currentStatus === "1" ? "公開" : "非公開"}`;
                }
                // ボタンを再度有効化
                button.disabled = false;
            })
            .catch((error) => {
                console.error("Error:", error);
                alert("エラーが発生しました。もう一度お試しください。");
                // ボタンを元の状態に戻す
                button.innerHTML = `<i class="fas ${
                    currentStatus === "1" ? "fa-eye" : "fa-eye-slash"
                } me-1"></i> ${currentStatus === "1" ? "公開" : "非公開"}`;
                button.disabled = false;
            });
    });
}

// 画像管理ページの初期化
function initPhotoPage() {
    // 新規登録ボタンのイベントリスナー
    const newEntryBtn = document.getElementById("newEntryBtn");
    const dataForm = document.getElementById("dataForm");
    const cancelBtn = document.getElementById("cancelBtn");

    if (newEntryBtn && dataForm) {
        newEntryBtn.addEventListener("click", () => {
            dataForm.style.display = "block";
            dataForm.scrollIntoView({ behavior: "smooth" });
        });
    }

    if (cancelBtn && dataForm) {
        cancelBtn.addEventListener("click", () => {
            dataForm.style.display = "none";
        });
    }

    // ファイルアップロード機能の初期化（必要な場合のみ）
    initFileUpload();
}

// コンテンツデータページの初期化
function initContentDataPage() {
    // ファイルアップロード機能の初期化
    initFileUpload();

    // 配列フィールドの初期化
    initArrayFields();

    // ソート機能の初期化
    initSortable();
}

// ページ読み込み時に遅延初期化
document.addEventListener("DOMContentLoaded", () => {
    // 遅延読み込みを使用
    setTimeout(lazyInit, 10);
});
