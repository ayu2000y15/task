@extends('layouts.app')

@section('title', 'カスタム項目管理')

@section('content')
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
    // Alpine.jsコンポーネント (アコーディオン開閉のみ)
    document.addEventListener('alpine:init', () => {
        Alpine.data('definitionsTable', () => ({
            openAccordions: {},
            toggle(id) {
                this.openAccordions[id] = !this.openAccordions[id];
            },
            isOpen(id) {
                return this.openAccordions[id] || false;
            }
        }));
    });

    // --- ここから素のJavaScriptによる制御 ---
    document.addEventListener('DOMContentLoaded', function () {
        const inventoryItemsJson = @json($availableInventoryItems->map(fn($item) => ['id' => $item->id, 'text' => $item->display_name]));

        // TomSelectを初期化する関数
        const initializeTomSelect = (selectElement) => {
            return new TomSelect(selectElement, {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                options: inventoryItemsJson,
                create: false,
                onChange: (value) => {
                    const row = selectElement.closest('.option-row');
                    const consumptionFields = row.querySelector('.consumption-fields');
                    if (value) {
                        consumptionFields.classList.remove('hidden');
                    } else {
                        consumptionFields.classList.add('hidden');
                    }
                }
            });
        };

        // すべてのインラインエディタコンテナを初期化
        document.querySelectorAll('.inline-options-container').forEach(container => {
            const definitionId = container.dataset.definitionId;
            const definitionType = container.dataset.definitionType;
            const optionsList = container.querySelector('.options-list');
            const addBtn = container.querySelector('.add-option-btn');
            const saveBtn = container.querySelector('.save-options-btn');
            const cancelBtn = container.querySelector('.cancel-btn');
            const inventoryCheckbox = container.querySelector('.is-inventory-linked-checkbox');
            const statusMessage = container.querySelector('.status-message');

            // 既存のTomSelectを初期化
            container.querySelectorAll('.tom-select-inventory-inline').forEach(initializeTomSelect);

            // 在庫連携チェックボックスのイベント
            inventoryCheckbox.addEventListener('change', (e) => {
                const isChecked = e.target.checked;
                container.querySelectorAll('.inventory-fields').forEach(field => {
                    field.classList.toggle('hidden', !isChecked);
                });
            });

            // 行のイベント（削除、画像プレビューなど）
            optionsList.addEventListener('click', (e) => {
                if (e.target.closest('.remove-option-btn')) {
                    const row = e.target.closest('.option-row');
                    const select = row.querySelector('.tom-select-inventory-inline');
                    if (select && select.tomselect) {
                        select.tomselect.destroy();
                    }
                    row.remove();
                }
            });
            optionsList.addEventListener('change', (e) => {
                if(e.target.classList.contains('option-image-input')) {
                    const row = e.target.closest('.option-row');
                    const preview = row.querySelector('.image-preview');
                    const file = e.target.files[0];
                    if (file && preview) {
                        preview.src = URL.createObjectURL(file);
                    }
                }
            });

            // 新しい選択肢を追加
            addBtn.addEventListener('click', () => {
                const template = document.getElementById('option-row-template');
                const newRow = template.content.cloneNode(true).firstElementChild;

                // タイプに応じて表示を切り替え
                if (definitionType === 'image_select') {
                    newRow.querySelector('.image-preview-wrapper').style.display = 'block';
                    newRow.querySelector('.image-field-wrapper').style.display = 'block';
                } else {
                    newRow.querySelector('.label-field-wrapper').style.display = 'block';
                }

                // 在庫連携の表示状態を同期
                if(inventoryCheckbox.checked) {
                    newRow.querySelectorAll('.inventory-fields').forEach(f => f.classList.remove('hidden'));
                }

                const select = newRow.querySelector('.tom-select-inventory-inline');
                optionsList.appendChild(newRow);
                initializeTomSelect(select);
            });

            // 保存処理
            saveBtn.addEventListener('click', () => {
                saveBtn.disabled = true;
                saveBtn.textContent = '保存中...';
                statusMessage.textContent = '';

                const formData = new FormData();
                formData.append('is_inventory_linked', inventoryCheckbox.checked ? '1' : '0');
                formData.append('_token', '{{ csrf_token() }}');

                container.querySelectorAll('.option-row').forEach((row, index) => {
                    const value = row.querySelector('[name="value"]')?.value || '';
                    const label = row.querySelector('[name="label"]')?.value || '';
                    const existingPath = row.querySelector('[name="existing_path"]')?.value || '';
                    const imageFile = row.querySelector('[name="image"]')?.files[0];
                    const inventoryId = row.querySelector('[name="inventory_item_id"]')?.value || '';
                    const consumptionQty = row.querySelector('[name="inventory_consumption_qty"]')?.value || '1';

                    formData.append(`options[${index}][value]`, value);
                    formData.append(`options[${index}][label]`, label);
                    formData.append(`options[${index}][existing_path]`, existingPath);
                    if(imageFile) {
                       formData.append(`options[${index}][image]`, imageFile);
                    }
                    formData.append(`options[${index}][inventory_item_id]`, inventoryId);
                    formData.append(`options[${index}][inventory_consumption_qty]`, consumptionQty);
                });

                axios.post(`/admin/form-definitions/${definitionId}/options`, formData)
                .then(response => {
                    statusMessage.textContent = response.data.message;
                    statusMessage.classList.add('text-green-600');
                    statusMessage.classList.remove('text-red-600');
                })
                .catch(error => {
                    let errorMessage = '保存に失敗しました。';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMessage = error.response.data.message;
                    }
                    statusMessage.textContent = errorMessage;
                    statusMessage.classList.add('text-red-600');
                    statusMessage.classList.remove('text-green-600');
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = '保存';
                });
            });

            // キャンセルボタン（アコーディオンを閉じる）
            cancelBtn.addEventListener('click', () => {
                const accordionRow = container.closest('tr[x-show]');
                if(accordionRow) {
                    accordionRow.style.display = 'none';
                    // Alpineコンポーネントの状態も更新（もしあれば）
                    const mainRow = accordionRow.previousElementSibling;
                    const id = mainRow.dataset.id;
                    const component = Alpine.$data(document.querySelector('[x-data="definitionsTable"]'));
                    if(component && component.isOpen(id)) {
                        component.toggle(id);
                    }
                }
            });
        });

        // SortableJSの初期化 (変更なし)
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
