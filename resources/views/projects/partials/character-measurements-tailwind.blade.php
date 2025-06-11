<div class="space-y-4" id="character-measurements-section-{{ $character->id }}" data-character-id="{{ $character->id }}" data-project-id="{{ $project->id }}">
    <div
        class="p-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-md dark:bg-blue-700/30 dark:text-blue-200 dark:border-blue-500">
        <i class="fas fa-info-circle mr-1"></i>
        採寸テンプレートを適用する場合は、まず採寸テンプレートを作成してください。<br>
        　採寸テンプレートを作成すると、採寸テンプレートの項目が自動的に採寸データに適用されます。<br>
        　※数値は0となっているので、適用後は数値を入力してください。<br>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-2 py-2 w-10"></th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        項目</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        数値</th>
                    <th scope="col"
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        備考</th>
                    <th scope="col"
                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-20">
                        操作</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 sortable-list" id="measurement-sortable-{{ $character->id }}">
                @forelse($character->measurements as $measurement)
                    <tr id="measurement-row-{{ $measurement->id }}" data-id="{{ $measurement->id }}">
                        <td class="px-2 py-1.5 whitespace-nowrap text-center text-gray-400 drag-handle">
                            <i class="fas fa-grip-vertical"></i>
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-item">
                            {{ $measurement->item }}
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-value" data-sort-value="{{ floatval($measurement->value) }}">
                            {{ $measurement->value }}
                        </td>
                        <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 break-words text-left leading-tight measurement-notes"
                            style="min-width: 150px;">
                            {!! trim(e($measurement->notes)) ?: '-' !!}
                        </td>
                        <td class="px-3 py-1.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-1">
                                @can('updateMeasurements', $project) {{-- 適切な権限名に変更 --}}
                                    <button type="button"
                                        class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-measurement-btn"
                                        title="編集" data-id="{{ $measurement->id }}" data-item="{{ $measurement->item }}"
                                        data-value="{{ $measurement->value }}" data-notes="{{ $measurement->notes }}">
                                        <i class="fas fa-edit fa-sm"></i>
                                    </button>
                                @endcan
                                @can('deleteMeasurements', $project) {{-- 適切な権限名に変更 --}}
                                    <form
                                        action="{{ route('projects.characters.measurements.destroy', [$project, $character, $measurement]) }}"
                                        method="POST" class="delete-measurement-form"
                                        data-id="{{ $measurement->id }}" onsubmit="return false;">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                            title="削除">
                                            <i class="fas fa-trash fa-sm"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="no-measurement-data-row-{{ $character->id }}">
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">採寸データがありません。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- ★ 並び順保存ボタンを追加 --}}
    @if($character->measurements->isNotEmpty())
    <div class="flex justify-start">
        <button type="button" class="save-order-btn inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition ease-in-out duration-150"
                data-target-list="#measurement-sortable-{{ $character->id }}"
                data-url="{{ route('projects.characters.measurements.updateOrder', [$project, $character]) }}">
            <i class="fas fa-save mr-2"></i>並び順を保存
        </button>
    </div>
    @endif

    @can('manageMeasurements', $project) {{-- 適切な権限名に変更 --}}
        <div x-data="{ expanded: false }" class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700" id="measurement-template-load-section-{{ $character->id }}">
            <div @click="expanded = !expanded" class="flex justify-between items-center cursor-pointer py-2">
                <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <i class="fas fa-chevron-right fa-fw mr-1 transition-transform duration-200" :class="{'rotate-90': expanded}"></i>
                    採寸テンプレートを適用
                </h6>
            </div>
            <div x-show="expanded" x-collapse class="mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-3 items-end">
                    <div class="sm:col-span-2">
                        <x-input-label for="measurement_template_select-{{ $character->id }}" value="テンプレートを選択" />
                        <select id="measurement_template_select-{{ $character->id }}" class="form-select mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200">
                            <option value="">テンプレートを選択...</option>
                        </select>
                    </div>
                    <div>
                        <button type="button" id="apply-measurement-template-btn-{{ $character->id }}"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-600 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                            <i class="fas fa-check"></i> <span class="ml-2">適用する</span>
                        </button>
                    </div>
                </div>
                <div id="apply-template-status-{{ $character->id }}" class="text-xs mt-2"></div>
            </div>
        </div>

        <div x-data="{ expanded: false }" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700" id="measurement-template-save-section-{{ $character->id }}">
            <div @click="expanded = !expanded" class="flex justify-between items-center cursor-pointer py-2">
                <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                     <i class="fas fa-chevron-right fa-fw mr-1 transition-transform duration-200" :class="{'rotate-90': expanded}"></i>
                    現在の採寸項目をテンプレートとして保存
                </h6>
            </div>
            <div x-show="expanded" x-collapse class="mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-3 items-end">
                    <div class="sm:col-span-2">
                        <x-input-label for="measurement_template_name_input-{{ $character->id }}" value="テンプレート名" :required="true" />
                        <x-text-input type="text" id="measurement_template_name_input-{{ $character->id }}"
                            class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                             />
                    </div>
                    <div>
                        <button type="button" id="save-measurement-template-btn-{{ $character->id }}"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-purple-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-600 active:bg-purple-700 focus:outline-none focus:border-purple-700 focus:ring ring-purple-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                            <i class="fas fa-save"></i> <span class="ml-2">この内容で保存</span>
                        </button>
                    </div>
                </div>
                 <div id="save-template-status-{{ $character->id }}" class="text-xs mt-2"></div>
            </div>
        </div>

        {{-- 既存の採寸データ追加フォーム --}}
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2"
                id="measurement-form-title-{{ $character->id }}">採寸データを追加</h6>
            <form id="measurement-form-{{ $character->id }}"
                action="{{ route('projects.characters.measurements.store', [$project, $character]) }}" method="POST"
                data-store-url="{{ route('projects.characters.measurements.store', [$project, $character]) }}"
                data-character-id="{{ $character->id }}"
                class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 items-start">
                @csrf
                <input type="hidden" name="_method" id="measurement-form-method-{{ $character->id }}" value="POST">
                <input type="hidden" name="measurement_id" id="measurement-form-id-{{ $character->id }}" value="">

                <div>
                    <x-input-label for="measurement_item_input-{{ $character->id }}" value="項目" :required="true" />
                    <x-text-input type="text" name="item" id="measurement_item_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        required />
                </div>
                <div>
                    <x-input-label for="measurement_value_input-{{ $character->id }}" value="数値" :required="true" />
                    <x-text-input type="text" name="value" id="measurement_value_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"
                        required />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="measurement_notes_input-{{ $character->id }}" value="備考" />
                    <x-textarea-input name="notes" id="measurement_notes_input-{{ $character->id }}"
                        class="form-input mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 leading-tight"
                        rows="2"></x-textarea-input>
                </div>
                <div class="sm:col-span-2 flex justify-end items-center space-x-2">
                    <x-secondary-button type="button" id="measurement-form-cancel-btn-{{ $character->id }}"
                        style="display: none;">
                        キャンセル
                    </x-secondary-button>
                    <button type="submit" id="measurement-form-submit-btn-{{ $character->id }}"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150 text-xs">
                        <i class="fas fa-plus"></i> <span class="ml-2"
                            id="measurement-form-submit-btn-text-{{ $character->id }}">追加</span>
                    </button>
                </div>
                <div id="measurement-form-errors-{{ $character->id }}"
                    class="sm:col-span-2 text-sm text-red-600 space-y-1 mt-1"></div>
            </form>
        </div>
    @endcan
