@extends('layouts.tool')

@section('title', '新規管理連絡先 作成')

@section('breadcrumbs')
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">営業ツール</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <a href="{{ route('tools.sales.managed-contacts.index') }}"
        class="hover:text-blue-600 dark:hover:text-blue-400">管理連絡先一覧</a>
    <span class="text-gray-500 dark:text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">新規作成</span>
@endsection

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-3xl mx-auto mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    新規管理連絡先 作成
                </h1>
                <x-secondary-button as="a" href="{{ route('tools.sales.managed-contacts.index') }}">
                    <i class="fas fa-arrow-left mr-2"></i> 一覧へ戻る
                </x-secondary-button>
            </div>
        </div>

        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">連絡先情報</h2>
            </div>
            <div class="p-6 sm:p-8">
                {{-- フォームにIDを追加 --}}
                <form action="{{ route('tools.sales.managed-contacts.store') }}" method="POST" id="main-contact-form">
                    @csrf
                    @include('tools.sales.managed_contacts._form', ['managedContact' => new \App\Models\ManagedContact(), 'isEditMode' => false])

                    <div class="mt-8 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                        <x-secondary-button as="a" href="{{ route('tools.sales.managed-contacts.index') }}">
                            キャンセル
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            <i class="fas fa-plus mr-2"></i> 作成する
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

{{-- ▼▼▼【ここから追加】リアルタイムチェック用のJavaScript ▼▼▼ --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // このフォーム内の要素を特定
            const form = document.getElementById('main-contact-form');
            if (form) {
                const emailInput = form.querySelector('#email');
                const errorMessageDiv = form.querySelector('#email-validation-error');
                const submitButton = form.querySelector('button[type="submit"]');

                if (emailInput && errorMessageDiv && submitButton) {
                    // メールアドレス入力欄からフォーカスが外れた時にチェックを実行
                    emailInput.addEventListener('blur', function () {
                        const email = this.value.trim();

                        // 入力が空の場合は何もしない
                        if (email.length === 0) {
                            errorMessageDiv.style.display = 'none';
                            errorMessageDiv.textContent = '';
                            submitButton.disabled = false;
                            return;
                        }

                        // fetch APIを使用してサーバーに問い合わせ
                        fetch('{{ route("tools.sales.managed-contacts.checkEmail") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ email: email })
                        })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.exists) {
                                    errorMessageDiv.textContent = 'このメールアドレスは既に登録されています。';
                                    errorMessageDiv.style.display = 'block';
                                    submitButton.disabled = true; // 重複している場合は登録ボタンを無効化
                                } else {
                                    errorMessageDiv.style.display = 'none';
                                    errorMessageDiv.textContent = '';
                                    submitButton.disabled = false; // 問題なければ登録ボタンを有効化
                                }
                            })
                            .catch(error => {
                                console.error('Email check error:', error);
                                errorMessageDiv.textContent = '重複チェック中にエラーが発生しました。';
                                errorMessageDiv.style.display = 'block';
                                submitButton.disabled = true; // エラー時も安全のため無効化
                            });
                    });

                    // フォーム送信時に再度チェック（ブラウザの自動入力などを考慮）
                    form.addEventListener('submit', function (event) {
                        if (submitButton.disabled) {
                            event.preventDefault(); // 送信を中止
                            alert('メールアドレスが重複しているため、登録できません。');
                        }
                    });
                }
            }
        });
    </script>
@endpush