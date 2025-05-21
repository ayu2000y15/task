@extends('layouts.admin')

@section('title', 'HP画像管理')

@section('content')
    @if (session('errors'))
        @foreach (session('errors') as $error)
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                {{ $error[1] }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
            </div>
        @endforeach
    @endif
    <div class="d-flex justify-content-between align-items-center page-title mb-4">
        <h2>HP画像管理</h2>
        <button type="button" class="btn btn-primary" id="newEntryBtn">
            <i class="fas fa-plus me-1"></i> 新規登録
        </button>
    </div>

    <!-- 新規画像アップロードフォーム -->
    @component('components.admin-card', ['title' => '新規画像アップロード'])
    <div id="dataForm" style="display: none;">
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn-close" id="cancelBtn" aria-label="閉じる"></button>
        </div>
        <form action="{{ route('admin.photo.store') }}" method="POST" enctype="multipart/form-data" class="data-form">
            @csrf

            <div class="row mb-4">
                <div class="col-md-8">
                    <label for="IMAGE" class="form-label fs-5 mb-3">
                        画像ファイル
                        <span class="required badge bg-danger ms-2" style="color: white;">必須</span>
                    </label>
                    <div class="file-upload-container" data-field="IMAGE">
                        <input type="file" id="IMAGE" name="IMAGE[]" class="file-upload-input" accept="image/*" multiple
                            required>
                        <div class="file-upload-area" id="upload-area-IMAGE">
                            <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                            <p>ここに複数のファイルをドラッグするか、クリックして選択してください</p>
                            <p class="text-muted small">対応形式: JPG, PNG, GIF (5MB以下)</p>
                        </div>
                        <div class="file-preview-container mt-3" id="preview-IMAGE"></div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="alt" class="form-label fs-5 mb-3">タイトル</label>
                    <input type="text" id="alt" name="alt" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="view_flg" class="form-label fs-5 mb-3">
                        表示先
                        <span class="required badge bg-danger ms-2" style="color: white;">必須</span>
                    </label>
                    <select id="view_flg" name="view_flg" class="form-select" required>
                        <option value="">選択してください</option>
                        @foreach($viewFlg as $select)
                            <option value="{{ $select['view_flg'] }}">{{ $select['comment'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="priority" class="form-label fs-5 mb-3">優先度</label>
                    <input type="number" id="priority" name="priority" class="form-control">
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-upload me-1"></i> アップロード
                </button>
            </div>
        </form>
    </div>
    @endcomponent

    <!-- 画像一覧 -->
    @component('components.admin-card', ['title' => 'アップロード済み画像一覧', 'icon' => 'images'])
    <div class="table-container">
        <table class="table table-striped table-hover wide-table">
            <thead class="table-light sticky-header">
                <tr>
                    <th class="col-actions">操作</th>
                    <th>プレビュー</th>
                    <th>表示先</th>
                    <th>タイトル</th>
                    <th>優先度</th>
                    <th class="col-date">アップロード日時</th>
                </tr>
            </thead>
            <tbody>
                @foreach($photos as $photo)
                    <tr>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.photo.edit', ['image_id' => $photo->image_id]) }}"
                                    class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> 編集
                                </a>
                                <form action="{{ route('admin.photo.delete') }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="image_id" value="{{$photo->image_id}}">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('本当に削除しますか？')">
                                        <i class="fas fa-trash"></i> 削除
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <img src="{{ asset($photo->file_path . $photo->file_name) }}" alt="{{ $photo->alt }}"
                                class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                        </td>
                        <td>{{ $photo->v_comment }}</td>
                        <td>{{ $photo->alt }}</td>
                        <td>{{ $photo->priority }}</td>
                        <td>{{ $photo->created_at}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endcomponent
@endsection

@push('scripts')
    <script>
        // 最小限のJavaScriptだけをインラインで記述
        document.addEventListener('DOMContentLoaded', function () {
            // 新規登録ボタンのイベントリスナー
            const newEntryBtn = document.getElementById('newEntryBtn');
            const dataForm = document.getElementById('dataForm');
            const cancelBtn = document.getElementById('cancelBtn');

            // 新規登録ボタン
            if (newEntryBtn && dataForm) {
                newEntryBtn.addEventListener('click', function () {
                    dataForm.style.display = 'block';
                    dataForm.scrollIntoView({ behavior: 'smooth' });
                });
            }

            // キャンセルボタン
            if (cancelBtn && dataForm) {
                cancelBtn.addEventListener('click', function () {
                    dataForm.style.display = 'none';
                });
            }
        });
    </script>
@endpush