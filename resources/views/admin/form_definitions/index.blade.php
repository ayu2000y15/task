@extends('layouts.app')

@section('title', 'カスタム項目管理')

@section('content')
    {{-- ★ テーブル全体をAlpine.jsコンポーネントでラップ --}}
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="definitionsTable">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">カスタム項目管理</h1>
            @can('create', App\Models\FormFieldDefinition::class)
                <x-primary-button
                    onclick="location.href='{{ route('admin.form-definitions.create', ['category' => $category]) }}'">
                    <i class="fas fa-plus mr-2"></i>新規項目定義を作成
                </x-primary-button>
            @endcan
        </div>

        {{-- タブ切り替え --}}
        <div class="mb-6">
            <nav class="flex space-x-8" aria-label="Tabs">
                @foreach($availableCategories as $key => $label)
                    <a href="{{ route('admin.form-definitions.index', ['category' => $key]) }}"
                        class="py-2 px-1 border-b-2 font-medium text-sm whitespace-nowrap {{ $category === $key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        {{ $label }}
                        @php
                            $count = \App\Models\FormFieldDefinition::category($key)->count();
                        @endphp
                        @if($count > 0)
                            <span class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs font-medium bg-gray-500 text-white rounded-full dark:bg-gray-600">
                                {{ $count }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12"></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">順序</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12"></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ラベル</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">名前 (スラグ)</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">タイプ</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">必須</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">有効</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="sortable-definitions">
                        @forelse($fieldDefinitions as $definition)
                            @php
                                $hasOptions = in_array($definition->type, ['select', 'radio', 'checkbox', 'image_select']);
                            @endphp
                            {{-- 1. メインの行 --}}
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" data-id="{{ $definition->id }}">
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-400 dark:text-gray-500 cursor-move drag-handle text-center">
                                    <i class="fas fa-grip-vertical"></i>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-4 py-4 text-center">
                                    @if($hasOptions)
                                        <button @click="toggle({{ $definition->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800" title="選択肢を編集">
                                            <i class="fas" :class="isOpen({{ $definition->id }}) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                        </button>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $definition->label }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $definition->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ App\Models\FormFieldDefinition::FIELD_TYPES[$definition->type] ?? $definition->type }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    @if($definition->is_required) <i class="fas fa-check-circle text-green-500"></i> @else <i class="fas fa-times-circle text-gray-400"></i> @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    @if($definition->is_enabled) <i class="fas fa-check-circle text-green-500"></i> @else <i class="fas fa-times-circle text-gray-400"></i> @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        @can('update', $definition) <x-icon-button :href="route('admin.form-definitions.edit', $definition)" icon="fas fa-edit" title="編集" color="blue" /> @endcan
                                        @can('delete', $definition)
                                            @if($definition->isBeingUsed())
                                                <x-icon-button icon="fas fa-trash" title="この項目は {{ $definition->getUsageCount() }} 件の投稿で使用されているため削除できません" color="gray" disabled="true" />
                                            @else
                                                <form action="{{ route('admin.form-definitions.destroy', $definition) }}" method="POST" onsubmit="return confirm('本当に削除しますか？この操作は取り消せません。');">
                                                    @csrf @method('DELETE')
                                                    <x-icon-button icon="fas fa-trash" title="削除" color="red" type="submit" />
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            {{-- 2. アコーディオンの行 --}}
                            @if($hasOptions)
                                <tr x-show="isOpen({{ $definition->id }})" style="display: none;">
                                    <td colspan="9" class="p-4 bg-slate-50 dark:bg-slate-900">
                                        @include('admin.form_definitions.partials.inline-options-editor', [
                                            'definition' => $definition,
                                            'availableInventoryItems' => $availableInventoryItems
                                        ])
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    カスタム項目定義がありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
    document.addEventListener('alpine:init', () => {
        // テーブル全体のアコーディオン開閉を管理するコンポーネント
        Alpine.data('definitionsTable', () => ({
            openAccordions: {},
            toggle(id) {
                this.openAccordions[id] = !this.openAccordions[id];
            },
            isOpen(id) {
                return this.openAccordions[id] || false;
            }
        }));

        // インラインエディタ用のコンポーネント
        Alpine.data('inlineOptionsManager', (config) => ({
            options: [],
            nextId: 0,
            isInventoryLinked: config.isInventoryLinkedInitially,
            isSaving: false,
            statusMessage: '',
            success: null,
            definitionType: config.definitionType,

            init() {
                this.rebuildOptionsState(config.initialOptions, config.initialInventoryMap);
            },

            rebuildOptionsState(rawOptions, inventoryMap) {
                let initialData = [];
                inventoryMap = inventoryMap || {};

                Object.entries(rawOptions || {}).forEach(([value, labelOrUrl]) => {
                    const inventorySettings = inventoryMap[value] || {};
                    initialData.push({
                        value: value,
                        label: this.definitionType !== 'image_select' ? labelOrUrl : '',
                        existing_path: this.definitionType === 'image_select' ? labelOrUrl : '',
                        previewUrl: this.definitionType === 'image_select' ? labelOrUrl : '/placeholder.svg',
                        inventory_item_id: inventorySettings.id || null,
                        inventory_consumption_qty: inventorySettings.qty || 1,
                        file: null // ★ 既存の選択肢にもfileプロパティを初期値nullで追加
                    });
                });

                this.nextId = 0;
                this.options = initialData.map(opt => {
                    const id = this.nextId++;
                    const inventoryId = opt.inventory_item_id;
                    const inventoryItem = config.inventoryItemsJson.find(i => i.id == inventoryId);
                    return {
                        ...opt,
                        id: id,
                        inventory_consumption_qty: opt.inventory_consumption_qty || 1,
                        inventory_item_display_name: inventoryItem ? inventoryItem.text : (inventoryId || null)
                    };
                });

                this.$nextTick(() => this.initializeAllTomSelects());
            },

            addOption() {
                // ★ 新しい選択肢にfileプロパティを追加
                this.options.push({
                    id: this.nextId++,
                    value: '',
                    label: '',
                    existing_path: '',
                    previewUrl: '/placeholder.svg',
                    inventory_item_id: null,
                    inventory_consumption_qty: 1,
                    file: null
                });
                this.$nextTick(() => this.initializeAllTomSelects());
            },

            removeOption(index) {
                const optionId = this.options[index].id;
                const selectEl = document.getElementById(`inventory_id_${optionId}`);
                if (selectEl && selectEl.tomselect) {
                    selectEl.tomselect.destroy();
                }
                this.options.splice(index, 1);
            },

            handleFileSelect(event, index) {
                const file = event.target.files[0];
                if (file) {
                    this.options[index].previewUrl = URL.createObjectURL(file);
                    // ★ 選択されたファイルオブジェクトをstateに直接保存
                    this.options[index].file = file;
                }
            },

            initializeAllTomSelects() {
                // この関数は変更なし
                document.querySelectorAll('.tom-select-inventory-inline').forEach(el => {
                    if (el.tomselect) { return; }
                    const index = parseInt(el.dataset.index, 10);
                    if (isNaN(index) || !this.options[index]) return;
                    const option = this.options[index];

                    const optionId = option.id; // 不変のIDを取得する

                    const tom = new TomSelect(el, {
                        valueField: 'id',
                        labelField: 'text',
                        searchField: 'text',
                        options: config.inventoryItemsJson,
                        // onChangeでは、位置(index)ではなくIDを使って更新対象の選択肢を検索する
                        onChange: (value) => {
                            const optionToUpdate = this.options.find(o => o.id === optionId);
                            if (optionToUpdate) {
                                optionToUpdate.inventory_item_id = value;
                            }
                        }
                    });

                    if (option.inventory_item_id && option.inventory_item_display_name) {
                        tom.addOption({ id: option.inventory_item_id, text: option.inventory_item_display_name });
                        tom.setValue(option.inventory_item_id, true);
                    }
                });
            },

            saveOptions() {
                this.isSaving = true;
                this.statusMessage = '';

                const formData = new FormData();
                this.options.forEach((option, index) => {
                    formData.append(`options[${index}][value]`, option.value || '');
                    formData.append(`options[${index}][label]`, option.label || '');
                    formData.append(`options[${index}][existing_path]`, option.existing_path || '');
                    formData.append(`options[${index}][inventory_item_id]`, option.inventory_item_id || '');
                    formData.append(`options[${index}][inventory_consumption_qty]`, option.inventory_consumption_qty || 1);

                    // ★★★★★ 修正の核心 ★★★★★
                    // DOMからではなく、stateに保存したファイルオブジェクトを直接使う
                    if (option.file) {
                        formData.append(`options[${index}][image]`, option.file);
                    }
                });
                formData.append('is_inventory_linked', this.isInventoryLinked ? '1' : '0');
                formData.append('_token', '{{ csrf_token() }}');

                // axiosの第3引数でheadersを指定しない（ブラウザに自動設定させる）
                axios.post(`/admin/form-definitions/${config.definitionId}/options`, formData)
                .then(response => {
                    this.success = true;
                    this.statusMessage = response.data.message;
                    if (response.data.options) {
                        // 保存が成功したら、stateをサーバーからの最新情報で再構築する
                        this.rebuildOptionsState(response.data.options, response.data.option_inventory_map);
                    }
                    setTimeout(() => this.statusMessage = '', 3000);
                })
                .catch(error => {
                    this.success = false;
                    let errorMessage = '保存に失敗しました。';
                    if (error.response && error.response.status === 422 && error.response.data.errors) {
                        const errorDetails = Object.values(error.response.data.errors).map(e => e.join(' ')).join('\n');
                        errorMessage += `\n- ${errorDetails}`;
                    } else if (error.response && error.response.data && error.response.data.message) {
                        errorMessage = error.response.data.message;
                    }
                    this.statusMessage = errorMessage;
                })
                .finally(() => {
                    this.isSaving = false;
                });
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('sortable-definitions');
        if (el) {
            new Sortable(el, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'bg-indigo-100',
                onEnd: function (evt) {
                    const orderedIds = Array.from(el.querySelectorAll('tr[data-id]')).map(item => item.dataset.id);
                    axios.post('{{ route('admin.form-definitions.reorder') }}', {
                        orderedIds: orderedIds,
                        _token: '{{ csrf_token() }}'
                    })
                    .then(response => { if(!response.data.success) { alert('順序の更新に失敗しました。'); } })
                    .catch(error => { console.error('Error:', error); alert('順序の更新中にエラーが発生しました。'); });
                }
            });
        }
    });
    </script>
@endpush
