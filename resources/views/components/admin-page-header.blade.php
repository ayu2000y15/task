<div class="d-flex justify-content-between align-items-center page-title mb-4">
    <h2>{{ $title }}</h2>
    <div>
        @if(isset($backUrl))
            <a href="{{ $backUrl }}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> 戻る
            </a>
        @endif
    </div>
</div>