@extends('layouts.app')

@section('title', 'フィードバック管理')

@push('styles')
<style>
    .details-row { display: none; }
    .details-row.expanded { display: table-row; }
    .editable-display:hover { background-color: #f9f9f9; cursor: pointer; }
    .dark .editable-display:hover { background-color: #3a3a3a; }
    .editing-input-area textarea, .editing-input-area input[type="text"] { min-height: 60px; width: 100%; }
    /* .details-content-grid は削除または調整される可能性があります */
    .feedback-main-row { cursor: pointer; }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ filtersOpen: false, activeFilterCount: {{ $activeFilterCount ?? 0 }}, expandedRows: {} }">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">フィードバック管理</h1>
        <div class="flex items-center space-x-2">
            @if(isset($unreadFeedbackCount) && $unreadFeedbackCount > 0)
            <span class="text-sm inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">
                <i class="fas fa-envelope mr-1"></i> 未読 {{ $unreadFeedbackCount }}件
            </span>
            @endif
            <button
                class="inline-flex items-center px-4 py-2 border rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                :class="{ 'bg-blue-100 dark:bg-blue-700 border-blue-300 dark:border-blue-600 text-blue-700 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-600': activeFilterCount > 0, 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600': activeFilterCount === 0 }"
                type="button" x-on:click="filtersOpen = !filtersOpen">
                <i class="fas fa-filter mr-2"></i> フィルター
                <span x-show="activeFilterCount > 0" class="ml-1 text-xs">({{ $activeFilterCount }})</span>
                <span x-show="filtersOpen" style="display: none;"><i class="fas fa-chevron-up ml-2 fa-xs"></i></span>
                <span x-show="!filtersOpen"><i class="fas fa-chevron-down ml-2 fa-xs"></i></span>
            </button>
            @can('viewAny', App\Models\FeedbackCategory::class)
            <x-secondary-button onclick="location.href='{{ route('admin.feedback-categories.index') }}'">
                <i class="fas fa-tags mr-2"></i> カテゴリ管理
            </x-secondary-button>
            @endcan
        </div>
    </div>

    <div x-show="filtersOpen" x-collapse class="mb-6">
        <x-feedback-filter-panel
            :action="route('admin.feedbacks.index')"
            :filters="$filters"
            :feedback-categories="$feedbackCategories"
            :status-options="$statusOptions"
        />
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="feedback-table">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    {{-- Thead (変更なし) --}}
                    <tr>
                        <th scope="col" class="w-12 px-2 py-3"></th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <a href="{{ route('admin.feedbacks.index', array_merge($filters, ['sort_field' => 'id', 'sort_direction' => ($filters['sort_field'] == 'id' && $filters['sort_direction'] == 'asc') ? 'desc' : 'asc'])) }}">
                                ID {!! $filters['sort_field'] == 'id' ? ($filters['sort_direction'] == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort"></i>' !!}
                            </a>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">送信者</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                             <a href="{{ route('admin.feedbacks.index', array_merge($filters, ['sort_field' => 'feedback_category_id', 'sort_direction' => ($filters['sort_field'] == 'feedback_category_id' && $filters['sort_direction'] == 'asc') ? 'desc' : 'asc'])) }}">
                                カテゴリ {!! $filters['sort_field'] == 'feedback_category_id' ? ($filters['sort_direction'] == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort"></i>' !!}
                            </a>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                             <a href="{{ route('admin.feedbacks.index', array_merge($filters, ['sort_field' => 'title', 'sort_direction' => ($filters['sort_field'] == 'title' && $filters['sort_direction'] == 'asc') ? 'desc' : 'asc'])) }}">
                                タイトル {!! $filters['sort_field'] == 'title' ? ($filters['sort_direction'] == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort"></i>' !!}
                            </a>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                             <a href="{{ route('admin.feedbacks.index', array_merge($filters, ['sort_field' => 'status', 'sort_direction' => ($filters['sort_field'] == 'status' && $filters['sort_direction'] == 'asc') ? 'desc' : 'asc'])) }}">
                                ステータス {!! $filters['sort_field'] == 'status' ? ($filters['sort_direction'] == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort"></i>' !!}
                            </a>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                             <a href="{{ route('admin.feedbacks.index', array_merge($filters, ['sort_field' => 'created_at', 'sort_direction' => ($filters['sort_field'] == 'created_at' && $filters['sort_direction'] == 'asc') ? 'desc' : 'asc'])) }}">
                                送信日時 {!! $filters['sort_field'] == 'created_at' ? ($filters['sort_direction'] == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort"></i>' !!}
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($feedbacks as $feedback)
                        <tr id="feedback-row-{{ $feedback->id }}" data-feedback-id="{{ $feedback->id }}"
                            class="feedback-main-row hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                            @click="expandedRows[{{ $feedback->id }}] = !expandedRows[{{ $feedback->id }}]">
                            {{-- メイン行の表示内容は変更なし --}}
                            <td class="px-2 py-3 text-center" @click.stop>
                                <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        @click="expandedRows[{{ $feedback->id }}] = !expandedRows[{{ $feedback->id }}]">
                                    <i class="fas" :class="expandedRows[{{ $feedback->id }}] ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                </button>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $feedback->id }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300" title="{{ $feedback->user_name }} ({{ $feedback->user->email }})">{{ Str::limit($feedback->user_name, 15) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-medium truncate" style="max-width:150px;">{{ $feedback->category->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 font-medium truncate" style="max-width: 200px;" title="{{ $feedback->title }}">{{ Str::limit($feedback->title, 30) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm status-cell" data-feedback-id="{{ $feedback->id }}" @click.stop>
                                <div class="flex items-center space-x-2">
                                    <select name="status" class="feedback-status-select form-select form-select-sm text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 rounded-md shadow-sm w-32" data-url="{{ route('admin.feedbacks.updateStatus', $feedback) }}" data-original-status="{{ $feedback->status }}">
                                        @foreach (\App\Models\Feedback::STATUS_OPTIONS as $value => $label)
                                            <option value="{{ $value }}" {{ $feedback->status == $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <span class="status-badge px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ \App\Models\Feedback::getStatusColorClass($feedback->status, 'badge') }}">
                                        {{ \App\Models\Feedback::STATUS_OPTIONS[$feedback->status] ?? $feedback->status }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" title="{{ $feedback->created_at ? $feedback->created_at->format('Y/m/d H:i:s') : '' }}">
                                @if($feedback->created_at)
                                    {{ $feedback->created_at->diffForHumans() }}
                                    <span class="block text-xs text-gray-400 dark:text-gray-500"> ({{ $feedback->created_at->format('Y/m/d H:i') }})</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        {{-- ★★★ 詳細行のレイアウト変更 ★★★ --}}
                        <tr class="details-row bg-gray-50 dark:bg-gray-700/30" :class="{ 'expanded': expandedRows[{{ $feedback->id }}] }">
                            <td colspan="7" class="px-4 py-3">
                                <div class="p-2 space-y-4"> {{-- space-y-3 から space-y-4 に変更 --}}
                                    {{-- 1. 送信者Email、送信者Tel --}}
                                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                                        <div>
                                            <strong class="block text-xs text-gray-500 dark:text-gray-400">送信者 Email:</strong>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $feedback->email ?: '-' }}</span>
                                        </div>
                                        <div>
                                            <strong class="block text-xs text-gray-500 dark:text-gray-400">送信者 電話番号:</strong>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $feedback->phone ?: '-' }}</span>
                                        </div>
                                    </div>

                                    {{-- 2. 担当者、管理者メモ --}}
                                    <div class="grid sm:grid-cols-2 gap-x-6 gap-y-4">
                                        <div>
                                            <strong class="block text-xs text-gray-500 dark:text-gray-400">担当者:</strong>
                                            <div class="text-sm text-gray-700 dark:text-gray-300 mt-1 assignee-cell" data-feedback-id="{{ $feedback->id }}" data-assignee-url="{{ route('admin.feedbacks.updateAssignee', $feedback) }}">
                                                <div class="assignee-display editable-display whitespace-pre-wrap break-words p-1 border border-transparent hover:border-gray-300 dark:hover:border-gray-600 rounded min-h-[20px]" title="クリックして編集" data-full-assignee="{{ $feedback->assignee_text ?? '' }}">{{ $feedback->assignee_text ?: '-' }}</div>
                                                <div class="assignee-editing editing-input-area" style="display:none;">
                                                    <input type="text" class="form-input block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200">
                                                    <div class="mt-1 text-xs">
                                                        <button class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 assignee-save-btn">保存</button>
                                                        <button class="text-gray-500 hover:text-gray-700 ml-2 assignee-cancel-btn">キャンセル</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div>
                                            <strong class="block text-xs text-gray-500 dark:text-gray-400">メモ:</strong>
                                            <div class="text-sm text-gray-700 dark:text-gray-300 mt-1 memo-cell" data-feedback-id="{{ $feedback->id }}" data-memo-url="{{ route('admin.feedbacks.updateMemo', $feedback) }}">
                                                <div class="memo-display editable-display whitespace-pre-wrap break-words p-1 border border-transparent hover:border-gray-300 dark:hover:border-gray-600 rounded min-h-[20px]" title="クリックして編集" data-full-memo="{{ $feedback->admin_memo ?? '' }}">{{ $feedback->admin_memo ?: '-' }}</div>
                                                <div class="memo-editing editing-input-area" style="display:none;">
                                                    <textarea class="form-textarea block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200"></textarea>
                                                    <div class="mt-1 text-xs">
                                                        <button class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 memo-save-btn">保存</button>
                                                        <button class="text-gray-500 hover:text-gray-700 ml-2 memo-cancel-btn">キャンセル</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    {{-- 3. フィードバック内容 --}}
                                    <div>
                                        <strong class="block text-xs text-gray-500 dark:text-gray-400">フィードバック内容:</strong>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-words p-1 bg-white dark:bg-gray-700 rounded border dark:border-gray-600">{{ $feedback->content }}</p>
                                    </div>

                                    {{-- 添付ファイル (変更なし) --}}
                                    @if($feedback->files->isNotEmpty())
                                    <div>
                                        <strong class="block text-xs text-gray-500 dark:text-gray-400 mb-1">添付ファイル:</strong>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                            @foreach($feedback->files as $file)
                                            <a href="{{ Storage::url($file->file_path) }}" target="_blank"
                                               class="block relative group rounded-md overflow-hidden border dark:border-gray-600 aspect-square hover:shadow-lg transition-shadow preview-image"
                                               data-full-image-url="{{ Storage::url($file->file_path) }}"
                                               title="{{ $file->original_name }} ({{ \Illuminate\Support\Number::fileSize($file->size) }})">
                                                @if(Str::startsWith($file->mime_type, 'image/'))
                                                    <img src="{{ Storage::url($file->file_path) }}" alt="{{ $file->original_name }}" class="w-full h-full object-cover" data-full-image-url="{{ Storage::url($file->file_path) }}">
                                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 flex items-center justify-center transition-opacity">
                                                        <i class="fas fa-search-plus text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                                    </div>
                                                @else
                                                    <div class="w-full h-full flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-700 p-2 text-center">
                                                        <i class="fas fa-file-alt text-3xl text-gray-400 dark:text-gray-500 mb-1"></i>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $file->original_name }}</p>
                                                        <span class="text-xs text-blue-500 hover:underline">ダウンロード</span>
                                                    </div>
                                                @endif
                                            </a>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    {{-- 4. 最終更新日時、完了日 --}}
                                    <div class="flex flex-wrap gap-x-6 gap-y-2 border-t dark:border-gray-600 pt-3 mt-3">
                                        <div>
                                            <strong class="block text-xs text-gray-500 dark:text-gray-400">最終更新日時:</strong>
                                            <span class="text-sm text-gray-700 dark:text-gray-300 feedback-updated-at-cell">{{ $feedback->updated_at ? $feedback->updated_at->format('Y/m/d H:i') : '-' }}</span>
                                        </div>
                                        <div>
                                            <strong class="block text-xs text-gray-500 dark:text-gray-400">完了日:</strong>
                                            <span class="text-sm text-gray-700 dark:text-gray-300 feedback-completed-at-display">{{ $feedback->completed_at ? $feedback->completed_at->format('Y/m/d H:i') : '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-inbox fa-3x text-gray-400 dark:text-gray-500 mb-3"></i>
                                    <span>該当するフィードバックはありません。</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($feedbacks->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $feedbacks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection