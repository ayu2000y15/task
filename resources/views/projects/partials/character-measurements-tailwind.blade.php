<div class="space-y-4">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
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
                        {{-- ★幅調整 --}}
                        操作</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($character->measurements as $measurement)
                    <tr id="measurement-row-{{ $measurement->id }}"> {{-- ★ 行にID付与 --}}
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-item">{{-- ★
                            クラス追加 --}}
                            {{ $measurement->item }}
                        </td>
                        <td class="px-4 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-200 measurement-value">{{-- ★
                            クラス追加 --}}
                            {{ $measurement->value }}
                        </td>
                        <td class="px-4 py-1.5 text-gray-700 dark:text-gray-200 break-words text-left leading-tight measurement-notes"
                            {{-- ★ クラス追加 --}} style="min-width: 150px;">
                            {!! nl2br(e($measurement->notes)) ?: '-' !!}
                        </td>
                        <td class="px-3 py-1.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-1"> {{-- ★ ボタンを横並びにするためのdiv --}}
                                @can('updateMeasurements', $project) {{-- ★ 適切な更新権限に変更 (例: manageMeasurements) --}}
                                    <button type="button"
                                        class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 edit-measurement-btn"
                                        title="編集" data-id="{{ $measurement->id }}" data-item="{{ $measurement->item }}"
                                        data-value="{{ $measurement->value }}" data-notes="{{ $measurement->notes }}">
                                        <i class="fas fa-edit fa-sm"></i>
                                    </button>
                                @endcan
                                @can('deleteMeasurements', $project)
                                    <form
                                        action="{{ route('projects.characters.measurements.destroy', [$project, $character, $measurement]) }}"
                                        method="POST" class="delete-measurement-form" {{-- ★ クラス追加 --}}
                                        data-id="{{ $measurement->id }}" onsubmit="return false;"> {{-- ★ AJAXで処理するため false
                                        に変更を検討 --}}
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
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">採寸データがありません。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @can('manageMeasurements', $project)
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