// resources/js/page-specific/project-show-measurements.js
import axios from "axios";
import {
    handleFormError,
    getCsrfToken,
    escapeHtml,
    nl2br,
} from "./project-show-utils.js";

export function resetMeasurementForm(characterId, storeUrl) {
    const form = document.getElementById(`measurement-form-${characterId}`);
    if (!form) {
        console.warn(
            `Measurement form not found for reset: measurement-form-${characterId}`
        );
        return;
    }
    form.reset();
    form.setAttribute("action", storeUrl);
    const methodField = document.getElementById(
        `measurement-form-method-${characterId}`
    );
    if (methodField) methodField.value = "POST";
    const idField = document.getElementById(
        `measurement-form-id-${characterId}`
    );
    if (idField) idField.value = "";

    const titleEl = document.getElementById(
        `measurement-form-title-${characterId}`
    );
    if (titleEl) titleEl.textContent = "採寸データを追加";

    const submitBtnTextEl = document.getElementById(
        `measurement-form-submit-btn-text-${characterId}`
    );
    if (submitBtnTextEl) submitBtnTextEl.textContent = "追加";

    const submitBtnEl = document.getElementById(
        `measurement-form-submit-btn-${characterId}`
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
        `measurement-form-cancel-btn-${characterId}`
    );
    if (cancelBtn) cancelBtn.style.display = "none";

    const errorDiv = document.getElementById(
        `measurement-form-errors-${characterId}`
    );
    if (errorDiv) errorDiv.innerHTML = "";

    form.querySelectorAll(".form-input, .form-select, .form-textarea").forEach(
        (input) => {
            input.classList.remove("border-red-500", "dark:border-red-600");
        }
    );
}

export function redrawMeasurementRow(
    characterId,
    measurementData,
    isUpdate,
    projectData
) {
    const tbody = document.querySelector(
        `#measurements-content-${characterId} table tbody`
    );
    if (!tbody) {
        console.warn(
            `Measurement table body not found for character ${characterId}`
        );
        return;
    }

    const existingRow = document.getElementById(
        `measurement-row-${measurementData.id}`
    );
    const notesDisplay = measurementData.notes
        ? nl2br(escapeHtml(measurementData.notes))
        : "-";
    const csrfToken = getCsrfToken();

    let actionsHtml = '<div class="flex items-center justify-end space-x-1">';
    actionsHtml += `
        <button type="button" class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-measurement-btn"
                title="編集"
                data-id="${measurementData.id}"
                data-item="${escapeHtml(measurementData.item || "")}"
                data-value="${escapeHtml(measurementData.value || "")}"
                data-notes="${escapeHtml(measurementData.notes || "")}">
            <i class="fas fa-edit fa-sm"></i>
        </button>`;
    actionsHtml += `
        <form action="/projects/${projectData.id}/characters/${characterId}/measurements/${measurementData.id}"
              method="POST" class="delete-measurement-form"
              data-id="${measurementData.id}"
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
        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-item">${escapeHtml(
            measurementData.item
        )}</td>
        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-value">${escapeHtml(
            measurementData.value
        )}</td>
        <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 whitespace-pre-wrap break-words text-left leading-tight measurement-notes" style="min-width: 150px;">
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
        newTr.id = `measurement-row-${measurementData.id}`;
        newTr.classList.add("hover:bg-gray-50", "dark:hover:bg-gray-700/50");
        newTr.innerHTML = newRowCellsHtml;
        const emptyRow = tbody.querySelector('td[colspan="4"]');
        if (emptyRow) emptyRow.parentElement.remove();
        tbody.appendChild(newTr);
    }
}

export function initializeMeasurementInteractions(
    measurementsContentContainer,
    characterId,
    csrfToken,
    projectData
) {
    const measurementForm = document.getElementById(
        `measurement-form-${characterId}`
    );
    if (!measurementForm) {
        console.warn(`Measurement form not found for character ${characterId}`);
        return;
    }

    const storeUrl = measurementForm.dataset.storeUrl;
    const formTitle = document.getElementById(
        `measurement-form-title-${characterId}`
    );
    const submitBtn = document.getElementById(
        `measurement-form-submit-btn-${characterId}`
    );
    const submitBtnText = document.getElementById(
        `measurement-form-submit-btn-text-${characterId}`
    );
    const cancelBtn = document.getElementById(
        `measurement-form-cancel-btn-${characterId}`
    );
    const methodField = document.getElementById(
        `measurement-form-method-${characterId}`
    );
    const idField = document.getElementById(
        `measurement-form-id-${characterId}`
    );
    const errorDivId = `measurement-form-errors-${characterId}`;

    measurementsContentContainer.addEventListener("click", function (event) {
        const editButton = event.target.closest(".edit-measurement-btn");
        if (editButton) {
            event.preventDefault();
            if (formTitle) formTitle.textContent = "採寸データを編集";
            measurementForm.setAttribute(
                "action",
                `/projects/${projectData.id}/characters/${characterId}/measurements/${editButton.dataset.id}`
            );
            if (methodField) methodField.value = "PUT";
            if (idField) idField.value = editButton.dataset.id;

            const itemInput = measurementForm.querySelector(
                `[id="measurement_item_input-${characterId}"]`
            );
            const valueInput = measurementForm.querySelector(
                `[id="measurement_value_input-${characterId}"]`
            );
            const notesInput = measurementForm.querySelector(
                `[id="measurement_notes_input-${characterId}"]`
            );

            if (itemInput) itemInput.value = editButton.dataset.item;
            if (valueInput) valueInput.value = editButton.dataset.value;
            if (notesInput) notesInput.value = editButton.dataset.notes || "";

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
            if (itemInput) itemInput.focus();
        }

        const deleteFormElement = event.target.closest(
            ".delete-measurement-form"
        );
        if (deleteFormElement) {
            event.preventDefault();
            if (confirm("この採寸データを本当に削除しますか？")) {
                axios
                    .delete(deleteFormElement.action, {
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            Accept: "application/json",
                        },
                    })
                    .then((response) => {
                        if (response.data.success) {
                            const rowId = `measurement-row-${deleteFormElement.dataset.id}`;
                            const rowToRemove = document.getElementById(rowId);
                            if (rowToRemove) rowToRemove.remove();
                            resetMeasurementForm(characterId, storeUrl);
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
            resetMeasurementForm(characterId, storeUrl);
        });
    }

    measurementForm.addEventListener("submit", function (event) {
        event.preventDefault();
        const errorDiv = document.getElementById(errorDivId);
        if (errorDiv) errorDiv.innerHTML = "";
        const formData = new FormData(measurementForm);
        const actionUrl = measurementForm.getAttribute("action");
        let httpMethod = "post";

        if (
            document.getElementById(`measurement-form-method-${characterId}`)
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
                if (response.data.success && response.data.measurement) {
                    const isUpdate =
                        document.getElementById(
                            `measurement-form-method-${characterId}`
                        ).value === "PUT";
                    redrawMeasurementRow(
                        characterId,
                        response.data.measurement,
                        isUpdate,
                        projectData
                    );
                    resetMeasurementForm(characterId, storeUrl);
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
}
