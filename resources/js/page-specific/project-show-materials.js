import axios from "axios";
import {
    handleFormError,
    getCsrfToken,
    escapeHtml,
    nl2br,
    refreshCharacterCosts,
} from "./project-show-utils.js";

// DOM要素を取得するヘルパー
function getMaterialFormElements(characterId) {
    const form = document.getElementById(`material-form-${characterId}`);
    if (!form) return null;

    return {
        form,
        inventorySelect: document.getElementById(
            `inventory_item_id_input-${characterId}`
        ),
        manualNameField: document.getElementById(
            `manual_material_name_field-${characterId}`
        ),
        manualNameInput: document.getElementById(
            `manual_material_name_input-${characterId}`
        ),
        unitDisplay: document.getElementById(
            `material_unit_display-${characterId}`
        ),
        quantityInput: document.getElementById(
            `material_quantity_input-${characterId}`
        ),
        priceDisplay: document.getElementById(
            `material_price_display-${characterId}`
        ),
        notesInput: document.getElementById(
            `material_notes_input-${characterId}`
        ),
        statusCheckbox: document.getElementById(
            `material_status_checkbox_for_form-${characterId}`
        ),
        // Hidden inputs
        methodField: document.getElementById(
            `material-form-method-${characterId}`
        ),
        idField: document.getElementById(`material-form-id-${characterId}`),
        nameHiddenInput: document.getElementById(
            `material_name_hidden_input-${characterId}`
        ),
        unitHiddenInput: document.getElementById(
            `material_unit_hidden_input-${characterId}`
        ),
        priceHiddenInput: document.getElementById(
            `material_price_hidden_input-${characterId}`
        ),
        unitPriceHiddenInput: document.getElementById(
            `material_unit_price_hidden_input-${characterId}`
        ),
        statusHiddenInput: document.getElementById(
            `material_status_hidden_input-${characterId}`
        ),
        // Form UI elements
        formTitle: document.getElementById(
            `material-form-title-${characterId}`
        ),
        submitBtn: document.getElementById(
            `material-form-submit-btn-${characterId}`
        ),
        submitBtnText: document.getElementById(
            `material-form-submit-btn-text-${characterId}`
        ),
        cancelBtn: document.getElementById(
            `material-form-cancel-btn-${characterId}`
        ),
        errorDiv: document.getElementById(
            `material-form-errors-${characterId}`
        ),
        storeUrl: form.dataset.storeUrl,
    };
}

function calculateAndSetPrice(characterId) {
    const els = getMaterialFormElements(characterId);
    if (!els) return;

    const selectedOption =
        els.inventorySelect.options[els.inventorySelect.selectedIndex];
    const quantity = parseFloat(els.quantityInput.value);

    if (selectedOption.value && selectedOption.value !== "manual_input") {
        // 在庫品目選択時
        const avgPrice = parseFloat(selectedOption.dataset.avg_price);
        if (!isNaN(avgPrice) && !isNaN(quantity) && quantity > 0) {
            const totalPrice = avgPrice * quantity;
            els.priceDisplay.value =
                Math.round(totalPrice).toLocaleString() + "円";
            els.priceHiddenInput.value = Math.round(totalPrice);
            els.unitPriceHiddenInput.value = avgPrice;
        } else {
            els.priceDisplay.value = "";
            els.priceHiddenInput.value = "";
            els.unitPriceHiddenInput.value = "";
        }
    } else if (selectedOption.value === "manual_input") {
        // 手入力時
        const priceString = els.priceDisplay.value.replace(/[円,]/g, "");
        const manualPrice = parseFloat(priceString);

        if (!isNaN(manualPrice)) {
            els.priceHiddenInput.value = manualPrice;
            if (!isNaN(quantity) && quantity > 0) {
                els.unitPriceHiddenInput.value = manualPrice / quantity;
            } else {
                els.unitPriceHiddenInput.value = "";
            }
        } else {
            els.priceHiddenInput.value = "";
            els.unitPriceHiddenInput.value = "";
        }
    } else {
        // 未選択時
        els.priceDisplay.value = "";
        els.priceHiddenInput.value = "";
        els.unitPriceHiddenInput.value = "";
    }
}

