{{-- resources/views/tasks/partials/file-list-tailwind.blade.php --}}

@forelse($files as $file)
    @can('fileView', $task)
        {{-- 論理削除済みの場合にスタイルを適用 --}}
        <li id="folder-file-item-{{ $file->id }}"
            class="flex items-center justify-between p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700/50 text-sm {{ $file->trashed() ? 'opacity-60' : '' }}">
            <div class="flex items-center min-w-0 flex-grow">
                @can('fileUpload', $task)
                    <div class="flex-shrink-0 mr-3">
                        <input type="checkbox"
                            class="soft-delete-file-checkbox rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                            data-toggle-url="{{ route('projects.tasks.files.toggleSoftDelete', [$project, $task, $file->id]) }}"
                            title="{{ $file->trashed() ? '復元する' : '論理削除する' }}" {{ $file->trashed() ? 'checked' : '' }}>
                    </div>
                @endcan

                @php $isImage = Str::startsWith($file->mime_type, 'image/'); @endphp

                @if ($isImage)
                    <img src="{{ route('projects.tasks.files.show', [$project, $task, $file]) }}" alt="{{ $file->original_name }}"
                        class="flex-shrink-0 inline-block h-10 w-10 rounded object-cover cursor-pointer preview-image mr-3 border border-gray-300 dark:border-gray-600"
                        data-full-image-url="{{ route('projects.tasks.files.show', [$project, $task, $file]) }}"
                        title="クリックしてプレビュー">
                @else
                    @php
                        $iconClass = 'fa-file-alt';
                        if (Str::contains($file->mime_type, 'pdf'))
                            $iconClass = 'fa-file-pdf';
                        elseif (Str::contains($file->mime_type, 'word'))
                            $iconClass = 'fa-file-word';
                        elseif (Str::contains($file->mime_type, 'excel'))
                            $iconClass = 'fa-file-excel';
                    @endphp
                    <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded mr-3">
                        <i class="fas {{ $iconClass }} fa-lg text-gray-400 dark:text-gray-500"></i>
                    </div>
                @endif

                {{-- 論理削除済みの場合にスタイルを適用 --}}
                <div class="truncate min-w-0 {{ $file->trashed() ? 'line-through text-gray-500 dark:text-gray-500' : '' }}">
                    @can('fileDownload', $task)
                        <a href="{{ route('projects.tasks.files.download', [$project, $task, $file]) }}"
                            class="text-blue-600 hover:underline truncate block" title="ダウンロード: {{ $file->original_name }}">
                            {{ Str::limit($file->original_name, 40) }}
                        </a>
                    @else
                        <span class="text-gray-700 dark:text-gray-300 truncate block"
                            title="{{ $file->original_name }}">{{ Str::limit($file->original_name, 40) }}</span>
                    @endcan
                    <span class="text-gray-500 text-xs block">{{ number_format($file->size / 1024, 1) }} KB |
                        {{ $file->created_at->format('Y/m/d H:i:s') }}</span>
                </div>
            </div>
            <div class="flex-shrink-0 ml-2">
                {{-- 削除ボタンは強い権限を持つユーザーのみに表示 --}}
                @can('delete', $task)
                    <button type="button"
                        class="folder-file-delete-btn text-gray-400 hover:text-red-600 dark:hover:text-red-400 p-1"
                        data-url="{{ route('projects.tasks.files.destroy', [$project, $task, $file]) }}"
                        data-file-id="{{ $file->id }}" title="完全に削除">
                        <i class="fas fa-times"></i>
                    </button>
                @endcan
            </div>
        </li>
    @endcan
@empty
    <li class="text-gray-500 dark:text-gray-400 text-sm px-2">ファイルはまだアップロードされていません。</li>
@endforelse