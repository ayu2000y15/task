@extends('layouts.admin')

@section('title', '汎用テーブル管理')

@section('content')
    <div class="d-flex justify-content-between align-items-center page-title mb-4">
        <h2>汎用テーブル管理</h2>
        <button type="button" class="btn btn-primary" id="newEntryBtn">
            <i class="fas fa-plus me-1"></i> 新規登録
        </button>
    </div>

    <!-- 登録・更新フォーム -->
    @component('components.admin-card', ['title' => '登録・更新'])
        <div id="dataForm" style="display: none;">
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn-close" id="cancelBtn" aria-label="閉じる"></button>
            </div>
            <form action="{{ route('admin.definition.store') }}" method="POST" class="data-form">
                @csrf
                <input type="hidden" name="definition_id" id="definition_id">

                <div class="row mb-3">
                    <div class="col-md-6">
                        @include('components.form-field', [
                            'name' => 'definition',
                            'label' => '定義',
                            'type' => 'text',
                            'required' => true
                        ])
                    </div>
                    <div class="col-md-6">
                        @include('components.form-field', [
                            'name' => 'item',
                            'label' => '内容',
                            'type' => 'text',
                            'required' => true
                        ])
                    </div>
                </div>

                @include('components.form-field', [
                    'name' => 'explanation',
                    'label' => '説明',
                    'type' => 'text',
                    'required' => true
                ])

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" onclick="return confirm('登録しますか？');" class="btn btn-primary"
                        id="submitBtn">登録</button>
                </div>
            </form>
        </div>
    @endcomponent

    <!-- データ一覧 -->
    @component('components.admin-card', ['title' => '登録済みデータ一覧', 'icon' => 'list'])
        @php
            $columns = [
                ['label' => '操作', 'class' => 'col-actions'],
                ['label' => '定義ID'],
                ['label' => '定義'],
                ['label' => '内容'],
                ['label' => '説明']
            ];
        @endphp

        @component('components.data-table', ['columns' => $columns, 'headerClass' => 'table-light'])
            @foreach ($definition as $def)
                <tr>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-warning edit-btn" data-id="{{ $def->definition_id }}"
                                data-definition="{{ $def->definition }}" data-item="{{ $def->item }}"
                                data-explanation="{{ $def->explanation }}">
                                <i class="fas fa-edit"></i> 編集
                            </button>
                            <form action="{{ route('admin.definition.delete') }}" method="POST"
                                style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="definition_id" value="{{ $def->definition_id }}">
                                <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('本当に削除しますか？');">
                                    <i class="fas fa-trash"></i> 削除
                                </button>
                            </form>
                        </div>
                    </td>
                    <td>{{ $def->definition_id }}</td>
                    <td>{{ $def->definition }}</td>
                    <td>{{ $def->item }}</td>
                    <td>{{ $def->explanation }}</td>
                </tr>
            @endforeach
        @endcomponent
    @endcomponent
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editButtons = document.querySelectorAll('.edit-btn');
            const form = document.querySelector('.data-form');
            const dataFormContainer = document.getElementById('dataForm');
            const newEntryBtn = document.getElementById('newEntryBtn');
            const submitBtn = document.getElementById('submitBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            //キャンセルボタンのイベントリスナー
            cancelBtn.addEventListener('click', function () {
                hideForm();
            });

            // 新規登録ボタンのイベントリスナー
            newEntryBtn.addEventListener('click', function () {
                resetForm();
                showForm();
            });

            // 編集ボタンのイベントリスナー
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const definitionId = this.getAttribute('data-id');
                    const definition = this.getAttribute('data-definition');
                    const item = this.getAttribute('data-item');
                    const explanation = this.getAttribute('data-explanation');

                    document.getElementById('definition_id').value = definitionId;
                    document.getElementById('definition').value = definition;
                    document.getElementById('item').value = item;
                    document.getElementById('explanation').value = explanation;

                    submitBtn.textContent = '更新';
                    form.action = "{{ route('admin.definition.update') }}";

                    showForm();
                });
            });

            function resetForm() {
                form.reset();
                document.getElementById('definition_id').value = '';
                document.getElementById('definition').value = '';
                document.getElementById('item').value = '';
                document.getElementById('explanation').value = '';

                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> 登録';
                form.action = "{{ route('admin.definition.store') }}";
            }

            function showForm() {
                dataFormContainer.style.display = 'block';
                dataFormContainer.scrollIntoView({ behavior: 'smooth' });
            }

            function hideForm() {
                dataFormContainer.style.display = 'none';
            }
        });
    </script>
@endpush

