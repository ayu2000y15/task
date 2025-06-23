@extends('layouts.app')

@section('title', '作業依頼の編集')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">作業依頼の編集</h1>
            <x-secondary-button as="a" href="{{ route('requests.index') }}">
                <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <form action="{{ route('requests.update', $request) }}" method="POST" x-data="requestForm()"
                x-init="initSortable()">
                @csrf
                @method('PATCH')
                <div class="p-6 sm:p-8 space-y-6">
                    <div class="space-y-4">
                        {{-- 件名 --}}
                        <div>
                            <x-input-label for="title" value="件名" :required="true" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $request->title)" required :has-error="$errors->has('title')" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        {{-- 関連案件・カテゴリ --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="project_id" value="関連案件" />
                                <select id="project_id" name="project_id"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                    <option value="">-- 案件を選択 --</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" {{ old('project_id', $request->project_id) == $project->id ? 'selected' : '' }}>
                                            {{ $project->title }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="request_category_id" value="カテゴリ" :required="true" />
                                <select id="request_category_id" name="request_category_id"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    required>
                                    <option value="">-- カテゴリを選択 --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('request_category_id', $request->request_category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('request_category_id')" class="mt-2" />
                            </div>
                        </div>

                        {{-- 担当者 --}}
                        <div>
                            <x-input-label for="assignees" value="担当者" :required="true" />
                            <select name="assignees[]" id="assignees" multiple required
                                class="tom-select mt-1 block w-full">
                                @foreach($assigneeCandidates as $user)
                                    <option value="{{ $user->id }}" {{ in_array($user->id, old('assignees', $selectedAssignees)) ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('assignees')" class="mt-2" />
                        </div>

                        {{-- 補足事項 --}}
                        <div>
                            <x-input-label for="notes" value="補足事項" />
                            <x-textarea-input id="notes" name="notes" class="mt-1 block w-full" rows="3"
                                :has-error="$errors->has('notes')">{{ old('notes', $request->notes) }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>

                    {{-- チェックリスト項目 --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-md font-semibold text-gray-800 dark:text-gray-100">チェックリスト <span
                                    class="text-red-500 text-sm">*</span></h3>
                            <x-secondary-button type="button" @click="addItem()" x-show="items.length < 15">
                                <i class="fas fa-plus mr-2"></i>追加
                            </x-secondary-button>
                        </div>
                        <div id="checklist-items" class="space-y-3">
                            <template x-for="(item, index) in items" :key="item.id || index">
                                <div class="flex items-center space-x-2 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md">
                                    {{-- ドラッグハンドル --}}
                                    <div class="flex-shrink-0 cursor-move handle">
                                        <i class="fas fa-grip-vertical text-gray-400"></i>
                                    </div>

                                    {{-- 項目入力 --}}
                                    <div class="flex-grow">
                                        <input type="hidden" x-bind:name="'items[' + index + '][id]'"
                                            :value="item.id || ''">
                                        <input type="text" x-bind:name="'items[' + index + '][content]'"
                                            x-model="item.content"
                                            class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="依頼内容を入力..." required />
                                    </div>

                                    {{-- 終了予定日時
                                    <div class="flex-shrink-0">
                                        <input type="datetime-local" x-bind:name="'items[' + index + '][due_date]'"
                                            x-model="item.due_date"
                                            class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm text-sm" />
                                    </div> --}}

                                    {{-- 削除ボタン --}}
                                    <div class="flex-shrink-0">
                                        <button type="button" @click="removeItem(index)"
                                            class="text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-300 transition-colors"
                                            title="削除">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- エラー表示 --}}
                    @if($errors->any())
                        <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700 rounded-lg">
                            <h4 class="font-medium text-red-800 dark:text-red-200 mb-2">入力エラーがあります:</h4>
                            <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                {{-- フッターボタン --}}
                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 flex justify-end space-x-3">
                    <x-secondary-button as="a" href="{{ route('requests.index') }}">キャンセル</x-secondary-button>
                    <x-primary-button type="submit">
                        <i class="fas fa-save mr-2"></i> 更新する
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function requestForm() {
            // バリデーション失敗時の古い入力値を取得
            const oldItems = @json(old('items', []));

            // 既存の項目データを取得
            const existingItems = @json($request->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'content' => $item->content,
                    'due_date' => optional($item->due_date)->format('Y-m-d\TH:i')
                ];
            }));

            // バリデーション失敗時は古い入力値を、そうでなければ既存データを使用
            let initialItems;
            if (oldItems.length > 0) {
                initialItems = oldItems.map(item => ({
                    id: item.id || null,
                    content: item.content || '',
                    due_date: item.due_date || null
                }));
            } else {
                initialItems = existingItems.length > 0 ? existingItems : [{ id: null, content: '', due_date: null }];
            }

            return {
                items: initialItems,
                addItem() {
                    if (this.items.length < 15) {
                        this.items.push({ id: null, content: '', due_date: null });
                    }
                },
                removeItem(index) {
                    // 最低1つの項目は残す
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    } else {
                        // 最後の1つの場合は内容を空にする
                        this.items[index].content = '';
                        this.items[index].due_date = null;
                    }
                },
                initSortable() {
                    // this.$el は x-data が定義された要素 (この場合はform)
                    const el = this.$el.querySelector('#checklist-items');
                    new Sortable(el, {
                        handle: '.handle', // ドラッグハンドルのクラス
                        animation: 150,
                        // ドラッグ終了時の処理
                        onEnd: (evt) => {
                            // Alpineの配列をDOMの並び順に合わせて更新
                            const movedItem = this.items.splice(evt.oldIndex, 1)[0];
                            this.items.splice(evt.newIndex, 0, movedItem);
                        }
                    });
                }
            }
        }
    </script>
@endsection

@push('scripts')
    {{-- TomSelect --}}
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.tom-select').forEach((el) => {
                new TomSelect(el, { plugins: ['remove_button'], create: false });
            });
        });
    </script>

    {{-- SortableJS --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
@endpush