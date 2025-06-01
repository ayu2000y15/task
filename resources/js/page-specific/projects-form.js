// resources/js/page-specific/projects-form.js

function initializeProjectForms() {
    // カラーピッカーとHEX入力フィールドの同期
    const colorPicker = document.getElementById("color");
    const colorHexInput = document.getElementById("colorHex");

    if (colorPicker && colorHexInput) {
        colorPicker.addEventListener("input", function () {
            colorHexInput.value = this.value;
        });
        // HEX入力からカラーピッカーへの反映は通常不要ですが、必要であれば追加
        // colorHexInput.addEventListener('change', function() {
        //     if (/^#[0-9A-F]{6}$/i.test(this.value)) {
        //         colorPicker.value = this.value;
        //     }
        // });
    }

    // 開始日と終了日のバリデーション (終了日は開始日以降)
    const startDateInput = document.getElementById("start_date");
    const endDateInput = document.getElementById("end_date");

    function validateProjectDates() {
        if (
            !startDateInput ||
            !endDateInput ||
            !startDateInput.value ||
            !endDateInput.value
        ) {
            // どちらかの日付が未入力の場合は案件依頼バリデーションをクリア
            if (endDateInput) endDateInput.setCustomValidity("");
            return;
        }

        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);

        if (endDate < startDate) {
            endDateInput.setCustomValidity(
                "終了日は開始日以降の日付を選択してください。"
            );
        } else {
            endDateInput.setCustomValidity("");
        }
        // ブラウザの標準バリデーションメッセージを表示するためにレポート
        // endDateInput.reportValidity(); // リアルタイムで表示すると入力途中で邪魔になる可能性あり
    }

    if (startDateInput && endDateInput) {
        startDateInput.addEventListener("change", validateProjectDates);
        endDateInput.addEventListener("change", validateProjectDates);
        // 初期ロード時にも一度バリデーションを実行（任意）
        // validateProjectDates();
    }
}

// DOMContentLoaded で初期化関数を呼び出す
document.addEventListener("DOMContentLoaded", () => {
    // このページ(案件作成・編集)に固有の要素があるか確認してから初期化
    if (document.getElementById("project-form-page")) {
        initializeProjectForms();
    }
});

export default {
    initializeProjectForms,
};
