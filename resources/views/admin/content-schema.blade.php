@extends('layouts.admin')

@section('title', 'スキーマ設定')

@section('content')
    <div class="d-flex justify-content-between align-items-center page-title">
        <h2>スキーマ設定</h2>
        <button type="button" class="btn btn-primary" id="newEntryBtn">
            <i class="fas fa-plus"></i> 新規フィールド追加
        </button>
    </div>

    <!-- フィールド登録・更新フォーム -->
    <div class="card mb-4" id="dataForm" style="display: none;">
        <div class="card-header">
            <h5 class="mb-0">フィールド登録・更新フォーム</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.content-schema.addField') }}" method="POST" class="data-form">
                @csrf
                <input type="hidden" name="original_col_name" id="original_col_name">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="master_id" class="form-label">マスタカテゴリ</label>
                        <select id="master_id" name="master_id" class="form-select" required>
                            @foreach ($masters as $master)
                                <option value="{{ $master->master_id }}">
                                    {{ $master->master_id . ':' . $master->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="col_name" class="form-label">カラム名</label>
                        <input type="text" id="col_name" name="col_name" class="form-control" required>
                        <div class="form-text">英数字で入力してください（例: first_name）</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="view_name" class="form-label">表示名</label>
                        <input type="text" id="view_name" name="view_name" class="form-control" required>
                        <div class="form-text">ユーザーに表示される名前です（例: 氏名）</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">入力タイプ</label>
                        <select id="type" name="type" class="form-select">
                            <option value="text">text (文字列)</option>
                            <option value="textarea">textarea (1行以上の長い文章)</option>
                            <option value="number">number (数字)</option>
                            <option value="email">email (メールアドレス)</option>
                            <option value="tel">tel (電話番号)</option>
                            <option value="date">date (日付[年月日])</option>
                            <option value="month">month (日付[年月])</option>
                            <option value="select">select (プルダウン)</option>
                            <option value="radio">radio</option>
                            <option value="file">file (単一ファイル)</option>
                            <option value="files">files (複数ファイル)</option>
                            <option value="array">array (配列)</option>
                        </select>
                    </div>
                </div>

                <!-- selectタイプの場合に表示される選択肢入力欄 -->
                <div class="row mb-3" id="optionsContainer" style="display: none;">
                    <div class="col-md-12">
                        <label for="options" class="form-label">選択肢</label>
                        <textarea id="options" name="options" class="form-control" rows="5"
                            placeholder="各行に「値:表示名」の形式で入力してください。&#10;例:&#10;1:選択肢1&#10;2:選択肢2&#10;3:選択肢3"></textarea>
                        <div class="form-text">
                            各行に「値:表示名」の形式で入力してください。表示名を省略すると値がそのまま表示されます。<br>
                            例: <code>male:男性</code>, <code>female:女性</code>, <code>other:その他</code>
                        </div>
                    </div>
                </div>

                <!-- arrayタイプの場合に表示される項目設定欄 -->
                <div class="row mb-3" id="arrayItemsContainer" style="display: none;">
                    <div class="col-md-12">
                        <label for="arrayItems" class="form-label">配列項目設定</label>
                        <div class="array-items-wrapper">
                            <div class="array-items-list" id="arrayItemsList">
                                <!-- 項目はJavaScriptで動的に追加されます -->
                            </div>
                            <button type="button" class="btn btn-sm btn-primary mt-2" id="addArrayItemBtn">
                                <i class="fas fa-plus"></i> 項目を追加
                            </button>
                        </div>
                        <div class="form-text">
                            配列の各項目の名前と型を設定します。項目は自由に追加・削除できます。
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="sort_order" class="form-label">表示順</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-control" min="0" value="0">
                        <div class="form-text">数字が小さいほど先頭に表示されます</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">必須フラグ</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="required_flg" id="required_yes" value="1">
                            <label class="form-check-label" for="required_yes">必須項目にする</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="required_flg" id="required_no" value="0"
                                checked>
                            <label class="form-check-label" for="required_no">任意項目にする</label>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">公開フラグ</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="public_flg" id="public_yes" value="1"
                                checked>
                            <label class="form-check-label" for="public_yes">公開</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="public_flg" id="public_no" value="0">
                            <label class="form-check-label" for="public_no">非公開</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">キャンセル</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">登録</button>
                </div>
            </form>
        </div>
    </div>

    <!-- スキーマ一覧 -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">登録済みスキーマ一覧</h5>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="schemaTabs" role="tablist">
                @foreach ($masters as $index => $master)
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link {{ (session('active_master_id') == $master->master_id) || ($index === 0 && !session('active_master_id')) ? 'active' : '' }}"
                            id="tab-{{ $master->master_id }}-tab" data-bs-toggle="tab"
                            data-bs-target="#tab-{{ $master->master_id }}" type="button" role="tab"
                            aria-controls="tab-{{ $master->master_id }}"
                            aria-selected="{{ (session('active_master_id') == $master->master_id) || ($index === 0 && !session('active_master_id')) ? 'true' : 'false' }}">
                            {{ $master->master_id }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content" id="schemaTabsContent">
                @foreach ($masters as $index => $master)
                        <div class="tab-pane fade {{ (session('active_master_id') == $master->master_id) || ($index === 0 && !session('active_master_id')) ? 'show active' : '' }}"
                            id="tab-{{ $master->master_id }}" role="tabpanel" aria-labelledby="tab-{{ $master->master_id }}-tab">

                            <h4 class="mt-3 mb-3">{{ $master->master_id . '：' . $master->title }}</h4>

                            @if(isset($master->schema) && is_array($master->schema) && count($master->schema) > 0)
                                        <div class="table-container">
                                            <table class="table table-striped table-hover wide-table">
                                                <thead class="table-light sticky-header">
                                                    <tr>
                                                        <th class="col-actions">操作</th>
                                                        <th class="col-name">カラム名</th>
                                                        <th class="col-view-name">表示名</th>
                                                        <th class="col-type">入力タイプ</th>
                                                        <th class="col-sort">表示順</th>
                                                        <th class="col-required">必須フラグ</th>
                                                        <th class="col-public">公開フラグ</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="sortable-schema-{{ $master->master_id }}">
                                                    @php
                                                        // スキーマを表示順でソート
                                                        $sortedSchema = collect($master->schema)->sortBy('sort_order')->values()->all();
                                                    @endphp
                                                    @foreach ($sortedSchema as $field)
                                                                        <tr data-col-name="{{ $field['col_name'] }}">
                                                                            <td>
                                                                                <div class="btn-group" role="group">
                                                                                    <button class="btn btn-sm btn-warning btn-action edit-btn"
                                                                                        data-master-id="{{ $master->master_id }}"
                                                                                        data-col-name="{{ $field['col_name'] }}"
                                                                                        data-view-name="{{ $field['view_name'] }}" data-type="{{ $field['type'] }}"
                                                                                        data-sort-order="{{ $field['sort_order'] ?? 0 }}"
                                                                                        data-required-flg="{{ $field['required_flg'] }}"
                                                                                        data-public-flg="{{ $field['public_flg'] }}" @php
                                                                                            $optionsJson = isset($field['options']) ? json_encode($field['options']) : ''
                                                                                            ;
                                                                                            $arrayItemsJson = isset($field['array_items']) ?
                                                                                        json_encode($field['array_items']) : ''; @endphp
                                                                                        data-options="{{ $optionsJson }}" data-array-items="{{ $arrayItemsJson }}">
                                                                                        <i class="fas fa-edit"></i> 編集
                                                                                    </button>
                                                                                    <button type="button" class="btn btn-sm btn-danger btn-action"
                                                                                        data-bs-toggle="modal"
                                                                                        data-bs-target="#deleteFieldModal{{ $master->master_id }}_{{ $field['col_name'] }}">
                                                                                        <i class="fas fa-trash"></i> 削除
                                                                                    </button>
                                                                                </div>

                                                                                <!-- 削除確認モーダル -->
                                                                                <div class="modal fade"
                                                                                    id="deleteFieldModal{{ $master->master_id }}_{{ $field['col_name'] }}"
                                                                                    tabindex="-1" aria-hidden="true">
                                                                                    <div class="modal-dialog">
                                                                                        <div class="modal-content">
                                                                                            <div class="modal-header">
                                                                                                <h5 class="modal-title">削除確認</h5>
                                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                                                    aria-label="閉じる"></button>
                                                                                            </div>
                                                                                            <div class="modal-body">
                                                                                                <p>フィールド「{{ $field['view_name'] }}」を削除してもよろしいですか？</p>
                                                                                                <p class="text-danger">この操作は取り消せません。また、このフィールドに関連するデータも失われる可能性があります。
                                                                                                </p>
                                                                                            </div>
                                                                                            <div class="modal-footer">
                                                                                                <button type="button" class="btn btn-secondary"
                                                                                                    data-bs-dismiss="modal">キャンセル</button>
                                                                                                <form action="{{ route('admin.content-schema.deleteField') }}"
                                                                                                    method="POST">
                                                                                                    @csrf
                                                                                                    @method('DELETE')
                                                                                                    <input type="hidden" name="master_id"
                                                                                                        value="{{ $master->master_id }}">
                                                                                                    <input type="hidden" name="col_name"
                                                                                                        value="{{ $field['col_name'] }}">
                                                                                                    <button type="submit" class="btn btn-danger">削除する</button>
                                                                                                </form>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </td>
                                                                            <td>{{ $field['col_name'] }}</td>
                                                                            <td>{{ $field['view_name'] }}</td>
                                                                            <td>
                                                                                {{ $field['type'] }}
                                                                                @if($field['type'] === 'select' && isset($field['options']) && count($field['options']) > 0)
                                                                                                    <span class="badge bg-info ms-1" data-bs-toggle="tooltip" data-bs-placement="top"
                                                                                                        title="{{ implode(', ', array_map(function ($opt) {
                                                                                    return $opt['label']; }, $field['options'])) }}">
                                                                                                        {{ count($field['options']) }}個の選択肢
                                                                                                    </span>
                                                                                @endif
                                                                                @if($field['type'] === 'array' && isset($field['array_items']) && count($field['array_items']) > 0)
                                                                                                    <span class="badge bg-info ms-1" data-bs-toggle="tooltip" data-bs-placement="top"
                                                                                                        title="{{ implode(', ', array_map(function ($item) {
                                                                                    return $item['name'] . '(' . $item['type'] . ')'; }, $field['array_items'])) }}">
                                                                                                        {{ count($field['array_items']) }}個の項目
                                                                                                    </span>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex align-items-center">
                                                                                    <span class="sort-order">{{ $field['sort_order'] ?? 0 }}</span>
                                                                                    <div class="ms-2">
                                                                                        <button type="button" class="btn btn-sm btn-outline-secondary move-up-btn"
                                                                                            title="上に移動">
                                                                                            <i class="fas fa-arrow-up"></i>
                                                                                        </button>
                                                                                        <button type="button" class="btn btn-sm btn-outline-secondary move-down-btn"
                                                                                            title="下に移動">
                                                                                            <i class="fas fa-arrow-down"></i>
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <span
                                                                                    class="badge {{ $field['required_flg'] == '1' ? 'bg-danger' : 'bg-secondary' }}">
                                                                                    {{ $field['required_flg'] == '1' ? '必須' : '任意' }}
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <span
                                                                                    class="badge {{ $field['public_flg'] == '1' ? 'bg-success' : 'bg-secondary' }}">
                                                                                    {{ $field['public_flg'] == '1' ? '公開' : '非公開' }}
                                                                                </span>
                                                                            </td>
                                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- 表示順保存ボタン -->
                                        <div class="mt-3">
                                            <form action="{{ route('admin.content-schema.update-order', ['masterId' => $master->master_id]) }}"
                                                method="POST" class="schema-sort-form">
                                                @csrf
                                                <input type="hidden" name="schema_order" class="schema-order-input" value="">
                                                <button type="submit" class="btn btn-success save-schema-order-btn">
                                                    <i class="fas fa-save"></i> 表示順を保存
                                                </button>
                                            </form>
                                        </div>
                            @else
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>このマスターにはまだフィールドが登録されていません。
                                </div>
                            @endif
                        </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* テーブルコンテナ - 縦横スクロール可能 */
        .table-container {
            height: 500px;
            /* 固定高さを設定 */
            overflow: auto;
            /* 縦横両方にスクロール可能 */
            position: relative;
            /* 子要素の位置決めの基準 */
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }

        /* 固定ヘッダー */
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8f9fa;
            /* Bootstrap table-lightの色 */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sticky-header th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            /* Bootstrap table-lightの色 */
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.1);
        }

        /* 幅の広いテーブル設定 */
        .wide-table {
            table-layout: fixed;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        /* 各列の幅を設定 */
        .wide-table .col-actions {
            width: 150px;
            min-width: 150px;
        }

        .wide-table .col-name {
            width: 180px;
            min-width: 180px;
        }

        .wide-table .col-view-name {
            width: 180px;
            min-width: 180px;
        }

        .wide-table .col-type {
            width: 150px;
            min-width: 150px;
        }

        .wide-table .col-sort {
            width: 120px;
            min-width: 120px;
        }

        .wide-table .col-required,
        .wide-table .col-public {
            width: 100px;
            min-width: 100px;
        }

        .array-items-wrapper {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            background-color: #f8f9fa;
        }

        .array-item {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
        }

        .array-field-container .array-item {
            position: relative;
        }

        .array-data-preview .table {
            font-size: 0.875rem;
        }

        .array-data-preview .table th,
        .array-data-preview .table td {
            padding: 0.25rem 0.5rem;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editButtons = document.querySelectorAll('.edit-btn');
            const form = document.querySelector('.data-form');
            const dataFormContainer = document.getElementById('dataForm');
            const newEntryBtn = document.getElementById('newEntryBtn');
            const submitBtn = document.getElementById('submitBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const masterIdSelect = document.getElementById('master_id');
            const typeSelect = document.getElementById('type');
            const optionsContainer = document.getElementById('optionsContainer');
            const optionsTextarea = document.getElementById('options');

            // 入力タイプが変更されたときの処理
            typeSelect.addEventListener('change', function () {
                toggleOptionsContainer();
            });

            // 選択肢入力欄と配列項目設定欄の表示/非表示を切り替える
            function toggleOptionsContainer() {
                if (typeSelect.value === 'select') {
                    optionsContainer.style.display = 'block';
                    arrayItemsContainer.style.display = 'none';
                } else if (typeSelect.value === 'array') {
                    optionsContainer.style.display = 'none';
                    arrayItemsContainer.style.display = 'block';
                } else {
                    optionsContainer.style.display = 'none';
                    arrayItemsContainer.style.display = 'none';
                }
            }

            // ツールチップの初期化
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // キャンセルボタンのイベントリスナー
            cancelBtn.addEventListener('click', function () {
                hideForm();
            });

            // 新規登録ボタンのイベントリスナー
            newEntryBtn.addEventListener('click', function () {
                resetForm();
                showForm();
                masterIdSelect.disabled = false;
            });

            // 編集ボタンのイベントリスナー
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const masterId = this.getAttribute('data-master-id');
                    const colName = this.getAttribute('data-col-name');
                    const viewName = this.getAttribute('data-view-name');
                    const type = this.getAttribute('data-type');
                    const sortOrder = this.getAttribute('data-sort-order');
                    const requiredFlg = this.getAttribute('data-required-flg');
                    const publicFlg = this.getAttribute('data-public-flg');
                    const optionsData = this.getAttribute('data-options');
                    const arrayItemsData = this.getAttribute('data-array-items');

                    document.getElementById('master_id').value = masterId;
                    document.getElementById('original_col_name').value = colName;
                    document.getElementById('col_name').value = colName;
                    document.getElementById('view_name').value = viewName;
                    document.getElementById('type').value = type;
                    document.getElementById('sort_order').value = sortOrder;
                    document.querySelector(`input[name="required_flg"][value="${requiredFlg}"]`).checked = true;
                    document.querySelector(`input[name="public_flg"][value="${publicFlg}"]`).checked = true;

                    // 選択肢データがある場合は表示
                    if (optionsData && type === 'select') {
                        try {
                            // HTMLエンティティをデコード
                            const decodedOptionsData = decodeHTMLEntities(optionsData);

                            const options = JSON.parse(decodedOptionsData);

                            let optionsText = '';

                            options.forEach(option => {
                                optionsText += `${option.value}:${option.label}\n`;
                            });

                            optionsTextarea.value = optionsText.trim();
                        } catch (e) {
                            console.error('選択肢データの解析に失敗しました:', e);
                            console.error('Raw options data:', optionsData);
                            optionsTextarea.value = '';
                        }
                    } else {
                        optionsTextarea.value = '';
                    }

                    // 配列項目データがある場合は表示
                    if (type === 'array') {
                        if (arrayItemsData) {
                            try {
                                const decodedArrayItemsData = decodeHTMLEntities(arrayItemsData);
                                const arrayItems = JSON.parse(decodedArrayItemsData);
                                loadArrayItems(arrayItems);
                            } catch (e) {
                                console.error('配列項目データの解析に失敗しました:', e);
                                console.error('Raw array items data:', arrayItemsData);
                            }
                        }
                    }

                    // 選択肢入力欄の表示/非表示を更新
                    toggleOptionsContainer();

                    submitBtn.textContent = '更新';
                    form.action = "{{ route('admin.content-schema.updateField') }}";

                    // 編集時はマスターカテゴリを変更できないようにする
                    masterIdSelect.disabled = true;
                    jQuery(function ($) {
                        $("#master_id").prop('disabled', true);
                        $('form').bind('submit', function () {
                            $(this).find("#master_id").prop('disabled', false);
                        });
                    });
                    showForm();
                });
            });

            function resetForm() {
                form.reset();
                document.getElementById('original_col_name').value = '';
                document.getElementById('col_name').value = '';
                document.getElementById('view_name').value = '';
                document.getElementById('type').value = 'text';
                document.getElementById('sort_order').value = '0';
                document.querySelector('input[name="required_flg"][value="0"]').checked = true;
                document.querySelector('input[name="public_flg"][value="1"]').checked = true;
                optionsTextarea.value = '';
                optionsContainer.style.display = 'none';

                // 配列項目をクリア
                arrayItemsList.innerHTML = '';
                arrayItemsContainer.style.display = 'none';

                submitBtn.textContent = '登録';
                form.action = "{{ route('admin.content-schema.addField') }}";
            }

            function showForm() {
                dataFormContainer.style.display = 'block';
                dataFormContainer.scrollIntoView({ behavior: 'smooth' });
            }

            function hideForm() {
                dataFormContainer.style.display = 'none';
            }

            // スキーマの並べ替え機能
            const masters = @json($masters);

            masters.forEach(master => {
                const container = document.getElementById(`sortable-schema-${master.master_id}`);
                if (container) {
                    // 上に移動ボタン
                    container.querySelectorAll('.move-up-btn').forEach(button => {
                        button.addEventListener('click', function () {
                            const row = this.closest('tr');
                            const prevRow = row.previousElementSibling;
                            if (prevRow) {
                                container.insertBefore(row, prevRow);
                                updateSchemaOrder(master.master_id);
                            }
                        });
                    });

                    // 下に移動ボタン
                    container.querySelectorAll('.move-down-btn').forEach(button => {
                        button.addEventListener('click', function () {
                            const row = this.closest('tr');
                            const nextRow = row.nextElementSibling;
                            if (nextRow) {
                                container.insertBefore(nextRow, row);
                                updateSchemaOrder(master.master_id);
                            }
                        });
                    });
                }
            });

            // スキーマの表示順更新
            function updateSchemaOrder(masterId) {
                const container = document.getElementById(`sortable-schema-${masterId}`);
                const rows = container.querySelectorAll('tr');
                const orderData = [];

                rows.forEach((row, index) => {
                    const colName = row.dataset.colName;
                    orderData.push({
                        col_name: colName,
                        sort_order: index
                    });
                });

                // 対応するフォームのinput要素を更新
                const form = container.closest('.tab-pane').querySelector('.schema-sort-form');
                const input = form.querySelector('.schema-order-input');
                input.value = JSON.stringify(orderData);
            }

            // 表示順保存ボタンのイベントリスナー
            const saveButtons = document.querySelectorAll('.save-schema-order-btn');
            saveButtons.forEach(button => {
                button.addEventListener('click', function (e) {
                    const form = this.closest('form');
                    const masterId = form.action.split('/').pop();
                    updateSchemaOrder(masterId);
                });
            });

            // HTMLエンティティをデコードする関数
            function decodeHTMLEntities(text) {
                if (!text) return '';
                const textarea = document.createElement('textarea');
                textarea.innerHTML = text;
                return textarea.value;
            }

            // 配列項目の管理
            const arrayItemsContainer = document.getElementById('arrayItemsContainer');
            const arrayItemsList = document.getElementById('arrayItemsList');
            const addArrayItemBtn = document.getElementById('addArrayItemBtn');

            // 配列項目を追加するボタンのイベントリスナー
            if (addArrayItemBtn) {
                addArrayItemBtn.addEventListener('click', function () {
                    addArrayItem();
                });
            }

            // 配列項目を追加する関数
            function addArrayItem(itemName = '', itemType = 'text') {
                const itemId = 'array-item-' + Date.now();
                const itemHtml = `
                            <div class="array-item card p-3 mb-2" id="${itemId}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">配列項目</h6>
                                    <button type="button" class="btn btn-sm btn-danger remove-array-item" data-item-id="${itemId}">
                                        <i class="fas fa-times"></i> 削除
                                    </button>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">項目名</label>
                                        <input type="text" name="array_items[name][]" class="form-control" value="${itemName}" placeholder="例: title" required>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">項目タイプ</label>
                                        <select name="array_items[type][]" class="form-select">
                                            <option value="text" ${itemType === 'text' ? 'selected' : ''}>文字列</option>
                                            <option value="number" ${itemType === 'number' ? 'selected' : ''}>数値</option>
                                            <option value="boolean" ${itemType === 'boolean' ? 'selected' : ''}>真偽値</option>
                                            <option value="date" ${itemType === 'date' ? 'selected' : ''}>日付</option>
                                            <option value="url" ${itemType === 'url' ? 'selected' : ''}>URL</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        `;

                arrayItemsList.insertAdjacentHTML('beforeend', itemHtml);

                // 削除ボタンのイベントリスナーを設定
                const removeBtn = document.querySelector(`#${itemId} .remove-array-item`);
                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        document.getElementById(itemId).remove();
                    });
                }
            }

            // 編集時に既存の配列項目を読み込む
            function loadArrayItems(items) {
                if (!items || !Array.isArray(items)) return;

                // 既存の項目をクリア
                arrayItemsList.innerHTML = '';

                // 項目を追加
                items.forEach(item => {
                    addArrayItem(item.name, item.type);
                });
            }
        });
    </script>
@endpush