function updateFormUIBasedOnSelection(characterId) {
    const els = getMaterialFormElements(characterId);
    if (!els) return;

    const selectedValue = els.inventorySelect.value;
    const selectedOption =
        els.inventorySelect.options[els.inventorySelect.selectedIndex];

    if (selectedValue === "manual_input") {
        els.manualNameField.classList.remove("hidden");
        els.manualNameInput.required = true;

        els.unitDisplay.readOnly = false;
        els.unitDisplay.placeholder = "単位を入力 (例: 個, m)";
        els.unitDisplay.classList.remove(
            "bg-gray-100",
            "dark:bg-gray-800",
            "dark:text-gray-400"
        );

        els.priceDisplay.readOnly = false;
        els.priceDisplay.placeholder = "合計価格を入力 (例: 1000)";
        els.priceDisplay.classList.remove(
            "bg-gray-100",
            "dark:bg-gray-800",
            "dark:text-gray-400"
        );

        els.statusCheckbox.checked = false;
        els.statusCheckbox.disabled = false;
        els.statusHiddenInput.value = "未購入";
    } else if (selectedValue) {
        els.manualNameField.classList.add("hidden");
        els.manualNameInput.required = false;
        els.manualNameInput.value = "";

        els.unitDisplay.readOnly = true;
        els.unitDisplay.placeholder = "品目選択で自動表示";
        els.unitDisplay.classList.add(
            "bg-gray-100",
            "dark:bg-gray-800",
            "dark:text-gray-400"
        );
        els.unitDisplay.value = selectedOption.dataset.unit || "";

        els.priceDisplay.readOnly = true;
        els.priceDisplay.placeholder = "自動計算";
        els.priceDisplay.classList.add(
            "bg-gray-100",
            "dark:bg-gray-800",
            "dark:text-gray-400"
        );

        els.statusCheckbox.checked = true;
        els.statusCheckbox.disabled = true;
        els.statusHiddenInput.value = "購入済";

        els.nameHiddenInput.value = selectedOption.dataset.name || "";
        els.unitHiddenInput.value = selectedOption.dataset.unit || "";
    } else {
        // 未選択状態
        els.manualNameField.classList.add("hidden");
        els.manualNameInput.required = false;
        els.manualNameInput.value = "";

        els.unitDisplay.readOnly = true;
        els.unitDisplay.placeholder = "品目選択で自動表示";
        els.unitDisplay.classList.add(
            "bg-gray-100",
            "dark:bg-gray-800",
            "dark:text-gray-400"
        );
        els.unitDisplay.value = "";

        els.priceDisplay.readOnly = true;
        els.priceDisplay.placeholder = "自動計算";
        els.priceDisplay.classList.add(
            "bg-gray-100",
            "dark:bg-gray-800",
            "dark:text-gray-400"
        );
        els.priceDisplay.value = "";

        els.statusCheckbox.checked = false;
        els.statusCheckbox.disabled = false;
        els.statusHiddenInput.value = "未購入";

        els.nameHiddenInput.value = "";
        els.unitHiddenInput.value = "";
    }
    calculateAndSetPrice(characterId);
}

