@forelse ($files as $file)
    <li class="list-group-item d-flex justify-content-between align-items-center" id="file-item-{{ $file->id }}">
        <div>
            <i class="fas fa-file-alt me-2"></i>
            <a
                href="{{ route('projects.tasks.files.download', [$file->task->project, $file->task, $file]) }}">{{ $file->original_name }}</a>
            <small class="text-muted ms-2">({{ round($file->size / 1024, 2) }} KB)</small>
        </div>
        <button class="btn btn-sm btn-outline-danger delete-file-btn" data-file-id="{{ $file->id }}"
            data-url="{{ route('projects.tasks.files.destroy', [$file->task->project, $file->task, $file]) }}">
            <i class="fas fa-trash"></i>
        </button>
    </li>
@empty
    <li class="list-group-item text-center text-muted">アップロードされたファイルはありません。</li>
@endforelse