@extends('layouts.app')

@section('title', '新規作業依頼')

@push('styles')
    {{-- 担当者選択のデザイン用 --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">新規作業依頼</h1>
            <x-secondary-button as="a" href="{{ route('requests.index') }}">
                <i class="fas fa-list-ul mr-2"></i> 依頼一覧へ戻る
            </x-secondary-button>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <form action="{{ route('requests.store') }}" method="POST" x-data="requestForm()">
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
                        <div>
                            <x-input-label for="assignees" value="担当者" :required="true" />
                            {{-- TomSelectはカスタムコンポーネント化が難しいため、タグを直接記述 --}}
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
                                <i class="fas fa-info-circle mr-1"></i>ヒント：担当者に自分を指定すると、この依頼は「自分用」タブに追加され、個人のタスク管理に利用できます。
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
                            <h3 class="text-md font-semibold text-gray-800 dark:text-gray-100">チェックリスト <span
                                    class="text-red-500 text-sm">*</span></h3>
                            <x-secondary-button type="button" @click="addItem()" x-show="items.length < 15">
                                <i class="fas fa-plus mr-2"></i>追加
                            </x-secondary-button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(item, index) in items" :key="index">
                                <div class="flex items-center space-x-3 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md">
                                    <div class="flex-grow">
                                        <x-text-input type="text" name="items[]" x-model="items[index]" class="block w-full"
                                            placeholder="依頼内容を入力..." required />
                                    </div>
                                    <div class="flex-shrink-0">
                                        <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                            class="text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-300 transition-colors"
                                            title="削除">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- バリデーションエラー表示 --}}
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

                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 flex justify-end space-x-3">
                    <x-secondary-button as="a" href="{{ route('requests.index') }}">
                        キャンセル
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        <i class="fas fa-clipboard-check mr-2"></i> 依頼を作成
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function requestForm() {
            // バリデーション失敗時に、入力された項目を復元する
            const oldItems = {!! json_encode(old('items', [''])) !!};
            // oldItemsが空配列の場合でも、最低1つの入力欄を保証する
            const initialItems = oldItems.length > 0 ? oldItems : [''];

            return {
                items: initialItems,
                addItem() {
                    if (this.items.length < 15) {
                        this.items.push('');
                    }
                },
                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                }
            }
        }
    </script>
@endsection

@push('scripts')
    {{-- 担当者選択のデザイン用 --}}
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // TomSelectの初期化
            document.querySelectorAll('.tom-select').forEach((el) => {
                new TomSelect(el, {
                    plugins: ['remove_button'],
                    create: false,
                });
            });
        });
    </script>
@endpush