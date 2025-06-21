function initializeTaskFormEventListeners() {
    const page = document.getElementById("task-form-page");
    if (!page) return;

    // フォームの要素を取得
    const taskTypeOnEditPage = page.dataset.taskType;
    const taskTypeRadios = document.querySelectorAll(
        'input[name="is_milestone_or_folder"]'
    );

    const taskFieldsDiv = document.getElementById("task-fields-individual");
    const statusFieldDiv = document.getElementById("status-field-individual");
    const assigneeWrapper = document.getElementById(
        "assignees_wrapper_individual"
    );
    const fileManagementSection = document.getElementById(
        "file-management-section"
    );
    const characterIdWrapper =
        document.getElementById("character_id_wrapper_individual") ||
        document.getElementById("character_id_wrapper_individual_edit");
    const parentIdWrapper =
        document.getElementById("parent_id_wrapper_individual") ||
        document.getElementById("parent_id_wrapper_individual_edit");
    const durationWrapper =
        document.getElementById("duration_wrapper_individual") ||
        document.getElementById("duration_wrapper_individual_edit");
    const startDateInput = document.getElementById("start_date_individual");
    const endDateInput = document.getElementById("end_date_individual");

    // Tom Selectの初期化
    const assigneeSelect = document.getElementById("assignees_select");
    if (assigneeSelect) {
        new TomSelect(assigneeSelect, {
            plugins: ["remove_button"],
            create: false,
            placeholder: "担当者を検索・選択...",
        });
    }

    /**
     * 工程種別に応じてフォームの表示/非表示を切り替える
     */
    function toggleTaskTypeFields(selectedType) {
        if (!selectedType) return;

        const isMilestone = selectedType === "milestone";
        const isFolder = selectedType === "folder";
        const isTodoTask = selectedType === "todo_task";

        // --- 表示/非表示の制御 ---
        // 「予定」の場合: キャラクター、親工程、工数、担当者、ステータスを非表示
        if (characterIdWrapper)
            characterIdWrapper.style.display = isMilestone ? "none" : "block";
        if (parentIdWrapper)
            parentIdWrapper.style.display = isMilestone ? "none" : "block";
        if (durationWrapper)
            durationWrapper.style.display =
                isMilestone || isFolder || isTodoTask ? "none" : "block";
        if (assigneeWrapper)
            assigneeWrapper.style.display =
                isMilestone || isFolder ? "none" : "block";
        if (statusFieldDiv)
            statusFieldDiv.style.display =
                isMilestone || isFolder ? "none" : "block";

        // 日付関連フィールド
        if (taskFieldsDiv)
            taskFieldsDiv.style.display =
                isFolder || isTodoTask ? "none" : "block";

        // ファイル管理はフォルダの編集画面でのみ表示
        const isEditPage = !!document.getElementById("task-edit-form");
        if (fileManagementSection) {
            fileManagementSection.style.display =
                isEditPage && isFolder ? "block" : "none";
        }

        // --- 有効/無効 (disabled) 属性の制御 ---
        const isDateTimeDisabled = isFolder || isTodoTask;
        if (startDateInput) startDateInput.disabled = isDateTimeDisabled;
        if (endDateInput) endDateInput.disabled = isDateTimeDisabled; // 「予定」でも終了日時は有効

        const durationValueInput = document.getElementById("duration_value");
        const durationUnitSelect = document.getElementById("duration_unit");
        if (durationValueInput)
            durationValueInput.disabled = isFolder || isTodoTask || isMilestone;
        if (durationUnitSelect)
            durationUnitSelect.disabled = isFolder || isTodoTask || isMilestone;
    }

    // --- イベントリスナーの設定 ---
    if (taskTypeRadios.length > 0) {
        // createページの場合
        taskTypeRadios.forEach((radio) => {
            radio.addEventListener("change", (e) =>
                toggleTaskTypeFields(e.target.value)
            );
        });
    }

    // --- 初期表示の実行 ---
    const initialType =
        taskTypeRadios.length > 0
            ? document.querySelector(
                  'input[name="is_milestone_or_folder"]:checked'
              )?.value || "task"
            : taskTypeOnEditPage; // editページの場合

    toggleTaskTypeFields(initialType);
}

// ページが読み込まれたら初期化処理を実行
if (document.readyState === "loading") {
    document.addEventListener(
        "DOMContentLoaded",
        initializeTaskFormEventListeners
    );
} else {
    initializeTaskFormEventListeners();
}
