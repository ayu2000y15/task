@extends('layouts.app')

@section('title', '新規採寸テンプレート作成')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">新規採寸テンプレート作成</h1>
            <x-secondary-button onclick="location.href='{{ route('admin.measurement-templates.index') }}'">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>一覧へ戻る</span>
            </x-secondary-button>
        </div>

        <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('admin.measurement-templates.store') }}" method="POST">
                    @csrf
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="name" value="テンプレート名" required />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')"
                                required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="description" value="説明" />
                            <x-textarea-input id="description" name="description" class="mt-1 block w-full"
                                rows="4">{{ old('description') }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                        {{-- 初期項目をJSONなどで入力させる場合はここに追加 --}}
                        {{--
                        <div>
                            <x-input-label for="items_json" value="初期項目 (JSON形式)" />
                            <x-textarea-input id="items_json" name="items_json_input_for_validation_only"
                                class="mt-1 block w-full font-mono text-xs" rows="5"
                                placeholder='[{"item":"項目1","value":"10","notes":"備考1"},{"item":"項目2","value":"20","notes":""}]'>{{
                                old('items_json_input_for_validation_only') }}</x-textarea-input>
                            <input type="hidden" name="items" id="items_real_input">
                            <x-input-error :messages="$errors->get('items')" class="mt-2" />
                            <x-input-error :messages="$errors->get('items.*.item')" class="mt-2" />
                            <x-input-error :messages="$errors->get('items.*.value')" class="mt-2" />
                            <x-input-error :messages="$errors->get('items.*.notes')" class="mt-2" />
                            <p class="text-xs text-gray-500 mt-1">各項目は {"item":"項目名", "value":"数値", "notes":"備考"}
                                の形式で入力してください。</p>
                        </div>
                        @push('scripts')
                        <script>
                            const itemsJsonInput = document.getElementById('items_json_input_for_validation_only');
                            const itemsRealInput = document.getElementById('items_real_input');
                            if (itemsJsonInput && itemsRealInput) {
                                itemsJsonInput.addEventListener('input', function () {
                                    try {
                                        const parsed = JSON.parse(this.value);
                                        if (Array.isArray(parsed)) {
                                            itemsRealInput.value = this.value; // 送信するのはパース成功時のみ（バリデーションはサーバーで）
                                        } else {
                                            itemsRealInput.value = ''; // 無効なJSON
                                        }
                                    } catch (e) {
                                        itemsRealInput.value = ''; // 無効なJSON
                                    }
                                });
                                // 初期値の反映
                                try {
                                    const parsed = JSON.parse(itemsJsonInput.value);
                                    if (Array.isArray(parsed)) itemsRealInput.value = itemsJsonInput.value;
                                } catch (e) { }
                            }
                        </script>
                        @endpush
                        --}}
                    </div>

                    <div class="flex items-center justify-end mt-8 pt-5 border-t border-gray-200 dark:border-gray-700">
                        <x-primary-button>
                            <i class="fas fa-plus mr-2"></i>作成して採寸項目を編集
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection