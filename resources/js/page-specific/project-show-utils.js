// resources/js/page-specific/project-show-utils.js
import axios from "axios";

export const deliveryFlagData = {
    0: {
        tooltip: "未納品",
        icon: "fa-truck",
        colorClass: "text-yellow-500 dark:text-yellow-400",
    },
    1: {
        tooltip: "納品済み",
        icon: "fa-check-circle",
        colorClass: "text-green-500 dark:text-green-400",
    },
};
export const paymentFlagData = {
    Pending: {
        tooltip: "未払い",
        icon: "fa-clock",
        colorClass: "text-yellow-500 dark:text-yellow-400",
    },
    Processing: {
        tooltip: "支払い中",
        icon: "fa-hourglass-half", // 変更後の静的アイコン
        colorClass: "text-blue-500 dark:text-blue-400",
    },
    Completed: {
        tooltip: "支払完了",
        icon: "fa-check-circle",
        colorClass: "text-green-500 dark:text-green-400",
    },
    "Partially Paid": {
        tooltip: "一部支払い",
        icon: "fa-adjust",
        colorClass: "text-orange-500 dark:text-orange-400",
    },
    Overdue: {
        tooltip: "期限切れ",
        icon: "fa-exclamation-triangle",
        colorClass: "text-red-500 dark:text-red-400",
    },
    Cancelled: {
        tooltip: "キャンセル",
        icon: "fa-ban",
        colorClass: "text-gray-500 dark:text-gray-400",
    },
    Refunded: {
        tooltip: "返金済み",
        icon: "fa-undo",
        colorClass: "text-purple-500 dark:text-purple-400",
    },
    "On Hold": {
        tooltip: "一時停止中",
        icon: "fa-pause-circle",
        colorClass: "text-indigo-500 dark:text-indigo-400",
    },
    "": {
        tooltip: "未設定",
        icon: "fa-question-circle",
        colorClass: "text-gray-400 dark:text-gray-500",
    },
};
export const projectStatusData = {
    not_started: {
        tooltip: "未着手",
        icon: "fa-minus-circle",
        colorClass: "text-gray-500 dark:text-gray-400",
    },
    in_progress: {
        tooltip: "進行中",
        icon: "fa-play-circle",
        colorClass: "text-blue-500 dark:text-blue-400",
    },
    completed: {
        tooltip: "完了",
        icon: "fa-check-circle",
        colorClass: "text-green-500 dark:text-green-400",
    },
    on_hold: {
        tooltip: "一時停止中",
        icon: "fa-pause-circle",
        colorClass: "text-yellow-500 dark:text-yellow-400",
    },
    cancelled: {
        tooltip: "キャンセル",
        icon: "fa-times-circle",
        colorClass: "text-red-500 dark:text-red-400",
    },
    "": {
        tooltip: "未設定",
        icon: "fa-question-circle",
        colorClass: "text-gray-400 dark:text-gray-500",
    },
};

export function updateIconAndTooltip(iconElement, value, map) {
    if (iconElement) {
        const defaultValueKey = Object.keys(map).includes("")
            ? ""
            : Object.keys(map)[0];
        const mapping = map[value] ||
            map[defaultValueKey] || {
                tooltip: value || "-",
                icon: "fa-question-circle",
                colorClass: "text-gray-400 dark:text-gray-500",
            };
        iconElement.title = mapping.tooltip;
        const iTag = iconElement.querySelector("i");
        if (iTag) {
            const classesToRemove = Array.from(iTag.classList).filter(
                (cls) =>
                    cls.startsWith("fa-") ||
                    cls.startsWith("text-") ||
                    cls.startsWith("dark:text-")
            );
            iTag.classList.remove(...classesToRemove);
            const newIconClasses = mapping.icon.split(" ");
            iTag.classList.add("fas", ...newIconClasses);
            if (mapping.colorClass) {
                mapping.colorClass
                    .split(" ")
                    .forEach((cls) => iTag.classList.add(cls));
            }
        } else {
            iconElement.innerHTML = `<i class="fas ${mapping.icon} ${
                mapping.colorClass || ""
            }"></i>`;
        }
    }
}

