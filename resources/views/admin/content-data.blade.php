@extends('layouts.admin')

@section('title', 'データ管理')

@section('content')
    @include('components.admin-page-header', ['title' => 'データ管理'])

    <div class="row">
        @foreach ($masters as $master)
            @php
                $dataCount = $master->contentData->where('delete_flg', '0')->count();
                $publicCount = $master->contentData->where('delete_flg', '0')->where('public_flg', '1')->count();
            @endphp
            <div class="col-md-6 col-lg-4 mb-4">
                @component('components.admin-card', ['title' => $master->title, 'icon' => 'database'])
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">
                        <i class="fas fa-table me-1"></i>マスターID: {{ $master->master_id }}
                    </span>
                    <div>
                        <span class="badge bg-primary">全体: {{ $dataCount }}件</span><br>
                        <span class="badge bg-success">公開: {{ $publicCount }}件</span>
                    </div>
                </div>

                @slot('footer')
                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.content-data.master', ['masterId' => $master->master_id]) }}"
                        class="btn btn-outline-primary">
                        <i class="fas fa-list me-1"></i> データ一覧
                    </a>
                    <a href="{{ route('admin.content-data.create', ['masterId' => $master->master_id]) }}"
                        class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> 新規登録
                    </a>
                </div>
                @endslot
                @endcomponent
            </div>
        @endforeach
    </div>
@endsection