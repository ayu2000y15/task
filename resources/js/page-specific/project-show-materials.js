import axios from "axios";
import {
    handleFormError,
    getCsrfToken,
    escapeHtml,
    nl2br,
    refreshCharacterCosts,
} from "./project-show-utils.js";

function calculateAndDisplayMaterialPrice(characterId) {
    const inventorySelect = document.getElementById(
        `inventory_item_id_input-${characterId}`
    );
    const quantityInput = document.getElementById(
        `material_quantity_input-${characterId}`
    );
    const priceDisplay = document.getElementById(
        `material_price_display-${characterId}`
    );
    const priceHidden = document.getElementById(
        `material_price_hidden_input-${characterId}`
    );
    const unitPriceHidden = document.getElementById(
        `material_unit_price_hidden_input-${characterId}`
    );

    if (
        inventorySelect &&
        quantityInput &&
        priceDisplay &&
        priceHidden &&
        unitPriceHidden
    ) {
        const selectedOption =
            inventorySelect.options[inventorySelect.selectedIndex];
        const avgPrice = parseFloat(selectedOption.dataset.avg_price);
        const quantity = parseFloat(quantityInput.value);

        if (
            selectedOption.value &&
            !isNaN(avgPrice) &&
            !isNaN(quantity) &&
            quantity > 0
        ) {
            const totalPrice = avgPrice * quantity;
            priceDisplay.value = Math.round(totalPrice).toLocaleString() + "円";
            priceHidden.value = Math.round(totalPrice);
            unitPriceHidden.value = avgPrice;
        } else {
            priceDisplay.value = "";
            priceHidden.value = "";
            unitPriceHidden.value = "";
        }
    }
}

export function resetMaterialForm(characterId, storeUrl) {
    const form = document.getElementById(`material-form-${characterId}`);
    if (!form) {
        // console.warn(`Material form not found for reset: material-form-${characterId}`);
        return;
    }
    form.reset();
    form.setAttribute("action", storeUrl);
    const methodField = document.getElementById(
        `material-form-method-${characterId}`
    );
    if (methodField) methodField.value = "POST";
    const idField = document.getElementById(`material-form-id-${characterId}`);
    if (idField) idField.value = "";

    const titleEl = document.getElementById(
        `material-form-title-${characterId}`
    );
    if (titleEl) titleEl.textContent = "材料を追加";

    const submitBtnTextEl = document.getElementById(
        `material-form-submit-btn-text-${characterId}`
    );
    if (submitBtnTextEl) submitBtnTextEl.textContent = "追加";

    const submitBtnEl = document.getElementById(
        `material-form-submit-btn-${characterId}`
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
        `material-form-cancel-btn-${characterId}`
    );
    if (cancelBtn) cancelBtn.style.display = "none";

    const errorDiv = document.getElementById(
        `material-form-errors-${characterId}`
    );
    if (errorDiv) errorDiv.innerHTML = "";

    form.querySelectorAll(".form-input, .form-select, .form-textarea").forEach(
        (input) => {
            input.classList.remove("border-red-500", "dark:border-red-600");
        }
    );

    const unitDisplay = document.getElementById(
        `material_unit_display-${characterId}`
    );
    if (unitDisplay) unitDisplay.value = "";
    const priceDisplay = document.getElementById(
        `material_price_display-${characterId}`
    );
    if (priceDisplay) priceDisplay.value = "";
    const nameHiddenInput = document.getElementById(
        `material_name_hidden_input-${characterId}`
    );
    if (nameHiddenInput) nameHiddenInput.value = "";
    const unitHiddenInput = document.getElementById(
        `material_unit_hidden_input-${characterId}`
    );
    if (unitHiddenInput) unitHiddenInput.value = "";
    const priceHidden = document.getElementById(
        `material_price_hidden_input-${characterId}`
    );
    if (priceHidden) priceHidden.value = "";
    const unitPriceHidden = document.getElementById(
        `material_unit_price_hidden_input-${characterId}`
    );
    if (unitPriceHidden) unitPriceHidden.value = "";
}