export function handleProjectFlagOrStatusUpdate(flagSelectElement, csrfToken) {
    const projectId = flagSelectElement.dataset.projectId;
    const fieldName = flagSelectElement.name;
    const value = flagSelectElement.value;
    const url = flagSelectElement.dataset.url;
    const iconTargetId = flagSelectElement.dataset.iconTarget;
    const iconElement = document.getElementById(iconTargetId);
    // const originalValue = flagSelectElement.dataset.originalValue || flagSelectElement.querySelector(`option[value="${flagSelectElement.value}"]`).defaultSelected ? flagSelectElement.value : null;

    axios
        .patch(
            url,
            {
                [fieldName]: value,
            },
            {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
            }
        )
        .then((response) => {
            if (response.data.success) {
                if (iconElement) {
                    let map;
                    if (fieldName === "delivery_flag") map = deliveryFlagData;
                    else if (fieldName === "payment_flag")
                        map = paymentFlagData;
                    else if (fieldName === "status") map = projectStatusData;

                    if (map) {
                        updateIconAndTooltip(iconElement, value, map);
                    }
                }
                if (response.data.updated_project_status !== undefined) {
                    const projectStatusSelectId =
                        flagSelectElement.dataset.statusTargetSelect;
                    const projectStatusIconId =
                        flagSelectElement.dataset.statusTargetIcon;

                    if (projectStatusSelectId) {
                        const projectStatusSelect = document.getElementById(
                            projectStatusSelectId
                        );
                        if (projectStatusSelect) {
                            projectStatusSelect.value =
                                response.data.updated_project_status;
                        }
                    }
                    if (projectStatusIconId) {
                        const projectStatusIcon =
                            document.getElementById(projectStatusIconId);
                        if (projectStatusIcon) {
                            updateIconAndTooltip(
                                projectStatusIcon,
                                response.data.updated_project_status,
                                projectStatusData
                            );
                        }
                    }
                }
                // flagSelectElement.dataset.originalValue = value;
            } else {
                alert(
                    "更新に失敗しました: " +
                        (response.data.message || "サーバーエラー")
                );
                // if (originalValue !== null) flagSelectElement.value = originalValue;
            }
        })
        .catch((error) => {
            let errorMessage = "更新中にエラーが発生しました。";
            if (
                error.response &&
                error.response.data &&
                error.response.data.message
            ) {
                errorMessage += "\n" + error.response.data.message;
            } else if (error.response && error.response.status) {
                errorMessage += ` (ステータス: ${error.response.status})`;
            }
            alert(errorMessage);
            // if (originalValue !== null) flagSelectElement.value = originalValue;
        });
}

export function handleFormError(
    response,
    defaultMessage = "処理に失敗しました。",
    errorDivId = null
) {
    let errorMessage = defaultMessage;
    let errors = null;

    if (response && response.data) {
        if (
            response.data.message &&
            (typeof response.data.message !== "object" ||
                Object.keys(response.data.message).length > 0)
        ) {
            errorMessage = response.data.message;
        }
        if (response.data.errors) {
            errors = response.data.errors;
            let detailedErrors = [];
            for (const key in errors) {
                detailedErrors.push(errors[key].join("; "));
            }
            if (detailedErrors.length > 0 && errorMessage === defaultMessage)
                errorMessage = detailedErrors.join("\n");
        }
    } else if (response && typeof response === "string") {
        errorMessage = response;
    }

    const errorDiv = errorDivId ? document.getElementById(errorDivId) : null;
    if (errorDiv && errors) {
        let errorMessagesHtml =
            '<ul class="list-disc pl-5 text-red-600 dark:text-red-400">';
        for (const key in errors) {
            errors[key].forEach((message) => {
                errorMessagesHtml += `<li>${escapeHtml(message)}</li>`;
            });
        }
        errorMessagesHtml += "</ul>";
        errorDiv.innerHTML = errorMessagesHtml;

        const form = errorDiv.closest("form");
        if (form) {
            Object.keys(errors).forEach((fieldName) => {
                const inputName = fieldName.includes(".")
                    ? fieldName
                          .split(".")
                          .map((part, index) =>
                              index === 0 ? part : `[${part}]`
                          )
                          .join("")
                    : fieldName;
                const field = form.querySelector(
                    `[name="${inputName}"], [name="${inputName}[]"]`
                );
                if (field) {
                    field.classList.add(
                        "border-red-500",
                        "dark:border-red-600"
                    );
                }
            });
        }
    } else if (errorDiv && errorMessage) {
        errorDiv.innerHTML = `<p class="text-red-600 dark:text-red-400">${nl2br(
            escapeHtml(String(errorMessage))
        )}</p>`;
    } else if (errorMessage && !errorDiv) {
        // Only alert if no specific errorDiv to show the message
        alert(String(errorMessage));
    }
}

export function getCsrfToken() {
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    return tokenElement ? tokenElement.getAttribute("content") : null;
}

export function escapeHtml(unsafe) {
    if (unsafe === null || typeof unsafe === "undefined") {
        return "";
    }
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

export function nl2br(str) {
    if (typeof str === "undefined" || str === null) {
        return "";
    }
    return String(str).replace(/(\r\n|\n\r|\r|\n)/g, "<br>$1");
}

export function refreshCharacterCosts(projectId, characterId) {
    const costsTabContainer = document.getElementById(
        `costs-content-${characterId}`
    );
    if (!costsTabContainer) {
        console.warn(
            `Could not find costs tab container for character: ${characterId}`
        );
        return;
    }
    const costsPartialUrl = `/projects/${projectId}/characters/${characterId}/costs-partial`;

    axios
        .get(costsPartialUrl)
        .then((response) => {
            costsTabContainer.innerHTML = response.data;
        })
        .catch((error) => {
            console.error("Error refreshing costs list:", error);
            costsTabContainer.innerHTML =
                '<p class="text-red-500 p-4">コスト情報の更新に失敗しました。</p>';
        });
}
