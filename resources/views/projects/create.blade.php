@extends('layouts.app')

@section('title', $formDisplayName ?? '新規衣装案件')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" id="project-form-page">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                {{ $formDisplayName ?? '新規衣装案件' }}
                @if($externalSubmission)
                    <span class="text-base font-normal text-gray-500 dark:text-gray-400">(外部申請 ID: {{ $externalSubmission->id }} より作成)</span>
                @endif
            </h1>
            <a href="{{ $externalSubmission ? route('admin.external-requests.index') : route('projects.index') }}"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i> 戻る
            </a>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @if($externalSubmission)
                        <input type="hidden" name="external_submission_id_on_creation" value="{{ $externalSubmission->id }}">
                    @endif
                    <div class="grid grid-cols-1 gap-y-6">
                        {{-- 専用カラムのフィールド --}}
                        <div>
                            <x-input-label for="title" value="案件名" :required="true" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $prefillStandardData['title'] ?? '')" required :hasError="$errors->has('title')" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="series_title" value="作品名" />
                            <x-text-input id="series_title" name="series_title" type="text" class="mt-1 block w-full" :value="old('series_title', $prefillStandardData['series_title'] ?? '')" :hasError="$errors->has('series_title')" />
                            <x-input-error :messages="$errors->get('series_title')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="client_name" value="依頼主名" />
                            <x-text-input id="client_name" name="client_name" type="text" class="mt-1 block w-full" :value="old('client_name', $prefillStandardData['client_name'] ?? '')" :hasError="$errors->has('client_name')" />
                            <x-input-error :messages="$errors->get('client_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="start_date" value="開始日" />
                            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', $prefillStandardData['start_date'] ?? date('Y-m-d'))" :hasError="$errors->has('start_date')" />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="end_date" value="終了日（納期）" />
                            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date', $prefillStandardData['end_date'] ?? date('Y-m-d', strtotime('+1 month')))" :hasError="$errors->has('end_date')" />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>
                         <div>
                            <x-input-label for="color_create" value="カラー" :required="true" />
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="color" name="color" id="color_create"
                                       class="form-input h-10 w-12 rounded-l-md border-gray-300 p-1 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 {{ $errors->has('color') ? 'border-red-500 dark:border-red-500' : 'dark:focus:border-indigo-600' }}"
                                       value="{{ old('color', $prefillStandardData['color'] ?? '#0d6efd') }}">
                                <input type="text" id="color_hex_display_create"
                                       class="form-input block w-full rounded-r-md border-l-0 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 {{ $errors->has('color') ? 'border-red-500 dark:border-red-500' : 'dark:focus:border-indigo-600' }}"
                                       value="{{ old('color', $prefillStandardData['color'] ?? '#0d6efd') }}" readonly>
                            </div>
                            <x-input-error :messages="$errors->get('color')" class="mt-2" />
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const colorPicker = document.getElementById('color_create');
                                    const colorHexDisplay = document.getElementById('color_hex_display_create');
                                    if (colorPicker && colorHexDisplay) {
                                        colorPicker.addEventListener('input', function() { colorHexDisplay.value = this.value; });
                                        colorHexDisplay.value = colorPicker.value; // 初期値も反映
                                    }
                                });
                            </script>
                        </div>
                        <div>
                            <x-input-label for="description" value="備考" />
                            <x-textarea-input id="description" name="description" class="mt-1 block w-full" rows="3" :hasError="$errors->has('description')">{{ old('description', $prefillStandardData['description'] ?? '') }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                        <div class="flex items-center pt-2">
                            <x-checkbox-input id="is_favorite" name="is_favorite" value="1" :checked="old('is_favorite', $prefillStandardData['is_favorite'] ?? false)" label="お気に入りに追加" :hasError="$errors->has('is_favorite')" />
                            <x-input-error :messages="$errors->get('is_favorite')" class="mt-0 ml-2" />
                        </div>
                        <div>
                            <x-input-label for="delivery_flag" value="納品フラグ" />
                            <x-select-input id="delivery_flag" name="delivery_flag" class="mt-1 block w-full" :options="['0' => '未納品', '1' => '納品済み']" :selected="old('delivery_flag', $prefillStandardData['delivery_flag'] ?? '0')" :hasError="$errors->has('delivery_flag')" />
                            <x-input-error :messages="$errors->get('delivery_flag')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="payment_flag" value="支払いフラグ" />
                            <x-select-input id="payment_flag" name="payment_flag" class="mt-1 block w-full"
                                :options="['' => '選択しない'] + \App\Models\Project::PAYMENT_FLAG_OPTIONS"
                                :selected="old('payment_flag', $prefillStandardData['payment_flag'] ?? '')" :hasError="$errors->has('payment_flag')" />
                            <x-input-error :messages="$errors->get('payment_flag')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="payment" value="支払条件" />
                            <x-textarea-input id="payment" name="payment" class="mt-1 block w-full" rows="2" :hasError="$errors->has('payment')">{{ old('payment', $prefillStandardData['payment'] ?? '') }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('payment')" class="mt-2" />
                        </div>
                         <div>
                            <x-input-label for="status" value="プロジェクトステータス" />
                            <x-select-input id="status" name="status" class="mt-1 block w-full"
                                :options="['' => '選択しない'] + \App\Models\Project::PROJECT_STATUS_OPTIONS"
                                :selected="old('status', $prefillStandardData['status'] ?? 'not_started')" :hasError="$errors->has('status')" />
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        @if(!empty($customFormFields))
                            <div class="mt-6 mb-2 border-t pt-6 dark:border-gray-700">

                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    @if($externalSubmission)
                                        案件情報（依頼フォームからコピーしたもの ※ファイルはコピーされません）
                                    @else
                                        案件情報（案件依頼項目）
                                    @endif
                                </h3>
                            </div>
                            @foreach ($customFormFields as $field)
                                @include('projects.partials.form-fields', [
                                    'field' => $field,
                                    'project' => null,
                                    'fieldNamePrefix' => 'attributes',
                                    // ★ prefillCustomAttributes を prefillValues として渡す (form-fields側でのキー名に合わせる)
                                    'prefillValues' => $prefillCustomAttributes ?? []
                                ])
                            @endforeach
                        @endif
                    </div>

                    <div class="mt-8 flex justify-end space-x-3">
                        <a href="{{ $externalSubmission ? route('admin.external-requests.index') : route('projects.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                            キャンセル
                        </a>
                        <x-primary-button>
                            <i class="fas fa-save mr-2"></i> 保存
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