export function redrawMaterialRow(
    characterId,
    materialData,
    isUpdate,
    projectData
) {
    const tbody = document.querySelector(
        `#materials-content-${characterId} table tbody`
    );
    if (!tbody) {
        // console.warn(`Material table body not found for character ${characterId}`);
        return;
    }

    const existingRow = document.getElementById(
        `material-row-${materialData.id}`
    );
    const notesDisplay = materialData.notes
        ? nl2br(escapeHtml(materialData.notes))
        : "-";
    const totalPriceDisplay =
        materialData.price !== null
            ? Number(materialData.price).toLocaleString() + "円"
            : "-";
    const csrfToken = getCsrfToken();
    const updateUrlForCheckbox = `/projects/${projectData.id}/characters/${characterId}/materials/${materialData.id}`;

    let actionsHtml = '<div class="flex items-center justify-end space-x-1">';
    actionsHtml += `
        <button type="button" class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-material-btn"
                title="編集"
                data-id="${materialData.id}"
                data-inventory_item_id="${materialData.inventory_item_id || ""}"
                data-name="${escapeHtml(materialData.name || "")}"
                data-price="${materialData.price || ""}"
                data-unit="${escapeHtml(materialData.unit || "")}"
                data-unit_price_at_creation="${
                    materialData.unit_price_at_creation || ""
                }"
                data-quantity_needed="${escapeHtml(
                    materialData.quantity_needed || ""
                )}"
                data-notes="${escapeHtml(materialData.notes || "")}">
            <i class="fas fa-edit fa-sm"></i>
        </button>`;
    actionsHtml += `
        <form action="/projects/${projectData.id}/characters/${characterId}/materials/${materialData.id}"
              method="POST" class="delete-material-form"
              data-id="${materialData.id}"
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

    let statusCheckboxHtml = `
        <input type="checkbox"
            id="material-status-checkbox-${materialData.id}"
            class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-600 material-status-checkbox"
            data-url="${updateUrlForCheckbox}"
            data-id="${materialData.id}" data-character-id="${characterId}"
            ${materialData.status === "購入済" ? "checked" : ""}>`;

    const itemNameDisplay = materialData.inventory_item
        ? materialData.inventory_item.name
        : materialData.name;
    const itemUnitDisplay =
        materialData.unit ||
        (materialData.inventory_item ? materialData.inventory_item.unit : "");

    const newRowCellsHtml = `
        <td class="px-3 py-1.5 whitespace-nowrap">
            ${statusCheckboxHtml}
        </td>
        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-name">
            ${escapeHtml(itemNameDisplay)} ${
        materialData.inventory_item_id
            ? '<span class="text-xs text-gray-400">(在庫品)</span>'
            : ""
    }
        </td>
        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-unit">${escapeHtml(
            itemUnitDisplay
        )}</td>
        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-quantity_needed">${escapeHtml(
            materialData.quantity_needed
        )}</td>
        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-price">${totalPriceDisplay}</td>
        <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 break-words text-left leading-tight material-notes" style="min-width: 150px;">
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
        newTr.id = `material-row-${materialData.id}`;
        newTr.classList.add("hover:bg-gray-50", "dark:hover:bg-gray-700/50");
        newTr.innerHTML = newRowCellsHtml;
        const emptyRow = tbody.querySelector('td[colspan="7"]');
        if (emptyRow) emptyRow.parentElement.remove();
        tbody.appendChild(newTr);
    }
}

