// resources/js/page-specific/project-show-main.js
import { initializeMeasurementInteractions } from "./project-show-measurements.js";
import { initializeMaterialInteractions } from "./project-show-materials.js";
import { initializeCostInteractions } from "./project-show-costs.js";
// ★修正箇所: 一括登録用の初期化関数もインポート
import {
    initializeTaskForms,
    initializeBatchTaskRegistration,
} from "./project-show-tasks.js";
import {
    handleProjectFlagOrStatusUpdate,
    getCsrfToken,
} from "./project-show-utils.js";

function initializeProjectShowPage() {
    const mainContainer = document.getElementById(
        "project-show-main-container"
    );
    if (!mainContainer) {
        console.error(
            "[project-show-main.js] CRITICAL: mainContainer not found. Exiting initialization."
        );
        return;
    }

    const projectId = mainContainer.dataset.projectId;
    const csrfToken = getCsrfToken();

    let projectData = { id: projectId };
    if (mainContainer.dataset.project) {
        try {
            projectData = JSON.parse(mainContainer.dataset.project);
        } catch (e) {
            console.error(
                "[project-show-main.js] Failed to parse project data from HTML attribute:",
                e
            );
        }
    }

    // 個別タスクフォームを初期化
    initializeTaskForms(mainContainer);
    // ★修正箇所: 一括登録フォームを初期化
    initializeBatchTaskRegistration();

    mainContainer.addEventListener("change", function (event) {
        const flagSelect = event.target.closest(
            ".project-flag-select, .project-status-select"
        );
        if (flagSelect && flagSelect.closest(".lg\\:col-span-1")) {
            handleProjectFlagOrStatusUpdate(flagSelect, csrfToken);
        }
    });

    const characterCards = mainContainer.querySelectorAll(".js-character-card");

    characterCards.forEach((charCard) => {
        const measurementsContent = charCard.querySelector(
            '[id^="measurements-content-"]'
        );
        const materialsContent = charCard.querySelector(
            '[id^="materials-content-"]'
        );
        const costsContent = charCard.querySelector('[id^="costs-content-"]');

        let characterId = null;
        if (measurementsContent)
            characterId = measurementsContent.id.split("-").pop();
        else if (materialsContent)
            characterId = materialsContent.id.split("-").pop();
        else if (costsContent) characterId = costsContent.id.split("-").pop();

        if (characterId) {
            if (measurementsContent) {
                initializeMeasurementInteractions(
                    measurementsContent,
                    characterId,
                    csrfToken,
                    projectData
                );
            }
            if (materialsContent) {
                initializeMaterialInteractions(
                    materialsContent,
                    characterId,
                    csrfToken,
                    projectData
                );
            }
            if (costsContent) {
                initializeCostInteractions(
                    costsContent,
                    characterId,
                    csrfToken,
                    projectData
                );
            }
        } else {
            console.warn(
                `[project-show-main.js] Could not determine characterId for a character card. Skipping interactions init.`,
                charCard
            );
        }
    });
}

if (document.getElementById("project-show-main-container")) {
    initializeProjectShowPage();
}
