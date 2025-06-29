// resources/js/page-specific/project-show-materials.js

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
            // 「購入済として処理」チェックボックス (フォーム内)
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
        applyToOthersCheckbox: document.getElementById(
            // 追加フォーム用
            `apply_material_to_other_characters-${characterId}`
        ),
        applyToOthersWrapperForAdd: document.getElementById(
            // 追加フォームの「他キャラ適用」のラッパー
            `apply_to_others_wrapper_for_add_material-${characterId}`
        ),
    };
}

function calculateAndSetPrice(characterId) {
    const els = getMaterialFormElements(characterId);
    if (
        !els ||
        !els.quantityInput ||
        !els.priceDisplay ||
        !els.priceHiddenInput ||
        !els.unitPriceHiddenInput
    )
        return;

    const selectedOption =
        els.inventorySelect.options[els.inventorySelect.selectedIndex];
    const quantity = parseFloat(els.quantityInput.value);

    if (selectedOption.value && selectedOption.value !== "manual_input") {
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
    if (els.methodField) els.methodField.value = "POST"; // 常にPOSTに戻す（追加モード）
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

    if (els.applyToOthersCheckbox) {
        // 追加フォームのチェックボックス
        els.applyToOthersCheckbox.checked = false;
    }
    if (els.applyToOthersWrapperForAdd) {
        // 追加フォームのチェックボックスラッパーを表示
        els.applyToOthersWrapperForAdd.style.display = "block";
    }

    updateFormUIBasedOnSelection(characterId);
}

export function redrawMaterialRow(
    characterId,
    materialData,
    isUpdate, // このフラグは行の挿入か置換かを決めるのに使う
    projectData
) {
    const tbody = document.querySelector(
        `#materials-content-${characterId} table tbody`
    );
    if (!tbody) {
        console.warn(
            `Table body not found for character materials: ${characterId}`
        );
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
    const updateUrlForCheckbox = `/projects/${projectData.id}/characters/${characterId}/materials/${materialData.id}`; // ステータス更新用URL

    let actionsHtml = '<div class="flex items-center justify-end space-x-1">';
    // projectDataに権限情報がない場合のフォールバックとしてtrueを設定（開発用）
    // 本番ではprojectDataに適切な権限情報が含まれていること
    const canUpdateMaterials =
        projectData.can_update_materials === undefined
            ? true
            : projectData.can_update_materials;
    const canDeleteMaterials =
        projectData.can_delete_materials === undefined
            ? true
            : projectData.can_delete_materials;

    if (canUpdateMaterials) {
        actionsHtml += `
            <button type="button" class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-material-btn"
                    title="編集" data-id="${materialData.id}"
                    data-inventory_item_id="${
                        materialData.inventory_item_id || ""
                    }"
                    data-name="${escapeHtml(
                        materialData.name || ""
                    )}" data-price="${materialData.price || ""}"
                    data-unit="${escapeHtml(
                        materialData.unit || ""
                    )}" data-unit_price_at_creation="${
            materialData.unit_price_at_creation || ""
        }"
                    data-quantity_needed="${escapeHtml(
                        materialData.quantity_needed || ""
                    )}"
                    data-status="${escapeHtml(
                        materialData.status || "未購入"
                    )}" data-notes="${escapeHtml(materialData.notes || "")}">
                <i class="fas fa-edit fa-sm"></i>
            </button>`;
    }
    if (canDeleteMaterials) {
        actionsHtml += `
            <form action="/projects/${projectData.id}/characters/${characterId}/materials/${materialData.id}"
                  method="POST" class="delete-material-form" data-id="${materialData.id}" onsubmit="return false;">
                <input type="hidden" name="_token" value="${csrfToken}"><input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" title="削除">
                    <i class="fas fa-trash fa-sm"></i>
                </button>
            </form>`;
    }
    actionsHtml += "</div>";

    let statusHtml;
    const canManageMaterials =
        projectData.can_manage_materials === undefined
            ? true
            : projectData.can_manage_materials;

    if (canManageMaterials) {
        if (materialData.inventory_item_id) {
            statusHtml = `<span class="text-xs px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">購入済</span>`;
        } else {
            statusHtml = `
                <input type="checkbox" id="material-status-checkbox-${
                    materialData.id
                }"
                    class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-600 material-status-checkbox"
                    data-url="${updateUrlForCheckbox}" data-id="${
                materialData.id
            }" data-character-id="${characterId}"
                    ${materialData.status === "購入済" ? "checked" : ""}>`;
        }
    } else {
        statusHtml = `<span class="text-gray-700 dark:text-gray-200">${escapeHtml(
            materialData.status
        )}</span>`;
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
    let decimalsQty = 0;
    if (unitLower === "m") {
        decimalsQty = qty % 1 !== 0 ? 1 : 0;
    } else {
        if (qty % 1 !== 0) {
            const decimalPart = String(qty).split(".")[1] || "";
            decimalsQty = Math.min(decimalPart.length, 2);
        }
    }
    qtyNeededDisplay = Number(qty).toFixed(decimalsQty);

    const newRowCellsHtml = `
        <td class="px-2 py-1.5 whitespace-nowrap text-center text-gray-400 drag-handle">
            <i class="fas fa-grip-vertical"></i>
        </td>
        <td class="px-3 py-1.5 whitespace-nowrap">${statusHtml}</td>
        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-name">
            ${escapeHtml(itemNameDisplay)} ${
        materialData.inventory_item_id
            ? '<span class="text-xs text-gray-400">(在庫品)</span>'
            : ""
    }
        </td>
        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-unit">${escapeHtml(
            qtyNeededDisplay + " " + itemUnitDisplay
        )}</td>

        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 material-price">${totalPriceDisplay}</td>
        <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 break-words text-left leading-tight material-notes" style="min-width: 150px;">${notesDisplay}</td>
        <td class="px-3 py-1.5 whitespace-nowrap text-right">${actionsHtml}</td>
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
    const els = getMaterialFormElements(characterId);
    if (!els || !els.form) {
        console.warn(
            `Material form elements not found for character ID: ${characterId}`
        );
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
            if (!this.readOnly) calculateAndSetPrice(characterId);
        });
        els.priceDisplay.addEventListener("blur", function () {
            if (!this.readOnly) {
                let value = this.value.replace(/[円,]/g, "");
                this.value =
                    !isNaN(parseFloat(value)) && isFinite(value)
                        ? parseFloat(value).toLocaleString() + "円"
                        : "";
                calculateAndSetPrice(characterId);
            }
        });
    }
    if (els.unitDisplay) {
        els.unitDisplay.addEventListener("input", function () {
            if (!this.readOnly && els.unitHiddenInput)
                els.unitHiddenInput.value = this.value;
        });
    }
    if (els.manualNameInput) {
        els.manualNameInput.addEventListener("input", function () {
            if (els.nameHiddenInput) els.nameHiddenInput.value = this.value;
        });
    }
    if (els.statusCheckbox) {
        els.statusCheckbox.addEventListener("change", function () {
            if (els.statusHiddenInput)
                els.statusHiddenInput.value = this.checked
                    ? "購入済"
                    : "未購入";
        });
    }

    updateFormUIBasedOnSelection(characterId);

    materialsContentContainer.addEventListener("click", function (event) {
        const editButton = event.target.closest(".edit-material-btn");
        const canUpdateMaterials =
            projectData.can_update_materials === undefined
                ? true
                : projectData.can_update_materials;
        if (editButton && canUpdateMaterials) {
            event.preventDefault();
            resetMaterialForm(characterId, storeUrl); // 編集前にフォームをリセット（他キャラ適用チェックもリセットされる）

            if (els.formTitle) els.formTitle.textContent = "材料を編集";
            els.form.setAttribute(
                "action",
                `/projects/${projectData.id}/characters/${characterId}/materials/${editButton.dataset.id}`
            );
            if (els.methodField) els.methodField.value = "PUT";
            if (els.idField) els.idField.value = editButton.dataset.id;

            // 「他のキャラクターへ適用」オプションを編集時は非表示にする
            if (els.applyToOthersWrapperForAdd) {
                els.applyToOthersWrapperForAdd.style.display = "none";
            }

            const inventoryItemId = editButton.dataset.inventory_item_id;
            const materialStatus = editButton.dataset.status;

            if (
                inventoryItemId &&
                inventoryItemId !== "null" &&
                inventoryItemId !== ""
            ) {
                els.inventorySelect.value = inventoryItemId;
            } else {
                els.inventorySelect.value = "manual_input";
            }
            els.inventorySelect.dispatchEvent(new Event("change"));

            setTimeout(() => {
                if (
                    !inventoryItemId ||
                    inventoryItemId === "null" ||
                    inventoryItemId === ""
                ) {
                    if (els.manualNameInput)
                        els.manualNameInput.value =
                            editButton.dataset.name || "";
                    if (els.unitDisplay)
                        els.unitDisplay.value = editButton.dataset.unit || "";
                }
                if (els.quantityInput)
                    els.quantityInput.value =
                        editButton.dataset.quantity_needed || "";
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
                    if (
                        !inventoryItemId ||
                        inventoryItemId === "null" ||
                        inventoryItemId === ""
                    ) {
                        els.statusCheckbox.disabled = false;
                    } else {
                        els.statusCheckbox.disabled = true;
                        els.statusCheckbox.checked = true;
                        els.statusHiddenInput.value = "購入済";
                    }
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

            if (
                inventoryItemId &&
                inventoryItemId !== "null" &&
                inventoryItemId !== ""
            ) {
                if (els.quantityInput) els.quantityInput.focus();
            } else {
                if (els.manualNameInput) els.manualNameInput.focus();
            }
        }

        const deleteFormElement = event.target.closest(".delete-material-form");
        const canDeleteMaterials =
            projectData.can_delete_materials === undefined
                ? true
                : projectData.can_delete_materials;
        if (deleteFormElement && canDeleteMaterials) {
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
                            document.getElementById(rowId)?.remove();
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
                    .catch((error) =>
                        handleFormError(
                            error.response,
                            "削除中にエラーが発生しました。",
                            `material-form-errors-${characterId}`
                        )
                    );
            }
        }
    });

    if (els.cancelBtn) {
        els.cancelBtn.addEventListener("click", function () {
            resetMaterialForm(characterId, storeUrl);
            // キャンセル時は「他のキャラクターへ適用」オプションを再表示（追加モードに戻るため）
            if (els.applyToOthersWrapperForAdd) {
                els.applyToOthersWrapperForAdd.style.display = "block";
            }
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
        if (
            els.statusHiddenInput &&
            els.statusCheckbox &&
            !els.statusCheckbox.disabled
        ) {
            els.statusHiddenInput.value = els.statusCheckbox.checked
                ? "購入済"
                : "未購入";
        } else if (
            els.statusHiddenInput &&
            els.statusCheckbox &&
            els.statusCheckbox.disabled &&
            els.inventorySelect.value !== "" &&
            els.inventorySelect.value !== "manual_input"
        ) {
            els.statusHiddenInput.value = "購入済";
        }

        const formData = new FormData(els.form);
        const actionUrl = els.form.getAttribute("action");
        const isUpdateOperation =
            els.methodField && els.methodField.value === "PUT";

        // 更新時は「他のキャラクターへ適用」関連のパラメータを送信しない
        if (
            !isUpdateOperation &&
            els.applyToOthersCheckbox &&
            els.applyToOthersCheckbox.checked
        ) {
            formData.append("apply_to_other_characters", "1");
        } else {
            formData.delete("apply_to_other_characters"); // 明示的に削除
        }
        formData.delete("update_same_name_on_others"); // 更新時専用フラグなので常に削除でOK

        axios({
            method: "post",
            url: actionUrl,
            data: formData,
            headers: { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" },
        })
            .then((response) => {
                if (response.data.success) {
                    // materials_data が配列で返ってくる場合、現在のキャラのものを探す
                    // そうでない場合は、response.data.material を使う
                    let materialToRedraw = response.data.material; // 更新時は単一のはず
                    if (
                        response.data.materials_data &&
                        Array.isArray(response.data.materials_data)
                    ) {
                        materialToRedraw =
                            response.data.materials_data.find(
                                (m) =>
                                    String(m.character_id) ===
                                        String(characterId) &&
                                    (isUpdateOperation
                                        ? String(m.id) === els.idField.value
                                        : true)
                            ) || response.data.materials_data[0]; // フォールバック
                    }

                    if (materialToRedraw) {
                        redrawMaterialRow(
                            characterId,
                            materialToRedraw,
                            isUpdateOperation,
                            projectData
                        );
                    }

                    resetMaterialForm(characterId, storeUrl);

                    if (
                        response.data.costs_updated_for_characters &&
                        Array.isArray(
                            response.data.costs_updated_for_characters
                        )
                    ) {
                        response.data.costs_updated_for_characters.forEach(
                            (charId) =>
                                refreshCharacterCosts(projectData.id, charId)
                        );
                    } else if (response.data.costs_updated) {
                        refreshCharacterCosts(projectData.id, characterId);
                    }

                    if (response.data.message)
                        console.log("Server message:", response.data.message);
                } else {
                    handleFormError(
                        response,
                        response.data.message || "処理に失敗しました。",
                        `material-form-errors-${characterId}`,
                        response.data.errors
                    );
                }
            })
            .catch((error) =>
                handleFormError(
                    error.response,
                    "送信中にエラーが発生しました。",
                    `material-form-errors-${characterId}`,
                    error.response?.data?.errors
                )
            );
    });

    // 既存材料の「購入」チェックボックスのイベントリスナー
    materialsContentContainer.addEventListener("change", function (event) {
        const checkbox = event.target.closest(".material-status-checkbox");
        if (checkbox && checkbox.dataset.characterId === String(characterId)) {
            const canManageMaterials =
                projectData.can_manage_materials === undefined
                    ? true
                    : projectData.can_manage_materials;
            if (!canManageMaterials) {
                event.preventDefault();
                checkbox.checked = !checkbox.checked; // UIを元に戻す
                alert("ステータスを変更する権限がありません。");
                return;
            }

            const materialId = checkbox.dataset.id;
            const intendedStatus = checkbox.checked ? "購入済" : "未購入";
            const url = checkbox.dataset.url;
            const originalCheckedState = !checkbox.checked; // 変更前の状態を記録

            console.log(
                `[Material Status Change] ID: ${materialId}, Intended Status: ${intendedStatus}, Original Checked: ${originalCheckedState}`
            );
            // checkbox.disabled = true; // 処理中の無効化は一旦コメントアウト

            axios
                .put(
                    url,
                    { status: intendedStatus },
                    {
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            Accept: "application/json",
                            "Content-Type": "application/json",
                        },
                    }
                )
                .then((response) => {
                    console.log(
                        `[Material Status Change Response] ID: ${materialId}`,
                        JSON.stringify(response.data)
                    );
                    // checkbox.disabled = false;
                    if (response.data.success && response.data.material) {
                        // サーバーからの最新情報でUIを再描画
                        console.log(
                            `[SUCCESS] Redrawing row for ${materialId} with status from server: ${response.data.material.status}`
                        );
                        redrawMaterialRow(
                            characterId,
                            response.data.material,
                            true,
                            projectData
                        );

                        // 再描画後に再度チェックボックスのIDで要素を取得して状態を確認（デバッグ用）
                        const newCheckbox = document.getElementById(
                            `material-status-checkbox-${materialId}`
                        );
                        if (newCheckbox) {
                            console.log(
                                `Checkbox ${materialId} state after redraw: ${newCheckbox.checked} (Server status: ${response.data.material.status})`
                            );
                        } else {
                            console.warn(
                                `Checkbox ${materialId} not found after redraw.`
                            );
                        }

                        if (response.data.costs_updated) {
                            console.log(
                                `Refreshing costs for char ${characterId}`
                            );
                            refreshCharacterCosts(projectData.id, characterId);
                        }
                    } else {
                        // サーバーが success: false を返したか、material データがなかった場合
                        console.error(
                            `[ERROR/UNEXPECTED] ID: ${materialId}, ServerSuccess: ${
                                response.data.success
                            }, MaterialData: ${!!response.data.material}`
                        );
                        handleFormError(
                            response,
                            response.data.message ||
                                "ステータス更新に失敗しました(レスポンス形式不正)。",
                            `material-form-errors-${characterId}`
                        );
                        // UIをユーザー操作前の状態に戻す
                        if (checkbox) checkbox.checked = originalCheckedState;
                        alert(
                            response.data.message ||
                                "ステータス更新に失敗しました。表示を元に戻します。"
                        );
                    }
                })
                .catch((error) => {
                    console.error(
                        `[CATCH ERROR] ID: ${materialId}`,
                        error.response || error
                    );
                    // checkbox.disabled = false;
                    handleFormError(
                        error.response,
                        "ステータス更新中に通信エラーが発生しました。",
                        `material-form-errors-${characterId}`
                    );
                    // UIをユーザー操作前の状態に戻す
                    if (checkbox) checkbox.checked = originalCheckedState;
                    alert(
                        "ステータス更新中にエラーが発生しました。表示を元に戻します。"
                    );
                });
        }
    });
}
