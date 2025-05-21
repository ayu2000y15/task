@extends('layouts.admin')

@section('title', 'HP画像編集')

@section('content')
    @include('components.admin-page-header', [
        'title' => 'HP画像編集',
        'backUrl' => route('admin.photo')
    ])

    @component('components.admin-card', ['title' => '画像編集フォーム', 'icon' => 'edit'])
        <form action="{{ route('admin.photo.update') }}" method="POST" class="data-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="image_id" value="{{ $photo->image_id }}">

            <!-- 画像アップロード機能を削除し、現在の画像を表示するだけにします -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <label class="form-label">現在の画像</label>
                    <div class="text-center mb-3">
                        <img src="{{ asset($photo->file_path . $photo->file_name) }}" alt="{{ $photo->alt }}"
                            class="img-thumbnail" style="max-width: 300px;">
                        <div class="mt-2 text-muted">
                            <small>{{ $photo->file_name }}</small>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>画像ファイルは変更できません。変更が必要な場合は、この画像を削除して新しく登録してください。
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    @include('components.form-field', [
                        'name' => 'alt',
                        'label' => 'タイトル',
                        'type' => 'text',
                        'value' => $photo->alt
                    ])
                </div>

                <div class="col-md-6">
                    @include('components.form-field', [
                        'name' => 'view_flg',
                        'label' => '表示先',
                        'type' => 'select',
                        'required' => true,
                        'value' => $photo->view_flg,
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
                'type' => 'number',
                'value' => $photo->priority
            ])

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.photo') }}" class="btn btn-secondary">キャンセル</a>
                <button type="submit" class="btn btn-primary">更新</button>
            </div>
        </form>
    @endcomponent
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.js') }}"></script>
@endpush

