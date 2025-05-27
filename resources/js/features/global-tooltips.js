// resources/js/features/global-tooltips.js

function initializeTaskTooltips() {
    const tooltipElement = document.getElementById("taskDescriptionTooltip");
    if (!tooltipElement) {
        return;
    }
    // ... (ツールチップのロジックはそのまま) ...
    let currentHoverElement = null;
    let tooltipTimeout = null;

    function scanAndAttachTooltips() {
        document.querySelectorAll(".task-row-hoverable").forEach((row) => {
            if (row.dataset.tooltipAttached === "true") return;
            const description = row.dataset.taskDescription;
            if (!description || description.trim() === "") return;

            const showTooltip = (e) => {
                clearTimeout(tooltipTimeout);
                currentHoverElement = row;
                tooltipElement.textContent = description;
                tooltipElement.classList.remove("hidden");
                updateTooltipPosition(e, tooltipElement);
            };
            const debouncedHideTooltip = () => {
                tooltipTimeout = setTimeout(() => {
                    if (
                        tooltipElement.classList.contains("hidden") === false &&
                        currentHoverElement === row
                    ) {
                        tooltipElement.classList.add("hidden");
                        currentHoverElement = null;
                    }
                }, 100);
            };
            row.addEventListener("mouseenter", showTooltip);
            row.addEventListener("mousemove", (e) => {
                if (
                    currentHoverElement === row &&
                    !tooltipElement.classList.contains("hidden")
                ) {
                    updateTooltipPosition(e, tooltipElement);
                }
            });
            row.addEventListener("mouseleave", debouncedHideTooltip);
            row.dataset.tooltipAttached = "true";
        });
    }
    tooltipElement.addEventListener("mouseenter", () =>
        clearTimeout(tooltipTimeout)
    );
    tooltipElement.addEventListener("mouseleave", () => {
        tooltipElement.classList.add("hidden");
        currentHoverElement = null;
    });
    function updateTooltipPosition(event, tooltip) {
        const tooltipRect = tooltip.getBoundingClientRect();
        let left = event.pageX + 15;
        let top = event.pageY + 15;
        if (left + tooltipRect.width > window.innerWidth - 5) {
            left = event.pageX - tooltipRect.width - 15;
        }
        if (left < 5) left = 5;
        if (top + tooltipRect.height > window.innerHeight - 5) {
            top = event.pageY - tooltipRect.height - 15;
        }
        if (top < 5) top = 5;
        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
    }
    const hideTooltipGlobal = () => {
        if (
            currentHoverElement &&
            !tooltipElement.classList.contains("hidden")
        ) {
            tooltipElement.classList.add("hidden");
            currentHoverElement = null;
            clearTimeout(tooltipTimeout);
        }
    };
    window.addEventListener("scroll", hideTooltipGlobal, true);
    window.addEventListener("resize", hideTooltipGlobal);
    scanAndAttachTooltips();
}

// --- 画像プレビューモーダル初期化関数 (新規追加またはここに統合) ---
function initializeImagePreviewModal(
    modalId,
    imageId,
    closeBtnId,
    triggerClass
) {
    const modal = document.getElementById(modalId);
    const fullImage = document.getElementById(imageId);
    const closeButton = document.getElementById(closeBtnId);

    if (!modal || !fullImage || !closeButton) {
        // console.warn(`Image preview modal elements not found for modalId: ${modalId}. Required: ${modalId}, ${imageId}, ${closeBtnId}`);
        return; // 要素がなければ何もしない
    }

    // 既に初期化済みの場合は重複してイベントリスナーを登録しない
    if (modal.dataset.imagePreviewInitialized === "true") {
        return;
    }
    modal.dataset.imagePreviewInitialized = "true";

    document.body.addEventListener("click", function (event) {
        const previewImageElement = event.target.closest("." + triggerClass);
        if (previewImageElement) {
            event.preventDefault();
            const fullImageUrl = previewImageElement.dataset.fullImageUrl;
            if (fullImageUrl) {
                fullImage.src = fullImageUrl;
                modal.style.display = "flex"; // flexで中央寄せ
            }
        }
    });

    closeButton.addEventListener("click", function () {
        modal.style.display = "none";
        fullImage.src = ""; // 画像URLをリセット
    });

    modal.addEventListener("click", function (event) {
        // モーダルの背景（画像以外の部分）がクリックされた場合のみ閉じる
        if (event.target === modal) {
            modal.style.display = "none";
            fullImage.src = ""; // 画像URLをリセット
        }
    });
}

export { initializeTaskTooltips, initializeImagePreviewModal };
