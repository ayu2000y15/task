<div class="space-y-6">
    {{-- Hidden type field --}}
    <input type="hidden" name="type" value="form">

    {{-- 基本情報 --}}
    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 dark:tex    // 案件カテゴリ選択欄の表示/非表示
    function toggleProjectCategorySelection() {
        console.log('toggleProjectCategorySelection called');
        if (requiresApprovalCheckbox && projectCategorySelection) {
            console.log('requiresApproval checked:', requiresApprovalCheckbox.checked);
            if (requiresApprovalCheckbox.checked) {
                projectCategorySelection.style.display = 'block';
                console.log('Project category selection shown');
            } else {
                projectCategorySelection.style.display = 'none';
                console.log('Project category selection hidden');
            }
        } else {
            console.error('Required elements not found:', {
                requiresApprovalCheckbox: !!requiresApprovalCheckbox,
                projectCategorySelection: !!projectCategorySelection
            });
        }
    } mb-4">基本情報</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="name" value="カテゴリ名（システム内部用）" :required="true" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                    :value="old('name', $formCategory->name ?? '')" required
                    placeholder="例: contact_inquiry" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">半角英数字とアンダースコアのみ。作成後の変更は推奨されません。</p>
            </div>

            <div>
                <x-input-label for="display_name" value="表示名" :required="true" />
                <x-text-input id="display_name" name="display_name" type="text" class="mt-1 block w-full"
                    :value="old('display_name', $formCategory->display_name ?? '')" required
                    placeholder="例: お問い合わせ" />
                <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="description" value="説明" />
                <x-textarea-input id="description" name="description" class="mt-1 block w-full" rows="3"
                    placeholder="このカテゴリの用途や目的を記入してください">{{ old('description', $formCategory->description ?? '') }}</x-textarea-input>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="order" value="表示順序" />
                <x-text-input id="order" name="order" type="number" min="0" class="mt-1 block w-full"
                    :value="old('order', $formCategory->order ?? 0)" />
                <x-input-error :messages="$errors->get('order')" class="mt-2" />
            </div>

            <div class="flex items-center">
                <x-checkbox-input id="is_enabled" name="is_enabled" value="1"
                    :label="'このカテゴリを有効にする'"
                    :checked="old('is_enabled', $formCategory->is_enabled ?? true)" />
            </div>
        </div>
    </div>

    {{-- 外部フォーム設定 --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">外部フォーム設定</h3>

        <div class="mb-4">
            <x-checkbox-input id="is_external_form" name="is_external_form" value="1"
                :label="'外部フォームとして公開する'"
                :checked="old('is_external_form', $formCategory->is_external_form ?? false)" />
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">チェックすると、このカテゴリのフィールドを使った外部フォームが作成されます。</p>
        </div>

        <div id="external-form-settings" class="space-y-4" style="display: {{ old('is_external_form', $formCategory->is_external_form ?? false) ? 'block' : 'none' }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="slug" value="URLスラッグ" />
                    <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full"
                        :value="old('slug', $formCategory->slug ?? '')"
                        placeholder="例: contact-inquiry" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        外部フォームのURL: <span class="font-mono">{{ url('/forms/') }}/<span id="slug-preview">{{ old('slug', $formCategory->slug ?? 'your-slug') }}</span></span>
                    </p>
                </div>

                <div>
                    <x-input-label for="form_title" value="フォーム画面タイトル" />
                    <x-text-input id="form_title" name="form_title" type="text" class="mt-1 block w-full"
                        :value="old('form_title', $formCategory->form_title ?? '')"
                        placeholder="例: お問い合わせフォーム" />
                    <x-input-error :messages="$errors->get('form_title')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="form_description" value="フォーム画面説明文" />
                    <x-textarea-input id="form_description" name="form_description" class="mt-1 block w-full" rows="3"
                        placeholder="フォームの説明や注意事項を記入してください">{{ old('form_description', $formCategory->form_description ?? '') }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('form_description')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="thank_you_title" value="送信完了画面タイトル" />
                    <x-text-input id="thank_you_title" name="thank_you_title" type="text" class="mt-1 block w-full"
                        :value="old('thank_you_title', $formCategory->thank_you_title ?? '')"
                        placeholder="例: お問い合わせありがとうございます" />
                    <x-input-error :messages="$errors->get('thank_you_title')" class="mt-2" />
                </div>

                <div>
                    <x-checkbox-input id="requires_approval" name="requires_approval" value="1"
                        :label="'案件化用'"
                        :checked="old('requires_approval', $formCategory->requires_approval ?? false)" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        チェックすると外部フォーム一覧で案件化ボタンが表示されます
                    </p>
                </div>

                <div id="project-category-selection" style="display: {{ old('requires_approval', $formCategory->requires_approval ?? false) ? 'block' : 'none' }}">
                    <x-input-label for="project_category_id" value="デフォルト案件カテゴリ" />
                    <div class="flex gap-2">
                        <select id="project_category_id" name="project_category_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="">カテゴリを選択（任意）</option>
                            @foreach(App\Models\ProjectCategory::orderBy('display_order')->orderBy('name')->get() as $category)
                                <option value="{{ $category->id }}" {{ old('project_category_id', $formCategory->project_category_id ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->display_name ?? $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" id="add-category-btn" onclick="window.showAddCategoryModal && window.showAddCategoryModal()" class="mt-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-md whitespace-nowrap">
                            <i class="fas fa-plus mr-1"></i>新規追加
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('project_category_id')" class="mt-2" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        案件化時にデフォルトで選択される案件カテゴリです
                    </p>
                </div>

                <div>
                    <x-checkbox-input id="send_completion_email" name="send_completion_email" value="1"
                        :label="'送信完了メールを送信'"
                        :checked="old('send_completion_email', $formCategory->send_completion_email ?? false)" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        チェックすると送信者に完了メールが送信されます
                    </p>
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="thank_you_message" value="送信完了画面メッセージ" />
                    <x-textarea-input id="thank_you_message" name="thank_you_message" class="mt-1 block w-full" rows="3"
                        placeholder="送信完了後に表示するメッセージを記入してください">{{ old('thank_you_message', $formCategory->thank_you_message ?? '') }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('thank_you_message')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="notification_emails_string" value="通知先メールアドレス" />
                    <x-text-input id="notification_emails_string" name="notification_emails_string" type="text" class="mt-1 block w-full"
                        :value="old('notification_emails_string', $formCategory->notification_emails_string ?? '')"
                        placeholder="例: admin@example.com, support@example.com" />
                    <x-input-error :messages="$errors->get('notification_emails_string')" class="mt-2" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">フォーム提出時に通知を送信するメールアドレス（カンマ区切りで複数指定可能）</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 案件カテゴリ新規追加モーダル --}}
    <div id="add-category-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">新しい案件カテゴリを追加</h3>
                <form id="add-category-form" novalidate>
                    <div class="mb-4">
                        <label for="new_category_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">カテゴリ名（システム内部用）</label>
                        <input type="text" id="new_category_name" name="new_category_name" required 
                               class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                               placeholder="例: new_category">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">半角英数字とアンダースコアのみ</p>
                    </div>
                    <div class="mb-4">
                        <label for="new_category_display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">表示名</label>
                        <input type="text" id="new_category_display_name" name="new_category_display_name" required 
                               class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                               placeholder="例: 新しいカテゴリ">
                    </div>
                    <div class="mb-4">
                        <label for="new_category_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">説明</label>
                        <textarea id="new_category_description" name="new_category_description" rows="3"
                                  class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                  placeholder="カテゴリの説明（任意）"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" id="cancel-add-category" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md">
                            キャンセル
                        </button>
                        <button type="button" id="submit-add-category" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                            <i class="fas fa-plus mr-1"></i>追加
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 保存ボタン --}}
    <div class="flex justify-end space-x-3 pt-6 border-t dark:border-gray-700">
        <x-secondary-button as="a" href="{{ route('admin.form-categories.index') }}">
            キャンセル
        </x-secondary-button>
        <x-primary-button type="submit">
            <i class="fas fa-save mr-2"></i> 保存
        </x-primary-button>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Starting initialization');
    
    const isExternalFormCheckbox = document.getElementById('is_external_form');
    const externalFormSettings = document.getElementById('external-form-settings');
    const slugInput = document.getElementById('slug');
    const slugPreview = document.getElementById('slug-preview');
    const nameInput = document.getElementById('name');
    const requiresApprovalCheckbox = document.getElementById('requires_approval');
    const projectCategorySelection = document.getElementById('project-category-selection');
    const addCategoryBtn = document.getElementById('add-category-btn');
    const addCategoryModal = document.getElementById('add-category-modal');
    const addCategoryForm = document.getElementById('add-category-form');
    const cancelAddCategory = document.getElementById('cancel-add-category');
    const projectCategorySelect = document.getElementById('project_category_id');

    // 要素の存在確認
    console.log('Elements found:', {
        isExternalFormCheckbox: !!isExternalFormCheckbox,
        requiresApprovalCheckbox: !!requiresApprovalCheckbox,
        projectCategorySelection: !!projectCategorySelection,
        addCategoryBtn: !!addCategoryBtn,
        addCategoryModal: !!addCategoryModal,
        addCategoryForm: !!addCategoryForm,
        cancelAddCategory: !!cancelAddCategory,
        projectCategorySelect: !!projectCategorySelect
    });

    // 外部フォーム設定の表示/非表示
    function toggleExternalFormSettings() {
        if (isExternalFormCheckbox && isExternalFormCheckbox.checked) {
            if (externalFormSettings) {
                externalFormSettings.style.display = 'block';
            }
            // スラッグが空の場合、nameから自動生成
            if (slugInput && nameInput && !slugInput.value && nameInput.value) {
                slugInput.value = nameInput.value.replace(/_/g, '-');
                updateSlugPreview();
            }
        } else {
            if (externalFormSettings) {
                externalFormSettings.style.display = 'none';
            }
        }
    }

    // 案件カテゴリ選択の表示/非表示
    function toggleProjectCategorySelection() {
        if (requiresApprovalCheckbox && projectCategorySelection) {
            if (requiresApprovalCheckbox.checked) {
                projectCategorySelection.style.display = 'block';
            } else {
                projectCategorySelection.style.display = 'none';
            }
        }
    }

    // スラッグプレビューの更新
    function updateSlugPreview() {
        if (slugInput && slugPreview) {
            const slug = slugInput.value || 'your-slug';
            slugPreview.textContent = slug;
        }
    }

    // モーダル表示（グローバルにも設定）
    function showAddCategoryModal() {
        console.log('showAddCategoryModal called');
        if (addCategoryModal) {
            console.log('Showing modal');
            addCategoryModal.classList.remove('hidden');
            const newCategoryNameInput = document.getElementById('new_category_name');
            if (newCategoryNameInput) {
                newCategoryNameInput.focus();
            }
        } else {
            console.error('addCategoryModal not found');
        }
    }
    
    // グローバルスコープにも設定
    window.showAddCategoryModal = showAddCategoryModal;

    // モーダル非表示
    function hideAddCategoryModal() {
        if (addCategoryModal) {
            addCategoryModal.classList.add('hidden');
        }
        if (addCategoryForm) {
            addCategoryForm.reset();
        }
    }

    // 新規カテゴリ追加
    async function addNewCategory(formData) {
        console.log('Starting category creation...');
        
        const requestData = {
            name: formData.get('new_category_name'),
            display_name: formData.get('new_category_display_name'),
            description: formData.get('new_category_description'),
            display_order: 0
        };
        
        console.log('Request data:', requestData);
        
        // クライアント側バリデーション
        if (!requestData.name || !requestData.display_name) {
            showNotification('カテゴリ名と表示名は必須です', 'error');
            return;
        }
        
        // カテゴリ名の形式チェック
        if (!/^[a-z0-9_]+$/.test(requestData.name)) {
            showNotification('カテゴリ名は半角英数字とアンダースコアのみ使用できます', 'error');
            return;
        }
        
        try {
            const response = await fetch('{{ route("admin.project-categories.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));

            const responseText = await response.text();
            console.log('Raw response:', responseText);

            let result;
            try {
                result = JSON.parse(responseText);
                console.log('Parsed response:', result);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                showNotification('サーバーから不正なレスポンスが返されました', 'error');
                return;
            }

            if (response.ok) {
                console.log('Category created successfully:', result);
                
                // セレクトボックスに新しいオプションを追加
                if (projectCategorySelect) {
                    const option = new Option(result.display_name || result.name, result.id);
                    projectCategorySelect.add(option);
                    // 新しく追加したカテゴリを選択
                    projectCategorySelect.value = result.id;
                    console.log('Option added to select, value set to:', result.id);
                }
                
                hideAddCategoryModal();
                
                // 成功メッセージを表示
                showNotification('案件カテゴリが追加されました', 'success');
            } else {
                console.error('Server error:', result);
                let errorMessage = 'エラーが発生しました';
                
                if (result.message) {
                    errorMessage = result.message;
                } else if (result.errors) {
                    // バリデーションエラーの場合
                    const errorArray = Object.values(result.errors).flat();
                    errorMessage = errorArray.join(', ');
                }
                
                showNotification(errorMessage, 'error');
            }
        } catch (error) {
            console.error('Network/Request error:', error);
            showNotification('ネットワークエラーが発生しました: ' + error.message, 'error');
        }
    }

    // 通知表示
    function showNotification(message, type = 'info') {
        // 簡単な通知システム
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-4 py-2 rounded-md text-white z-50 ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 'bg-blue-500'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // イベントリスナー
    if (isExternalFormCheckbox) {
        isExternalFormCheckbox.addEventListener('change', toggleExternalFormSettings);
        console.log('Added change listener to isExternalFormCheckbox');
    }
    if (requiresApprovalCheckbox) {
        requiresApprovalCheckbox.addEventListener('change', toggleProjectCategorySelection);
        console.log('Added change listener to requiresApprovalCheckbox');
    }
    if (slugInput) {
        slugInput.addEventListener('input', updateSlugPreview);
        console.log('Added input listener to slugInput');
    }
    if (addCategoryBtn) {
        addCategoryBtn.addEventListener('click', function(e) {
            console.log('Add category button clicked!');
            e.preventDefault();
            showAddCategoryModal();
        });
        console.log('Added click listener to addCategoryBtn');
    } else {
        console.error('addCategoryBtn not found!');
    }
    if (cancelAddCategory) {
        cancelAddCategory.addEventListener('click', hideAddCategoryModal);
        console.log('Added click listener to cancelAddCategory');
    }

    // モーダル外クリックで閉じる
    if (addCategoryModal) {
        addCategoryModal.addEventListener('click', function(e) {
            if (e.target === addCategoryModal) {
                hideAddCategoryModal();
            }
        });
    }

    // 新規カテゴリ追加ボタンのクリック処理
    const submitAddCategoryBtn = document.getElementById('submit-add-category');
    if (submitAddCategoryBtn) {
        submitAddCategoryBtn.addEventListener('click', function(e) {
            console.log('Submit add category button clicked!');
            e.preventDefault();
            e.stopPropagation();
            
            if (addCategoryForm) {
                const formData = new FormData(addCategoryForm);
                addNewCategory(formData);
            } else {
                console.error('addCategoryForm not found');
            }
        });
        console.log('Added click listener to submitAddCategoryBtn');
    } else {
        console.error('submitAddCategoryBtn not found!');
    }

    // nameからslugへの自動変換（外部フォームが有効で、slugが空の場合のみ）
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            if (isExternalFormCheckbox && isExternalFormCheckbox.checked && slugInput && !slugInput.value) {
                slugInput.value = this.value.replace(/_/g, '-');
                updateSlugPreview();
            }
        });
    }

    // 初期状態の設定
    toggleExternalFormSettings();
    toggleProjectCategorySelection();
    updateSlugPreview();
    
    // デバッグ用グローバル関数
    window.debugFormCategories = function() {
        console.log('=== Debug Info ===');
        console.log('addCategoryBtn:', document.getElementById('add-category-btn'));
        console.log('addCategoryModal:', document.getElementById('add-category-modal'));
        console.log('requiresApprovalCheckbox:', document.getElementById('requires_approval'));
        console.log('projectCategorySelection:', document.getElementById('project-category-selection'));
        console.log('Elements in DOM:', {
            'add-category-btn': !!document.getElementById('add-category-btn'),
            'add-category-modal': !!document.getElementById('add-category-modal'),
            'requires_approval': !!document.getElementById('requires_approval'),
            'project-category-selection': !!document.getElementById('project-category-selection')
        });
    };
    
    // デバッグ情報を即座に出力
    window.debugFormCategories();
    
    console.log('Script initialization completed');
});
</script>
@endpush
