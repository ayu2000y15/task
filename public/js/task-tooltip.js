// 工程メモツールチップ機能
document.addEventListener("DOMContentLoaded", () => {
    const tooltip = document.getElementById("taskDescriptionTooltip");
    if (!tooltip) return;

    let currentTooltipElement = null;
    let tooltipTimeout = null;

    // ホバー可能な工程行を取得
    const taskRows = document.querySelectorAll(".task-row-hoverable");

    taskRows.forEach((row) => {
        const description = row.dataset.taskDescription;
        if (!description || description.trim() === "") return;

        // マウスエンター時
        row.addEventListener("mouseenter", function (e) {
            clearTimeout(tooltipTimeout);

            // 既存のツールチップを隠す
            if (currentTooltipElement && currentTooltipElement !== this) {
                hideTooltip();
            }

            currentTooltipElement = this;
            showTooltip(e, description);
        });

        // マウスムーブ時（ツールチップの位置を更新）
        row.addEventListener("mousemove", function (e) {
            if (currentTooltipElement === this) {
                updateTooltipPosition(e);
            }
        });

        // マウスリーブ時
        row.addEventListener("mouseleave", function () {
            if (currentTooltipElement === this) {
                tooltipTimeout = setTimeout(() => {
                    hideTooltip();
                }, 100); // 少し遅延を入れてツールチップが消えるのを防ぐ
            }
        });
    });

    // ツールチップ自体のホバーイベント
    tooltip.addEventListener("mouseenter", () => {
        clearTimeout(tooltipTimeout);
    });

    tooltip.addEventListener("mouseleave", () => {
        hideTooltip();
    });

    function showTooltip(event, description) {
        tooltip.textContent = description;
        tooltip.classList.add("show");
        updateTooltipPosition(event);
    }

    function hideTooltip() {
        tooltip.classList.remove("show");
        currentTooltipElement = null;
        clearTimeout(tooltipTimeout);
    }

    function updateTooltipPosition(event) {
        const tooltipRect = tooltip.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        let left = event.pageX + 10;
        let top = event.pageY - tooltipRect.height - 10;

        // 右端からはみ出る場合は左側に表示
        if (left + tooltipRect.width > viewportWidth) {
            left = event.pageX - tooltipRect.width - 10;
        }

        // 上端からはみ出る場合は下側に表示
        if (top < window.pageYOffset) {
            top = event.pageY + 10;
        }

        // 左端からはみ出る場合は調整
        if (left < 0) {
            left = 10;
        }

        tooltip.style.left = left + "px";
        tooltip.style.top = top + "px";
    }

    // ページスクロール時にツールチップを隠す
    window.addEventListener("scroll", () => {
        if (currentTooltipElement) {
            hideTooltip();
        }
    });

    // ウィンドウリサイズ時にツールチップを隠す
    window.addEventListener("resize", () => {
        if (currentTooltipElement) {
            hideTooltip();
        }
    });
});
