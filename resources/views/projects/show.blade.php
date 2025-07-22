@extends('layouts.app')

@section('title', '案件詳細 - ' . $project->title)
@push('styles')
    {{-- Dropzoneのスタイルを追加 --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <style>
        .dropzone-custom-style {
            @apply border-2 border-dashed border-blue-500 rounded-md p-4 flex flex-wrap gap-3 min-h-[150px] bg-gray-50 dark:bg-gray-700/50;
        }
        .dropzone-custom-style .dz-message {
            @apply text-gray-600 dark:text-gray-400 font-medium w-full text-center self-center;
        }
        .dropzone-custom-style .dz-message p {
            @apply mb-2;
        }
        .dropzone-custom-style .dz-button-bootstrap {
            @apply inline-flex items-center px-3 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500;
        }
        .dropzone-custom-style .dz-preview {
            @apply w-32 h-auto m-1 bg-transparent border border-gray-300 dark:border-gray-600 flex flex-col items-center relative rounded-lg overflow-hidden;
        }
        .dropzone-custom-style .dz-image {
            @apply w-20 h-20 flex border border-gray-300 dark:border-gray-600 items-center justify-center overflow-hidden relative z-10;
        }
        .dropzone-custom-style .dz-image img {
            @apply max-w-full max-h-full object-contain bg-transparent;
        }
        .dropzone-custom-style .dz-details {
            @apply block text-center w-full relative p-1;
        }
        .dropzone-custom-style .dz-filename {
            @apply block text-xs text-gray-700 dark:text-gray-200 break-words leading-tight mt-1;
        }
        .dropzone-custom-style .dz-filename span {
            @apply bg-transparent;
        }
        .dropzone-custom-style .dz-size {
            @apply text-[0.65em] text-gray-500 dark:text-gray-400 mt-0.5 bg-transparent;
        }
        .dropzone-custom-style .dz-progress,
        .dropzone-custom-style .dz-error-message,
        .dropzone-custom-style .dz-success-mark,
        .dropzone-custom-style .dz-error-mark {
            @apply hidden;
        }
        .dropzone-custom-style .dz-remove {
            @apply absolute top-1 right-1 bg-red-600/80 hover:bg-red-700/90 text-white rounded-full w-[18px] h-[18px] text-xs leading-[18px] text-center font-bold no-underline cursor-pointer opacity-100 z-30;
        }

        /* ★ ドラッグ中の行のスタイル */
        .sortable-ghost {
            background-color: #dbeafe; /* bg-blue-100 */
            opacity: 0.5;
        }
        /* ★ ドラッグハンドルのカーソル */
        .drag-handle {
            cursor: move;
        }
        /* ★ ヘッダーソートのカーソル */
        .sortable-header {
            cursor: pointer;
        }
        .sortable-header:hover {
            background-color: #f3f4f6; /* bg-gray-100 */
        }
        .dark .sortable-header:hover {
             background-color: #374151; /* dark:bg-gray-700 */
        }
    </style>
@endpush
@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
     id="project-show-main-container"
     data-project-id="{{ $project->id }}"
     @keydown.escape.window="closeModal"
     @keydown.arrow-left.window="prevImage"
     @keydown.arrow-right.window="nextImage"
     x-data="{
        isOpen: false,
        images: [],
        currentIndex: 0,

        get currentImage() {
            return this.images[this.currentIndex] || { src: '', alt: '' };
        },

        openModalFromClick(event) {
            const button = event.currentTarget;
            const galleryContainer = button.closest('[data-gallery]');
            if (!galleryContainer) return;

            try {
                const galleryData = JSON.parse(galleryContainer.dataset.gallery);
                const index = parseInt(button.dataset.index, 10);
                this.openModal(index, galleryData);
            } catch (e) {
                console.error('Failed to parse gallery data:', e);
            }
        },

        openModal(index, gallery) {
            if (!gallery || gallery.length === 0) return;

            const cleanedGallery = gallery.map(image => {
                const cleanedSrc = image.src.replace(/\\/g, '/').replace(/¥/g, '/');
                return { src: cleanedSrc, alt: image.alt };
            });

            this.currentIndex = index;
            this.images = cleanedGallery;
            this.isOpen = true;
        },

        closeModal() {
            this.isOpen = false;
        },

        nextImage() {
            if (!this.isOpen || this.images.length < 2) return;
            this.currentIndex = (this.currentIndex + 1) % this.images.length;
        },

        prevImage() {
            if (!this.isOpen || this.images.length < 2) return;
            this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
        }
     }"
>

    {{-- ヘッダーセクション --}}
    <div class="mb-6 p-4 sm:p-6 rounded-lg shadow-lg text-white" style="background: linear-gradient(135deg, {{ $project->color ?? '#6c757d' }}DD, {{ $project->color ?? '#6c757d' }}FF); border-left: 4px solid {{ $project->color ?? '#6c757d' }};">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div class="flex items-center mb-3 sm:mb-0">
                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full flex items-center justify-center text-xl sm:text-2xl font-bold mr-3 sm:mr-4 flex-shrink-0" style="background-color: rgba(255,255,255,0.2);">
                    <i class="fas fa-tshirt"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold truncate" title="{{ $project->title }}">{{ $project->title }}</h1>
                    @if($project->series_title)
                        <p class="text-sm opacity-90">{{ $project->series_title }}</p>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-2 mt-3 sm:mt-0 self-start sm:self-center">
                @can('create', App\Models\Task::class)
                    <button type="button" x-data @click="$dispatch('open-modal', 'batch-task-modal')" class="inline-flex items-center px-3 py-2 bg-white/20 hover:bg-white/30 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                        <i class="fas fa-stream mr-1"></i> 一括登録
                    </button>
                @endcan
                 @can('create', App\Models\Task::class)
                    <a href="{{ route('projects.tasks.create', $project) }}" class="inline-flex items-center px-3 py-2 bg-white/20 hover:bg-white/30 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                        <i class="fas fa-plus mr-1"></i> 工程追加
                    </a>
                @endcan
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center px-3 py-2 bg-white/20 hover:bg-white/30 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                        <i class="fas fa-edit mr-1"></i> 案件編集
                    </a>
                @endcan
                <a href="{{ route('gantt.index', ['project_id' => $project->id]) }}"
                    class="inline-flex items-center px-3 py-2 bg-white/20 hover:bg-white/30 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                    <i class="fas fa-chart-gantt mr-1"></i> ガント
                </a>
            </div>
        </div>
    </div>


    {{-- ▼▼▼ コスト進捗バーと警告 ここから ▼▼▼ --}}
    @can('manageCosts', $project)
    <div x-data="{ expanded: true }" class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg">
        <div @click="expanded = !expanded" class="p-4 flex justify-between items-center cursor-pointer border-b border-gray-200 dark:border-gray-700">
            <h6 class="text-lg font-semibold text-gray-700 dark:text-gray-200 flex items-center"><i class="fas fa-coins mr-2 text-gray-600 dark:text-gray-300"></i>コスト進捗</h6>
            <button type="button" class="text-gray-500 dark:text-gray-400"><i class="fas" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i></button>
        </div>

        <div x-show="expanded" x-collapse class="p-4 space-y-6">
            @php
                // 計算の基準を budget から target_cost (目標コスト全体) に変更
                $overall_target_cost = $project->target_cost ?? 0;
                $total_actual_cost = $actual_material_cost + $actual_labor_cost;
                $budget = $project->budget ?? 0; // 予算は参考情報として保持
            @endphp

            {{-- 1. 全体サマリー --}}
            <div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-2">全体コスト</h4>
                @php
                    // パーセンテージ計算の分母を $overall_target_cost に変更
                    $actual_vs_target_percentage = ($overall_target_cost > 0) ? ($total_actual_cost / $overall_target_cost) * 100 : 0;
                    $target_vs_overall_target_percentage = ($overall_target_cost > 0) ? ($total_target_cost / $overall_target_cost) * 100 : 0;
                    $display_max_percentage_overall = max(100, $actual_vs_target_percentage, $target_vs_overall_target_percentage) * 1.2;

                    // 表示幅の計算の分母を $overall_target_cost に変更
                    $material_display_width = ($overall_target_cost > 0) ? (($actual_material_cost / $overall_target_cost) * 100 / $display_max_percentage_overall) * 100 : 0;
                    $labor_display_width = ($overall_target_cost > 0) ? (($actual_labor_cost / $overall_target_cost) * 100 / $display_max_percentage_overall) * 100 : 0;

                    // バーの超過判定の基準を $overall_target_cost に変更
                    $overall_bar_color_material = ($total_actual_cost > $overall_target_cost && $overall_target_cost > 0) ? 'bg-yellow-500' : 'bg-green-500';
                    $overall_bar_color_labor = ($total_actual_cost > $overall_target_cost && $overall_target_cost > 0) ? 'bg-orange-500' : 'bg-purple-500';
                @endphp

                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 relative flex items-center mt-6">
                    <div class="flex h-full w-full">
                        <div class="{{ $overall_bar_color_material }} h-full flex items-center justify-center rounded-l-full" style="width: {{ $material_display_width }}%" title="実績材料費: {{number_format($actual_material_cost)}}円"></div>
                        <div class="{{ $overall_bar_color_labor }} h-full flex items-center justify-center rounded-r-full" style="width: {{ $labor_display_width }}%" title="実績人件費: {{number_format($actual_labor_cost)}}円"></div>
                    </div>

                    @if($overall_target_cost > 0)
                        {{-- 目標合計(内訳)マーカー --}}
                        @if($total_target_cost > 0)
                            {{-- マーカー位置計算の分母を $overall_target_cost に変更 --}}
                            @php $target_marker_position = (($total_target_cost / $overall_target_cost) * 100 / $display_max_percentage_overall) * 100; @endphp
                            <div class="absolute top-0 h-full border-r-2 border-dashed border-indigo-500" style="left: {{ $target_marker_position }}%;" title="目標合計(内訳): {{ number_format($total_target_cost) }}円">
                                <span class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 text-xs text-indigo-600 dark:text-indigo-300 whitespace-nowrap bg-white dark:bg-gray-800 px-1 rounded shadow flex items-center">
                                    目標合計: ¥{{ number_format($total_target_cost, 0) }}
                                    {{-- 超過比較の対象を $overall_target_cost に変更 --}}
                                    @if($total_target_cost > $overall_target_cost)
                                        <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="目標合計(内訳)が予算を超過しています。"></i>
                                    @endif
                                </span>
                            </div>
                        @endif

                        {{-- 予算 (100%) マーカー (旧: 予算マーカー) --}}
                        @php $target_cost_marker_position = (100 / $display_max_percentage_overall) * 100; @endphp
                        <div class="absolute top-0 h-full border-r-2 border-dashed border-red-500" style="left: {{ $target_cost_marker_position }}%;" title="予算: {{ number_format($overall_target_cost) }}円">
                            <span class="absolute top-full mt-1 left-1/2 -translate-x-1/2 text-xs text-red-600 dark:text-red-300 whitespace-nowrap bg-white dark:bg-gray-800 px-1 rounded shadow">予算</span>
                        </div>
                    @endif
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between text-sm mt-5">
                    <span class="text-gray-600 dark:text-gray-300">実績合計: <strong class="font-bold">¥{{ number_format($total_actual_cost, 0) }}</strong>
                        {{-- 超過比較の対象とメッセージを target_cost ベースに変更 --}}
                        @if($total_actual_cost > $overall_target_cost && $overall_target_cost > 0)
                            <i class="fas fa-exclamation-triangle text-red-500 ml-2" title="予算を{{ number_format($total_actual_cost - $overall_target_cost) }}円超過しています。"></i>
                            <span class="text-red-500 text-xs">予算を{{ number_format($total_actual_cost - $overall_target_cost) }}円超過しています。</span>
                        @endif
                    </span>
                    <div class="sm:text-right">
                        <span class="text-gray-500 dark:text-gray-400 ml-2">予算: ¥{{ number_format($overall_target_cost, 0) }}</span>
                    </div>
                </div>
            </div>

            <hr class="dark:border-gray-700">

            <div class="space-y-8">
                {{-- 2. 材料費 --}}
                <div x-data="{ breakdownOpen: false }">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-200">材料費</h4>
                        @if($material_cost_breakdown->isNotEmpty())
                            <button type="button" @click="breakdownOpen = !breakdownOpen" class="text-xs text-blue-600 hover:underline">
                                <span x-show="!breakdownOpen">内訳を表示</span><span x-show="breakdownOpen" style="display: none;">内訳を隠す</span>
                            </button>
                        @endif
                    </div>
                    @php $material_target = $project->target_material_cost ?? 0; @endphp
                    @php
                        $material_progress = ($material_target > 0) ? ($actual_material_cost / $material_target) * 100 : 0;
                        $material_display_max = max(100, $material_progress) * 1.2;
                        $material_display_width = ($material_display_max > 0) ? ($material_progress / $material_display_max) * 100 : 0;
                        $material_target_marker = ($material_display_max > 0) ? (100 / $material_display_max) * 100 : 0;
                        $material_bar_color = ($actual_material_cost > $material_target && $material_target > 0) ? 'bg-yellow-500' : 'bg-green-500';
                    @endphp
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 relative flex items-center mt-6">
                        <div class="{{ $material_bar_color }} h-4 rounded-full text-center text-white text-xs font-semibold leading-4 flex items-center justify-center" style="width: {{ $material_display_width }}%">
                            @if($material_progress > 10) {{ number_format($material_progress, 0) }}% @endif
                        </div>
                        <div class="absolute top-0 h-full border-r-2 border-dashed border-gray-500" style="left: {{$material_target_marker}}%;" title="目標: {{ number_format($material_target) }}円">
                            <span class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap bg-white dark:bg-gray-800 px-1 rounded shadow flex items-center">
                                目標
                                @if($material_target > $budget && $budget > 0)
                                    <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="目標が総予算を超過しています。"></i>
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:justify-between text-sm mt-1">
                        <span class="text-gray-600 dark:text-gray-300">
                            実績: <strong class="font-bold">¥{{ number_format($actual_material_cost, 0) }}</strong>
                            @if($actual_material_cost > $material_target && $material_target > 0)
                                <i class="fas fa-exclamation-circle text-yellow-500 ml-2" title="目標材料費を{{ number_format($actual_material_cost - $material_target) }}円超過しています。"></i>
                                <span class="text-yellow-700 text-xs">目標材料費を{{ number_format($actual_material_cost - $material_target) }}円超過しています。</span>
                            @endif
                        </span>
                        <span class="text-gray-500 dark:text-gray-400 sm:text-right">目標: ¥{{ number_format($material_target, 0) }}</span>
                    </div>
                    <div x-show="breakdownOpen" x-collapse class="mt-3 pt-3 border-t dark:border-gray-600">
                        <div class="max-h-60 overflow-y-auto">
                            <div class="sm:hidden space-y-3">
                                @forelse($material_cost_breakdown as $cost)
                                    <div class="p-2 border rounded-md dark:border-gray-600">
                                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $cost->name }}</p>
                                        <div class="mt-1 flex justify-between items-center text-xs">
                                            <span class="text-gray-500 dark:text-gray-400">{{ $cost->character->name ?? '案件全体' }} / {{ $cost->type ?? '-' }}</span>
                                            <span class="font-bold text-gray-700 dark:text-gray-200">¥{{ number_format($cost->amount) }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="px-2 py-4 text-center text-xs text-gray-500">登録された材料費はありません。</p>
                                @endforelse
                            </div>
                            <table class="min-w-full text-sm hidden sm:table">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-400">キャラクター</th>
                                        <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-400">種別</th>
                                        <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-400">内容</th>
                                        <th class="px-2 py-1 text-right text-xs font-medium text-gray-500 dark:text-gray-400">金額</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse($material_cost_breakdown as $cost)
                                        <tr>
                                            <td class="px-2 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $cost->character->name ?? '案件全体' }}</td>
                                            <td class="px-2 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $cost->type ?? '-' }}</td>
                                            <td class="px-2 py-1.5 text-gray-600 dark:text-gray-300 truncate" title="{{ $cost->item_description }}">{{ $cost->item_description }}</td>
                                            <td class="px-2 py-1.5 text-right font-semibold text-gray-800 dark:text-gray-200">¥{{ number_format($cost->amount) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-2 py-4 text-center text-xs text-gray-500">登録された材料費はありません。</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <hr class="dark:border-gray-600">

                {{-- 3. 人件費 --}}
                <div x-data="{ breakdownOpen: false }">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-200">人件費</h4>
                        @if(!empty($labor_cost_breakdown))
                            <button type="button" @click="breakdownOpen = !breakdownOpen" class="text-xs text-blue-600 hover:underline">
                                <span x-show="!breakdownOpen">内訳を表示</span><span x-show="breakdownOpen" style="display: none;">内訳を隠す</span>
                            </button>
                        @endif
                    </div>

                    @php
                        $labor_progress = ($target_labor_cost > 0) ? ($actual_labor_cost / $target_labor_cost) * 100 : 0;
                        $labor_display_max = max(100, $labor_progress) * 1.2;
                        $labor_display_width = ($labor_display_max > 0) ? ($labor_progress / $labor_display_max) * 100 : 0;
                        $labor_target_marker = ($labor_display_max > 0) ? (100 / $labor_display_max) * 100 : 0;
                        $labor_bar_color = ($actual_labor_cost > $target_labor_cost && $target_labor_cost > 0) ? 'bg-orange-500' : 'bg-purple-500';
                    @endphp
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 relative flex items-center mt-6">
                        <div class="{{ $labor_bar_color }} h-4 rounded-full text-center text-white text-xs font-semibold leading-4 flex items-center justify-center" style="width: {{ $labor_display_width }}%">
                             @if($labor_progress > 10) {{ number_format($labor_progress, 0) }}% @endif
                        </div>
                        <div class="absolute top-0 h-full border-r-2 border-dashed border-gray-500" style="left: {{$labor_target_marker}}%;" title="目標: {{ number_format($target_labor_cost) }}円">
                            <span class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap bg-white dark:bg-gray-800 px-1 rounded shadow flex items-center">
                               目標
                               @if($target_labor_cost > $budget && $budget > 0)
                                   <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="目標が総予算を超過しています。"></i>
                               @endif
                           </span>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:justify-between text-sm mt-1">
                        <span class="text-gray-600 dark:text-gray-300">
                            実績: <strong class="font-bold">¥{{ number_format($actual_labor_cost, 0) }}</strong>
                            @if($actual_labor_cost > $target_labor_cost && $target_labor_cost > 0)
                                <i class="fas fa-exclamation-circle text-orange-500 ml-2" title="目標人件費を{{ number_format($actual_labor_cost - $target_labor_cost) }}円超過しています。"></i>
                                <span class="text-orange-700 text-xs">目標人件費を{{ number_format($actual_labor_cost - $target_labor_cost) }}円超過しています。</span>
                            @endif
                        </span>
                        <span class="text-gray-500 dark:text-gray-400 sm:text-right">目標: ¥{{ number_format($target_labor_cost, 0) }}</span>
                    </div>
                    <div x-show="breakdownOpen" x-collapse class="mt-3 pt-3 border-t dark:border-gray-600">
                        <div class="max-h-60 overflow-y-auto">
                            <div class="sm:hidden space-y-3">
                                 @forelse($labor_cost_breakdown as $item)
                                    <div class="p-2 border rounded-md dark:border-gray-600">
                                        <p class="font-semibold text-gray-800 dark:text-gray-200 truncate" title="{{ $item['task_name'] }}">{{ $item['task_name'] }}</p>
                                        <div class="mt-1 flex justify-between items-center text-xs">
                                            <span class="text-gray-500 dark:text-gray-400">予定: {{ $item['estimated_duration_seconds'] > 0 ? gmdate('H:i:s', $item['estimated_duration_seconds']) : '-' }}</span>
                                            <span class="font-bold {{ $item['actual_work_seconds'] > $item['estimated_duration_seconds'] && $item['estimated_duration_seconds'] > 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-200' }}">
                                                実績: {{ $item['actual_work_seconds'] > 0 ? gmdate('H:i:s', $item['actual_work_seconds']) : '-' }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="px-2 py-4 text-center text-xs text-gray-500">計上された人件費はありません。</p>
                                @endforelse
                            </div>
                            <table class="min-w-full text-sm hidden sm:table">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-400">キャラクター名</th>
                                        <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-400">工程名</th>
                                        <th class="px-2 py-1 text-right text-xs font-medium text-gray-500 dark:text-gray-400">予定工数</th>
                                        <th class="px-2 py-1 text-right text-xs font-medium text-gray-500 dark:text-gray-400">実績作業時間</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse($labor_cost_breakdown as $item)
                                        <tr>
                                            <td class="px-2 py-1.5 text-gray-600 dark:text-gray-300 truncate" title="{{ $item['character_name'] }}">{{ $item['character_name'] }}</td>
                                            <td class="px-2 py-1.5 text-gray-600 dark:text-gray-300 truncate" title="{{ $item['task_name'] }}">{{ $item['task_name'] }}</td>
                                            <td class="px-2 py-1.5 text-right text-gray-600 dark:text-gray-300">{{ $item['estimated_duration_seconds'] > 0 ? gmdate('H:i:s', $item['estimated_duration_seconds']) : '-' }}</td>
                                            <td class="px-2 py-1.5 text-right font-semibold {{ $item['actual_work_seconds'] > $item['estimated_duration_seconds'] && $item['estimated_duration_seconds'] > 0 ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }}">
                                                {{ $item['actual_work_seconds'] > 0 ? gmdate('H:i:s', $item['actual_work_seconds']) : '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="px-2 py-4 text-center text-xs text-gray-500">計上された人件費はありません。</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    {{-- ▲▲▲ コスト進捗バーと警告 ここまで ▲▲▲ --}}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            {{-- 案件情報カード --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center" style="background: linear-gradient(135deg, {{ $project->color ?? '#6c757d' }}1A, {{ $project->color ?? '#6c757d' }}0A); border-left: 4px solid {{ $project->color ?? '#6c757d' }};">
                    <i class="fas fa-info-circle mr-2" style="color: {{ $project->color ?? '#6c757d' }};"></i>
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-0">案件基本情報</h5>
                </div>
                <div class="p-5 space-y-4">
                    {{-- 各項目をレスポンシブ対応 --}}
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0 mb-1 sm:mb-0">案件名</span>
                        <span class="text-sm text-gray-700 dark:text-gray-300 text-left flex-1 whitespace-pre-wrap break-words">{{ $project->title }}</span>
                    </div>
                    @if($project->projectCategory)
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0 mb-1 sm:mb-0">カテゴリ</span>
                        <span class="text-sm text-gray-700 dark:text-gray-300 text-left flex-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                {{ $project->projectCategory->display_name ?? $project->projectCategory->name }}
                            </span>
                        </span>
                    </div>
                    @endif
                    @if($project->series_title)
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0 mb-1 sm:mb-0">作品名</span>
                        <span class="text-sm text-gray-700 dark:text-gray-300 text-left flex-1 whitespace-pre-wrap break-words">{{ $project->series_title }}</span>
                    </div>
                    @endif
                    @if($project->client_name)
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0 mb-1 sm:mb-0">依頼主名</span>
                        <span class="text-sm text-gray-700 dark:text-gray-300 text-left flex-1 whitespace-pre-wrap break-words">{{ $project->client_name }}</span>
                    </div>
                    @endif
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0 mb-1 sm:mb-0">期間</span>
                        <span class="text-sm text-gray-700 dark:text-gray-300 text-left">{{ $project->start_date ? $project->start_date->format('Y/m/d') : '-' }} 〜 {{ $project->end_date ? $project->end_date->format('Y/m/d') : '-' }}</span>
                    </div>
                    @if($project->description)
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0 mb-1 sm:mb-0">備考</span>
                            <p class="text-sm text-gray-700 dark:text-gray-300 text-left whitespace-pre-wrap break-words flex-1">{{ $project->description }}</p>
                        </div>
                    @endif

                    {{-- 納品フラグ --}}
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start pt-2">
                        <div class="flex items-center space-x-2 mb-2 sm:mb-0">
                            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">納品状況</span>
                            @php
                                $deliveryFlagValue = $project->delivery_flag ?? '0';
                                $deliveryIcon = $deliveryFlagValue == '1' ? 'fa-check-circle' : 'fa-truck';
                                $deliveryIconColor = $deliveryFlagValue == '1' ? 'text-green-500 dark:text-green-400' : 'text-yellow-500 dark:text-yellow-400';
                                $deliveryTooltip = $deliveryFlagValue == '1' ? '納品済み' : '未納品';
                            @endphp
                            <span id="project_delivery_flag_icon_{{ $project->id }}" title="{{ $deliveryTooltip }}" class="text-base">
                                <i class="fas {{ $deliveryIcon }} {{ $deliveryIconColor }}"></i>
                            </span>
                        </div>
                        @can('update', $project)
                            <select name="delivery_flag" id="project_delivery_flag_select_{{ $project->id }}"
                                    class="project-flag-select form-select form-select-sm text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 rounded-md shadow-sm w-full sm:w-36"
                                    data-project-id="{{ $project->id }}" data-url="{{ route('projects.updateDeliveryFlag', $project) }}"
                                    data-icon-target="project_delivery_flag_icon_{{ $project->id }}"
                                    data-status-target-icon="project_status_icon_{{ $project->id }}"
                                    data-status-target-select="project_status_select_{{ $project->id }}">
                                <option value="0" {{ $deliveryFlagValue == '0' ? 'selected' : '' }}>未納品</option>
                                <option value="1" {{ $deliveryFlagValue == '1' ? 'selected' : '' }}>納品済み</option>
                            </select>
                        @endcan
                        @cannot('update', $project)
                            <span class="sm:ml-auto text-sm text-gray-700 dark:text-gray-300">{{ $deliveryTooltip }}</span>
                        @endcannot
                    </div>

                    {{-- 支払いフラグ --}}
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                        @php
                            $paymentFlagOptions = ['' => '未設定'] + (\App\Models\Project::PAYMENT_FLAG_OPTIONS ?? []);
                            $paymentFlagIcons = [
                                'Pending'        => 'fa-clock text-yellow-500 dark:text-yellow-400', 'Processing'     => 'fa-hourglass-half text-blue-500 dark:text-blue-400',
                                'Completed'      => 'fa-check-circle text-green-500 dark:text-green-400', 'Partially Paid' => 'fa-adjust text-orange-500 dark:text-orange-400',
                                'Overdue'        => 'fa-exclamation-triangle text-red-500 dark:text-red-400', 'Cancelled'      => 'fa-ban text-gray-500 dark:text-gray-400',
                                'Refunded'       => 'fa-undo text-purple-500 dark:text-purple-400', 'On Hold'        => 'fa-pause-circle text-indigo-500 dark:text-indigo-400',
                                ''               => 'fa-question-circle text-gray-400 dark:text-gray-500',
                            ];
                            $currentPaymentFlag = $project->payment_flag ?? '';
                            $paymentFlagTooltip = $paymentFlagOptions[$currentPaymentFlag] ?? $currentPaymentFlag;
                            $paymentFlagIconClass = $paymentFlagIcons[$currentPaymentFlag] ?? $paymentFlagIcons[''];
                        @endphp
                        <div class="flex items-center space-x-2 mb-2 sm:mb-0">
                            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">支払状況</span>
                            <span id="project_payment_flag_icon_{{ $project->id }}" title="{{ $paymentFlagTooltip }}" class="text-base">
                                <i class="fas {{ $paymentFlagIconClass }}"></i>
                            </span>
                        </div>
                        @can('update', $project)
                            <select name="payment_flag" id="project_payment_flag_select_{{ $project->id }}"
                                    class="project-flag-select form-select form-select-sm text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 rounded-md shadow-sm w-full sm:w-36"
                                    data-project-id="{{ $project->id }}" data-url="{{ route('projects.updatePaymentFlag', $project) }}"
                                    data-icon-target="project_payment_flag_icon_{{ $project->id }}"
                                    data-status-target-icon="project_status_icon_{{ $project->id }}"
                                    data-status-target-select="project_status_select_{{ $project->id }}">
                                @foreach($paymentFlagOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $currentPaymentFlag == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        @endcan
                        @cannot('update', $project)
                            <span class="sm:ml-auto text-sm text-gray-700 dark:text-gray-300">{{ $paymentFlagTooltip }}</span>
                        @endcannot
                    </div>

                    @if($project->payment)
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0 mb-1 sm:mb-0">支払条件</span>
                        <p class="text-sm text-gray-700 dark:text-gray-300 text-left sm:text-right whitespace-pre-wrap break-words flex-1">{{ $project->payment }}</p>
                    </div>
                    @endif

                    {{-- 案件ステータス --}}
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                        @php
                            $projectStatusOptions = ['' => '未設定'] + (\App\Models\Project::PROJECT_STATUS_OPTIONS ?? []);
                            $projectStatusIcons = [
                                'not_started' => 'fa-minus-circle text-gray-500 dark:text-gray-400', 'in_progress' => 'fa-play-circle text-blue-500 dark:text-blue-400',
                                'completed'   => 'fa-check-circle text-green-500 dark:text-green-400', 'on_hold'     => 'fa-pause-circle text-yellow-500 dark:text-yellow-400',
                                'cancelled'   => 'fa-times-circle text-red-500 dark:text-red-400', '' => 'fa-question-circle text-gray-400 dark:text-gray-500',
                            ];
                            $currentProjectStatus = $project->status ?? '';
                            $projectStatusTooltip = $projectStatusOptions[$currentProjectStatus] ?? $currentProjectStatus;
                            $projectStatusIconClass = $projectStatusIcons[$currentProjectStatus] ?? $projectStatusIcons[''];
                        @endphp
                        <div class="flex items-center space-x-2 mb-2 sm:mb-0">
                            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">案件ステータス</span>
                            <span id="project_status_icon_{{ $project->id }}" title="{{ $projectStatusTooltip }}" class="text-base">
                                <i class="fas {{ $projectStatusIconClass }}"></i>
                            </span>
                        </div>
                        @can('update', $project)
                            <select name="status" id="project_status_select_{{ $project->id }}"
                                    class="project-status-select form-select form-select-sm text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 rounded-md shadow-sm w-full sm:w-36"
                                    data-project-id="{{ $project->id }}" data-url="{{ route('projects.updateStatus', $project) }}"
                                    data-icon-target="project_status_icon_{{ $project->id }}">
                                @foreach($projectStatusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $currentProjectStatus == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        @endcan
                        @cannot('update', $project)
                            <span class="sm:ml-auto text-sm text-gray-700 dark:text-gray-300">{{ $projectStatusTooltip }}</span>
                        @endcannot
                    </div>
                    <div class="p-2 text-xs bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-md dark:bg-yellow-700/30 dark:text-yellow-200 dark:border-yellow-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        案件ステータスは「納品済み」、かつ「支払完了」とならないと完了になりません。
                    </div>

                    @if(!empty($project->tracking_info))
                        <hr class="dark:border-gray-700">
                        <div class="space-y-3">
                            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-full block">送り状情報</span>
                            @foreach($project->tracking_info as $info)
                                @php
                                    $carrierConfig = config('shipping.carriers.' . ($info['carrier'] ?? 'other'));
                                    $trackingUrl = $carrierConfig && $carrierConfig['url'] && !empty($info['number']) ? $carrierConfig['url'] . $info['number'] : null;
                                @endphp
                                <div class="pl-4 border-l-2 dark:border-gray-600">
                                    <div class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-semibold">{{ $carrierConfig['name'] ?? '不明' }}:</span>
                                        @if($trackingUrl)
                                            <a href="{{ $trackingUrl }}" target="_blank" class="font-bold text-blue-600 hover:underline">{{ $info['number'] }} <i class="fas fa-external-link-alt fa-xs"></i></a>
                                        @else
                                            <span class="font-bold">{{ $info['number'] ?? '番号なし' }}</span>
                                        @endif
                                    </div>
                                    @if(!empty($info['memo']))
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 pl-1">
                                            <i class="far fa-comment-dots mr-1"></i>{{ $info['memo'] }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                    {{-- プロジェクト固有の form_definitions に基づく追加情報 (案件依頼項目) --}}
                    @can('viewAny', App\Models\FormFieldDefinition::class)
                        @if(!empty($customFormFields) && count($customFormFields) > 0)
                            @if(collect($customFormFields)->isNotEmpty())
                                <hr class="dark:border-gray-600">
                                <h6 class="text-base font-semibold text-gray-600 dark:text-gray-400 pt-1">- 詳細情報 -</h6>
                                <div class="space-y-4">
                                    @foreach($customFormFields as $field)
                                        @php
                                            $fieldName = $field['name'];
                                            $fieldLabel = $field['label'];
                                            $fieldType = $field['type'];
                                            $value = $project->getCustomAttributeValue($fieldName);
                                            $options = is_array($field['options']) ? $field['options'] : (json_decode($field['options'], true) ?? []);
                                        @endphp
                                        <div class="flex flex-col sm:flex-row sm:items-start">
                                            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0 mb-1 sm:mb-0">{{ $fieldLabel }}</span>

                                            <div class="flex-1 text-left">
                                                @switch($fieldType)
                                                    @case('checkbox')
                                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                                            @if(empty($options))
                                                                {{ filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'はい' : 'いいえ' }}
                                                            @else
                                                                @if(!empty($value) && is_array($value))
                                                                    {{ collect($value)->map(fn($val) => $options[$val] ?? $val)->implode(', ') }}
                                                                @else
                                                                    <span class="text-gray-400">-</span>
                                                                @endif
                                                            @endif
                                                        </span>
                                                        @break

                                                    @case('select')
                                                    @case('radio')
                                                        <p class="text-sm text-gray-700 dark:text-gray-300 break-words">
                                                            {{ $options[$value] ?? $value ?? '-' }}
                                                        </p>
                                                        @break

                                                    @case('image_select')
                                                        @if(!empty($value) && is_array($value))
                                                            @php
                                                                $galleryData = collect($value)->map(function($selectedValue) use ($options) {
                                                                    if (isset($options[$selectedValue])) {
                                                                        return ['src' => $options[$selectedValue], 'alt' => $selectedValue];
                                                                    }
                                                                    return null;
                                                                })->filter()->values();
                                                            @endphp
                                                            {{-- データをdata-gallery属性に格納 --}}
                                                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2" data-gallery='{!! json_encode($galleryData) !!}'>
                                                                @foreach($galleryData as $index => $image)
                                                                    <div class="text-center">
                                                                        {{-- クリックイベントを簡素化 --}}
                                                                        <button type="button" @click="openModalFromClick($event)" data-index="{{ $index }}">
                                                                            <img src="{{ str_replace('\\', '/', $image['src']) }}" alt="{{ $image['alt'] }}" class="w-full h-20 object-cover rounded-md border dark:border-gray-600 hover:opacity-80 transition-opacity">
                                                                        </button>
                                                                        <span class="text-xs text-gray-600 dark:text-gray-400 mt-1 block ">{{ $image['alt'] }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="text-sm text-gray-400">-</span>
                                                        @endif
                                                        @break

                                                    @case('color')
                                                        <div class="flex items-center">
                                                            <span class="w-5 h-5 rounded-full inline-block border dark:border-gray-600 shadow-sm mr-2" style="background-color: {{ $value ?? '#ffffff' }};"></span>
                                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $value ?? '-' }}</span>
                                                        </div>
                                                        @break

                                                    @case('date')
                                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                                            {{ $value ? \Carbon\Carbon::parse($value)->format('Y/m/d') : '-' }}
                                                        </span>
                                                        @break

                                                    @case('textarea')
                                                        <p class="text-sm text-gray-700 dark:text-gray-300 break-words">{!! nl2br($value) ?? '-' !!}</p>
                                                        @break

                                                    @case('file')
                                                    @case('file_multiple')
                                                        @php
                                                            $filesToDisplay = [];
                                                            if (is_array($value) && !empty($value)) {
                                                                if (isset($value['path'])) { $filesToDisplay = [$value]; } else { $filesToDisplay = $value; }
                                                            }
                                                            $imageGalleryData = collect($filesToDisplay)->filter(fn($f) => is_array($f) && isset($f['mime_type']) && str_starts_with($f['mime_type'], 'image/'))
                                                                ->map(function($fileInfo) {
                                                                    return ['src' => Storage::url($fileInfo['path']), 'alt' => $fileInfo['original_name'] ?? ''];
                                                                })->values();
                                                        @endphp

                                                        @if(!empty($filesToDisplay))
                                                            <div class="w-full">
                                                                @if($imageGalleryData->isNotEmpty())
                                                                {{-- データをdata-gallery属性に格納 --}}
                                                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2" data-gallery='{!! json_encode($imageGalleryData) !!}'>
                                                                    @foreach($imageGalleryData as $index => $image)
                                                                        <div class="text-center">
                                                                            {{-- クリックイベントを簡素化 --}}
                                                                            <button type="button" @click="openModalFromClick($event)" data-index="{{ $index }}">
                                                                                <img src="{{ str_replace('\\', '/', $image['src']) }}" alt="{{ $image['alt'] }}" class="w-full h-20 object-cover rounded-md border dark:border-gray-600 hover:opacity-80 transition-opacity">
                                                                            </button>
                                                                            {{-- <span class="text-xs text-gray-600 dark:text-gray-400 mt-1 block " title="{{ $image['alt'] }}">{{ Str::limit($image['alt'], 15) }}</span> --}}
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                                @endif
                                                                <ul class="list-none space-y-1 @if($imageGalleryData->isNotEmpty()) mt-2 @endif">
                                                                    @foreach($filesToDisplay as $fileInfo)
                                                                        @if(is_array($fileInfo) && isset($fileInfo['path']) && !str_starts_with($fileInfo['mime_type'] ?? '', 'image/'))
                                                                            <li class="text-sm">
                                                                                <a href="{{ str_replace('\\', '/', Storage::url($fileInfo['path'])) }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400 dark:hover:text-blue-300" title="ダウンロード: {{ $fileInfo['original_name'] }}">
                                                                                    <i class="fas fa-file-download mr-1"></i>{{ Str::limit($fileInfo['original_name'], 30) }}
                                                                                </a>
                                                                                @if(isset($fileInfo['size'])) <span class="text-gray-400 text-xs">({{ \Illuminate\Support\Number::fileSize($fileInfo['size']) }})</span> @endif
                                                                            </li>
                                                                        @endif
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @else
                                                            <span class="text-sm text-gray-400">-</span>
                                                        @endif
                                                        @break
                                                    @case('url')
                                                        <div class="text-sm text-gray-700 dark:text-gray-300">
                                                            @if($value && filter_var($value, FILTER_VALIDATE_URL))
                                                                <a href="{{ $value }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline dark:text-blue-400 dark:hover:text-blue-300 break-all whitespace-normal">
                                                                    {{ $value }}
                                                                </a>
                                                            @else
                                                                <p class="break-words whitespace-normal">{{ $value ?? '-' }}</p>
                                                            @endif
                                                        </div>
                                                        @break

                                                    @default
                                                        <p class="text-sm text-gray-700 dark:text-gray-300 break-words">
                                                            @if(is_array($value))
                                                                {{ implode(', ', $value) }}
                                                            @else
                                                                {{ $value ?? '-' }}
                                                            @endif
                                                        </p>
                                                @endswitch
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    @endcan

                    <hr class="dark:border-gray-700">
                    {{-- 進捗バー --}}
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2 sm:mb-0">進捗状況</span>
                        @php
                            $progressTasks = $project->tasks()->where('is_folder', false)->where('is_milestone', false)->get();
                            $totalProgressTasks = $progressTasks->count();
                            $completedProgressTasks = $progressTasks->where('status', 'completed')->count();
                            $progress = $totalProgressTasks > 0 ? round(($completedProgressTasks / $totalProgressTasks) * 100) : 0;
                        @endphp
                        <div class="w-full sm:w-auto sm:text-right">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-1">
                                <div class="h-2 rounded-full" style="width: {{ $progress }}%; background-color: {{ $project->color ?? '#6c757d' }};"></div>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background-color: {{ $project->color ?? '#6c757d' }}; color:white;">{{ $progress }}%</span>
                            <small class="text-gray-500 dark:text-gray-400 ml-1"> ({{ $completedProgressTasks }}/{{ $totalProgressTasks }} 工程完了)</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ▼▼▼【変更後】完成データ管理カード（グリッド表示） ▼▼▼ --}}
            @can('fileViewAny', App\Models\Task::class)
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-0 flex items-center">
                        <i class="fas fa-archive mr-2 text-gray-600 dark:text-gray-300"></i>
                        完成データ
                    </h5>
                    {{-- 新規フォルダ作成用のボタンを配置 --}}
                    <button type="button" x-data @click="$dispatch('open-modal', 'create-completion-folder-modal')" class="inline-flex items-center px-3 py-1 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150">
                        <i class="fas fa-folder-plus mr-1"></i> フォルダ追加
                    </button>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @forelse ($completionDataFolders as $folder)
                            @php
                                // ファイルを名前順でソートし、最初のファイルを取得
                                $firstFile = $folder->files->sortBy('original_name')->first();
                                $isImage = $firstFile && Str::startsWith($firstFile->mime_type, 'image/');
                            @endphp
                            <div class="group relative">
                                <a href="{{ route('projects.tasks.edit', [$project, $folder]) }}" class="block border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm hover:shadow-lg hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-300">
                                    {{-- サムネイル表示エリア --}}
                                    <div class="aspect-w-16 aspect-h-9 bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        @if ($isImage)
                                            <img src="{{ route('projects.tasks.files.show', [$project, $folder, $firstFile]) }}" alt="{{ $firstFile->original_name }}" class="w-full h-full object-cover">
                                        @else
                                            {{-- フォールバックアイコン --}}
                                            <i class="fas fa-folder-open text-4xl text-gray-400 dark:text-gray-500"></i>
                                        @endif
                                    </div>
                                    {{-- フォルダ情報エリア --}}
                                    <div class="p-3 bg-white dark:bg-gray-800">
                                        <h6 class="font-semibold text-sm text-gray-800 dark:text-gray-100 truncate group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ $folder->name }}</h6>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $folder->files->count() }} 件のファイル</p>
                                    </div>
                                </a>
                                {{-- フォルダ削除ボタン --}}
                                @can('delete', $folder)
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <form action="{{ route('projects.tasks.destroy', [$project, $folder]) }}" method="POST" onsubmit="return confirm('このフォルダと中の全てのファイルを本当に削除しますか？');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-7 h-7 flex items-center justify-center bg-red-600/80 hover:bg-red-700 text-white rounded-full shadow-md" title="フォルダを削除">
                                                <i class="fas fa-trash fa-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endcan
                            </div>
                        @empty
                            <p class="col-span-2 md:col-span-3 text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                                「フォルダ追加」ボタンから新しいフォルダを作成してください。
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>

            <x-modal name="create-completion-folder-modal" :show="$errors->folderCreation->isNotEmpty()" focusable>
                <form action="{{ route('projects.completionFolders.store', $project) }}" method="POST" class="p-6">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $masterFolder->id }}">

                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        完成データフォルダを新規作成
                    </h2>

                    <div class="mt-6">
                        <x-input-label for="new_folder_name_modal" value="フォルダ名" class="sr-only" />
                        <x-text-input
                            id="new_folder_name_modal"
                            name="name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="例: 写真、仕様書、パターンなど"
                            required
                        />
                        <x-input-error :messages="$errors->folderCreation->get('name')" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-secondary-button x-on:click="$dispatch('close')">
                            キャンセル
                        </x-secondary-button>

                        <x-primary-button class="ml-3">
                            作成する
                        </x-primary-button>
                    </div>
                </form>
            </x-modal>
            @endcan
            {{-- ▲▲▲ ここまで ▲▲▲ --}}

            {{-- 統計情報セクション --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center">
                    <i class="fas fa-chart-pie mr-2 text-gray-600 dark:text-gray-300"></i>
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-0">統計情報</h5>
                </div>
                <div class="p-5">
                    <h6 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">ステータス別工程数 <span class="text-gray-400 normal-case">(フォルダ・予定を除く)</span></h6>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md"><div class="text-xl font-bold text-gray-500 dark:text-gray-400">{{ $project->tasks()->where('is_milestone', false)->where('is_folder', false)->where('status', 'not_started')->count() }}</div><small class="text-gray-500 dark:text-gray-400">未着手</small></div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md"><div class="text-xl font-bold text-blue-500 dark:text-blue-400">{{ $project->tasks()->where('is_milestone', false)->where('is_folder', false)->where('status', 'in_progress')->count() }}</div><small class="text-gray-500 dark:text-gray-400">進行中</small></div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md"><div class="text-xl font-bold text-orange-500 dark:text-orange-400">{{ $project->tasks()->where('is_milestone', false)->where('is_folder', false)->where('status', 'rework')->count() }}</div><small class="text-gray-500 dark:text-gray-400">直し</small></div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md"><div class="text-xl font-bold text-yellow-500 dark:text-yellow-400">{{ $project->tasks()->where('is_milestone', false)->where('is_folder', false)->where('status', 'on_hold')->count() }}</div><small class="text-gray-500 dark:text-gray-400">一時停止中</small></div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md"><div class="text-xl font-bold text-green-500 dark:text-green-400">{{ $project->tasks()->where('is_milestone', false)->where('is_folder', false)->where('status', 'completed')->count() }}</div><small class="text-gray-500 dark:text-gray-400">完了</small></div>
                    </div>
                    <h6 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">タイプ別工程数</h6>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm"><span class="text-gray-600 dark:text-gray-300">通常工程</span><span class="px-2 py-0.5 text-xs font-semibold text-blue-800 bg-blue-100 dark:bg-blue-700 dark:text-blue-200 rounded-full">{{ $project->tasks->where('is_milestone', false)->where('is_folder', false)->count() }}</span></div>
                        <div class="flex justify-between items-center text-sm"><span class="text-gray-600 dark:text-gray-300">予定</span><span class="px-2 py-0.5 text-xs font-semibold text-red-800 bg-red-100 dark:bg-red-700 dark:text-red-200 rounded-full">{{ $project->tasks->where('is_milestone', true)->count() }}</span></div>
                        <div class="flex justify-between items-center text-sm"><span class="text-gray-600 dark:text-gray-300">フォルダ</span><span class="px-2 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 dark:bg-gray-600 dark:text-gray-200 rounded-full">{{ $project->tasks->where('is_folder', true)->count()-1 }}</span></div>
                        <div class="flex justify-between items-center text-sm"><span class="text-gray-600 dark:text-gray-300">直し</span><span class="px-2 py-0.5 text-xs font-semibold text-orange-800 bg-orange-100 dark:bg-orange-600 dark:text-orange-200 rounded-full">{{ $project->tasks->where('is_rework_task', true)->count() }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 右側カラム (登場キャラクターカード、案件全体の工程一覧カード) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- 登場キャラクターカード --}}
            <div x-data="{
                    expanded: true,
                    activeCharacterTab: {}
                 }"
                 class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center cursor-pointer" @click="expanded = !expanded">
                    <div class="flex items-center"> <i class="fas fa-users mr-2 text-gray-600 dark:text-gray-300"></i> <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-0">キャラクター</h5> </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                        <span class="mr-2">{{ $project->characters->count() }}体</span>
                        {{-- @can('manageCosts', $project)
                        @if($project->characters->count() > 0)
                        <span class="mr-2 hidden sm:inline">合計コスト: {{ number_format($project->characters->sum(function ($char) { return $char->costs->sum('amount'); })) }}円</span>
                        @endif
                        @endcan --}}

                        <i class="fas" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                </div>
                <div x-show="expanded" x-collapse class="p-1 sm:p-3 md:p-5 border-t border-gray-200 dark:border-gray-700">
                    @can('update', $project)
                    <div x-data="{ open: {{ $errors->characterCreation->any() ? 'true' : 'false' }} }" class="mb-6 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400 transition-colors overflow-hidden">
                        {{-- クリックで開閉するためのヘッダー --}}
                        <div @click="open = !open" class="p-4 cursor-pointer flex justify-between items-center">
                            <h6 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-0">
                                <i class="fas fa-plus mr-2"></i>新しいキャラクターを追加
                            </h6>
                            <button type="button" class="text-gray-500 dark:text-gray-400">
                                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                        </div>

                        {{-- 開閉するフォーム本体 --}}
                        <div x-show="open" x-collapse>
                            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                                <form action="{{ route('projects.characters.store', $project) }}" method="POST">
                                    @csrf
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-start">
                                            <div class="sm:col-span-2">
                                                <label for="new_character_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">キャラクター名 <span class="text-red-500">*</span></label>
                                                <input type="text" name="name" id="new_character_name" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200" placeholder="例: 主人公" required value="{{ old('name') }}">
                                                @error('name', 'characterCreation')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                            </div>
                                            <div>
                                                <label for="new_character_gender" class="block text-xs font-medium text-gray-700 dark:text-gray-300">性別</label>
                                                <select name="gender" id="new_character_gender" class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 text-sm">
                                                    <option value="" @selected(old('gender') == '')>選択しない</option>
                                                    <option value="male" @selected(old('gender') == 'male')>男性</option>
                                                    <option value="female" @selected(old('gender') == 'female')>女性</option>
                                                </select>
                                                @error('gender', 'characterCreation')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                        <div>
                                            <label for="new_character_description" class="block text-xs font-medium text-gray-700 dark:text-gray-300">備考</label>
                                            <textarea name="description" id="new_character_description" rows="3" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200" placeholder="例: メイン">{{ old('description') }}</textarea>
                                            @error('description', 'characterCreation')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-800 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                                <i class="fas fa-plus mr-1 sm:mr-2"></i><span class="hidden sm:inline">追加</span><span class="sm:hidden">追加</span>
                                            </button>
                                        </div>
                                    </div>
                                    @if ($errors->characterCreation->any() && !$errors->characterCreation->has('name') && !$errors->characterCreation->has('gender') && !$errors->characterCreation->has('description'))
                                        <div class="mt-2 text-xs text-red-500">
                                            <ul>
                                                @foreach ($errors->characterCreation->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                    @endcan
                    {{-- ▼▼▼【追加】並び順保存ボタン ▼▼▼ --}}
                    <button type="button" id="save-character-order-btn" class="hidden inline-flex items-center px-3 py-1 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                        <i class="fas fa-save mr-1"></i>並び順を保存
                    </button>
                    @if($project->characters->isEmpty())
                        <div class="text-center py-10"> <i class="fas fa-user-plus text-4xl text-gray-400 dark:text-gray-500 mb-3"></i> <h6 class="text-md font-semibold text-gray-700 dark:text-gray-300">キャラクターが登録されていません</h6> <p class="text-sm text-gray-500 dark:text-gray-400">上のフォームから新しいキャラクターを追加してください。</p> </div>
                    @else
                        <div id="character-list" class="space-y-6">
                            @foreach($project->characters->sortBy('display_order') as $character)
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden js-character-card" data-id="{{ $character->id }}">                                    <div class="px-5 py-3 flex justify-between items-center border-b dark:border-gray-700" style="background: linear-gradient(135deg, {{ $project->color ?? '#6c757d' }}1A, {{ $project->color ?? '#6c757d' }}0A); border-left: 3px solid {{ $project->color ?? '#6c757d' }};">
                                <h6 class="text-md font-semibold text-gray-800 dark:text-gray-100 flex items-center flex-grow truncate">

                                    {{-- ドラッグハンドル --}}
                                    <span class="drag-handle text-gray-400 mr-3 cursor-move flex-shrink-0" title="ドラッグして並び替え"><i class="fas fa-grip-vertical"></i></span>

                                    {{-- キャラクター名 --}}
                                    <span class="truncate" title="{{ $character->name }}">
                                        <i class="fas fa-user mr-2" style="color: {{ $project->color ?? '#6c757d' }};"></i>
                                        {{ $character->name }}
                                    </span>

                                    {{-- 性別 --}}
                                    @if($character->gender)
                                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-2 flex-shrink-0">({{ $character->gender_label }})</span>
                                    @endif
                                </h6>

                                        {{-- コスト情報表示 --}}
                                        @can('manageCosts', $project)
                                        <span class="flex-shrink-0 text-xs font-mono bg-gray-100 dark:bg-gray-900/50 px-2 py-1 rounded mr-3 hidden md:block" title="">
                                            <span title="実績コスト: ¥{{ number_format($character->actual_total_cost ?? 0) }}">
                                                実績コスト: ¥{{ number_format($character->actual_total_cost ?? 0) }}
                                            </span>
                                            {{-- <span class="mx-1">/</span> --}}
                                            {{-- <span class="text-gray-500 dark:text-gray-400" title="目標コスト: ¥{{ number_format($character->target_total_cost ?? 0) }}">
                                                ¥{{ number_format($character->target_total_cost ?? 0) }}
                                            </span> --}}
                                        </span>
                                        @endcan
                                        @can('update', $project)
                                        <div class="flex space-x-1 flex-shrink-0">
                                            <x-icon-button :href="route('characters.edit', $character)" icon="fas fa-edit" title="編集" color="blue" size="sm" />
                                            <form action="{{ route('characters.destroy', $character->id) }}" method="POST" onsubmit="return confirm('このキャラクターを削除しますか？関連データも全て削除されます。');" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800" title="削除">
                                                    <i class="fas fa-trash fa-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                        @endcan
                                    </div>
                                    @if($character->description) <div class="px-5 py-2 text-xs text-gray-600 dark:text-gray-400 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50"> {{ $character->description }} </div> @endif
                                    <div class="p-1 sm:p-2">
                                        <div class="border-b border-gray-200 dark:border-gray-700">
                                            <div
                                                class="p-2 text-xs bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-md dark:bg-yellow-700/30 dark:text-yellow-200 dark:border-yellow-500">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                各タブの数値が実データと異なっている場合、画面を再読み込みしてください
                                            </div>
                                            <nav class="-mb-px flex space-x-2 sm:space-x-4 overflow-x-auto text-xs sm:text-sm" aria-label="Character Tabs for {{ $character->id }}">
                                                @can('manageMeasurements', $project)
                                                <button @click="activeCharacterTab[{{ $character->id }}] = (activeCharacterTab[{{ $character->id }}] === 'measurements-{{ $character->id }}') ? null : 'measurements-{{ $character->id }}'"
                                                        :class="{ 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-semibold': activeCharacterTab[{{ $character->id }}] === 'measurements-{{ $character->id }}', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeCharacterTab[{{ $character->id }}] !== 'measurements-{{ $character->id }}' }"
                                                        class="whitespace-nowrap py-2 px-2 sm:px-3 border-b-2 font-medium focus:outline-none">
                                                    <i class="fas fa-ruler mr-1"></i> 採寸 <span class="ml-1 text-xs">({{ $character->measurements->count() }})</span>
                                                </button>
                                                @endcan
                                                @can('manageMaterials', $project)
                                                <button @click="activeCharacterTab[{{ $character->id }}] = (activeCharacterTab[{{ $character->id }}] === 'materials-{{ $character->id }}') ? null : 'materials-{{ $character->id }}'"
                                                        :class="{ 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-semibold': activeCharacterTab[{{ $character->id }}] === 'materials-{{ $character->id }}', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeCharacterTab[{{ $character->id }}] !== 'materials-{{ $character->id }}' }"
                                                        class="whitespace-nowrap py-2 px-2 sm:px-3 border-b-2 font-medium focus:outline-none">
                                                    <i class="fas fa-box mr-1"></i> 材料 <span class="ml-1 text-xs">({{ $character->materials->count() }})</span>
                                                </button>
                                                @endcan
                                                @can('viewAny', App\Models\Task::class)
                                                <button @click="activeCharacterTab[{{ $character->id }}] = (activeCharacterTab[{{ $character->id }}] === 'tasks-{{ $character->id }}') ? null : 'tasks-{{ $character->id }}'"
                                                        :class="{ 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-semibold': activeCharacterTab[{{ $character->id }}] === 'tasks-{{ $character->id }}', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeCharacterTab[{{ $character->id }}] !== 'tasks-{{ $character->id }}' }"
                                                        class="whitespace-nowrap py-2 px-2 sm:px-3 border-b-2 font-medium focus:outline-none">
                                                    <i class="fas fa-tasks mr-1"></i> 工程 <span class="ml-1 text-xs">({{ $character->tasks->count() }})</span>
                                                </button>
                                                @endcan
                                                @can('manageCosts', $project)
                                                <button @click="activeCharacterTab[{{ $character->id }}] = (activeCharacterTab[{{ $character->id }}] === 'costs-{{ $character->id }}') ? null : 'costs-{{ $character->id }}'"
                                                        :class="{ 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-semibold': activeCharacterTab[{{ $character->id }}] === 'costs-{{ $character->id }}', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeCharacterTab[{{ $character->id }}] !== 'costs-{{ $character->id }}' }"
                                                        class="whitespace-nowrap py-2 px-2 sm:px-3 border-b-2 font-medium focus:outline-none">
                                                    <i class="fas fa-yen-sign mr-1"></i> コスト <span class="ml-1 text-xs">({{ number_format($character->costs->sum('amount')) }}円)</span>
                                                </button>
                                                @endcan
                                            </nav>
                                        </div>
                                        <div class="py-3">
                                            @can('manageMeasurements', $project)
                                            <div x-show="activeCharacterTab[{{ $character->id }}] === 'measurements-{{ $character->id }}'"
                                                x-collapse
                                                id="measurements-content-{{ $character->id }}"
                                                x-effect="if (activeCharacterTab[{{ $character->id }}] === 'measurements-{{ $character->id }}') {
                                                    setTimeout(() => {
                                                        if (typeof window.setupMeasurementTemplateFunctionality === 'function') {
                                                             console.log('Calling setup for char: {{ $character->id }}');
                                                            window.setupMeasurementTemplateFunctionality({{ $character->id }}, {{ $project->id }});
                                                        } else {
                                                             console.warn('setupMeasurementTemplateFunctionality not defined when trying to init for char: {{ $character->id }}');
                                                        }
                                                    }, 100);
                                                }">
                                                @include('projects.partials.character-measurements-tailwind', ['character' => $character, 'project' => $project])
                                            </div>
                                            @endcan
                                            @can('manageMaterials', $project)
                                            <div x-show="activeCharacterTab[{{ $character->id }}] === 'materials-{{ $character->id }}'" x-collapse id="materials-content-{{ $character->id }}" >
                                                @include('projects.partials.character-materials-tailwind', ['character' => $character, 'project' => $project, 'availableInventoryItems' => $availableInventoryItems])
                                            </div>
                                            @endcan
                                            @can('viewAny', App\Models\Task::class)
                                            <div x-show="activeCharacterTab[{{ $character->id }}] === 'tasks-{{ $character->id }}'"
                                                x-collapse id="tasks-content-{{ $character->id }}"
                                                x-effect="
                                                if (activeCharacterTab[{{ $character->id }}] === 'tasks-{{ $character->id }}') {
                                                    console.log('[TGGL] x-effect: Tasks tab for character {{ $character->id }} (ID: tasks-content-{{ $character->id }}) is active.');
                                                    const tableId = 'character-tasks-table-{{ $character->id }}';
                                                    Alpine.nextTick(() => {
                                                        console.log('[TGGL] x-effect (nextTick): Attempting to init tableId:', tableId);
                                                        if (typeof window.setupTaskToggle === 'function') {
                                                            window.setupTaskToggle(tableId);
                                                        } else {
                                                            console.warn('[TGGL] x-effect (nextTick): setupTaskToggle function NOT FOUND for tableId:', tableId);
                                                        }
                                                    });
                                                }">

                                                {{-- @include の第1引数を、コントローラーでソート済みの $character->sorted_tasks に変更 --}}
                                                @include('projects.partials.character-tasks-table', [
                                                    'tasksToList' => $character->sorted_tasks,
                                                    'tableId' => 'character-tasks-table-' . $character->id,
                                                    'project' => $project,
                                                    'character' => $character,
                                                    'assigneeOptions' => $assigneeOptions ?? []
                                                ])
                                            </div>
                                            @endcan
                                            @can('manageCosts', $project)
                                            <div x-show="activeCharacterTab[{{ $character->id }}] === 'costs-{{ $character->id }}'" x-collapse id="costs-content-{{ $character->id }}" class="character-costs-list-container" data-character-id="{{ $character->id }}">
                                                @include('projects.partials.character-costs-tailwind', ['character' => $character, 'project' => $project])
                                            </div>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- 案件全体の工程一覧カード --}}
            <div x-data="{ expanded: true }" class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                 <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center cursor-pointer" @click="expanded = !expanded">
                    <div class="flex items-center"> <i class="fas fa-tasks mr-2 text-gray-600 dark:text-gray-300"></i> <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-0">工程一覧 (案件全体)</h5> </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400"> <span class="mr-2">{{ $project->tasksWithoutCharacter->count() }}件</span> <i class="fas" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i> </div>
                </div>
                <div x-show="expanded" x-collapse class="border-t border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto max-h-[60vh] overflow-y-auto">
                        @include('projects.partials.projects-task-table',
                        ['tasksToList' => $tasksToList,
                        'tableId' => 'project-tasks-table',
                        'projectForTable' => $project,
                        'isProjectTaskView' => true])
                    </div>
                </div>
            </div>

            {{-- 予定・依頼 --}}
            {{-- <div x-data="{ expanded: true }" class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center cursor-pointer" @click="expanded = !expanded">
                    <div class="flex items-center">
                        <i class="fas fa-clipboard-list mr-2 text-gray-600 dark:text-gray-300"></i>
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-0">関連する予定・依頼</h5>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span class="mr-2">{{ $project->requests->count() }}件</span>
                        <i class="fas" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                </div>
                <div x-show="expanded" x-collapse class="p-4 space-y-4 border-t border-gray-200 dark:border-gray-700">
                    @forelse($project->requests as $request)
                        @include('requests.partials.request-card', ['request' => $request])
                    @empty
                        <p class="text-center text-sm text-gray-500 dark:text-gray-400 py-6">
                            この案件に関連する予定・依頼はありません。
                        </p>
                    @endforelse
                </div>
            </div> --}}
        </div>
    </div>

    {{-- resources/views/projects/show.blade.php の中の一括登録モーダル部分 --}}
    @can('create', [App\Models\Task::class, $project])
    <x-modal name="batch-task-modal" focusable max-width="4xl">
        <div x-data="{
            tasks: [ { name: '', character_id: '', start_date: '', end_date: '', duration_value: '', duration_unit: 'minutes', description: '' } ],
            characters: {{ $project->characters->map->only(['id', 'name'])->values() }},
            addRow() { this.tasks.push({ name: '', character_id: '', start_date: '', end_date: '', duration_value: '', duration_unit: 'minutes', description: '' }); },
            removeRow(index) { if (this.tasks.length > 1) this.tasks.splice(index, 1); }
        }">
            <form id="batch-task-form" class="p-6" onsubmit="return false;">
                @csrf
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    <i class="fas fa-stream mr-2"></i>工程の一括登録
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    複数の工程情報をまとめて登録します。「行を追加」ボタンで入力欄を増やせます。
                </p>

                <div class="mt-6 space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                    <template x-for="(task, index) in tasks" :key="index">
                        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800/50 relative">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">

                                {{-- ▼▼▼【ここから下の :for, :id の部分を全て修正】▼▼▼ --}}

                                <div class="md:col-span-2">
                                    <x-input-label x-bind:for="'task_name_' + index" value="工程名" :required="true" />
                                    <x-text-input type="text" x-model="task.name" x-bind:name="`tasks[${index}][name]`" x-bind:id="'task_name_' + index" class="mt-1 block w-full" required />
                                </div>

                                {{-- 所属キャラクター --}}
                                <div class="md:col-span-2">
                                    <x-input-label x-bind:for="'task_character_' + index" value="所属キャラクター" />
                                    <select x-model="task.character_id" x-bind:name="`tasks[${index}][character_id]`" x-bind:id="'task_character_' + index" class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200">
                                        <option value="">案件全体 (キャラクターなし)</option>
                                        <template x-for="character in characters" :key="character.id">
                                            <option :value="character.id" x-text="character.name"></option>
                                        </template>
                                    </select>
                                </div>

                                {{-- 開始日時 --}}
                                <div>
                                    <x-input-label x-bind:for="'task_start_date_' + index" value="開始日時" :required="true" />
                                    <x-text-input type="datetime-local" x-model="task.start_date" x-bind:name="`tasks[${index}][start_date]`" x-bind:id="'task_start_date_' + index" class="mt-1 block w-full" required />
                                </div>
                                {{-- 終了日時 --}}
                                <div>
                                    <x-input-label x-bind:for="'task_end_date_' + index" value="終了日時" :required="true" />
                                    <x-text-input type="datetime-local" x-model="task.end_date" x-bind:name="`tasks[${index}][end_date]`" x-bind:id="'task_end_date_' + index" class="mt-1 block w-full" required />
                                </div>

                                {{-- 予定工数 --}}
                                <div class="md:col-span-2">
                                    <x-input-label x-bind:for="'task_duration_value_' + index" value="予定工数" :required="true" />
                                    <div class="flex items-center mt-1 space-x-2">
                                        <x-text-input type="number" x-model="task.duration_value" x-bind:name="`tasks[${index}][duration_value]`" x-bind:id="'task_duration_value_' + index" class="block w-1/2" min="0" step="any" placeholder="例: 8" required />
                                        <select x-model="task.duration_unit" x-bind:name="`tasks[${index}][duration_unit]`" x-bind:id="'task_duration_unit_' + index" class="block w-1/2 mt-0 form-select rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                                            <option value="days">日</option>
                                            <option value="hours">時間</option>
                                            <option value="minutes">分</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- メモ欄 --}}
                                <div class="md:col-span-2">
                                    <x-input-label x-bind:for="'task_description_' + index" value="メモ" />
                                    <x-textarea-input x-model="task.description" x-bind:name="`tasks[${index}][description]`" x-bind:id="'task_description_' + index" class="mt-1 block w-full" rows="2"></x-textarea-input>
                                </div>

                            </div>
                            <button type="button" @click="removeRow(index)" x-show="tasks.length > 1" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 dark:hover:text-red-400" title="この行を削除">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <div class="mt-4">
                    <x-secondary-button type="button" @click="addRow()">
                        <i class="fas fa-plus mr-2"></i>行を追加
                    </x-secondary-button>
                </div>

                <div id="batch-task-form-errors" class="text-sm text-red-600 space-y-1 mt-2"></div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">キャンセル</x-secondary-button>
                    <x-primary-button type="submit" class="ml-3">
                        <i class="fas fa-check mr-2"></i>この内容で登録する
                    </x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>
    @endcan
    <div x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4"
        style="display: none;">

        <div @click="closeModal" class="absolute inset-0"></div>
        <div @click.stop class="relative z-10 w-full max-w-5xl max-h-full flex items-center justify-center">
            <img :src="currentImage.src" :alt="currentImage.alt" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-xl">
        </div>
        <button @click="closeModal" class="absolute top-4 right-4 text-white/80 hover:text-white text-4xl font-bold z-20">&times;</button>
        <button x-show="images.length > 1" @click="prevImage" class="absolute left-4 top-1/2 -translate-y-1/2 text-white/80 hover:text-white text-4xl z-20 bg-black/20 rounded-full w-12 h-12 flex items-center justify-center">&#8249;</button>
        <button x-show="images.length > 1" @click="nextImage" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/80 hover:text-white text-4xl z-20 bg-black/20 rounded-full w-12 h-12 flex items-center justify-center">&#8250;</button>
    </div>
