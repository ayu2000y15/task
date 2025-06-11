// resources/js/page-specific/project-show-costs.js
import axios from "axios";
import {
    handleFormError,
    getCsrfToken,
    escapeHtml,
    nl2br,
    refreshCharacterCosts as globalRefreshCosts,
} from "./project-show-utils.js";

function updateCharacterTotalCostDisplay(characterId) {
    const costsTabContainer = document.getElementById(
        `costs-content-${characterId}`
    );
    if (!costsTabContainer) {
        // console.warn(`Costs tab container not found for total update: costs-content-${characterId}`);
        return;
    }
    const tbody = costsTabContainer.querySelector("table tbody");
    if (!tbody) return;

    let currentTotal = 0;
    tbody.querySelectorAll('tr[id^="cost-row-"]').forEach((row) => {
        const amountCell = row.querySelector(".cost-amount");
        if (amountCell) {
            currentTotal +=
                parseFloat(
                    String(amountCell.textContent).replace(/[^0-9.-]+/g, "")
                ) || 0;
        }
    });

    const totalCostElement = costsTabContainer.querySelector(
        ".p-3.rounded-md span.font-semibold"
    );
    if (totalCostElement) {
        totalCostElement.textContent =
            Number(currentTotal).toLocaleString() + "円";
        const parentDiv = totalCostElement.closest(".p-3.rounded-md");
        if (parentDiv) {
            if (currentTotal > 0) {
                parentDiv.classList.remove(
                    "bg-gray-100",
                    "text-gray-700",
                    "dark:bg-gray-700/30",
                    "dark:text-gray-300"
                );
                parentDiv.classList.add(
                    "bg-green-100",
                    "text-green-700",
                    "dark:bg-green-700/30",
                    "dark:text-green-200"
                );
            } else {
                parentDiv.classList.remove(
                    "bg-green-100",
                    "text-green-700",
                    "dark:bg-green-700/30",
                    "dark:text-green-200"
                );
                parentDiv.classList.add(
                    "bg-gray-100",
                    "text-gray-700",
                    "dark:bg-gray-700/30",
                    "dark:text-gray-300"
                );
            }
        }
    }
}

export function resetCostForm(characterId, storeUrl) {
    const form = document.getElementById(`cost-form-${characterId}`);
    if (!form) {
        // console.warn(`Cost form not found for reset: cost-form-${characterId}`);
        return;
    }
    form.reset();
    form.setAttribute("action", storeUrl);
    const methodField = document.getElementById(
        `cost-form-method-${characterId}`
    );
    if (methodField) methodField.value = "POST";
    const idField = document.getElementById(`cost-form-id-${characterId}`);
    if (idField) idField.value = "";

    const titleEl = document.getElementById(`cost-form-title-${characterId}`);
    if (titleEl) titleEl.textContent = "コストを追加";

    const submitBtnTextEl = document.getElementById(
        `cost-form-submit-btn-text-${characterId}`
    );
    if (submitBtnTextEl) submitBtnTextEl.textContent = "追加";

    const submitBtnEl = document.getElementById(
        `cost-form-submit-btn-${characterId}`
    );
    if (submitBtnEl) {
        submitBtnEl.classList.remove(
            "bg-yellow-500",
            "hover:bg-yellow-600",
            "active:bg-yellow-700",
            "focus:border-yellow-700",
            "focus:ring-yellow-300"
        );
        submitBtnEl.classList.add(
            "bg-green-500",
            "hover:bg-green-600",
            "active:bg-green-700",
            "focus:border-green-700",
            "focus:ring-green-300"
        );
    }
    const cancelBtn = document.getElementById(
        `cost-form-cancel-btn-${characterId}`
    );
    if (cancelBtn) cancelBtn.style.display = "none";

    const errorDiv = document.getElementById(`cost-form-errors-${characterId}`);
    if (errorDiv) errorDiv.innerHTML = "";

    form.querySelectorAll(".form-input, .form-select, .form-textarea").forEach(
        (input) => {
            input.classList.remove("border-red-500", "dark:border-red-600");
        }
    );
    const dateInput =
        form.querySelector(`#cost_cost_date_input-${characterId}`) ||
        form.querySelector('[id^="cost_cost_date_input"]');
    if (dateInput && !(idField && idField.value)) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, "0");
        const day = String(today.getDate()).padStart(2, "0");
        dateInput.value = `${year}-${month}-${day}`;
    }
}

