@extends('layouts.app')

@section('title', '工程テンプレート編集: ' . $processTemplate->name)

@push('styles')
<style>
    .modal-active { display: flex !important; }
    /* 他の必要なスタイルがあればここに追加 */
</style>
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ editItemModal: false, currentItem: null }">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">工程テンプレート編集: <span
                    class="font-normal">{{ $processTemplate->name }}</span></h1>
            <x-secondary-button as="a" href="{{ route('admin.process-templates.index') }}">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>一覧へ戻る</span>
            </x-secondary-button>
        </div>

        <div class="max-w-4xl mx-auto space-y-6">
            {{-- テンプレート情報編集フォーム --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">テンプレート情報</h2>
                </div>
                <div class="p-6">
                    @can('update', App\Models\ProcessTemplate::class)
                        <form action="{{ route('admin.process-templates.update', $processTemplate) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="name" value="テンプレート名" required />
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                        :value="old('name', $processTemplate->name)" required :hasError="$errors->has('name')" />
                                     <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="description" value="説明" />
                                    <x-textarea-input id="description" name="description" class="mt-1 block w-full"
                                        rows="3">{{ old('description', $processTemplate->description) }}</x-textarea-input>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>
                            </div>
                            <div class="flex items-center justify-end mt-6">
                                <x-primary-button type="submit">
                                    <i class="fas fa-save mr-2"></i>テンプレート情報を更新
                                </x-primary-button>
                            </div>
                        </form>
                    @endcan
                </div>
            </div>

            {{-- 工程項目一覧と追加フォーム --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">工程項目</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    順序</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    工程名</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    標準工数</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($processTemplate->items()->orderBy('order')->get() as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item->order }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $item->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item->formatted_default_duration ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            @can('update', App\Models\ProcessTemplate::class)
                                            <button @click="currentItem = {{ $item }}; editItemModal = true"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="編集">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @endcan
                                            @can('delete', App\Models\ProcessTemplate::class)
                                                <form action="{{ route('admin.process-templates.items.destroy', [$processTemplate, $item]) }}"
                                                    method="POST" onsubmit="return confirm('本当にこの工程項目を削除しますか？');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="削除">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        工程項目がありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- 工程項目追加フォーム --}}
                <div class="p-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">工程項目を追加</h3>
                    <form action="{{ route('admin.process-templates.items.store', $processTemplate) }}" method="POST"> @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-start">
                            <div class="sm:col-span-5">
                                <x-input-label for="item_name_new" value="工程名" required/>
                                <x-text-input id="item_name_new" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required :hasError="$errors->has('name')"/>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-3">
                                <x-input-label for="item_default_duration_value_new" value="標準工数" />
                                <x-text-input id="item_default_duration_value_new" name="default_duration_value" type="number" min="0" step="any"
                                    class="mt-1 block w-full" :value="old('default_duration_value')" :hasError="$errors->has('default_duration_value')"/>
                                <x-input-error :messages="$errors->get('default_duration_value')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="item_default_duration_unit_new" value="単位" />
                                <select name="default_duration_unit" id="item_default_duration_unit_new" class="mt-1 block w-full form-select rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 {{ $errors->has('default_duration_unit') ? 'border-red-500' : '' }}">
                                    {{-- <option value="days" @if(old('default_duration_unit', 'days') == 'days') selected @endif>日</option> --}}
                                    <option value="hours" @if(old('default_duration_unit') == 'hours') selected @endif>時間</option>
                                    <option value="minutes" @if(old('default_duration_unit') == 'minutes') selected @endif>分</option>
                                </select>
                                <x-input-error :messages="$errors->get('default_duration_unit')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="item_order_new" value="順序" required />
                                <x-text-input id="item_order_new" name="order" type="number" min="0" class="mt-1 block w-full"
                                    :value="old('order', ($processTemplate->items()->orderByDesc('order')->first()->order ?? -1) + 1)" required :hasError="$errors->has('order')"/>
                                <x-input-error :messages="$errors->get('order')" class="mt-2" />
                            </div>
                        </div>
                        <div class="flex justify-end mt-4">
                            <x-primary-button type="submit">追加</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 工程項目編集モーダル --}}
        <div x-show="editItemModal"
             class="fixed inset-0 z-50 overflow-y-auto items-center justify-center bg-black bg-opacity-50"
             style="display: none;" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.away="editItemModal = false; currentItem = null"
                 class="relative p-6 mx-auto my-12 bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 transform translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 transform -translate-y-4 sm:translate-y-0 sm:scale-95">

                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4" x-text="'工程項目編集: ' + (currentItem ? currentItem.name : '')"></h3>

                <form x-show="currentItem" :action="currentItem ? '{{ url('admin/process-templates/'.$processTemplate->id.'/items') }}/' + currentItem.id : '#'" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="edit_item_name" value="工程名" required />
                            <x-text-input id="edit_item_name" name="name" type="text" class="mt-1 block w-full"
                                          x-model="currentItem.name" required />
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-1">
                                <x-input-label for="edit_item_default_duration_value" value="標準工数" />
                                <x-text-input id="edit_item_default_duration_value" name="default_duration_value" type="number" min="0" step="any"
                                    class="mt-1 block w-full" x-model="currentItem.default_duration_value_for_edit" />
                                    {{-- default_duration_value_for_edit はcurrentItemに事前にセットするか、JSで計算 --}}
                            </div>
                            <div class="col-span-2">
                                <x-input-label for="edit_item_default_duration_unit" value="単位" />
                                <select name="default_duration_unit" id="edit_item_default_duration_unit"
                                        x-model="currentItem.default_duration_unit_for_edit"
                                        class="mt-1 block w-full form-select rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                                    {{-- <option value="days">日</option> --}}
                                    <option value="hours">時間</option>
                                    <option value="minutes">分</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <x-input-label for="edit_item_order" value="順序" required />
                            <x-text-input id="edit_item_order" name="order" type="number" min="0"
                                          class="mt-1 block w-full" x-model="currentItem.order" required />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <x-secondary-button type="button" @click="editItemModal = false; currentItem = null">
                            キャンセル
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            更新
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('templateItemForm', () => ({

            init() {
                this.$watch('currentItem', (newItem) => {
                    if (newItem) {
                        if (newItem.default_duration === null || newItem.default_duration < 0) {
                            newItem.default_duration_value_for_edit = '';
                            newItem.default_duration_unit_for_edit = 'days'; // Default unit
                        } else {
                            let totalMinutes = newItem.default_duration;
                            if (newItem.default_duration_unit === 'days') {
                                // If unit was days, and duration is stored in minutes (e.g. 1 day = 1440 min)
                                // We need to convert it back to days for the input value
                                if (totalMinutes > 0 && totalMinutes % (24 * 60) === 0) {
                                    newItem.default_duration_value_for_edit = totalMinutes / (24 * 60);
                                    newItem.default_duration_unit_for_edit = 'days';
                                } else if (totalMinutes > 0 && totalMinutes % 60 === 0) { // Fallback to hours if not perfectly days
                                    newItem.default_duration_value_for_edit = totalMinutes / 60;
                                    newItem.default_duration_unit_for_edit = 'hours';
                                } else { // Fallback to minutes
                                    newItem.default_duration_value_for_edit = totalMinutes;
                                    newItem.default_duration_unit_for_edit = 'minutes';
                                }
                            } else if (newItem.default_duration_unit === 'hours') {
                                if (totalMinutes > 0 && totalMinutes % 60 === 0) {
                                    newItem.default_duration_value_for_edit = totalMinutes / 60;
                                    newItem.default_duration_unit_for_edit = 'hours';
                                } else {
                                     newItem.default_duration_value_for_edit = totalMinutes;
                                     newItem.default_duration_unit_for_edit = 'minutes';
                                }
                            } else { // minutes or undefined unit
                                newItem.default_duration_value_for_edit = totalMinutes;
                                newItem.default_duration_unit_for_edit = newItem.default_duration_unit || 'minutes';
                            }
                             if (totalMinutes === 0) {
                                newItem.default_duration_value_for_edit = 0;
                                newItem.default_duration_unit_for_edit = newItem.default_duration_unit || 'minutes';
                            }
                        }
                    }
                });
            }
        }));
    });
</script>
@endpush