</div>

<script>
    if (typeof window.initializeMeasurementTemplateFlags === 'undefined') {
        window.initializeMeasurementTemplateFlags = {};
    }

    function setupMeasurementTemplateFunctionality(characterId, projectId) {
        const section = document.getElementById(`character-measurements-section-${characterId}`);
        if (!section) {
            return;
        }

        if (window.initializeMeasurementTemplateFlags[`char_${characterId}`]) {
            return;
        }
        window.initializeMeasurementTemplateFlags[`char_${characterId}`] = true;

        const templateSelect = section.querySelector(`#measurement_template_select-${characterId}`);
        const applyTemplateBtn = section.querySelector(`#apply-measurement-template-btn-${characterId}`);
        const applyStatusDiv = section.querySelector(`#apply-template-status-${characterId}`);

        const templateNameInput = section.querySelector(`#measurement_template_name_input-${characterId}`);
        const saveTemplateBtn = section.querySelector(`#save-measurement-template-btn-${characterId}`);
        const saveStatusDiv = section.querySelector(`#save-template-status-${characterId}`);

        const measurementForm = section.querySelector(`#measurement-form-${characterId}`);
        const measurementTableBody = section.querySelector(`#measurement-table-body-${characterId}`);

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function displayError(div, errors) {
            if (!div) return;
            let errorMsg = errors.message || 'エラーが発生しました。';
            if (errors.errors) {
                errorMsg += '<ul class="list-disc list-inside pl-4">';
                for (const key in errors.errors) {
                    errorMsg += `<li>${errors.errors[key].join(', ')}</li>`;
                }
                errorMsg += '</ul>';
            }
            div.innerHTML = `<span class="text-red-500">${errorMsg}</span>`;
        }

        function displaySuccess(div, message) {
            if (!div) return;
            div.innerHTML = `<span class="text-green-500">${message}</span>`;
            setTimeout(() => { if (div) div.innerHTML = ''; }, 5000);
        }

        function displayInfo(div, message) {
            if (!div) return;
            div.innerHTML = `<span class="text-blue-500">${message}</span>`;
        }

        function loadTemplates() {
            if (!templateSelect) return;
            if (applyStatusDiv) displayInfo(applyStatusDiv, 'テンプレートを読み込み中...');

            fetch(`/projects/${projectId}/characters/${characterId}/measurement-templates`)
                .then(response => {
                    if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
                    return response.json();
                })
                .then(data => {
                    templateSelect.innerHTML = '<option value="">テンプレートを選択...</option>';
                    if (data.templates && data.templates.length > 0) {
                        data.templates.forEach(template => {
                            const option = document.createElement('option');
                            option.value = template.id;
                            option.textContent = template.name;
                            templateSelect.appendChild(option);
                        });
                        if (applyStatusDiv) applyStatusDiv.innerHTML = '';
                    } else {
                         if (applyStatusDiv) applyStatusDiv.innerHTML = '<span class="text-xs text-gray-500">利用可能なテンプレートはありません。</span>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching measurement templates:', error);
                    if (applyStatusDiv) displayError(applyStatusDiv, {message: 'テンプレートの読み込みに失敗しました。'});
                });
        }

        if (saveTemplateBtn) {
            saveTemplateBtn.addEventListener('click', function() {
                const templateName = templateNameInput.value.trim();
                if (!templateName) {
                    displayError(saveStatusDiv, {message: 'テンプレート名を入力してください。'});
                    templateNameInput.focus();
                    return;
                }

                const itemsToSave = [];
                measurementTableBody.querySelectorAll('tr').forEach(row => {
                    if (row.id && row.id.startsWith('measurement-row-')) {
                        const itemCell = row.querySelector('.measurement-item');
                        const notesCell = row.querySelector('.measurement-notes');
                        if (itemCell) {
                            const item = itemCell.textContent.trim();
                            const notesHTML = notesCell ? notesCell.innerHTML : '';
                            let notesText = '';
                            if (notesHTML.toLowerCase() !== '-') {
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = notesHTML.replace(/<br\s*\/?>/gi, "\n");
                                notesText = tempDiv.textContent || tempDiv.innerText || "";
                            }
                            const finalNotes = (notesText.trim() === '-' || notesText.trim() === '') ? '' : notesText.trim();
                            if (item) {
                                itemsToSave.push({ item: item, notes: finalNotes });
                            }
                        }
                    }
                });

                if (itemsToSave.length === 0) {
                    displayError(saveStatusDiv, {message: '保存する採寸項目がありません。現在の採寸リストに項目を追加してください。'});
                    return;
                }

                displayInfo(saveStatusDiv, 'テンプレートを保存中...');
                fetch(`/projects/${projectId}/characters/${characterId}/measurement-templates`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        template_name: templateName,
                        items_to_save: itemsToSave
                    })
                })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(({ status, body }) => {
                    if (status === 200 || status === 201) {
                        displaySuccess(saveStatusDiv, body.message || 'テンプレートを保存しました。');
                        templateNameInput.value = '';
                        loadTemplates();
                    } else {
                        displayError(saveStatusDiv, body);
                    }
                })
                .catch(error => {
                    console.error('Error saving measurement template:', error);
                    displayError(saveStatusDiv, {message: 'テンプレートの保存中にエラーが発生しました。'});
                });
            });
        }

        if (applyTemplateBtn) {
            applyTemplateBtn.addEventListener('click', async function() {
                const templateId = templateSelect.value;
                if (!templateId) {
                    displayError(applyStatusDiv, {message: '適用するテンプレートを選択してください。'});
                    return;
                }

                displayInfo(applyStatusDiv, 'テンプレートを適用中...');
                console.log(`[CharID: ${characterId}] Applying template ID: ${templateId}`);

                try {
                    const templateResponse = await fetch(`/measurement-templates/${templateId}/load?project_id=${projectId}`);
                    if (!templateResponse.ok) {
                        const errorText = await templateResponse.text();
                        console.error(`[CharID: ${characterId}] Failed to load template. Status: ${templateResponse.status}. Response: ${errorText}`);
                        throw new Error(`テンプレートの読み込みに失敗しました: ${templateResponse.statusText}`);
                    }
                    const templateData = await templateResponse.json();
                    console.log(`[CharID: ${characterId}] Template data loaded:`, templateData);


                    if (templateData && templateData.items && Array.isArray(templateData.items)) {
                        if (templateData.items.length === 0) {
                            if(applyStatusDiv) applyStatusDiv.innerHTML = `<span class="text-yellow-500">テンプレートに適用する項目がありません。</span>`;
                            console.log(`[CharID: ${characterId}] Template has no items.`);
                            return;
                        }

                        let successCount = 0;
                        let errorCount = 0;
                        const totalItems = templateData.items.length;
                        console.log(`[CharID: ${characterId}] Starting to apply ${totalItems} items.`);

                        for (let i = 0; i < templateData.items.length; i++) {
                            const item = templateData.items[i];
                            console.log(`[CharID: ${characterId}] Processing item ${i + 1}/${totalItems}:`, item);

                            if(applyStatusDiv) displayInfo(applyStatusDiv, `テンプレート適用中... (${i + 1}/${totalItems})`);

                            const formData = new FormData();
                            formData.append('item', item.item);
                            formData.append('value', item.value !== undefined && item.value !== null && item.value !== '' ? item.value : '0');
                            const notesToSend = (item.notes === null || typeof item.notes === 'undefined' || item.notes.trim() === '-' || item.notes.trim() === '') ? '' : item.notes;
                            formData.append('notes', notesToSend);
                            formData.append('_token', csrfToken);

                            const storeUrl = measurementForm.dataset.storeUrl;
                            try {
                                const itemAddResponse = await fetch(storeUrl, {
                                    method: 'POST',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                                    body: formData
                                });
                                const itemResult = await itemAddResponse.json();

                                if (itemAddResponse.ok && itemResult.success && itemResult.measurement) {
                                    successCount++;
                                    addMeasurementRowToTable(itemResult.measurement, characterId, projectId);
                                    console.log(`[CharID: ${characterId}] Item added successfully:`, itemResult.measurement);
                                } else {
                                    errorCount++;
                                    console.error(`[CharID: ${characterId}] Error adding item from template. Item:`, item, 'Result:', itemResult.message || `Failed with status ${itemAddResponse.status}`, 'Details:', itemResult.errors);
                                }
                            } catch (singleItemError) {
                                errorCount++;
                                console.error(`[CharID: ${characterId}] Exception during single item fetch for item:`, item, 'Error:', singleItemError);
                            }
                        }
                        console.log(`[CharID: ${characterId}] Loop finished. Success: ${successCount}, Errors: ${errorCount}`);


                        if (successCount > 0) {
                            displaySuccess(applyStatusDiv, `${successCount}件の項目を適用しました。` + (errorCount > 0 ? ` (${errorCount}件失敗)` : ''));
                        } else if (errorCount > 0) {
                            displayError(applyStatusDiv, {message: `全${totalItems}件の項目の適用に失敗しました。コンソールで詳細を確認してください。`});
                        } else {
                           if(applyStatusDiv) applyStatusDiv.innerHTML = `<span class="text-yellow-500">テンプレートの項目を適用できませんでした。</span>`;
                        }

                    } else {
                        console.error(`[CharID: ${characterId}] Template data format incorrect:`, templateData);
                        displayError(applyStatusDiv, {message: 'テンプレートデータの形式が正しくありません。'});
                    }
                } catch (error) {
                    console.error(`[CharID: ${characterId}] Error applying measurement template:`, error);
                    displayError(applyStatusDiv, {message: `テンプレートの適用中にエラーが発生しました: ${error.message}`});
                }
            });
        }

        function addMeasurementRowToTable(measurement, charId, projId) {
            const noDataRow = measurementTableBody.querySelector(`#no-measurement-data-row-${charId}`);
            if (noDataRow) noDataRow.remove();

            const newRowHtml = `
                <tr id="measurement-row-${measurement.id}">
                    <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-item">${escapeHtml(measurement.item)}</td>
                    <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-value">${escapeHtml(measurement.value)}</td>
                    <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 break-words text-left leading-tight measurement-notes" style="min-width: 150px;">${measurement.notes ? nl2br(escapeHtml(measurement.notes)) : '-'}</td>
                    <td class="px-3 py-1.5 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end space-x-1">
                            <button type="button" class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-measurement-btn" title="編集" data-id="${measurement.id}" data-item="${escapeHtml(measurement.item)}" data-value="${escapeHtml(measurement.value)}" data-notes="${escapeHtml(measurement.notes || '')}"><i class="fas fa-edit fa-sm"></i></button>
                            <form action="/projects/${projId}/characters/${charId}/measurements/${measurement.id}" method="POST" class="delete-measurement-form" data-id="${measurement.id}" onsubmit="return false;">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="p-1 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" title="削除"><i class="fas fa-trash fa-sm"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            `;
            measurementTableBody.insertAdjacentHTML('beforeend', newRowHtml);

            if (typeof window.setupDynamicMeasurementRowEventListeners === 'function') {
                window.setupDynamicMeasurementRowEventListeners(measurementTableBody.lastElementChild, charId, projId);
            }
        }

        function nl2br(str) {
            if (typeof str === 'undefined' || str === null) return '';
            return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
        }
        function escapeHtml(unsafe) {
            if (typeof unsafe === 'undefined' || unsafe === null) return '';
            return unsafe
                 .toString()
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        loadTemplates();
    }
    window.setupMeasurementTemplateFunctionality = setupMeasurementTemplateFunctionality;
</script>
