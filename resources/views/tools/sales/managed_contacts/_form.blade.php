<div class="space-y-6">
    <div>
        <x-input-label for="email" value="メールアドレス" :required="true" />
        <x-text-input type="email" id="email" name="email" class="mt-1 block w-full" :value="old('email', $managedContact->email ?? '')" required :hasError="$errors->has('email')" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="name" value="名前" />
        <x-text-input type="text" id="name" name="name" class="mt-1 block w-full" :value="old('name', $managedContact->name ?? '')" :hasError="$errors->has('name')" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="company_name" value="会社名" />
        <x-text-input type="text" id="company_name" name="company_name" class="mt-1 block w-full"
            :value="old('company_name', $managedContact->company_name ?? '')"
            :hasError="$errors->has('company_name')" />
        <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-input-label for="postal_code" value="郵便番号" />
            <x-text-input type="text" id="postal_code" name="postal_code" class="mt-1 block w-full"
                :value="old('postal_code', $managedContact->postal_code ?? '')"
                :hasError="$errors->has('postal_code')" />
            <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="phone_number" value="電話番号" />
            <x-text-input type="tel" id="phone_number" name="phone_number" class="mt-1 block w-full"
                :value="old('phone_number', $managedContact->phone_number ?? '')"
                :hasError="$errors->has('phone_number')" />
            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
        </div>
    </div>
    <div>
        <x-input-label for="address" value="住所" />
        <x-textarea-input id="address" name="address" class="mt-1 block w-full" rows="3"
            :hasError="$errors->has('address')">{{ old('address', $managedContact->address ?? '') }}</x-textarea-input>
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-input-label for="fax_number" value="FAX番号" />
            <x-text-input type="tel" id="fax_number" name="fax_number" class="mt-1 block w-full"
                :value="old('fax_number', $managedContact->fax_number ?? '')" :hasError="$errors->has('fax_number')" />
            <x-input-error :messages="$errors->get('fax_number')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="url" value="URL" />
            <x-text-input type="url" id="url" name="url" class="mt-1 block w-full" :value="old('url', $managedContact->url ?? '')" :hasError="$errors->has('url')" placeholder="https://example.com" />
            <x-input-error :messages="$errors->get('url')" class="mt-2" />
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-input-label for="representative_name" value="代表者名" />
            <x-text-input type="text" id="representative_name" name="representative_name" class="mt-1 block w-full"
                :value="old('representative_name', $managedContact->representative_name ?? '')"
                :hasError="$errors->has('representative_name')" />
            <x-input-error :messages="$errors->get('representative_name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="establishment_date" value="設立年月日" />
            <x-text-input type="date" id="establishment_date" name="establishment_date" class="mt-1 block w-full"
                :value="old('establishment_date', $managedContact->establishment_date ? \Carbon\Carbon::parse($managedContact->establishment_date)->format('Y-m-d') : '')"
                :hasError="$errors->has('establishment_date')" />
            <x-input-error :messages="$errors->get('establishment_date')" class="mt-2" />
        </div>
    </div>
    <div>
        <x-input-label for="industry" value="業種" />
        <x-text-input type="text" id="industry" name="industry" class="mt-1 block w-full" :value="old('industry', $managedContact->industry ?? '')" :hasError="$errors->has('industry')" />
        <x-input-error :messages="$errors->get('industry')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="notes" value="備考" />
        <x-textarea-input id="notes" name="notes" class="mt-1 block w-full" rows="3"
            :hasError="$errors->has('notes')">{{ old('notes', $managedContact->notes ?? '') }}</x-textarea-input>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" value="ステータス" :required="true" />
        <x-select-input id="status" name="status" class="mt-1 block w-full" :hasError="$errors->has('status')">
            @php
                $currentStatus = old('status', $managedContact->status ?? 'active');
            @endphp
            <option value="active" @selected($currentStatus === 'active')>有効</option>
            <option value="do_not_contact" @selected($currentStatus === 'do_not_contact')>連絡不要</option>
            <option value="archived" @selected($currentStatus === 'archived')>アーカイブ済</option>
        </x-select-input>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
</div>