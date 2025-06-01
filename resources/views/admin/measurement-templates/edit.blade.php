@extends('layouts.app')

@section('title', '採寸テンプレート編集: ' . $measurementTemplate->name)

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data='{
                                                templateName: @json(old("name", $measurementTemplate->name)),
                                                templateDescription: @json(old("description", $measurementTemplate->description)),
                                                items: @json(old("items", $measurementTemplate->items ?? [])),
                                                newItemName: "",
                                                newItemValue: "",
                                                newItemNotes: "",
                                                addItem() {
                                                    if (!this.newItemName.trim()) { alert("項目名を入力してください。"); return; }
                                                    this.items.push({
                                                        item: this.newItemName.trim(),
                                                        value: this.newItemValue.trim(),
                                                        notes: this.newItemNotes.trim()
                                                    });
                                                    this.newItemName = "";
                                                    this.newItemValue = "";
                                                    this.newItemNotes = "";
                                                },
                                                removeItem(index) {
                                                    // ★★★ 確認アラートを追加 ★★★
                                                    if (confirm("この採寸項目を本当に削除しますか？")) {
                                                        this.items.splice(index, 1);
                                                        this.updateHiddenItemsInput(); // 項目削除後に隠しフィールドを更新
                                                    }
                                                },
                                                updateHiddenItemsInput() {
                                                    document.getElementById("items_json_input").value = JSON.stringify(this.items);
                                                },
                                                submitForm() {
                                                    this.updateHiddenItemsInput();
                                                    this.$refs.templateForm.submit();
                                                }
                                            }' x-init="updateHiddenItemsInput()">

        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">採寸テンプレート編集: <span class="font-normal"
                    x-text="templateName"></span></h1>
            <x-secondary-button as="a" href="{{ route('admin.measurement-templates.index') }}">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>一覧へ戻る</span>
            </x-secondary-button>
        </div>

        <div class="max-w-4xl mx-auto space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">テンプレート情報</h2>
                </div>
                <div class="p-6">
                    @can('updateMeasurementTemplates', App\Models\Project::class)
                        <form x-ref="templateForm"
                            action="{{ route('admin.measurement-templates.update', $measurementTemplate) }}" method="POST"
                            @submit.prevent="submitForm">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="items" id="items_json_input">
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="name" value="テンプレート名" required />
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                        x-model="templateName" required />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="description" value="説明" />
                                    <x-textarea-input id="description" name="description" class="mt-1 block w-full" rows="3"
                                        x-model="templateDescription"></x-textarea-input>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>
                            </div>
                            <div class="flex items-center justify-end mt-6">
                                <x-primary-button type="submit">
                                    <i class="fas fa-save mr-2"></i>テンプレート情報を更新
                                </x-primary-button>
                            </div>
                        </form>
                    @else
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">テンプレート名</p>
                                <p class="mt-1 text-gray-900 dark:text-gray-100" x-text="templateName"></p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">説明</p>
                                <p class="mt-1 text-gray-900 dark:text-gray-100 whitespace-pre-wrap"
                                    x-text="templateDescription || '-'"></p>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">採寸項目</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    項目名</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    数値</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    備考</th>
                                @can('updateMeasurementTemplates', App\Models\Project::class)
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        操作</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                            @input="updateHiddenItemsInput()">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @can('updateMeasurementTemplates', App\Models\Project::class)
                                            <x-text-input type="text" x-model="item.item" class="w-full text-sm"
                                                placeholder="項目名" required />
                                        @else
                                            <span class="text-sm text-gray-900 dark:text-gray-100" x-text="item.item"></span>
                                        @endcan
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @can('updateMeasurementTemplates', App\Models\Project::class)
                                            <x-text-input type="text" x-model="item.value" class="w-full text-sm"
                                                placeholder="数値" />
                                        @else
                                            <span class="text-sm text-gray-500 dark:text-gray-400"
                                                x-text="item.value || '-'"></span>
                                        @endcan
                                    </td>
                                    <td class="px-6 py-4">
                                        @can('updateMeasurementTemplates', App\Models\Project::class)
                                            <x-textarea-input x-model="item.notes" class="w-full text-sm leading-tight" rows="1"
                                                placeholder="備考"></x-textarea-input>
                                        @else
                                            <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-pre-wrap"
                                                x-text="item.notes || '-'"></span>
                                        @endcan
                                    </td>
                                    @can('updateMeasurementTemplates', App\Models\Project::class)
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <x-danger-button type="button" @click="removeItem(index)" title="この項目を削除">
                                                <i class="fas fa-trash"></i>
                                            </x-danger-button>
                                        </td>
                                    @endcan
                                </tr>
                            </template>
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="{{ Gate::check('update', $measurementTemplate) ? 4 : 3 }}"
                                        class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        採寸項目がありません。
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                @can('updateMeasurementTemplates', App\Models\Project::class)
                    <div class="p-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                        <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">新しい項目を追加</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-8 gap-4 items-end">
                            <div class="sm:col-span-3">
                                <x-input-label for="new_item_name" value="項目名" />
                                <x-text-input id="new_item_name" type="text" x-model="newItemName"
                                    class="mt-1 block w-full text-sm" placeholder="例: 肩幅" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="new_item_value" value="数値" />
                                <x-text-input id="new_item_value" type="text" x-model="newItemValue"
                                    class="mt-1 block w-full text-sm" placeholder="例: 40" />
                            </div>
                            <div class="sm:col-span-3">
                                <x-input-label for="new_item_notes" value="備考" />
                                <x-text-input id="new_item_notes" type="text" x-model="newItemNotes"
                                    class="mt-1 block w-full text-sm" placeholder="例: ヌード寸法" />
                            </div>
                        </div>
                        <div class="flex justify-end mt-3">
                            <x-secondary-button type="button" @click="addItem()" @keydown.enter.prevent="addItem()">
                                <i class="fas fa-plus mr-2"></i>項目を追加
                            </x-secondary-button>
                        </div>
                        <x-input-error :messages="$errors->get('items')" class="mt-2" />
                        <x-input-error :messages="$errors->get('items.*.item')" class="mt-2" />
                        <x-input-error :messages="$errors->get('items.*.value')" class="mt-2" />
                        <x-input-error :messages="$errors->get('items.*.notes')" class="mt-2" />
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection