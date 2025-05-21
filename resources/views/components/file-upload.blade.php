@extends('layouts.admin')

@section('title', 'HP画像管理')

@section('content')
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

                @include('components.form-field', [
                    'name' => 'IMAGE',
                    'label' => '画像ファイル',
                    'type' => 'files',
                    'required' => true
                ])

                <div class="row mb-3">
                    <div class="col-md-6">
                        @include('components.form-field', [
                            'name' => 'alt',
                            'label' => 'タイトル',
                            'type' => 'text'
                        ])
                    </div>
                    <div class="col-md-6">
                        @include('components.form-field', [
                            'name' => 'view_flg',
                            'label' => '表示先',
                            'type' => 'select',
                            'required' => true,
                            'options' => $viewFlg->map(function($select) {
                                return [
                                    'value' => $select['view_flg'],
                                    'label' => $select['comment']
                                ];
                            })->toArray()
                        ])
                    </div>
                </div>

                @include('components.form-field', [
                    'name' => 'priority',
                    'label' => '優先度',
                    'type' => 'number'
                ])

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
        @php
            $columns = [
                ['label' => '操作', 'class' => 'col-actions'],
                ['label' => 'プレビュー'],
                ['label' => '表示先'],
                ['label' => 'タイトル'],
                ['label' => '優先度'],
                ['label' => 'アップロード日時', 'class' => 'col-date']
            ];
        @endphp

        @component('components.data-table', ['columns' => $columns, 'headerClass' => 'table-light'])
            @foreach($photos as $photo)
                <tr>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="{{ route('admin.photo.edit', ['image_id' => $photo->image_id]) }}"
                                class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> 編集
                            </a>
                            <form action="{{ route('admin.photo.delete') }}" method="POST"
                                style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="image_id" value="{{$photo->image_id}}">
                                <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('本当に削除しますか？')">
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
        @endcomponent
    @endcomponent
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 新規登録ボタンのイベントリスナー
            const newEntryBtn = document.getElementById('newEntryBtn');
            const dataForm = document.getElementById('dataForm');
            const cancelBtn = document.getElementById('cancelBtn');

            // 新規登録ボタン
            newEntryBtn.addEventListener('click', function () {
                dataForm.style.display = 'block';
                dataForm.scrollIntoView({ behavior: 'smooth' });
            });

            // キャンセルボタン
            cancelBtn.addEventListener('click', function () {
                dataForm.style.display = 'none';
            });
        });
    </script>
@endpush