export function resetMaterialForm(characterId, storeUrlFromFunc) {
    const els = getMaterialFormElements(characterId);
    if (!els) return;

    const storeUrlToUse = storeUrlFromFunc || els.storeUrl;

    els.form.reset();
    els.form.setAttribute("action", storeUrlToUse);
    if (els.methodField) els.methodField.value = "POST";
    if (els.idField) els.idField.value = "";

    if (els.formTitle) els.formTitle.textContent = "材料を追加";
    if (els.submitBtnText) els.submitBtnText.textContent = "追加";

    if (els.submitBtn) {
        els.submitBtn.classList.remove(
            "bg-yellow-500",
            "hover:bg-yellow-600",
            "active:bg-yellow-700",
            "focus:border-yellow-700",
            "focus:ring-yellow-300"
        );
        els.submitBtn.classList.add(
            "bg-green-500",
            "hover:bg-green-600",
            "active:bg-green-700",
            "focus:border-green-700",
            "focus:ring-green-300"
        );
    }
    if (els.cancelBtn) els.cancelBtn.style.display = "none";
    if (els.errorDiv) els.errorDiv.innerHTML = "";

    els.form
        .querySelectorAll(".form-input, .form-select, .form-textarea")
        .forEach((input) => {
            input.classList.remove("border-red-500", "dark:border-red-600");
        });

    if (els.inventorySelect) els.inventorySelect.value = "";

    if (els.manualNameField) els.manualNameField.classList.add("hidden");
    if (els.manualNameInput) {
        els.manualNameInput.value = "";
        els.manualNameInput.required = false;
    }

    if (els.unitDisplay) {
        els.unitDisplay.value = "";
        els.unitDisplay.readOnly = true;
        els.unitDisplay.placeholder = "品目選択で自動表示";
        els.unitDisplay.classList.add(
            "bg-gray-100",
            "dark:bg-gray-800",
            "dark:text-gray-400"
        );
    }
    if (els.priceDisplay) {
        els.priceDisplay.value = "";
        els.priceDisplay.readOnly = true;
        els.priceDisplay.placeholder = "自動計算";
        els.priceDisplay.classList.add(
            "bg-gray-100",
            "dark:bg-gray-800",
            "dark:text-gray-400"
        );
    }

    if (els.statusCheckbox) {
        els.statusCheckbox.checked = false;
        els.statusCheckbox.disabled = false;
    }
    if (els.statusHiddenInput) els.statusHiddenInput.value = "未購入";

    if (els.nameHiddenInput) els.nameHiddenInput.value = "";
    if (els.unitHiddenInput) els.unitHiddenInput.value = "";
    if (els.priceHiddenInput) els.priceHiddenInput.value = "";
    if (els.unitPriceHiddenInput) els.unitPriceHiddenInput.value = "";

    updateFormUIBasedOnSelection(characterId);
}

