@extends('layouts.app')

@section('title', '衣装案件編集 - ' . $project->title)

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" id="project-form-page">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">衣装案件編集: {{ $project->title }}</h1>
            <div>
                <a href="{{ route('projects.show', $project) }}"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 mr-2">
                    <i class="fas fa-arrow-left mr-2"></i> 詳細に戻る
                </a>
                @can('delete', $project)
                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline-block"
                        onsubmit="return confirm('本当に削除しますか？衣装案件内のすべての工程も削除されます。');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">
                            <i class="fas fa-trash mr-2"></i> 削除
                        </x-danger-button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="p-6 sm:p-8">
                <form action="{{ route('projects.update', $project) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 gap-y-6"> {{-- 常に1カラムレイアウト --}}
                        {{-- 専用カラムのフィールド --}}
                        <div>
                            <x-input-label for="title" value="案件名" :required="true" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $project->title)" required :hasError="$errors->has('title')" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="series_title" value="作品名" />
                            <x-text-input id="series_title" name="series_title" type="text" class="mt-1 block w-full" :value="old('series_title', $project->series_title)" :hasError="$errors->has('series_title')" />
                            <x-input-error :messages="$errors->get('series_title')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="client_name" value="依頼主名" />
                            <x-text-input id="client_name" name="client_name" type="text" class="mt-1 block w-full" :value="old('client_name', $project->client_name)" :hasError="$errors->has('client_name')" />
                            <x-input-error :messages="$errors->get('client_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="start_date" value="開始日" />
                            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', $project->start_date ? $project->start_date->format('Y-m-d') : '')" :hasError="$errors->has('start_date')" />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="end_date" value="終了日（納期）" />
                            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date', $project->end_date ? $project->end_date->format('Y-m-d') : '')" :hasError="$errors->has('end_date')" />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="color_edit" value="カラー" :required="true" />
                             <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="color" name="color" id="color_edit"
                                       class="form-input h-10 w-12 rounded-l-md border-gray-300 p-1 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 {{ $errors->has('color') ? 'border-red-500 dark:border-red-500' : 'dark:focus:border-indigo-600' }}"
                                       value="{{ old('color', $project->color) }}">
                                <input type="text" id="color_hex_display_edit"
                                       class="form-input block w-full rounded-r-md border-l-0 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 {{ $errors->has('color') ? 'border-red-500 dark:border-red-500' : 'dark:focus:border-indigo-600' }}"
                                       value="{{ old('color', $project->color) }}" readonly>
                            </div>
                            <x-input-error :messages="$errors->get('color')" class="mt-2" />
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const colorPicker = document.getElementById('color_edit');
                                    const colorHexDisplay = document.getElementById('color_hex_display_edit');
                                    if (colorPicker && colorHexDisplay) {
                                        colorPicker.addEventListener('input', function() {
                                            colorHexDisplay.value = this.value;
                                        });
                                        colorHexDisplay.value = colorPicker.value; // 初期値も反映
                                    }
                                });
                            </script>
                        </div>
                        <div> {{-- 以前はmd:col-span-2だったが、1カラムなので不要 --}}
                            <x-input-label for="description" value="備考" />
                            <x-textarea-input id="description" name="description" class="mt-1 block w-full" rows="3" :hasError="$errors->has('description')">{{ old('description', $project->description) }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                        <div class="flex items-center pt-2"> {{-- 以前はmd:col-span-2だったが、1カラムなので不要 --}}
                            <x-checkbox-input id="is_favorite" name="is_favorite" value="1" :checked="old('is_favorite', $project->is_favorite)" label="お気に入りに追加" :hasError="$errors->has('is_favorite')" />
                            <x-input-error :messages="$errors->get('is_favorite')" class="mt-0 ml-2" />
                        </div>
                        <div>
                            <x-input-label for="delivery_flag" value="納品フラグ" />
                            <x-select-input id="delivery_flag" name="delivery_flag" class="mt-1 block w-full" :options="['0' => '未納品', '1' => '納品済み']" :selected="old('delivery_flag', $project->delivery_flag)" :hasError="$errors->has('delivery_flag')" />
                             <x-input-error :messages="$errors->get('delivery_flag')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="payment_flag" value="支払いフラグ" />
                            <x-select-input id="payment_flag" name="payment_flag" class="mt-1 block w-full"
                                :options="['' => '選択してください'] + \App\Models\Project::PAYMENT_FLAG_OPTIONS"
                                :selected="old('payment_flag', $project->payment_flag)" :hasError="$errors->has('payment_flag')" />
                            <x-input-error :messages="$errors->get('payment_flag')" class="mt-2" />
                        </div>
                        <div> {{-- 以前はmd:col-span-2だったが、1カラムなので不要 --}}
                            <x-input-label for="payment" value="支払条件" />
                            <x-textarea-input id="payment" name="payment" class="mt-1 block w-full" rows="2" :hasError="$errors->has('payment')">{{ old('payment', $project->payment) }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('payment')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="status" value="案件ステータス" />
                             <x-select-input id="status" name="status" class="mt-1 block w-full"
                                :options="['' => '選択してください'] + \App\Models\Project::PROJECT_STATUS_OPTIONS"
                                :selected="old('status', $project->status)" :hasError="$errors->has('status')" />
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        {{-- 送り状情報 --}}
                        <div class="mt-4 border-t pt-6 dark:border-gray-700" x-data='{
                            trackings: {{ old('tracking_info') ? json_encode(old('tracking_info')) : json_encode($project->tracking_info ?? [['carrier' => '', 'number' => '', 'memo' => '']]) }},
                            carriers: {{ json_encode(config('shipping.carriers')) }},
                            addRow() { this.trackings.push({ carrier: "", number: "", memo: "" }); },
                            removeRow(index) { this.trackings.splice(index, 1); }
                        }'>
                            <x-input-label value="送り状情報" />
                            <div class="space-y-4 mt-1">
                                <template x-for="(tracking, index) in trackings" :key="index">
                                    <div class="p-3 border rounded-md dark:border-gray-600 space-y-3 relative">
                                        {{-- 削除ボタン --}}
                                        <button type="button" @click="removeRow(index)" class="absolute top-2 right-2 p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400">
                                            <i class="fas fa-times"></i>
                                        </button>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div class="sm:col-span-1">
                                                <x-input-label ::for="`tracking_carrier_${index}`" value="配送業者" class="text-xs" />
                                                {{-- select要素自体に x-model を適用します --}}
                                                <select :name="`tracking_info[${index}][carrier]`"
                                                        @change="tracking.carrier = $event.target.value"
                                                        class="form-select mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                                    <option value="">業者を選択...</option>
                                                    <template x-for="(carrier, key) in carriers" :key="key">
                                                        <option :value="key"
                                                                x-text="carrier.name"
                                                                :selected="key === tracking.carrier">
                                                        </option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="sm:col-span-2">
                                                <x-input-label ::for="`tracking_number_${index}`" value="送り状番号" class="text-xs" />
                                                <x-text-input ::id="`tracking_number_${index}`" ::name="`tracking_info[${index}][number]`" type="text" class="mt-1 block w-full text-sm" x-model="tracking.number" />
                                            </div>
                                        </div>
                                        <div>
                                            <x-input-label ::for="`tracking_memo_${index}`" value="メモ" class="text-xs" />
                                            <x-text-input ::id="`tracking_memo_${index}`" ::name="`tracking_info[${index}][memo]`" type="text" class="mt-1 block w-full text-sm" x-model="tracking.memo" placeholder="例: ○○様宛、同梱品あり" />
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="addRow()" class="mt-2 text-sm text-blue-600 hover:underline">+ 送り状を追加</button>
                        </div>

                        <div>
                            <x-input-label for="budget" value="総売上 (円)" />
                            <x-text-input id="budget" name="budget" type="number" class="mt-1 block w-full" :value="old('budget', $project->budget)" min="0" :hasError="$errors->has('budget')" />
                            <x-input-error :messages="$errors->get('budget')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="target_cost" value="予算 (円)" />
                            <x-text-input id="target_cost" name="target_cost" type="number" class="mt-1 block w-full" :value="old('target_cost', $project->target_cost)" min="0" :hasError="$errors->has('target_cost')" />
                            <x-input-error :messages="$errors->get('target_cost')" class="mt-2" />
                        </div>
                        {{-- 目標材料費 --}}
                        <div>
                            <x-input-label for="target_material_cost" value="目標材料費" />
                            <x-text-input id="target_material_cost" class="block mt-1 w-full" type="number" name="target_material_cost" :value="old('target_material_cost', $project->target_material_cost ?? '')" placeholder="例: 50000" />
                            <x-input-error :messages="$errors->get('target_material_cost')" class="mt-2" />
                        </div>

                        {{-- 目標人件費 時給 --}}
                        <div style="display:none">
                            <x-input-label for="target_labor_cost_rate" value="目標人件費 計算用固定時給" />
                            <x-text-input id="target_labor_cost_rate" class="block mt-1 w-full" type="number" name="target_labor_cost_rate" :value="old('target_labor_cost_rate', $project->target_labor_cost_rate ?? '1200')" placeholder="例: 1200" />
                            <p class="text-xs text-gray-500 mt-1">目標人件費を計算するための固定時給です。全工程の予定工数にこの時給を掛けて目標額を算出します。</p>
                            <x-input-error :messages="$errors->get('target_labor_cost_rate')" class="mt-2" />
                        </div>

                        @if(!empty($customFormFields) && count($customFormFields) > 0)
                            <div class="mt-6 mb-2 border-t pt-6 dark:border-gray-700"> {{-- 以前はmd:col-span-2だったが、1カラムなので不要 --}}
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">追加情報（案件依頼項目）</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">これらの項目はこのプロジェクト固有のものです。</p>
                                {{-- TODO: プロジェクト固有のフォーム定義（$project->form_definitions）を編集する画面へのリンク --}}
                                {{-- <a href="{{ route('projects.form_definition.edit', $project) }}" class="text-sm text-blue-600 hover:underline">案件依頼項目定義を編集</a> --}}
                            </div>
                            @foreach ($customFormFields as $field)
                                @include('projects.partials.form-fields', [
                                    'field' => $field,
                                    'project' => $project, // 既存の値を取得するために渡す
                                    'fieldNamePrefix' => 'attributes',
                                    // 'prefillValues' => [] // edit画面では通常外部申請のプリフィルは不要
                                ])
                            @endforeach
                        @else
                            <div class="mt-6 pt-4"> {{-- 以前はmd:col-span-2だったが、1カラムなので不要 --}}
                                <p class="text-sm text-gray-500 dark:text-gray-400">このプロジェクトには追加の案件依頼項目は定義されていません。</p>
                                 {{-- TODO: プロジェクト固有のフォーム定義（$project->form_definitions）を編集する画面へのリンク --}}
                                {{-- <a href="{{ route('projects.form_definition.edit', $project) }}" class="text-sm text-blue-600 hover:underline">案件依頼項目定義を作成・編集</a> --}}
                            </div>
                        @endif
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <a href="{{ route('projects.show', $project) }}"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                            キャンセル
                        </a>
                        <x-primary-button>
                            <i class="fas fa-save mr-2"></i> 更新
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
