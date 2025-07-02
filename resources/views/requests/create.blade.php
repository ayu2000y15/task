@extends('layouts.app')

@section('title', '新規予定・依頼')

@push('styles')
    {{-- 担当者選択のデザイン用 --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">新規予定・依頼</h1>
            <x-secondary-button as="a" href="{{ route('requests.index') }}">
                <i class="fas fa-list-ul mr-2"></i> 予定・依頼一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <form action="{{ route('requests.store') }}" method="POST" x-data="requestForm()" x-init="initSortable()">
                @csrf
                <div class="p-6 sm:p-8 space-y-6">
                    {{-- 基本情報 --}}
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="title" value="件名" :required="true" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                :value="old('title')" required :has-error="$errors->has('title')" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="project_id" value="関連案件" />
                                <select id="project_id" name="project_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                    <option value="">-- 案件を選択 --</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                            {{ $project->title }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="request_category_id" value="カテゴリ" :required="true" />
                                <select id="request_category_id" name="request_category_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                                    <option value="">-- カテゴリを選択 --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('request_category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('request_category_id')" class="mt-2" />
                            </div>
                        </div>

                        {{-- ▼▼▼【ここから追加】開始・終了日時 ▼▼▼ --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="start_at" value="開始日時" />
                                <x-text-input id="start_at" name="start_at" type="datetime-local" class="mt-1 block w-full" :value="old('start_at')" :has-error="$errors->has('start_at')" />
                                <x-input-error :messages="$errors->get('start_at')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="end_at" value="終了日時" />
                                <x-text-input id="end_at" name="end_at" type="datetime-local" class="mt-1 block w-full" :value="old('end_at')" :has-error="$errors->has('end_at')" />
                                <x-input-error :messages="$errors->get('end_at')" class="mt-2" />
                            </div>
                        </div>
                        {{-- ▲▲▲【追加ここまで】▲▲▲ --}}

                        <div>
                            <x-input-label for="assignees" value="担当者" :required="true" />
                            <select name="assignees[]" id="assignees" multiple required
                                class="tom-select mt-1 block w-full">
                                @foreach($assigneeCandidates as $user)
                                    <option value="{{ $user->id }}" {{ in_array($user->id, old('assignees', [])) ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('assignees')" class="mt-2" />
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <i class="fas fa-info-circle mr-1"></i>ヒント：担当者に自分を指定すると、この予定・依頼は「自分用」タブに追加され、個人のタスク管理に利用できます。
                            </p>
                        </div>
                        <div>
                            <x-input-label for="notes" value="補足事項" />
                            <x-textarea-input id="notes" name="notes" class="mt-1 block w-full" rows="3"
                                :has-error="$errors->has('notes')">{{ old('notes') }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>

                    {{-- チェックリスト項目 --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="flex justify-between items-center mb-4">
                            {{-- ▼▼▼【修正】見出しを「任意」に ▼▼▼ --}}
                            <h3 class="text-md font-semibold text-gray-800 dark:text-gray-100">チェックリスト (任意)</h3>
                            {{-- ▲▲▲【修正ここまで】▲▲▲ --}}
                            <x-secondary-button type="button" @click="addItem()" x-show="items.length < 15">
                                <i class="fas fa-plus mr-2"></i>追加
                            </x-secondary-button>
                        </div>

                        <div id="checklist-items" class="space-y-3">
                            <template x-for="(item, index) in items" :key="index">
                                <div class="flex items-center space-x-2 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md item-container">
                                    <div class="flex-shrink-0 cursor-move handle">
                                        <i class="fas fa-grip-vertical text-gray-400"></i>
                                    </div>
                                    <div class="flex-grow">
                                        <x-text-input type="text" x-bind:name="'items[' + index + '][content]'" x-model="item.content" class="block w-full"
                                            placeholder="予定・依頼内容を入力..." required />
                                    </div>
                                    <div class="flex-shrink-0">
                                        {{-- ▼▼▼【修正】最後の項目も削除できるようにx-showを削除 ▼▼▼ --}}
                                        <button type="button" @click="removeItem(index)"
                                            class="text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-300 transition-colors"
                                            title="削除">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        {{-- ▲▲▲【修正ここまで】▲▲▲ --}}
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    {{-- ... (エラー表示、フッターボタンは変更なし) --}}
                </div>
                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 flex justify-end space-x-3">
                    <x-secondary-button as="a" href="{{ route('requests.index') }}">
                        キャンセル
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        <i class="fas fa-clipboard-check mr-2"></i> 予定・依頼を作成
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function requestForm() {
            // ▼▼▼【修正】バリデーション失敗時の復元ロジックを更新 ▼▼▼
            // 初期状態を空の配列 `[]` に変更
            const oldItems = {!! json_encode(old('items', [])) !!};
            const initialItems = oldItems.length > 0 ? oldItems.map(item => ({ content: item.content || '', due_date: item.due_date || null })) : [];
            // ▲▲▲【修正ここまで】▲▲▲

            return {
                items: initialItems,
                addItem() {
                    if (this.items.length < 15) {
                        this.items.push({ content: '', due_date: null });
                    }
                },
                // ▼▼▼【修正】最後の項目も削除できるように修正 ▼▼▼
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                // ▲▲▲【修正ここまで】▲▲▲
                initSortable() {
                    const el = this.$el.querySelector('#checklist-items');
                    new Sortable(el, {
                        handle: '.handle',
                        animation: 150,
                        onEnd: (evt) => {
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
    {{-- (変更なし) --}}
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.tom-select').forEach((el) => {
                new TomSelect(el, { plugins: ['remove_button'], create: false, });
            });
        });
    </script>
@endpush