export function redrawMaterialRow(
    characterId,
    materialData,
    isUpdate,
    projectData // projectData should contain: id, can_manage_materials, can_update_materials, can_delete_materials
) {
    console.log(projectData);
    const tbody = document.querySelector(
        `#materials-content-${characterId} table tbody`
    );
    if (!tbody) {
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
    // Check specific permission for editing
    actionsHtml += `
            <button type="button" class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-material-btn"
                    title="編集"
                    data-id="${materialData.id}"
                    data-inventory_item_id="${
                        materialData.inventory_item_id || ""
                    }"
                    data-name="${escapeHtml(materialData.name || "")}"
                    data-price="${materialData.price || ""}"
                    data-unit="${escapeHtml(materialData.unit || "")}"
                    data-unit_price_at_creation="${
                        materialData.unit_price_at_creation || ""
                    }"
                    data-quantity_needed="${escapeHtml(
                        materialData.quantity_needed || ""
                    )}"
                    data-status="${escapeHtml(materialData.status || "未購入")}"
                    data-notes="${escapeHtml(materialData.notes || "")}">
                <i class="fas fa-edit fa-sm"></i>
            </button>`;
    // Check specific permission for deleting
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

    let statusHtml;
    // General permission to manage materials (for status changes)
    if (materialData.inventory_item_id) {
        statusHtml = `<span class="text-xs px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">購入済</span>`;
    } else {
        statusHtml = `
                <input type="checkbox"
                    id="material-status-checkbox-${materialData.id}"
                    class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-600 material-status-checkbox"
                    data-url="${updateUrlForCheckbox}"
                    data-id="${
                        materialData.id
                    }" data-character-id="${characterId}"
                    ${materialData.status === "購入済" ? "checked" : ""}>`;
    }

    const itemNameDisplay = materialData.inventory_item
        ? materialData.inventory_item.name
        : materialData.name;
    const itemUnitDisplay =
        materialData.unit ||
        (materialData.inventory_item ? materialData.inventory_item.unit : "");

    let qtyNeededDisplay;
    const qty = parseFloat(materialData.quantity_needed);
    const unitLower = (itemUnitDisplay || "").toLowerCase();
    if (unitLower === "m") {
        qtyNeededDisplay = qty.toFixed(qty % 1 !== 0 ? 1 : 0);
    } else {
        if (qty % 1 === 0) {
            qtyNeededDisplay = qty.toFixed(0);
        } else {
            qtyNeededDisplay = qty.toString(); // Or qty.toFixed(2) for a consistent 2 decimal places
        }
    }

    const newRowCellsHtml = `
        <td class="px-3 py-1.5 whitespace-nowrap">
            ${statusHtml}
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
            qtyNeededDisplay
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
    projectData // projectData now expects: id, can_manage_materials, can_update_materials, can_delete_materials
) {
    const els = getMaterialFormElements(characterId);
    if (!els || !els.form) {
        return;
    }
    const storeUrl = els.storeUrl;

    if (els.inventorySelect) {
        els.inventorySelect.addEventListener("change", function () {
            updateFormUIBasedOnSelection(characterId);
        });
    }

    if (els.quantityInput) {
        els.quantityInput.addEventListener("input", function () {
            calculateAndSetPrice(characterId);
        });
    }

    if (els.priceDisplay) {
        els.priceDisplay.addEventListener("input", function () {
            if (!this.readOnly) {
                calculateAndSetPrice(characterId);
            }
        });
        els.priceDisplay.addEventListener("blur", function () {
            if (!this.readOnly) {
                let value = this.value.replace(/[円,]/g, "");
                if (!isNaN(parseFloat(value)) && isFinite(value)) {
                    this.value = parseFloat(value).toLocaleString() + "円";
                } else if (value.trim() !== "") {
                    this.value = "";
                } else {
                    this.value = ""; // Also clear if just whitespace
                }
                calculateAndSetPrice(characterId);
            }
        });
    }

    if (els.unitDisplay) {
        els.unitDisplay.addEventListener("input", function () {
            if (!this.readOnly && els.unitHiddenInput) {
                els.unitHiddenInput.value = this.value;
            }
        });
    }

    if (els.manualNameInput) {
        els.manualNameInput.addEventListener("input", function () {
            if (els.nameHiddenInput) {
                els.nameHiddenInput.value = this.value;
            }
        });
    }

    if (els.statusCheckbox) {
        els.statusCheckbox.addEventListener("change", function () {
            if (els.statusHiddenInput) {
                els.statusHiddenInput.value = this.checked
                    ? "購入済"
                    : "未購入";
            }
        });
    }

    updateFormUIBasedOnSelection(characterId);

    materialsContentContainer.addEventListener("click", function (event) {
        const editButton = event.target.closest(".edit-material-btn");
        if (editButton) {
            event.preventDefault();
            resetMaterialForm(characterId, storeUrl);

            if (els.formTitle) els.formTitle.textContent = "材料を編集";
            els.form.setAttribute(
                "action",
                `/projects/${projectData.id}/characters/${characterId}/materials/${editButton.dataset.id}`
            );
            if (els.methodField) els.methodField.value = "PUT";
            if (els.idField) els.idField.value = editButton.dataset.id;

            const inventoryItemId = editButton.dataset.inventory_item_id;
            const materialStatus = editButton.dataset.status;

            if (inventoryItemId) {
                els.inventorySelect.value = inventoryItemId;
            } else {
                els.inventorySelect.value = "manual_input";
            }

            const changeEvent = new Event("change");
            els.inventorySelect.dispatchEvent(changeEvent);

            setTimeout(() => {
                if (!inventoryItemId) {
                    if (els.manualNameInput)
                        els.manualNameInput.value =
                            editButton.dataset.name || "";
                    if (els.unitDisplay)
                        els.unitDisplay.value = editButton.dataset.unit || "";
                }
                if (els.quantityInput)
                    els.quantityInput.value =
                        editButton.dataset.quantity_needed;
                if (els.notesInput)
                    els.notesInput.value = editButton.dataset.notes || "";

                if (els.priceDisplay) {
                    const price = editButton.dataset.price;
                    els.priceDisplay.value =
                        price && !isNaN(parseFloat(price))
                            ? Number(price).toLocaleString() + "円"
                            : "";
                }
                if (els.priceHiddenInput)
                    els.priceHiddenInput.value = editButton.dataset.price || "";
                if (els.unitPriceHiddenInput)
                    els.unitPriceHiddenInput.value =
                        editButton.dataset.unit_price_at_creation || "";

                if (els.statusCheckbox && els.statusHiddenInput) {
                    const isPurchased = materialStatus === "購入済";
                    els.statusCheckbox.checked = isPurchased;
                    els.statusHiddenInput.value = materialStatus;
                    // disabled state already handled by updateFormUIBasedOnSelection via inventorySelect change
                }
                calculateAndSetPrice(characterId);
            }, 0);

            if (els.submitBtnText) els.submitBtnText.textContent = "更新";
            if (els.submitBtn) {
                els.submitBtn.classList.remove(
                    "bg-green-500",
                    "hover:bg-green-600",
                    "active:bg-green-700",
                    "focus:border-green-700",
                    "focus:ring-green-300"
                );
                els.submitBtn.classList.add(
                    "bg-yellow-500",
                    "hover:bg-yellow-600",
                    "active:bg-yellow-700",
                    "focus:border-yellow-700",
                    "focus:ring-yellow-300"
                );
            }
            if (els.cancelBtn) els.cancelBtn.style.display = "inline-flex";
            if (els.errorDiv) els.errorDiv.innerHTML = "";

            if (inventoryItemId) {
                if (els.quantityInput) els.quantityInput.focus();
            } else {
                if (els.manualNameInput) els.manualNameInput.focus();
            }
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
                                `material-form-errors-${characterId}`
                            );
                        }
                    })
                    .catch((error) => {
                        handleFormError(
                            error.response,
                            "削除中にエラーが発生しました。",
                            `material-form-errors-${characterId}`
                        );
                    });
            }
        }
    });

    if (els.cancelBtn) {
        els.cancelBtn.addEventListener("click", function () {
            resetMaterialForm(characterId, storeUrl);
        });
    }

    els.form.addEventListener("submit", function (event) {
        event.preventDefault();
        if (els.errorDiv) els.errorDiv.innerHTML = "";

        if (els.inventorySelect.value === "manual_input") {
            if (els.nameHiddenInput && els.manualNameInput)
                els.nameHiddenInput.value = els.manualNameInput.value;
            if (els.unitHiddenInput && els.unitDisplay)
                els.unitHiddenInput.value = els.unitDisplay.value;
        } else {
            const selectedOption =
                els.inventorySelect.options[els.inventorySelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                if (els.nameHiddenInput)
                    els.nameHiddenInput.value =
                        selectedOption.dataset.name || "";
                if (els.unitHiddenInput)
                    els.unitHiddenInput.value =
                        selectedOption.dataset.unit || "";
            } else {
                if (els.nameHiddenInput) els.nameHiddenInput.value = "";
                if (els.unitHiddenInput) els.unitHiddenInput.value = "";
            }
        }

        const formData = new FormData(els.form);
        const actionUrl = els.form.getAttribute("action");

        axios({
            method: "post",
            url: actionUrl,
            data: formData,
            headers: { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" },
        })
            .then((response) => {
                if (response.data.success && response.data.material) {
                    const isUpdate =
                        els.methodField && els.methodField.value === "PUT";
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
                        response.data.message || "処理に失敗しました。",
                        `material-form-errors-${characterId}`,
                        response.data.errors
                    );
                }
            })
            .catch((error) => {
                handleFormError(
                    error.response,
                    "送信中にエラーが発生しました。",
                    `material-form-errors-${characterId}`,
                    error.response?.data?.errors
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
                            true, // isUpdate
                            projectData
                        );
                        if (response.data.costs_updated) {
                            refreshCharacterCosts(projectData.id, characterId);
                        }
                    } else {
                        handleFormError(
                            response,
                            "ステータス更新に失敗しました。",
                            `material-form-errors-${characterId}`
                        );
                        checkbox.checked = !checkbox.checked;
                    }
                })
                .catch((error) => {
                    handleFormError(
                        error.response,
                        "ステータス更新中にエラーが発生しました。",
                        `material-form-errors-${characterId}`
                    );
                    checkbox.checked = !checkbox.checked;
                });
        }
    });
}