export function redrawCostRow(characterId, costData, isUpdate, projectData) {
    const tbody = document.querySelector(
        `#costs-content-${characterId} table tbody`
    );
    if (!tbody) {
        // console.warn(`Cost table body not found for character ${characterId}`);
        return;
    }

    const existingRow = document.getElementById(`cost-row-${costData.id}`);
    const notesDisplay = costData.notes
        ? nl2br(escapeHtml(costData.notes))
        : "-";
    const amountDisplay = Number(costData.amount).toLocaleString() + "円";

    let dateDisplay = "-";
    if (costData.cost_date && typeof costData.cost_date === "string") {
        const dateObj = new Date(costData.cost_date);
        if (!isNaN(dateObj.getTime())) {
            dateDisplay = dateObj.toLocaleDateString("ja-JP", {
                year: "numeric",
                month: "2-digit",
                day: "2-digit",
                timeZone: "Asia/Tokyo", // または 'UTC' など、表示したいタイムゾーン
            });
        } else {
            // console.warn(`[project-show-costs.js] redrawCostRow - Invalid Date object created from cost_date: "${costData.cost_date}"`);
            dateDisplay = "日付エラー";
        }
    } else {
        // console.warn(`[project-show-costs.js] redrawCostRow - cost_date is missing or not a string:`, costData.cost_date);
        dateDisplay = "日付なし";
    }

    const csrfToken = getCsrfToken();

    let typeBadgeHtml = "";
    let typeColorClass =
        "bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-100";
    if (costData.type === "材料費") {
        typeColorClass =
            "bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100";
    } else if (costData.type === "作業費") {
        typeColorClass =
            "bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100";
    }
    typeBadgeHtml = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${typeColorClass}">${escapeHtml(
        costData.type
    )}</span>`;

    let actionsHtml = '<div class="flex items-center justify-end space-x-1">';
    actionsHtml += `
        <button type="button" class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-cost-btn"
                title="編集"
                data-id="${costData.id}"
                data-cost_date="${
                    costData.cost_date
                        ? costData.cost_date.substring(0, 10)
                        : ""
                }"
                data-type="${escapeHtml(costData.type || "")}"
                data-amount="${costData.amount || ""}"
                data-item_description="${escapeHtml(
                    costData.item_description || ""
                )}"
                data-notes="${escapeHtml(costData.notes || "")}">
            <i class="fas fa-edit fa-sm"></i>
        </button>`;
    actionsHtml += `
        <form action="/projects/${projectData.id}/characters/${characterId}/costs/${costData.id}"
              method="POST" class="delete-cost-form"
              data-id="${costData.id}"
              onsubmit="return false;">
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit"
                class="p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                title="削除">
                <i class="fas fa-trash fa-sm"></i>
            </button>
        </form>`;
    actionsHtml += "</div>";

    const newRowCellsHtml = `
        <td class="px-2 py-1.5 whitespace-nowrap text-center text-gray-400 drag-handle">
            <i class="fas fa-grip-vertical"></i>
        </td>
        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 cost-cost_date">${escapeHtml(
            dateDisplay
        )}</td>
        <td class="px-4 py-1.5 whitespace-nowrap cost-type">${typeBadgeHtml}</td>
        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 cost-amount">${amountDisplay}</td>
        <td class="px-4 py-1.5 whitespace-normal break-words text-gray-700 dark:text-gray-200 cost-item_description">${escapeHtml(
            costData.item_description
        )}</td>
        <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 whitespace-pre-wrap break-words text-left leading-tight cost-notes" style="min-width: 150px;">
            ${notesDisplay}
        </td>
        <td class="px-3 py-1.5 whitespace-nowrap text-right">
            ${actionsHtml}
        </td>
    `;

    if (isUpdate && existingRow) {
        existingRow.innerHTML = newRowCellsHtml;
    } else if (!isUpdate) {
        const newTr = document.createElement("tr");
        newTr.id = `cost-row-${costData.id}`;
        newTr.classList.add("hover:bg-gray-50", "dark:hover:bg-gray-700/50");
        newTr.innerHTML = newRowCellsHtml;
        const emptyRow = tbody.querySelector('td[colspan="6"]');
        if (emptyRow) emptyRow.parentElement.remove();
        tbody.appendChild(newTr);
    }
    updateCharacterTotalCostDisplay(characterId);
}

export function initializeCostInteractions(
    costsContentContainer,
    characterId,
    csrfToken,
    projectData
) {
    const costForm = document.getElementById(`cost-form-${characterId}`);
    if (!costForm) {
        // console.warn(`Cost form not found for character ${characterId}`);
        return;
    }

    const storeUrl = costForm.dataset.storeUrl;
    const formTitle = document.getElementById(`cost-form-title-${characterId}`);
    const submitBtn = document.getElementById(
        `cost-form-submit-btn-${characterId}`
    );
    const submitBtnText = document.getElementById(
        `cost-form-submit-btn-text-${characterId}`
    );
    const cancelBtn = document.getElementById(
        `cost-form-cancel-btn-${characterId}`
    );
    const methodField = document.getElementById(
        `cost-form-method-${characterId}`
    );
    const idField = document.getElementById(`cost-form-id-${characterId}`);
    const errorDivId = `cost-form-errors-${characterId}`;

    costsContentContainer.addEventListener("click", function (event) {
        const editButton = event.target.closest(".edit-cost-btn");
        if (editButton) {
            event.preventDefault();
            if (formTitle) formTitle.textContent = "コストを編集";
            costForm.setAttribute(
                "action",
                `/projects/${projectData.id}/characters/${characterId}/costs/${editButton.dataset.id}`
            );
            if (methodField) methodField.value = "PUT";
            if (idField) idField.value = editButton.dataset.id;

            costForm.querySelector(
                `[id="cost_cost_date_input-${characterId}"]`
            ).value = editButton.dataset.cost_date;
            costForm.querySelector(
                `[id="cost_type_input-${characterId}"]`
            ).value = editButton.dataset.type;
            costForm.querySelector(
                `[id="cost_amount_input-${characterId}"]`
            ).value = editButton.dataset.amount;
            costForm.querySelector(
                `[id="cost_item_description_input-${characterId}"]`
            ).value = editButton.dataset.item_description;
            costForm.querySelector(
                `[id="cost_notes_input-${characterId}"]`
            ).value = editButton.dataset.notes || "";

            if (submitBtnText) submitBtnText.textContent = "更新";
            if (submitBtn) {
                submitBtn.classList.remove(
                    "bg-green-500",
                    "hover:bg-green-600"
                );
                submitBtn.classList.add("bg-yellow-500", "hover:bg-yellow-600");
            }
            if (cancelBtn) cancelBtn.style.display = "inline-flex";
            const errorDiv = document.getElementById(errorDivId);
            if (errorDiv) errorDiv.innerHTML = "";
            costForm
                .querySelector(`[id="cost_cost_date_input-${characterId}"]`)
                .focus();
        }

        const deleteFormElement = event.target.closest(".delete-cost-form");
        if (deleteFormElement) {
            event.preventDefault();
            if (confirm("このコストを本当に削除しますか？")) {
                axios
                    .delete(deleteFormElement.action, {
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            Accept: "application/json",
                        },
                    })
                    .then((response) => {
                        if (response.data.success) {
                            const rowId = `cost-row-${deleteFormElement.dataset.id}`;
                            const rowToRemove = document.getElementById(rowId);
                            if (rowToRemove) rowToRemove.remove();
                            resetCostForm(characterId, storeUrl);
                            updateCharacterTotalCostDisplay(characterId);
                            if (response.data.material_status_updated) {
                                // if (typeof refreshCharacterMaterials === "function") {
                                //    refreshCharacterMaterials(projectData.id, characterId);
                                // }
                            }
                        } else {
                            handleFormError(
                                response,
                                "削除に失敗しました。",
                                errorDivId
                            );
                        }
                    })
                    .catch((error) => {
                        handleFormError(
                            error.response,
                            "削除中にエラーが発生しました。",
                            errorDivId
                        );
                    });
            }
        }
    });

    if (cancelBtn) {
        cancelBtn.addEventListener("click", function () {
            resetCostForm(characterId, storeUrl);
        });
    }

    costForm.addEventListener("submit", function (event) {
        event.preventDefault();
        const errorDiv = document.getElementById(errorDivId);
        if (errorDiv) errorDiv.innerHTML = "";

        const formData = new FormData(costForm);
        const actionUrl = costForm.getAttribute("action");
        let httpMethod = "post";

        if (methodField && methodField.value === "PUT") {
            formData.append("_method", "PUT");
        }

        axios({
            method: httpMethod,
            url: actionUrl,
            data: formData,
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
        })
            .then((response) => {
                if (response.data.success && response.data.cost) {
                    const isUpdate = methodField && methodField.value === "PUT";
                    redrawCostRow(
                        characterId,
                        response.data.cost,
                        isUpdate,
                        projectData
                    );
                    resetCostForm(characterId, costForm.dataset.storeUrl);
                    if (response.data.material_status_updated) {
                        // if (typeof refreshCharacterMaterials === "function") {
                        //    refreshCharacterMaterials(projectData.id, characterId);
                        // }
                    }
                } else {
                    handleFormError(
                        response,
                        "コスト処理に失敗しました。",
                        errorDivId
                    );
                }
            })
            .catch((error) => {
                handleFormError(
                    error.response,
                    "コスト送信中にエラーが発生しました。",
                    errorDivId
                );
            });
    });
}
