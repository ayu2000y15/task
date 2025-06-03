@extends('layouts.tool')

@section('title', '購読者編集 - ' . $subscriber->email . ' (リスト: ' . $emailList->name . ')')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    {{-- ... 他のパンくず ... --}}
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">編集</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-3xl mx-auto mb-6"> {{-- フォームの幅を少し広げる max-w-3xl --}}
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    購読者編集 <span class="text-base font-normal text-gray-600 dark:text-gray-400">(リスト:
                        {{ $emailList->name }})</span>
                </h1>
                <x-secondary-button as="a" href="{{ route('tools.sales.email-lists.show', $emailList) }}">
                    <i class="fas fa-arrow-left mr-2"></i> リスト詳細へ戻る
                </x-secondary-button>
            </div>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">購読者情報編集</h2>
            </div>
            <div class="p-6 sm:p-8">
                <form action="{{ route('tools.sales.email-lists.subscribers.update', [$emailList, $subscriber]) }}"
                    method="POST">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
                        <div>
                            <x-input-label for="email" value="メールアドレス" :required="true" />
                            <x-text-input type="email" id="email" name="email" class="mt-1 block w-full"
                                :value="old('email', $subscriber->email)" required :hasError="$errors->has('email')"
                                placeholder="example@domain.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="name" value="名前" />
                            <x-text-input type="text" id="name" name="name" class="mt-1 block w-full" :value="old('name', $subscriber->name)" :hasError="$errors->has('name')" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="company_name" value="会社名" />
                            <x-text-input type="text" id="company_name" name="company_name" class="mt-1 block w-full"
                                :value="old('company_name', $subscriber->company_name)"
                                :hasError="$errors->has('company_name')" />
                            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="postal_code" value="郵便番号" />
                            <x-text-input type="text" id="postal_code" name="postal_code" class="mt-1 block w-full"
                                :value="old('postal_code', $subscriber->postal_code)"
                                :hasError="$errors->has('postal_code')" placeholder="123-4567" />
                            <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="address" value="住所" />
                            <x-textarea-input id="address" name="address" class="mt-1 block w-full" rows="3"
                                :hasError="$errors->has('address')">{{ old('address', $subscriber->address) }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="phone_number" value="電話番号" />
                            <x-text-input type="tel" id="phone_number" name="phone_number" class="mt-1 block w-full"
                                :value="old('phone_number', $subscriber->phone_number)"
                                :hasError="$errors->has('phone_number')" placeholder="090-1234-5678" />
                            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="fax_number" value="FAX番号" />
                            <x-text-input type="tel" id="fax_number" name="fax_number" class="mt-1 block w-full"
                                :value="old('fax_number', $subscriber->fax_number)"
                                :hasError="$errors->has('fax_number')" />
                            <x-input-error :messages="$errors->get('fax_number')" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="url" value="URL" />
                            <x-text-input type="url" id="url" name="url" class="mt-1 block w-full" :value="old('url', $subscriber->url)" :hasError="$errors->has('url')" placeholder="https://example.com" />
                            <x-input-error :messages="$errors->get('url')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="representative_name" value="代表者" />
                            <x-text-input type="text" id="representative_name" name="representative_name"
                                class="mt-1 block w-full" :value="old('representative_name', $subscriber->representative_name)" :hasError="$errors->has('representative_name')" />
                            <x-input-error :messages="$errors->get('representative_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="establishment_date" value="設立日" />
                            <x-text-input type="date" id="establishment_date" name="establishment_date"
                                class="mt-1 block w-full" :value="old('establishment_date', optional($subscriber->establishment_date)->format('Y-m-d'))"
                                :hasError="$errors->has('establishment_date')" />
                            <x-input-error :messages="$errors->get('establishment_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="industry" value="業種" />
                            <x-text-input type="text" id="industry" name="industry" class="mt-1 block w-full"
                                :value="old('industry', $subscriber->industry)" :hasError="$errors->has('industry')" />
                            <x-input-error :messages="$errors->get('industry')" class="mt-2" />
                        </div>
                        {{-- <div>
                            <x-input-label for="job_title" value="役職" />
                            <x-text-input type="text" id="job_title" name="job_title" class="mt-1 block w-full"
                                :value="old('job_title', $subscriber->job_title)" :hasError="$errors->has('job_title')" />
                            <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
                        </div> --}}
                        <div>
                            @php
                                $statusOptions = [
                                    'subscribed' => '購読中 (Subscribed)',
                                    // 'unsubscribed' => '解除済 (Unsubscribed)',
                                    // 'bounced' => 'エラー (Bounced)',
                                    'pending' => '保留中 (Pending)',
                                ];
                            @endphp
                            <x-select-input label="ステータス" id="status" name="status" class="mt-1 block w-full"
                                :options="$statusOptions" :selected="old('status', $subscriber->status)" :required="true"
                                :hasError="$errors->has('status')" />
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <x-secondary-button as="a" href="{{ route('tools.sales.email-lists.show', $emailList) }}">
                            キャンセル
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            <i class="fas fa-save mr-2"></i> 更新する
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection