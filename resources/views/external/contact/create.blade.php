@extends('layouts.app')

@section('title', '連絡先の登録')

@section('content')
    <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-xl font-bold text-gray-800 dark:text-gray-200">連絡先 登録フォーム</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                新しい連絡先を登録します。
            </p>
        </div>

        <div class="p-6 sm:p-8">
            @if ($errors->any())
                <div
                    class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-900/50 dark:text-red-300 dark:border-red-700 text-sm">
                    ご入力内容にエラーがあります。ご確認ください。
                </div>
            @endif

            {{-- ▼▼▼【修正点2】フォームにIDを追加し、actionのルート名を変更 ▼▼▼ --}}
            <form method="POST" action="{{ route('external-contact.store') }}" id="external-contact-form" class="space-y-6">
                @csrf

                {{-- 共通フォームを読み込む --}}
                @include('tools.sales.managed_contacts._form', ['managedContact' => new \App\Models\ManagedContact(), 'isEditMode' => false])

                <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-200 dark:border-gray-600">
                    <x-primary-button>
                        登録する
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
@endsection

{{-- ▼▼▼【修正点3】リアルタイムチェック用のJavaScriptを追加 ▼▼▼ --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('external-contact-form');
        if (form) {
            const emailInput = form.querySelector('#email');
            const errorMessageDiv = form.querySelector('#email-validation-error');
            const submitButton = form.querySelector('button[type="submit"]');

            if (emailInput && errorMessageDiv && submitButton) {
                emailInput.addEventListener('blur', function () {
                    const email = this.value.trim();
                    if (email.length === 0) {
                        errorMessageDiv.style.display = 'none';
                        errorMessageDiv.textContent = '';
                        submitButton.disabled = false;
                        return;
                    }

                    fetch('{{ route("tools.sales.managed-contacts.checkEmail") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ email: email })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                errorMessageDiv.textContent = 'このメールアドレスは既に登録されています。';
                                errorMessageDiv.style.display = 'block';
                                submitButton.disabled = true;
                            } else {
                                errorMessageDiv.style.display = 'none';
                                errorMessageDiv.textContent = '';
                                submitButton.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Email check error:', error);
                        });
                });

                form.addEventListener('submit', function (event) {
                    if (submitButton.disabled) {
                        event.preventDefault();
                        alert('メールアドレスが重複しているため、登録できません。');
                    }
                });
            }
        }
    });
</script>