<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0 fw-bold">
            @if(isset($icon))
                <i class="fas fa-{{ $icon }} me-2"></i>
            @endif
            {{ $title }}
        </h5>
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
    @if(isset($footer))
        <div class="card-footer bg-transparent">
            {{ $footer }}
        </div>
    @endif
</div>