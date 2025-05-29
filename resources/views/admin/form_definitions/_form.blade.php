@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
    <div>
        <x-input-label for="label" value="表示ラベル" :required="true" />
        <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" :value="old('label', $formFieldDefinition->label ?? '')" required />
        <x-input-error :messages="$errors->get('label')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="name" value="フィールド名 (半角英数字と_のみ)" :required="true" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $formFieldDefinition->name ?? '')" required placeholder="e.g. client_contact_person" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">システム内部で使用する名前です。一度作成すると変更しないでください。</p>
    </div>

    <div>
        <x-input-label for="type" value="フィールドタイプ" :required="true" />
        <x-select-input id="type" name="type" class="mt-1 block w-full" :options="$fieldTypes" :selected="old('type', $formFieldDefinition->type ?? 'text')" required />
        <x-input-error :messages="$errors->get('type')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="placeholder" value="プレースホルダー" />
        <x-text-input id="placeholder" name="placeholder" type="text" class="mt-1 block w-full"
            :value="old('placeholder', $formFieldDefinition->placeholder ?? '')" />
        <x-input-error :messages="$errors->get('placeholder')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="options_text" value="選択肢 (タイプがセレクト/ラジオ/チェックボックスの場合)" />
        <x-textarea-input id="options_text" name="options_text" class="mt-1 block w-full" rows="3"
            placeholder="例: value1:表示名1, value2:表示名2">{{ old('options_text', $optionsText ?? ($formFieldDefinition->options_text ?? '')) }}</x-textarea-input>
        <x-input-error :messages="$errors->get('options')" class="mt-2" />
        <x-input-error :messages="$errors->get('options_text')" class="mt-2" />
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">カンマ区切りで「値:表示ラベル」の形式で入力します。値のみの場合は値と表示ラベルが同じになります。</p>
    </div>

    <div>
        <x-input-label for="max_length" value="最大長 (テキスト系フィールド)" />
        <x-text-input id="max_length" name="max_length" type="number" min="1" class="mt-1 block w-full"
            :value="old('max_length', $formFieldDefinition->max_length ?? '')" />
        <x-input-error :messages="$errors->get('max_length')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="order" value="表示順" />
        <x-text-input id="order" name="order" type="number" class="mt-1 block w-full" :value="old('order', $formFieldDefinition->order ?? 0)" />
        <x-input-error :messages="$errors->get('order')" class="mt-2" />
    </div>

    <div class="md:col-span-1">
        <x-checkbox-input id="is_required" name="is_required" value="1" :label="'この項目を必須にする'"
            :checked="old('is_required', $formFieldDefinition->is_required ?? false)" />
        <x-checkbox-input id="is_enabled" name="is_enabled" value="1" :label="'この項目定義を有効にする (プロジェクト作成時に選択可能にする)'"
            :checked="old('is_enabled', $formFieldDefinition->is_enabled ?? true)" />
    </div>
</div>

<div class="mt-8 flex justify-end space-x-3">
    <x-secondary-button as="a" :href="route('admin.form-definitions.index')">
        キャンセル
    </x-secondary-button>
    <x-primary-button type="submit">
        <i class="fas fa-save mr-2"></i> 保存
    </x-primary-button>
</div>