</div>
@include('projects.partials.image-measurement-batch-modal')

@endsection

@push('scripts')
{{-- @include('requests.partials.request-card-scripts') --}}

{{-- Dropzone.jsライブラリを読み込みます --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

{{-- Dropzoneの自動検出を、DOM読み込みより「前」に無効化します --}}
<script>
    // Dropzoneの自動検出をグローバルに無効化
    Dropzone.autoDiscover = false;
</script>

<script>
    // グローバルスコープに関数を定義 (一度だけ実行されるようにする)
    if (typeof window.setupTaskToggle !== 'function') {
        window.setupTaskToggle = function(tableContainerId) {
            const tableContainer = document.getElementById(tableContainerId);
            if (!tableContainer) {
                return;
            }
            if (tableContainer.dataset.taskToggleInitialized === 'true') {
                return;
            }
            tableContainer.dataset.taskToggleInitialized = 'true';

            tableContainer.addEventListener('click', function (event) {
                const toggleTrigger = event.target.closest('.task-toggle-trigger');

                if (toggleTrigger && tableContainer.contains(toggleTrigger)) {
                    event.preventDefault();
                    const taskId = toggleTrigger.dataset.taskId;
                    const icon = toggleTrigger.querySelector('.toggle-icon');
                    const isExpanded = toggleTrigger.getAttribute('aria-expanded') === 'true';

                    if (isExpanded) {
                        if(icon) {
                            icon.classList.remove('fa-chevron-down');
                            icon.classList.add('fa-chevron-right');
                        }
                        toggleTrigger.setAttribute('aria-expanded', 'false');
                    } else {
                        if(icon) {
                            icon.classList.remove('fa-chevron-right');
                            icon.classList.add('fa-chevron-down');
                        }
                        toggleTrigger.setAttribute('aria-expanded', 'true');
                    }
                    window.toggleChildRowsInTable(tableContainer, taskId, !isExpanded);
                }
            });
        };

        window.toggleChildRowsInTable = function(tableContainer, parentId, show) {
            const childRows = tableContainer.querySelectorAll('tr.child-row.child-of-' + parentId);
            childRows.forEach(row => {
                row.style.display = show ? '' : 'none';

                const currentTaskId = row.dataset.taskId;
                const nestedToggleTrigger = tableContainer.querySelector('.task-toggle-trigger[data-task-id="' + currentTaskId + '"]');

                if (!show) { // 親を閉じるとき、その子も全て閉じる
                    if (nestedToggleTrigger && nestedToggleTrigger.getAttribute('aria-expanded') === 'true') {
                        const nestedIcon = nestedToggleTrigger.querySelector('.toggle-icon');
                        if (nestedIcon) {
                            nestedIcon.classList.remove('fa-chevron-down');
                            nestedIcon.classList.add('fa-chevron-right');
                        }
                        nestedToggleTrigger.setAttribute('aria-expanded', 'false');
                        // 子の子も再帰的に閉じる
                        window.toggleChildRowsInTable(tableContainer, currentTaskId, false);
                    }
                } else { // 親を開くとき
                    // 子が以前に展開状態('aria-expanded' === 'true')であった場合、その子の下も再帰的に開く
                    if (nestedToggleTrigger && nestedToggleTrigger.getAttribute('aria-expanded') === 'true') {
                         window.toggleChildRowsInTable(tableContainer, currentTaskId, true);
                    }
                }
            });
        };
    }

    // DOMContentLoaded ですべての初期化処理を実行
    document.addEventListener('DOMContentLoaded', function() {
        // 案件全体のタスクテーブルのトグル機能を初期化
        if (typeof window.setupTaskToggle === 'function') {
            window.setupTaskToggle('project-tasks-table');
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // --- ★ 並び替え & ソート機能 ---

        // SortableJSの初期化
        document.querySelectorAll('.sortable-list').forEach(list => {
            new Sortable(list, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                handle: '.drag-handle', // ドラッグハンドルを指定
            });
        });

        // 「並び順を保存」ボタンのイベントリスナー
        document.querySelectorAll('.save-order-btn').forEach(button => {
            button.addEventListener('click', function() {
                const targetListSelector = this.dataset.targetList;
                const url = this.dataset.url;
                const list = document.querySelector(targetListSelector);

                if (!list) return;

                const sortableInstance = Sortable.get(list);
                const ids = sortableInstance.toArray();

                // ローディング表示
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>保存中...';

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ ids: ids })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                    } else {
                        alert('エラー: ' + (data.message || '並び順の保存に失敗しました。'));
                    }
                })
                .catch(err => {
                    console.error('Save order error:', err);
                    alert('通信エラーが発生しました。');
                })
                .finally(() => {
                    // ボタンを元に戻す
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save mr-2"></i>並び順を保存';
                });
            });
        });

        // テーブルヘッダーのソート機能
        document.querySelectorAll('.sortable-header').forEach(header => {
            header.addEventListener('click', function() {
                const tableBody = this.closest('table').querySelector('tbody');
                if (!tableBody.rows.length) return;

                const rows = Array.from(tableBody.querySelectorAll('tr'));
                const cellIndex = this.cellIndex;
                const sortType = this.dataset.sortType || 'string';

                // 現在のソート方向を取得・トグル
                let direction = this.dataset.sortDirection === 'desc' ? 'asc' : 'desc';

                // 他のヘッダーのソート状態をリセット
                this.closest('thead').querySelectorAll('.sortable-header').forEach(h => {
                    h.removeAttribute('data-sort-direction');
                    h.querySelector('.sort-icon').className = 'fas fa-sort sort-icon text-gray-400';
                });

                // 現在のヘッダーのソート状態をセット
                this.dataset.sortDirection = direction;
                this.querySelector('.sort-icon').className = `fas fa-sort-${direction === 'asc' ? 'up' : 'down'} sort-icon`;

                // 行の並び替え
                rows.sort((a, b) => {
                    const cellA = a.cells[cellIndex];
                    const cellB = b.cells[cellIndex];

                    const valA = cellA.dataset.sortValue || cellA.textContent.trim();
                    const valB = cellB.dataset.sortValue || cellB.textContent.trim();

                    let comparison = 0;
                    if (sortType === 'numeric') {
                        comparison = (parseFloat(valA) || 0) - (parseFloat(valB) || 0);
                    } else if (sortType === 'date') {
                        comparison = new Date(valA) - new Date(b);
                    } else {
                        comparison = valA.localeCompare(valB, 'ja', { numeric: true });
                    }

                    return direction === 'asc' ? comparison : -comparison;
                });

                // DOMにソート後の行を再追加
                tableBody.append(...rows);
            });
        });

        // 完成データ用Dropzone
        document.querySelectorAll('.completion-dropzone').forEach(dropzoneElement => {
            const projectId = dropzoneElement.dataset.projectId;
            const taskId = dropzoneElement.dataset.taskId;
            const dropzoneUrl = `/projects/${projectId}/tasks/${taskId}/files`;

            const myDropzone = new Dropzone(dropzoneElement, {
                url: dropzoneUrl,
                paramName: "file",
                maxFilesize: 100, // 100MB
                acceptedFiles: "image/*,application/pdf,.doc,.docx,.xls,.xlsx,.zip,txt",
                addRemoveLinks: false,
                headers: { 'X-CSRF-TOKEN': csrfToken },
                dictDefaultMessage: dropzoneElement.querySelector('.dz-message').innerHTML,
                init: function() {
                    this.on("success", function(file, response) {
                        if (response.success && response.html) {
                            const fileListContainer = document.getElementById(`file-list-container-${taskId}`);
                            if (fileListContainer) {
                                fileListContainer.innerHTML = response.html;
                                initializeDynamicDeleteButtons();
                            }
                        }
                        this.removeFile(file);
                    });
                    this.on("error", function(file, message) {
                        let errorMessage = "アップロードに失敗しました。";
                        if (typeof message === 'string') {
                            errorMessage += message;
                        } else if (message.error) {
                            errorMessage += message.error;
                        } else if (message.message) {
                            errorMessage += message.message;
                        }
                        alert(errorMessage);
                        this.removeFile(file);
                    });
                }
            });
        });

        // ファイル削除ボタンの初期化
        function initializeDynamicDeleteButtons() {
            document.querySelectorAll('.completion-file-delete-btn, .folder-file-delete-btn').forEach(button => {
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);

                newButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!confirm('このファイルを削除しますか？')) return;

                    const url = this.dataset.url;
                    const fileId = this.dataset.fileId;

                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const fileItem = document.getElementById(`folder-file-item-${fileId}`);
                            if (fileItem) fileItem.remove();
                        } else {
                            alert('ファイルの削除に失敗しました: ' + (data.message || '不明なエラー'));
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting file:', error);
                        alert('ファイルの削除中にエラーが発生しました。');
                    });
                });
            });
        }
        initializeDynamicDeleteButtons();

        //「完了を表示/非表示」のAJAX処理
        document.body.addEventListener('click', function(event) {
            const toggleButton = event.target.closest('[id^="toggle-completed-tasks-btn-"]');
            if (!toggleButton) {
                return;
            }
            event.preventDefault();
            const containerId = toggleButton.dataset.containerId;
            const container = document.getElementById(containerId);
            const url = toggleButton.href;
            if (!container) {
                console.error('Target container not found:', containerId);
                return;
            }
            container.style.opacity = '0.5';
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok.');
                return response.json();
            })
            .then(data => {
                if (data.html) {
                    container.innerHTML = data.html;

                    // 1. タイマーUI（時間記録）を再描画
                    if (typeof window.initializeWorkTimers === 'function') {
                        window.initializeWorkTimers();
                    }

                    // 2. 担当者編集やステータス変更機能などを再初期化
                    if (typeof window.initTasksIndex === 'function') {
                        window.initTasksIndex();
                    }

                    // 3. 工程の親子トグル機能を再初期化
                    const newTable = container.querySelector('table');
                    if (newTable && typeof window.setupTaskToggle === 'function') {
                        // setupTaskToggleは初期化済みフラグを見ているため、一度解除する
                        container.dataset.taskToggleInitialized = 'false';
                        window.setupTaskToggle(newTable.id);
                    }

                }
            })
            .catch(error => {
                console.error('Error fetching updated task list:', error);
                alert('工程リストの更新に失敗しました。');
            })
            .finally(() => {
                if (container) container.style.opacity = '1';
            });
        });

        // キャラクター並び替えの初期化
        const characterList = document.getElementById('character-list');
        if (characterList) {
            const saveBtn = document.getElementById('save-character-order-btn');
            const projectId = document.getElementById('project-show-main-container').dataset.projectId;
            const sortable = new Sortable(characterList, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                handle: '.drag-handle',
                onUpdate: function () {
                    saveBtn.classList.remove('hidden');
                }
            });
            saveBtn.addEventListener('click', function() {
                let ids = sortable.toArray().map(id => parseInt(id, 10));
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                fetch(`/projects/${projectId}/characters/update-order`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ ids: ids })
                })
                .then(res => res.ok ? res.json() : res.json().then(err => Promise.reject(err)))
                .then(data => {
                    if(data.success) {
                        alert(data.message || '並び順を保存しました。');
                        this.classList.add('hidden');
                    } else {
                        throw new Error(data.message || '並び順の保存に失敗しました。');
                    }
                })
                .catch(err => {
                    alert('並び順の保存中にエラーが発生しました。');
                })
                .finally(() => {
                    this.innerHTML = '<i class="fas fa-save mr-1"></i>並び順を保存';
                    this.disabled = false;
                });
            });
        }
    });
</script>

@endpush
