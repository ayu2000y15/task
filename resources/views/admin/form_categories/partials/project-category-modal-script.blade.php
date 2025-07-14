{{-- 案件カテゴリ新規追加モーダル --}}
<div id="add-category-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">新しい案件カテゴリを追加</h3>

            <div id="add-category-form-container">
                <div class="mb-4">
                    <label for="new_category_name"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">カテゴリ名（システム内部用）</label>
                    <input type="text" id="new_category_name" name="new_category_name" required
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        placeholder="例: new_category">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">半角英数字とアンダースコアのみ</p>
                </div>
                <div class="mb-4">
                    <label for="new_category_display_name"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">表示名</label>
                    <input type="text" id="new_category_display_name" name="new_category_display_name" required
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        placeholder="例: 新しいカテゴリ">
                </div>
                <div class="mb-4">
                    <label for="new_category_description"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">説明</label>
                    <textarea id="new_category_description" name="new_category_description" rows="3"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        placeholder="カテゴリの説明（任意）"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancel-add-category"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md">
                        キャンセル
                    </button>
                    <button type="button" id="submit-add-category"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                        <i class="fas fa-plus mr-1"></i>追加
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // フォーム部分のスクリプト
            const isExternalFormCheckbox = document.getElementById('is_external_form');
            const externalFormSettings = document.getElementById('external-form-settings');
            const requiresApprovalCheckbox = document.getElementById('requires_approval');
            const projectCategorySelection = document.getElementById('project-category-selection');

            const toggleExternalFormSettings = () => {
                if (isExternalFormCheckbox) externalFormSettings.style.display = isExternalFormCheckbox.checked ? 'block' : 'none';
            };
            const toggleProjectCategorySelection = () => {
                if (requiresApprovalCheckbox) projectCategorySelection.style.display = requiresApprovalCheckbox.checked ? 'block' : 'none';
            };

            isExternalFormCheckbox?.addEventListener('change', toggleExternalFormSettings);
            requiresApprovalCheckbox?.addEventListener('change', toggleProjectCategorySelection);
            toggleExternalFormSettings();
            toggleProjectCategorySelection();


            // モーダル部分のスクリプト
            const submitAddCategoryBtn = document.getElementById('submit-add-category');
            const addCategoryModal = document.getElementById('add-category-modal');
            const cancelAddCategory = document.getElementById('cancel-add-category');
            const addCategoryBtn = document.getElementById('add-category-btn');
            const projectCategorySelect = document.getElementById('project_category_id');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) console.error('CSRFトークンが見つかりません。');
            if (!submitAddCategoryBtn) console.error('「追加」ボタンが見つかりません。');

            const showAddCategoryModal = () => {
                addCategoryModal?.classList.remove('hidden');
                document.getElementById('new_category_name')?.focus();
            };

            const hideAddCategoryModal = () => {
                if (addCategoryModal) {
                    addCategoryModal.classList.add('hidden');
                    document.getElementById('new_category_name').value = '';
                    document.getElementById('new_category_display_name').value = '';
                    document.getElementById('new_category_description').value = '';
                }
            };

            const addNewCategory = async () => {
                if (!csrfToken) {
                    alert('エラー: ページを再読み込みしてください。');
                    return;
                }

                const name = document.getElementById('new_category_name').value;
                const displayName = document.getElementById('new_category_display_name').value;
                const description = document.getElementById('new_category_description').value;

                const requestData = { name: name, display_name: displayName, description: description };

                if (!requestData.name || !requestData.display_name) {
                    alert('カテゴリ名と表示名は必須です。');
                    return;
                }
                if (!/^[a-z0-9_]+$/.test(requestData.name)) {
                    alert('カテゴリ名は半角英数字とアンダースコアのみ使用できます');
                    return;
                }

                submitAddCategoryBtn.disabled = true;
                submitAddCategoryBtn.textContent = '追加中...';

                try {
                    const response = await fetch('{{ route("admin.project-categories.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(requestData)
                    });
                    const result = await response.json();
                    if (response.ok) {
                        if (projectCategorySelect) {
                            const option = new Option(result.display_name || result.name, result.id, true, true);
                            projectCategorySelect.add(option);
                            projectCategorySelect.value = result.id;
                        }
                        hideAddCategoryModal();
                    } else {
                        const errorMessage = result.errors ? Object.values(result.errors).flat().join('\n') : (result.message || 'エラーが発生しました');
                        alert('エラー:\n' + errorMessage);
                    }
                } catch (error) {
                    console.error('通信エラー:', error);
                    alert('重大な通信エラーが発生しました。');
                } finally {
                    submitAddCategoryBtn.disabled = false;
                    submitAddCategoryBtn.innerHTML = '<i class="fas fa-plus mr-1"></i>追加';
                }
            };

            addCategoryBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                showAddCategoryModal();
            });

            cancelAddCategory?.addEventListener('click', hideAddCategoryModal);
            submitAddCategoryBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                addNewCategory();
            });
        });
    </script>
@endpush
