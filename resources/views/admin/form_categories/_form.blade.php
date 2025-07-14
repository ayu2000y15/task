<div class="space-y-6">
    {{-- Hidden type field --}}
    <input type="hidden" name="type" value="form">

    {{-- 基本情報 --}}
    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">基本情報</h3>
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
                    <x-checkbox-input id="send_completion_email" name="send_completion_email" value="1"
                        :label="'送信完了メールを送信'"
                        :checked="old('send_completion_email', $formCategory->send_completion_email ?? false)" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        チェックすると送信者に完了メールが送信されます
                    </p>
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
                        <button type="button" id="add-category-btn" class="mt-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-md whitespace-nowrap">
                            <i class="fas fa-plus mr-1"></i>新規追加
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('project_category_id')" class="mt-2" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        案件化時にデフォルトで選択される案件カテゴリです
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
