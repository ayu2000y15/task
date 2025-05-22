@extends('layouts.admin')

@section('title', $master->title . ' - データ登録')

@section('content')
    @include('components.admin-page-header', [
        'title' => $master->title . ' - データ登録',
        'backUrl' => route('admin.content-data.master', ['masterId' => $master->master_id])
    ])

    @component('components.admin-card', ['title' => 'データ登録フォーム', 'icon' => 'plus-circle'])
        <form action="{{ route('admin.content-data.store', ['masterId' => $master->master_id]) }}" method="POST" class="data-form" enctype="multipart/form-data">
            @csrf

            @if(isset($master->schema) && is_array($master->schema))
                @php
                    // スキーマを表示順でソート
                    $sortedSchema = collect($master->schema)->sortBy('sort_order')->values()->all();
                @endphp

                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary bg-opacity-10">
                        <h6 class="mb-0 fw-bold">基本設定</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sort_order" class="form-label fw-bold">表示順</label>
                                <input type="number" id="sort_order" name="sort_order" class="form-control" min="0" value="0">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">公開状態</label>
                                <div class="d-flex gap-4 mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                                type="radio"
                                                name="public_flg"
                                                id="public_yes"
                                                value="1"
                                                checked>
                                        <label class="form-check-label" for="public_yes">
                                            <span class="badge bg-success">公開</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input"
                                                type="radio"
                                                name="public_flg"
                                                id="public_no"
                                                value="0">
                                        <label class="form-check-label" for="public_no">
                                            <span class="badge bg-secondary">非公開</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="is_milestone_or_folder"
                                           id="is_milestone_or_folder"
                                           value="1"
                                           checked>
                                    <label class="form-check-label fw-bold" for="is_milestone_or_folder">
                                        マイルストーン/フォルダー
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">コンテンツ詳細</h6>
                    </div>
                    <div class="card-body">
                        @foreach($sortedSchema as $field)
                            @if($field['type'] == 'file' || $field['type'] == 'files')
                                <div class="row mb-4">
                                    <div class="col-md-8">
                                        <label for="{{ $field['col_name'] }}" class="form-label fs-5 mb-3">
                                            {{ $field['view_name'] }}
                                            @if($field['required_flg'] == '1')
                                                <span class="required badge bg-danger ms-2" style="color: white;">必須</span>
                                            @endif
                                        </label>
                                        <div class="file-upload-container" data-field="{{ $field['col_name'] }}">
                                            <input type="file"
                                                id="{{ $field['col_name'] }}"
                                                name="{{ $field['col_name'] }}{{ $field['type'] == 'files' ? '[]' : '' }}"
                                                class="file-upload-input"
                                                accept="image/*"
                                                {{ $field['type'] == 'files' ? 'multiple' : '' }}
                                                {{ $field['required_flg'] == '1' ? 'required' : '' }}>
                                            <div class="file-upload-area" id="upload-area-{{ $field['col_name'] }}">
                                                <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                                                <p>ここに{{ $field['type'] == 'files' ? '複数の' : '' }}ファイルをドラッグするか、クリックして選択してください</p>
                                                <p class="text-muted small">対応形式: JPG, PNG, GIF (5MB以下)</p>
                                            </div>
                                            <div class="file-preview-container mt-3" id="preview-{{ $field['col_name'] }}"></div>
                                        </div>
                                        @if($errors->has($field['col_name']))
                                            <div class="text-danger mt-1">
                                                {{ $errors->first($field['col_name']) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                @include('components.form-field', [
                                    'name' => $field['col_name'],
                                    'label' => $field['view_name'],
                                    'type' => $field['type'],
                                    'required' => $field['required_flg'] == '1',
                                    'value' => old($field['col_name']),
                                    'options' => $field['options'] ?? [],
                                    'error' => $errors->first($field['col_name'])
                                ])
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.content-data.master', ['masterId' => $master->master_id]) }}" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i> キャンセル
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> 登録
                </button>
            </div>
        </form>
    @endcomponent
@endsection



