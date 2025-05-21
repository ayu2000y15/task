@extends('layouts.admin')

@section('title', $master->title . ' - データ一覧')

@section('content')
    <div class="d-flex justify-content-between align-items-center page-title mb-4">
        <h2>{{ $master->title }} - データ一覧</h2>
        <div>
            <a href="{{ route('admin.content-data') }}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> 戻る
            </a>
            <a href="{{ route('admin.content-data.create', ['masterId' => $master->master_id]) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> 新規登録
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0 fw-bold"><i class="fas fa-table me-2"></i>データ一覧</h5>
        </div>
        <div class="card-body">
            @if(count($data) > 0)
                <div class="table-container">
                    <table class="table table-striped table-hover wide-table">
                        <thead class="table-primary sticky-header">
                            <tr>
                                <th class="col-actions">操作</th>
                                <th class="col-status">公開状態</th>
                                <th class="col-sort">表示順</th>
                                @if(isset($master->schema) && is_array($master->schema))
                                                    @php
                                                        // スキーマを表示順でソート
                                                        $sortedSchema = collect($master->schema)->sortBy('sort_order')->values()->all();
                                                    @endphp
                                                    @foreach($sortedSchema as $field)
                                                        @if($field['public_flg'] == '1')
                                                            <th class="col-data">{{ $field['view_name'] }}</th>
                                                        @endif
                                                    @endforeach
                                @endif
                                <th class="col-date">登録日時</th>
                                <th class="col-date">更新日時</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-items">
                            @foreach($data as $item)
                                <tr data-id="{{ $item->data_id }}" data-sort-order="{{ $item->sort_order ?? 0 }}">
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.content-data.edit', ['dataId' => $item->data_id]) }}"
                                                class="btn btn-sm btn-warning btn-action">
                                                <i class="fas fa-edit"></i> 編集
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal"
                                                data-bs-target="#deleteModal{{ $item->data_id }}">
                                                <i class="fas fa-trash"></i> 削除
                                            </button>
                                        </div>

                                        <!-- 削除確認モーダル -->
                                        <div class="modal fade" id="deleteModal{{ $item->data_id }}" tabindex="-1"
                                            aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">削除確認</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="閉じる"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>このデータを削除してもよろしいですか？</p>
                                                        <p>この操作は取り消せません。</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">キャンセル</button>
                                                        <form
                                                            action="{{ route('admin.content-data.delete', ['dataId' => $item->data_id]) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">削除する</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button"
                                            class="btn btn-sm toggle-public-btn {{ $item->public_flg == '1' ? 'btn-success' : 'btn-secondary' }}"
                                            data-id="{{ $item->data_id }}" data-status="{{ $item->public_flg }}"
                                            title="{{ $item->public_flg == '1' ? '公開中（クリックで非公開に切り替え）' : '非公開（クリックで公開に切り替え）' }}">
                                            <i class="fas {{ $item->public_flg == '1' ? 'fa-eye' : 'fa-eye-slash' }} me-1"></i>
                                            {{ $item->public_flg == '1' ? '公開' : '非公開' }}
                                        </button>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="sort-handle me-2"><i class="fas fa-grip-vertical text-muted"></i></span>
                                            <span class="sort-order badge bg-secondary">{{ $item->sort_order ?? 0 }}</span>
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
                                    @if(isset($master->schema) && is_array($master->schema))
                                        @foreach($sortedSchema as $field)
                                            @if($field['public_flg'] == '1')
                                                <td class="cell-content">
                                                    @if(isset($item->content[$field['col_name']]))
                                                        @if($field['type'] == 'textarea')
                                                            <div class="text-content">
                                                                {!! nl2br(e($item->content[$field['col_name']])) !!}
                                                            </div>
                                                        @elseif($field['type'] == 'file')
                                                            @if(!empty($item->content[$field['col_name']]))
                                                                <a href="{{ asset($item->content[$field['col_name']]) }}" target="_blank"
                                                                    class="image-preview">
                                                                    <img src="{{ asset($item->content[$field['col_name']]) }}" alt="画像"
                                                                        class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                                                                </a>
                                                            @else
                                                                <span class="text-muted">ファイルなし</span>
                                                            @endif
                                                        @elseif($field['type'] == 'array')
                                                            @if(!empty($item->content[$field['col_name']]) && is_array($item->content[$field['col_name']]))
                                                                <div class="array-data-preview">
                                                                    <button type="button" class="btn btn-sm btn-outline-info array-preview-toggle"
                                                                        data-bs-toggle="collapse"
                                                                        data-bs-target="#array-preview-{{ $item->data_id }}-{{ $field['col_name'] }}">
                                                                        {{ count($item->content[$field['col_name']]) }}個の項目 <i
                                                                            class="fas fa-chevron-down"></i>
                                                                    </button>
                                                                    <div class="collapse mt-2"
                                                                        id="array-preview-{{ $item->data_id }}-{{ $field['col_name'] }}">
                                                                        <div class="card card-body p-2">
                                                                            <div class="table-responsive">
                                                                                <table class="table table-sm table-bordered mb-0">
                                                                                    <thead>
                                                                                        <tr>
                                                                                            <th>#</th>
                                                                                            @if(isset($field['array_items']) && is_array($field['array_items']))
                                                                                                @foreach($field['array_items'] as $arrayItem)
                                                                                                    <th>{{ $arrayItem['name'] }}</th>
                                                                                                @endforeach
                                                                                            @endif
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        @foreach($item->content[$field['col_name']] as $index => $arrayItem)
                                                                                            <tr>
                                                                                                <td>{{ $index + 1 }}</td>
                                                                                                @if(isset($field['array_items']) && is_array($field['array_items']))
                                                                                                    @foreach($field['array_items'] as $arrayItemDef)
                                                                                                        <td>
                                                                                                            @if(isset($arrayItem[$arrayItemDef['name']]))
                                                                                                                @if($arrayItemDef['type'] == 'boolean')
                                                                                                                    <span
                                                                                                                        class="badge {{ $arrayItem[$arrayItemDef['name']] ? 'bg-success' : 'bg-secondary' }}">
                                                                                                                        {{ $arrayItem[$arrayItemDef['name']] ? '有効' : '無効' }}
                                                                                                                    </span>
                                                                                                                @elseif($arrayItemDef['type'] == 'date')
                                                                                                                    {{ $arrayItem[$arrayItemDef['name']] }}
                                                                                                                @elseif($arrayItemDef['type'] == 'url')
                                                                                                                    <a href="{{ $arrayItem[$arrayItemDef['name']] }}"
                                                                                                                        target="_blank" class="text-break">
                                                                                                                        {{ $arrayItem[$arrayItemDef['name']] }}
                                                                                                                    </a>
                                                                                                                @else
                                                                                                                    {{ $arrayItem[$arrayItemDef['name']] }}
                                                                                                                @endif
                                                                                                            @endif
                                                                                                        </td>
                                                                                                    @endforeach
                                                                                                @endif
                                                                                            </tr>
                                                                                        @endforeach
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <span class="text-muted">データなし</span>
                                                            @endif
                                                        @elseif($field['type'] == 'files')
                                                            @if(!empty($item->content[$field['col_name']]) && is_array($item->content[$field['col_name']]))
                                                                <div class="d-flex flex-wrap gap-1">
                                                                    @foreach($item->content[$field['col_name']] as $filePath)
                                                                        <a href="{{ asset($filePath) }}" target="_blank" class="image-preview">
                                                                            <img src="{{ asset($filePath) }}" alt="画像" class="img-thumbnail"
                                                                                style="width: 50px; height: 50px; object-fit: cover;">
                                                                        </a>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <span class="text-muted">ファイルなし</span>
                                                            @endif
                                                        @else
                                                            <span class="data-value">{{ $item->content[$field['col_name']] }}</span>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endif
                                        @endforeach
                                    @endif
                                    <td>{{ $item->created_at }}</td>
                                    <td>{{ $item->updated_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- 表示順保存ボタン -->
                <div class="mt-3">
                    <form id="sort-form"
                        action="{{ route('admin.content-data.update-order', ['masterId' => $master->master_id]) }}"
                        method="POST">
                        @csrf
                        <input type="hidden" id="sort-data" name="sort_data" value="">
                        <button type="submit" class="btn btn-success" id="save-sort-btn">
                            <i class="fas fa-save"></i> 表示順を保存
                        </button>
                    </form>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>データがありません。新規登録ボタンからデータを追加してください。
                </div>
            @endif
        </div>
    </div>

    <!-- 画像プレビューモーダル -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">画像プレビュー</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="/placeholder.svg" id="previewImage" class="img-fluid" alt="プレビュー">
                </div>
            </div>
        </div>
    </div>

    <style>
        /* テーブルコンテナ - 縦横スクロール可能 */
        .table-container {
            height: 600px;
            /* 固定高さを設定 */
            overflow: auto;
            /* 縦横両方にスクロール可能 */
            position: relative;
            /* 子要素の位置決めの基準 */
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }

        /* 固定ヘッダー */
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #e7f1ff;
            /* Bootstrap table-primaryの色 */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sticky-header th {
            position: sticky;
            top: 0;
            background-color: #e7f1ff;
            /* Bootstrap table-primaryの色 */
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

        .wide-table .col-sort {
            width: 120px;
            min-width: 120px;
        }

        .wide-table .col-status {
            width: 100px;
            min-width: 100px;
        }

        .wide-table .col-date {
            width: 180px;
            min-width: 180px;
        }

        .wide-table .col-data {
            width: 250px;
            min-width: 250px;
        }

        /* セル内のコンテンツのスタイル */
        .wide-table td.cell-content {
            padding: 0.75rem;
            word-break: break-word;
        }

        /* データ一覧のスタイル改善 */
        .table-primary th {
            font-weight: 600;
        }

        .data-value {
            font-weight: 500;
            word-break: break-word;
        }

        .text-content {
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            max-height: 150px;
            overflow-y: auto;
        }

        .array-preview-toggle {
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
        }

        .sort-handle {
            cursor: grab;
        }

        .sort-handle:active {
            cursor: grabbing;
        }

        /* 画像プレビューのスタイル */
        .image-preview img {
            transition: transform 0.2s;
            border: 2px solid transparent;
        }

        .image-preview:hover img {
            transform: scale(1.05);
            border-color: #0d6efd;
        }

        /* テキストの折り返し */
        .text-break {
            word-break: break-word;
        }

        /* 公開状態切り替えボタンのスタイル */
        .toggle-public-btn {
            font-size: 0.8rem;
            transition: all 0.3s ease;
            min-width: 80px;
        }

        .toggle-public-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* アラートメッセージのスタイル */
        .position-fixed.alert {
            z-index: 1050;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 350px;
        }
    </style>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 画像プレビュー機能
            const imageLinks = document.querySelectorAll('.image-preview');
            const previewModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
            const previewImage = document.getElementById('previewImage');

            imageLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    previewImage.src = this.href;
                    previewModal.show();
                });
            });

            // ドラッグ&ドロップでの並べ替え
            const sortableList = document.getElementById('sortable-items');
            const sortForm = document.getElementById('sort-form');
            const sortDataInput = document.getElementById('sort-data');

            if (sortableList) {
                // Sortable.jsの初期化
                new Sortable(sortableList, {
                    handle: '.sort-handle',
                    animation: 150,
                    onEnd: function () {
                        updateSortOrder();
                    }
                });

                // 表示順の更新
                function updateSortOrder() {
                    const items = sortableList.querySelectorAll('tr');
                    items.forEach((item, index) => {
                        const orderSpan = item.querySelector('.sort-order');
                        if (orderSpan) {
                            orderSpan.textContent = index + 1;
                            item.dataset.sortOrder = index + 1;
                        }
                    });

                    updateSortData();
                }

                // ソートデータを更新する関数
                function updateSortData() {
                    const items = sortableList.querySelectorAll('tr');
                    const sortData = [];

                    items.forEach((item) => {
                        const id = item.dataset.id;
                        const order = item.dataset.sortOrder;
                        if (id && order) {
                            sortData.push({ id: id, order: order });
                        }
                    });

                    sortDataInput.value = JSON.stringify(sortData);
                }

                // 上に移動ボタン
                sortableList.querySelectorAll('.move-up-btn').forEach(button => {
                    button.addEventListener('click', function () {
                        const row = this.closest('tr');
                        const prevRow = row.previousElementSibling;
                        if (prevRow) {
                            sortableList.insertBefore(row, prevRow);
                            updateSortOrder();
                        }
                    });
                });

                // 下に移動ボタン
                sortableList.querySelectorAll('.move-down-btn').forEach(button => {
                    button.addEventListener('click', function () {
                        const row = this.closest('tr');
                        const nextRow = row.nextElementSibling;
                        if (nextRow) {
                            sortableList.insertBefore(nextRow, row);
                            updateSortOrder();
                        }
                    });
                });

                // 表示順保存
                if (sortForm) {
                    sortForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        updateSortData();
                        this.submit();
                    });
                }

                // 初期化時にソートデータを設定
                updateSortData();
            }

            // 公開状態切り替え機能
            const toggleButtons = document.querySelectorAll('.toggle-public-btn');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const dataId = this.dataset.id;
                    const currentStatus = this.dataset.status;
                    const newStatus = currentStatus === '1' ? '0' : '1';

                    // ボタンを無効化して処理中を表示
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> 処理中...';

                    // Ajaxリクエスト
                    fetch(`{{ route('admin.content-data.toggle-public') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            data_id: dataId,
                            public_flg: newStatus
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // ボタンの状態を更新
                                this.dataset.status = newStatus;
                                this.classList.remove(newStatus === '1' ? 'btn-secondary' : 'btn-success');
                                this.classList.add(newStatus === '1' ? 'btn-success' : 'btn-secondary');
                                this.innerHTML = `<i class="fas ${newStatus === '1' ? 'fa-eye' : 'fa-eye-slash'} me-1"></i> ${newStatus === '1' ? '公開' : '非公開'}`;
                                this.title = newStatus === '1' ? '公開中（クリックで非公開に切り替え）' : '非公開（クリックで公開に切り替え）';

                                // 成功メッセージを表示
                                const alertDiv = document.createElement('div');
                                alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
                                alertDiv.setAttribute('role', 'alert');
                                alertDiv.innerHTML = `
                                                        <i class="fas fa-check-circle me-2"></i> ${data.message}
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
                                                    `;
                                document.body.appendChild(alertDiv);

                                // 3秒後にアラートを自動的に閉じる
                                setTimeout(() => {
                                    const bsAlert = new bootstrap.Alert(alertDiv);
                                    bsAlert.close();
                                }, 3000);
                            } else {
                                // エラーメッセージを表示
                                alert('エラー: ' + data.message);
                                // ボタンを元の状態に戻す
                                this.innerHTML = `<i class="fas ${currentStatus === '1' ? 'fa-eye' : 'fa-eye-slash'} me-1"></i> ${currentStatus === '1' ? '公開' : '非公開'}`;
                            }
                            // ボタンを再度有効化
                            this.disabled = false;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('エラーが発生しました。もう一度お試しください。');
                            // ボタンを元の状態に戻す
                            this.innerHTML = `<i class="fas ${currentStatus === '1' ? 'fa-eye' : 'fa-eye-slash'} me-1"></i> ${currentStatus === '1' ? '公開' : '非公開'}`;
                            this.disabled = false;
                        });
                });
            });
        });
    </script>
@endpush