export function initializeMaterialInteractions(
    materialsContentContainer,
    characterId,
    csrfToken,
    projectData
) {
    const materialForm = document.getElementById(
        `material-form-${characterId}`
    );
    if (!materialForm) {
        // console.warn(`Material form not found for character ${characterId}. Aborting init.`);
        return;
    }

    const storeUrl = materialForm.dataset.storeUrl;
    const formTitle = document.getElementById(
        `material-form-title-${characterId}`
    );
    const submitBtn = document.getElementById(
        `material-form-submit-btn-${characterId}`
    );
    const submitBtnText = document.getElementById(
        `material-form-submit-btn-text-${characterId}`
    );
    const cancelBtn = document.getElementById(
        `material-form-cancel-btn-${characterId}`
    );
    const methodField = document.getElementById(
        `material-form-method-${characterId}`
    );
    const idField = document.getElementById(`material-form-id-${characterId}`);
    const errorDivId = `material-form-errors-${characterId}`;

    const inventorySelect = document.getElementById(
        `inventory_item_id_input-${characterId}`
    );
    const unitDisplay = document.getElementById(
        `material_unit_display-${characterId}`
    );
    const quantityInput = document.getElementById(
        `material_quantity_input-${characterId}`
    );
    const nameHiddenInput = document.getElementById(
        `material_name_hidden_input-${characterId}`
    );
    const unitHiddenInput = document.getElementById(
        `material_unit_hidden_input-${characterId}`
    );
    const priceDisplay = document.getElementById(
        `material_price_display-${characterId}`
    );
    const priceHidden = document.getElementById(
        `material_price_hidden_input-${characterId}`
    );
    const unitPriceHidden = document.getElementById(
        `material_unit_price_hidden_input-${characterId}`
    );

    if (inventorySelect) {
        inventorySelect.addEventListener("change", function () {
            const selectedOption = this.options[this.selectedIndex];
            if (unitDisplay && nameHiddenInput && unitHiddenInput) {
                if (selectedOption && selectedOption.value) {
                    unitDisplay.value = selectedOption.dataset.unit || "";
                    nameHiddenInput.value = selectedOption.dataset.name || "";
                    unitHiddenInput.value = selectedOption.dataset.unit || "";
                } else {
                    unitDisplay.value = "";
                    nameHiddenInput.value = "";
                    unitHiddenInput.value = "";
                }
            }
            calculateAndDisplayMaterialPrice(characterId);
        });
    }

    if (quantityInput) {
        quantityInput.addEventListener("input", function () {
            calculateAndDisplayMaterialPrice(characterId);
        });
    }

    materialsContentContainer.addEventListener("click", function (event) {
        const editButton = event.target.closest(".edit-material-btn");
        if (editButton) {
            event.preventDefault();
            if (formTitle) formTitle.textContent = "材料を編集";
            materialForm.setAttribute(
                "action",
                `/projects/${projectData.id}/characters/${characterId}/materials/${editButton.dataset.id}`
            );
            if (methodField) methodField.value = "PUT";
            if (idField) idField.value = editButton.dataset.id;

            const inventoryItemId = editButton.dataset.inventory_item_id;
            if (inventorySelect) {
                inventorySelect.value = inventoryItemId || "";
                const changeEvent = new Event("change"); // Ensure event dispatch works
                inventorySelect.dispatchEvent(changeEvent); // Trigger change to update unit and potentially price
            }

            if (nameHiddenInput && !inventoryItemId)
                nameHiddenInput.value = editButton.dataset.name || ""; // Fallback if not inventory item
            if (unitHiddenInput && !inventoryItemId)
                unitHiddenInput.value = editButton.dataset.unit || ""; // Fallback

            if (quantityInput)
                quantityInput.value = editButton.dataset.quantity_needed;
            materialForm.elements["notes"].value =
                editButton.dataset.notes || "";

            if (priceDisplay)
                priceDisplay.value = editButton.dataset.price
                    ? Number(editButton.dataset.price).toLocaleString() + "円"
                    : "";
            if (priceHidden) priceHidden.value = editButton.dataset.price || "";
            if (unitPriceHidden)
                unitPriceHidden.value =
                    editButton.dataset.unit_price_at_creation || "";

            // After setting quantity, recalculate if an inventory item is selected.
            if (
                inventorySelect &&
                inventorySelect.value &&
                quantityInput.value
            ) {
                calculateAndDisplayMaterialPrice(characterId);
            }

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
            if (inventorySelect) inventorySelect.focus();
            else materialForm.elements["name"]?.focus();
        }

        const deleteFormElement = event.target.closest(".delete-material-form");
        if (deleteFormElement) {
            event.preventDefault();
            if (confirm("この材料を本当に削除しますか？")) {
                axios
                    .delete(deleteFormElement.action, {
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            Accept: "application/json",
                        },
                    })
                    .then((response) => {
                        if (response.data.success) {
                            const rowId = `material-row-${deleteFormElement.dataset.id}`;
                            const rowToRemove = document.getElementById(rowId);
                            if (rowToRemove) rowToRemove.remove();
                            resetMaterialForm(characterId, storeUrl);
                            if (response.data.costs_updated) {
                                refreshCharacterCosts(
                                    projectData.id,
                                    characterId
                                );
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
            resetMaterialForm(characterId, storeUrl);
        });
    }

    materialForm.addEventListener("submit", function (event) {
        event.preventDefault();
        const errorDiv = document.getElementById(errorDivId);
        if (errorDiv) errorDiv.innerHTML = "";
        const formData = new FormData(materialForm);
        const actionUrl = materialForm.getAttribute("action");
        let httpMethod = "post";

        if (
            document.getElementById(`material-form-method-${characterId}`)
                .value === "PUT"
        ) {
            formData.append("_method", "PUT");
        }

        axios({
            method: httpMethod,
            url: actionUrl,
            data: formData,
            headers: { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" },
        })
            .then((response) => {
                if (response.data.success && response.data.material) {
                    const isUpdate =
                        document.getElementById(
                            `material-form-method-${characterId}`
                        ).value === "PUT";
                    redrawMaterialRow(
                        characterId,
                        response.data.material,
                        isUpdate,
                        projectData
                    );
                    resetMaterialForm(characterId, storeUrl);
                    if (response.data.costs_updated) {
                        refreshCharacterCosts(projectData.id, characterId);
                    }
                } else {
                    handleFormError(
                        response,
                        "処理に失敗しました。",
                        errorDivId
                    );
                }
            })
            .catch((error) => {
                handleFormError(
                    error.response,
                    "送信中にエラーが発生しました。",
                    errorDivId
                );
            });
    });

    materialsContentContainer.addEventListener("change", function (event) {
        const checkbox = event.target.closest(".material-status-checkbox");
        if (checkbox && checkbox.dataset.characterId === characterId) {
            const materialId = checkbox.dataset.id;
            const status = checkbox.checked ? "購入済" : "未購入";
            const url = checkbox.dataset.url;

            axios
                .put(
                    url,
                    { status: status },
                    {
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            Accept: "application/json",
                            "Content-Type": "application/json",
                        },
                    }
                )
                .then((response) => {
                    if (response.data.success && response.data.material) {
                        redrawMaterialRow(
                            characterId,
                            response.data.material,
                            true,
                            projectData
                        ); // true for isUpdate
                        if (response.data.costs_updated) {
                            refreshCharacterCosts(projectData.id, characterId);
                        }
                    } else {
                        handleFormError(
                            response,
                            "ステータス更新に失敗しました。",
                            errorDivId
                        );
                        checkbox.checked = !checkbox.checked;
                    }
                })
                .catch((error) => {
                    handleFormError(
                        error.response,
                        "ステータス更新中にエラーが発生しました。",
                        errorDivId
                    );
                    checkbox.checked = !checkbox.checked;
                });
        }
    });
}
