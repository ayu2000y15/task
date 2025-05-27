// resources/js/page-specific/tasks-form.js

function initializeTaskFormEventListeners() {
    // 個別作成フォームの要素を取得 (テンプレート適用フォームとはIDを区別)
    const startDateInput = document.getElementById("start_date_individual");
    const durationInput = document.getElementById("duration_individual");
    const endDateInput = document.getElementById("end_date_individual");
    const taskTypeRadios = document.querySelectorAll(
        'input[name="is_milestone_or_folder"]'
    ); // name属性でグループを取得
    const taskFieldsDiv = document.getElementById("task-fields-individual");
    const statusFieldDiv = document.getElementById("status-field-individual");
    const characterIdWrapper = document.getElementById(
        "character_id_wrapper_individual"
    ); // 個別フォーム用
    const parentIdWrapper = document
        .getElementById("parent_id_individual")
        ?.closest("div"); // 親工程のラッパーdiv

    function formatDate(date) {
        if (!(date instanceof Date) || isNaN(date.getTime())) return "";
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    function updateEndDate() {
        const selectedTypeRadio = document.querySelector(
            'input[name="is_milestone_or_folder"]:checked'
        );
        if (
            !selectedTypeRadio ||
            !startDateInput ||
            !durationInput ||
            !endDateInput
        )
            return;
        const selectedType = selectedTypeRadio.value;

        if (selectedType === "milestone") {
            endDateInput.value = startDateInput.value;
            return;
        }
        if (selectedType === "todo_task") {
            endDateInput.value = "";
            return;
        }
        if (!startDateInput.value || !durationInput.value) {
            endDateInput.value = "";
            return;
        }
        const startDate = new Date(startDateInput.value + "T00:00:00");
        if (isNaN(startDate.getTime())) {
            endDateInput.value = "";
            return;
        }
        const duration = parseInt(durationInput.value);
        if (duration > 0) {
            const endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + duration - 1);
            endDateInput.value = formatDate(endDate);
        } else {
            endDateInput.value = "";
        }
    }

    function updateDuration() {
        const selectedTypeRadio = document.querySelector(
            'input[name="is_milestone_or_folder"]:checked'
        );
        if (
            !selectedTypeRadio ||
            !startDateInput ||
            !endDateInput ||
            !durationInput
        )
            return;
        const selectedType = selectedTypeRadio.value;

        if (selectedType === "milestone") {
            durationInput.value = startDateInput.value ? 1 : "";
            return;
        }
        if (selectedType === "todo_task") {
            durationInput.value = "";
            return;
        }
        if (!startDateInput.value || !endDateInput.value) {
            durationInput.value = "";
            return;
        }
        const startDate = new Date(startDateInput.value + "T00:00:00");
        const endDate = new Date(endDateInput.value + "T00:00:00");
        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
            durationInput.value = "";
            return;
        }

        if (endDate >= startDate) {
            const diffTime = endDate.getTime() - startDate.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            durationInput.value = diffDays > 0 ? diffDays : 1;
        } else {
            durationInput.value = 1;
        }
    }

    function toggleTaskTypeFields() {
        const selectedTypeRadio = document.querySelector(
            'input[name="is_milestone_or_folder"]:checked'
        );
        if (!selectedTypeRadio) return; // 何も選択されていない場合は何もしない
        const selectedType = selectedTypeRadio.value;

        const dateAndDurationFields = [
            startDateInput,
            durationInput,
            endDateInput,
        ];
        const statusSelect = document.getElementById("status_individual");

        // characterIdWrapper と parentIdWrapper が存在するか確認
        if (characterIdWrapper) characterIdWrapper.style.display = "block"; // 基本表示
        if (parentIdWrapper) parentIdWrapper.style.display = "block"; // 基本表示

        if (selectedType === "folder") {
            if (taskFieldsDiv) taskFieldsDiv.style.display = "none";
            if (statusFieldDiv) statusFieldDiv.style.display = "none";
            dateAndDurationFields.forEach((field) => {
                if (field) {
                    field.removeAttribute("required");
                    field.value = "";
                }
            });
            if (statusSelect) statusSelect.removeAttribute("required");
        } else if (selectedType === "milestone") {
            if (taskFieldsDiv) taskFieldsDiv.style.display = "grid";
            if (statusFieldDiv) statusFieldDiv.style.display = "block";
            if (startDateInput) {
                startDateInput.removeAttribute("disabled");
                startDateInput.setAttribute("required", "required");
                if (!startDateInput.value)
                    startDateInput.value = formatDate(new Date());
            }
            if (durationInput) {
                durationInput.value = 1;
                durationInput.setAttribute("disabled", "disabled"); // マイルストーンは工数固定
                durationInput.removeAttribute("required");
            }
            if (endDateInput) {
                endDateInput.value = startDateInput ? startDateInput.value : "";
                endDateInput.setAttribute("disabled", "disabled"); // マイルストーンは終了日固定
                endDateInput.removeAttribute("required");
            }
            if (statusSelect) statusSelect.setAttribute("required", "required");
        } else if (selectedType === "task") {
            if (taskFieldsDiv) taskFieldsDiv.style.display = "grid";
            if (statusFieldDiv) statusFieldDiv.style.display = "block";
            dateAndDurationFields.forEach((field) => {
                if (field) {
                    field.removeAttribute("disabled");
                    field.setAttribute("required", "required");
                }
            });
            if (durationInput) durationInput.removeAttribute("disabled");
            if (endDateInput) endDateInput.removeAttribute("disabled");

            if (startDateInput && !startDateInput.value)
                startDateInput.value = formatDate(new Date());
            if (durationInput && !durationInput.value) durationInput.value = 1;
            updateEndDate();

            if (statusSelect) statusSelect.setAttribute("required", "required");
        } else if (selectedType === "todo_task") {
            if (taskFieldsDiv) taskFieldsDiv.style.display = "none"; // 期限なしタスクでは日付関連を非表示
            if (statusFieldDiv) statusFieldDiv.style.display = "block";
            dateAndDurationFields.forEach((field) => {
                if (field) {
                    field.removeAttribute("disabled");
                    field.removeAttribute("required");
                    field.value = "";
                }
            });
            if (statusSelect) statusSelect.setAttribute("required", "required");
        }
    }

    if (startDateInput)
        startDateInput.addEventListener("change", updateEndDate);
    if (durationInput) {
        durationInput.addEventListener("input", updateEndDate);
        durationInput.addEventListener("change", updateEndDate); // Ensure change event also triggers
    }
    if (endDateInput) endDateInput.addEventListener("change", updateDuration);

    taskTypeRadios.forEach((radio) => {
        radio.addEventListener("change", toggleTaskTypeFields);
        // 初期状態でチェックされているラジオボタンに基づいてフィールド表示を更新
        if (radio.checked) {
            toggleTaskTypeFields();
        }
    });

    // 初期表示時の制御（もしラジオボタンが未選択の場合などに対応するため、明示的に呼び出す）
    if (
        !document.querySelector('input[name="is_milestone_or_folder"]:checked')
    ) {
        const defaultRadio = document.getElementById("is_task_type_task"); // デフォルトを「工程」に
        if (defaultRadio) {
            defaultRadio.checked = true;
        }
    }
    toggleTaskTypeFields();
}

if (document.getElementById("task-form-page")) {
    initializeTaskFormEventListeners();
}

export default {
    initializeTaskFormEventListeners,
};
