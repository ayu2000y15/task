@extends('layouts.app')

@section('title', '案件詳細 - ' . $project->title)

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" id="project-show-main-container" data-project-id="{{ $project->id }}">

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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            {{-- 案件情報カード --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center" style="background: linear-gradient(135deg, {{ $project->color ?? '#6c757d' }}1A, {{ $project->color ?? '#6c757d' }}0A); border-left: 4px solid {{ $project->color ?? '#6c757d' }};">
                    <i class="fas fa-info-circle mr-2" style="color: {{ $project->color ?? '#6c757d' }};"></i>
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-0">案件基本情報</h5>
                </div>
                <div class="p-5 space-y-3">
                    {{-- 専用カラムの情報を直接表示 --}}
                    <div class="flex justify-between items-start"><span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">案件名</span><span class="text-sm text-gray-700 dark:text-gray-300 text-right flex-1 whitespace-pre-wrap break-words">{{ $project->title }}</span></div>
                    @if($project->series_title)<div class="flex justify-between items-start"><span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">作品名</span><span class="text-sm text-gray-700 dark:text-gray-300 text-right flex-1 whitespace-pre-wrap break-words">{{ $project->series_title }}</span></div>@endif
                    @if($project->client_name)<div class="flex justify-between items-start"><span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">依頼主名</span><span class="text-sm text-gray-700 dark:text-gray-300 text-right flex-1 whitespace-pre-wrap break-words">{{ $project->client_name }}</span></div>@endif
                    <div class="flex justify-between items-start"><span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">期間</span><span class="text-sm text-gray-700 dark:text-gray-300 text-right">{{ $project->start_date ? $project->start_date->format('Y/m/d') : '-' }} 〜 {{ $project->end_date ? $project->end_date->format('Y/m/d') : '-' }}</span></div>
                    @if($project->description)
                        <div class="flex justify-between items-start"><span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">備考</span><p class="text-sm text-gray-700 dark:text-gray-300 text-right whitespace-pre-wrap break-words flex-1">{{ $project->description }}</p></div>
                    @endif

                    {{-- 納品フラグ --}}
                    <div class="flex justify-between items-center min-h-[2.5rem]"> {{-- 高さを確保 --}}
                        <div class="flex items-center space-x-2"> {{-- ラベルとアイコンの間隔調整 --}}
                            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">納品状況</span>
                            @php
                                $deliveryFlagValue = $project->delivery_flag ?? '0';
                                $deliveryIcon = $deliveryFlagValue == '1' ? 'fa-check-circle' : 'fa-truck';
                                $deliveryIconColor = $deliveryFlagValue == '1' ? 'text-green-500 dark:text-green-400' : 'text-yellow-500 dark:text-yellow-400';
                                $deliveryTooltip = $deliveryFlagValue == '1' ? '納品済み' : '未納品';
                            @endphp
                            <span id="project_delivery_flag_icon_{{ $project->id }}" title="{{ $deliveryTooltip }}" class="text-base"> {{-- アイコンサイズ調整 --}}
                                <i class="fas {{ $deliveryIcon }} {{ $deliveryIconColor }}" style="margin-right: 4px;"></i>
                            </span>
                        </div>
                        @can('update', $project)
                            <select name="delivery_flag" id="project_delivery_flag_select_{{ $project->id }}"
                                    class="project-flag-select form-select form-select-sm text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 rounded-md shadow-sm ml-auto w-32 sm:w-36" {{-- 幅調整 --}}
                                    data-project-id="{{ $project->id }}" data-url="{{ route('projects.updateDeliveryFlag', $project) }}"
                                    data-icon-target="project_delivery_flag_icon_{{ $project->id }}"
                                    data-status-target-icon="project_status_icon_{{ $project->id }}"
                                    data-status-target-select="project_status_select_{{ $project->id }}">
                                <option value="0" {{ $deliveryFlagValue == '0' ? 'selected' : '' }}>未納品</option>
                                <option value="1" {{ $deliveryFlagValue == '1' ? 'selected' : '' }}>納品済み</option>
                            </select>
                        @endcan
                    </div>

                    {{-- 支払いフラグ --}}
                    <div class="flex justify-between items-center min-h-[2.5rem]">
                         @php
                            $paymentFlagOptions = ['' => '未設定'] + (\App\Models\Project::PAYMENT_FLAG_OPTIONS ?? []);
                            $paymentFlagIcons = [
                                'Pending'        => 'fa-clock text-yellow-500 dark:text-yellow-400', 'Processing'     => 'fa-spinner fa-spin text-blue-500 dark:text-blue-400',
                                'Completed'      => 'fa-check-circle text-green-500 dark:text-green-400', 'Partially Paid' => 'fa-adjust text-orange-500 dark:text-orange-400',
                                'Overdue'        => 'fa-exclamation-triangle text-red-500 dark:text-red-400', 'Cancelled'      => 'fa-ban text-gray-500 dark:text-gray-400',
                                'Refunded'       => 'fa-undo text-purple-500 dark:text-purple-400', 'On Hold'        => 'fa-pause-circle text-indigo-500 dark:text-indigo-400',
                                ''               => 'fa-question-circle text-gray-400 dark:text-gray-500',
                            ];
                            $currentPaymentFlag = $project->payment_flag ?? '';
                            $paymentFlagTooltip = $paymentFlagOptions[$currentPaymentFlag] ?? $currentPaymentFlag;
                            $paymentFlagIconClass = $paymentFlagIcons[$currentPaymentFlag] ?? $paymentFlagIcons[''];
                        @endphp
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">支払状況</span>
                            <span id="project_payment_flag_icon_{{ $project->id }}" title="{{ $paymentFlagTooltip }}" class="text-base">
                                <i class="fas {{ $paymentFlagIconClass }}" style="margin-right: 4px;"></i>
                            </span>
                        </div>
                        @can('update', $project)
                            <select name="payment_flag" id="project_payment_flag_select_{{ $project->id }}"
                                    class="project-flag-select form-select form-select-sm text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 rounded-md shadow-sm ml-auto w-32 sm:w-36"
                                    data-project-id="{{ $project->id }}" data-url="{{ route('projects.updatePaymentFlag', $project) }}"
                                    data-icon-target="project_payment_flag_icon_{{ $project->id }}"
                                    data-status-target-icon="project_status_icon_{{ $project->id }}"
                                    data-status-target-select="project_status_select_{{ $project->id }}">
                                @foreach($paymentFlagOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $currentPaymentFlag == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        @endcan
                    </div>

                    @if($project->payment)<div class="flex justify-between items-start"><span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">支払条件</span><p class="text-sm text-gray-700 dark:text-gray-300 text-right whitespace-pre-wrap break-words flex-1">{{ $project->payment }}</p></div>@endif

                    {{-- プロジェクトステータス --}}
                    <div class="flex justify-between items-center min-h-[2.5rem]">
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
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">案件ステータス</span>
                            <span id="project_status_icon_{{ $project->id }}" title="{{ $projectStatusTooltip }}" class="text-base">
                                <i class="fas {{ $projectStatusIconClass }}" style="margin-right: 4px;"></i>
                            </span>
                        </div>
                        @can('update', $project)
                            <select name="status" id="project_status_select_{{ $project->id }}"
                                    class="project-status-select form-select form-select-sm text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 rounded-md shadow-sm ml-auto w-32 sm:w-36"
                                    data-project-id="{{ $project->id }}" data-url="{{ route('projects.updateStatus', $project) }}"
                                    data-icon-target="project_status_icon_{{ $project->id }}">
                                @foreach($projectStatusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $currentProjectStatus == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        @endcan
                    </div>

                    {{-- プロジェクト固有の form_definitions に基づく追加情報 (カスタム項目) --}}
                    @can('view', $project)
                    @if(!empty($customFormFields) && count($customFormFields) > 0)
                        @if(collect($customFormFields)->isNotEmpty())
                            <hr class="dark:border-gray-600 my-3">
                            <h6 class="text-sm font-semibold text-gray-600 dark:text-gray-400 pt-1 -mb-1">追加情報（カスタム項目）</h6>
                            @foreach($customFormFields as $field)
                                @php
                                    $fieldName = $field['name'];
                                    $fieldLabel = $field['label'];
                                    $fieldType = $field['type'];
                                    $value = $project->getCustomAttributeValue($fieldName);
                                @endphp
                                <div class="flex justify-between items-start">
                                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-28 flex-shrink-0">{{ $fieldLabel }}</span>
                                    @switch($fieldType)
                                        @case('checkbox')
                                            <span class="text-sm text-gray-700 dark:text-gray-300 text-right">
                                                {{ filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'はい' : 'いいえ' }}
                                            </span>
                                            @break
                                        @case('color')
                                            <div class="flex items-center justify-end flex-1">
                                                <span class="w-5 h-5 rounded-full inline-block border dark:border-gray-600 shadow-sm mr-2" style="background-color: {{ $value ?? '#ffffff' }};"></span>
                                                <span class="text-sm text-gray-700 dark:text-gray-300 text-right">{{ $value ?? '-' }}</span>
                                            </div>
                                            @break
                                        @case('date')
                                            <span class="text-sm text-gray-700 dark:text-gray-300 text-right">
                                                {{ $value ? \Carbon\Carbon::parse($value)->format('Y/m/d') : '-' }}
                                            </span>
                                            @break
                                        @case('textarea')
                                            <p class="text-sm text-gray-700 dark:text-gray-300 text-right whitespace-pre-wrap break-words flex-1 max-h-40 overflow-y-auto">{{ $value ?? '-' }}</p>
                                            @break
                                        @case('file'):
                                        @case('file_multiple'):
                                            @if(is_array($value) && !empty($value)) {{-- 配列であれば、複数ファイルまたは配列形式の単一ファイルとして処理 --}}
                                                <div class="text-sm text-gray-700 dark:text-gray-300 text-right flex-1 max-h-40 overflow-y-auto">
                                                    <ul class="list-none space-y-1">
                                                        @foreach($value as $fileInfo)
                                                            @if(is_array($fileInfo) && isset($fileInfo['path']) && isset($fileInfo['original_name']))
                                                                {{-- 標準的な file_multiple の各ファイル情報 --}}
                                                                <li>
                                                                    <a href="{{ Storage::url($fileInfo['path']) }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400 dark:hover:text-blue-300" title="ダウンロード: {{ $fileInfo['original_name'] }}">
                                                                        <i class="fas fa-file-download mr-1"></i>{{ Str::limit($fileInfo['original_name'], 25) }}
                                                                    </a>
                                                                    <span class="text-gray-400 text-xs">({{ \Illuminate\Support\Number::fileSize($fileInfo['size'] ?? 0) }})</span>
                                                                </li>
                                                            @elseif(is_string($fileInfo))
                                                                {{-- 古い形式などで、ファイルパス文字列が直接配列に含まれる場合 --}}
                                                                <li>
                                                                    <a href="{{ Storage::url($fileInfo) }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400 dark:hover:text-blue-300">{{ Str::limit(basename($fileInfo), 25) }}</a>
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @elseif(is_string($value) && !empty($value)) {{-- 文字列であれば、単一ファイル（パス文字列）として処理 --}}
                                                <span class="text-sm text-gray-700 dark:text-gray-300 text-right truncate hover:whitespace-normal" title="{{ basename($value) }}">{{ Str::limit(basename($value), 20) }}</span>
                                            @else {{-- $valueが空、null、またはその他の予期しない型の場合 --}}
                                                <span class="text-sm text-gray-700 dark:text-gray-300 text-right">-</span>
                                            @endif
                                            @break
                                        @case('url')
                                            <div class="text-sm text-gray-700 dark:text-gray-300 text-right flex-1">
                                                @if($value && filter_var($value, FILTER_VALIDATE_URL))
                                                    <a href="{{ $value }}" target="_blank" rel="noopener noreferrer"
                                                       class="text-blue-600 hover:underline dark:text-blue-400 dark:hover:text-blue-300 break-all whitespace-normal">
                                                        {{ $value }}
                                                    </a>
                                                @elseif($value)
                                                    <p class="break-words whitespace-normal">{{ $value }}</p>
                                                @else
                                                    <span>-</span>
                                                @endif
                                            </div>
                                            @break
                                        @default
                                            <p class="text-sm text-gray-700 dark:text-gray-300 text-right whitespace-pre-wrap break-words flex-1 max-h-40 overflow-y-auto">{{ $value ?? '-' }}</p>
                                    @endswitch
                                </div>
                            @endforeach
                        @endif
                    @endif
                    @endcan

                    <hr class="dark:border-gray-700 my-3">
                    {{-- 進捗バー --}}
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">進捗状況</span>
                        @php
                            $progressTasks = $project->tasks()->where('is_folder', false)->where('is_milestone', false)->get();
                            $totalProgressTasks = $progressTasks->count();
                            $completedProgressTasks = $progressTasks->where('status', 'completed')->count();
                            $progress = $totalProgressTasks > 0 ? round(($completedProgressTasks / $totalProgressTasks) * 100) : 0;
                        @endphp
                        <div class="text-right">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-1">
                                <div class="h-2 rounded-full" style="width: {{ $progress }}%; background-color: {{ $project->color ?? '#6c757d' }};"></div>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background-color: {{ $project->color ?? '#6c757d' }}; color:white;">{{ $progress }}%</span>
                            <small class="text-gray-500 dark:text-gray-400 ml-1"> ({{ $completedProgressTasks }}/{{ $totalProgressTasks }} 工程完了)</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 統計情報セクション --}}
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center">
                    <i class="fas fa-chart-pie mr-2 text-gray-600 dark:text-gray-300"></i>
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-0">統計情報</h5>
                </div>
                <div class="p-5">
                    {{-- 統計情報の内容は変更なし --}}
                    <h6 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">ステータス別工程数 <span class="text-gray-400 normal-case">(フォルダ・重要納期を除く)</span></h6>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md"><div class="text-xl font-bold text-gray-500 dark:text-gray-400">{{ $project->tasks()->where('is_milestone', false)->where('is_folder', false)->where('status', 'not_started')->count() }}</div><small class="text-gray-500 dark:text-gray-400">未着手</small></div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md"><div class="text-xl font-bold text-blue-500 dark:text-blue-400">{{ $project->tasks()->where('is_milestone', false)->where('is_folder', false)->where('status', 'in_progress')->count() }}</div><small class="text-gray-500 dark:text-gray-400">進行中</small></div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md"><div class="text-xl font-bold text-green-500 dark:text-green-400">{{ $project->tasks()->where('is_milestone', false)->where('is_folder', false)->where('status', 'completed')->count() }}</div><small class="text-gray-500 dark:text-gray-400">完了</small></div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md"><div class="text-xl font-bold text-yellow-500 dark:text-yellow-400">{{ $project->tasks()->where('is_milestone', false)->where('is_folder', false)->where('status', 'on_hold')->count() }}</div><small class="text-gray-500 dark:text-gray-400">保留中</small></div>
                    </div>
                    <h6 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">タイプ別工程数</h6>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm"><span class="text-gray-600 dark:text-gray-300">通常工程</span><span class="px-2 py-0.5 text-xs font-semibold text-blue-800 bg-blue-100 dark:bg-blue-700 dark:text-blue-200 rounded-full">{{ $project->tasks->where('is_milestone', false)->where('is_folder', false)->count() }}</span></div>
                        <div class="flex justify-between items-center text-sm"><span class="text-gray-600 dark:text-gray-300">重要納期</span><span class="px-2 py-0.5 text-xs font-semibold text-red-800 bg-red-100 dark:bg-red-700 dark:text-red-200 rounded-full">{{ $project->tasks->where('is_milestone', true)->count() }}</span></div>
                        <div class="flex justify-between items-center text-sm"><span class="text-gray-600 dark:text-gray-300">フォルダ</span><span class="px-2 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 dark:bg-gray-600 dark:text-gray-200 rounded-full">{{ $project->tasks->where('is_folder', true)->count() }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 右側カラム (登場キャラクターカード、案件全体の工程一覧カード) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- 登場キャラクターカード --}}
            <div x-data="{
                    expanded: true,
                    activeCharacterTab: {},
                    initCharacterTabs() {
                        @if($project->characters->isNotEmpty())
                            this.activeCharacterTab[{{ $project->characters->first()->id }}] = 'measurements-{{ $project->characters->first()->id }}';
                            @foreach($project->characters->skip(1) as $character)
                                this.activeCharacterTab[{{ $character->id }}] = 'measurements-{{ $character->id }}';
                            @endforeach
                        @endif
                    }
                 }"
                 x-init="initCharacterTabs()"
                 class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center cursor-pointer" @click="expanded = !expanded">
                    <div class="flex items-center"> <i class="fas fa-users mr-2 text-gray-600 dark:text-gray-300"></i> <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-0">登場キャラクター</h5> </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center"> <span class="mr-2">{{ $project->characters->count() }}体</span> @if($project->characters->count() > 0) <span class="mr-2 hidden sm:inline">合計コスト: {{ number_format($project->characters->sum(function ($char) { return $char->costs->sum('amount'); })) }}円</span> @endif <i class="fas" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i> </div>
                </div>
                <div x-show="expanded" x-collapse class="p-1 sm:p-3 md:p-5 border-t border-gray-200 dark:border-gray-700">
                    @can('update', $project)
                        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                            <h6 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-3"><i class="fas fa-plus mr-2"></i>新しいキャラクターを追加</h6>
                            <form action="{{ route('projects.characters.store', $project) }}" method="POST">
                                @csrf
                                <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 items-end">
                                    <div class="sm:col-span-2">
                                        <label for="new_character_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">キャラクター名 <span class="text-red-500">*</span></label>
                                        <input type="text" name="name" id="new_character_name" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200" placeholder="例: 主人公" required value="{{ old('name') }}">
                                        @error('name', 'characterCreation')<span class="text-xs text-red-500">{{ $message }}</span>@else @error('name')<span class="text-xs text-red-500">{{ $message }}</span>@enderror @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="new_character_description" class="block text-xs font-medium text-gray-700 dark:text-gray-300">備考</label>
                                        <input type="text" name="description" id="new_character_description" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200" placeholder="例: メイン衣装" value="{{ old('description') }}">
                                        @error('description', 'characterCreation')<span class="text-xs text-red-500">{{ $message }}</span>@else @error('description')<span class="text-xs text-red-500">{{ $message }}</span>@enderror @enderror
                                    </div>
                                    <div class="sm:col-span-1">
                                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-800 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                            <i class="fas fa-plus mr-1 sm:mr-2"></i><span class="hidden sm:inline">追加</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endcan
                    @if($project->characters->isEmpty())
                        <div class="text-center py-10"> <i class="fas fa-user-plus text-4xl text-gray-400 dark:text-gray-500 mb-3"></i> <h6 class="text-md font-semibold text-gray-700 dark:text-gray-300">キャラクターが登録されていません</h6> <p class="text-sm text-gray-500 dark:text-gray-400">上のフォームから新しいキャラクターを追加してください。</p> </div>
                    @else
                        <div class="space-y-6">
                            @foreach($project->characters as $character)
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden" >
                                    <div class="px-5 py-3 flex justify-between items-center border-b dark:border-gray-700" style="background: linear-gradient(135deg, {{ $project->color ?? '#6c757d' }}1A, {{ $project->color ?? '#6c757d' }}0A); border-left: 3px solid {{ $project->color ?? '#6c757d' }};">
                                        <h6 class="text-md font-semibold text-gray-800 dark:text-gray-100 truncate" title="{{ $character->name }}"> <i class="fas fa-user mr-2" style="color: {{ $project->color ?? '#6c757d' }};"></i> {{ $character->name }} </h6>
                                        @can('update', $project)
                                        <div class="flex space-x-1 flex-shrink-0">
                                            <x-icon-button :href="route('characters.edit', $character)" icon="fas fa-edit" title="編集" color="blue" size="sm" />
                                            <form action="{{ route('characters.destroy', $character) }}" method="POST" class="inline-block"> <x-icon-button icon="fas fa-trash" title="削除" color="red" size="sm" type="submit" method="DELETE" :confirm="'このキャラクターを削除しますか？関連データも全て削除されます。'" /> </form>
                                        </div>
                                        @endcan
                                    </div>
                                    @if($character->description) <div class="px-5 py-2 text-xs text-gray-600 dark:text-gray-400 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50"> {{ $character->description }} </div> @endif
                                    <div class="p-1 sm:p-2">
                                        <div class="border-b border-gray-200 dark:border-gray-700">
                                            <nav class="-mb-px flex space-x-2 sm:space-x-4 overflow-x-auto text-xs sm:text-sm" aria-label="Character Tabs for {{ $character->id }}">
                                                @can('manageMeasurements', $project)<button @click="activeCharacterTab[{{ $character->id }}] = 'measurements-{{ $character->id }}'" :class="{ 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-semibold': activeCharacterTab[{{ $character->id }}] === 'measurements-{{ $character->id }}', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeCharacterTab[{{ $character->id }}] !== 'measurements-{{ $character->id }}' }" class="whitespace-nowrap py-2 px-2 sm:px-3 border-b-2 font-medium focus:outline-none"><i class="fas fa-ruler mr-1"></i> 採寸</button>@endcan
                                                @can('manageMaterials', $project)<button @click="activeCharacterTab[{{ $character->id }}] = 'materials-{{ $character->id }}'" :class="{ 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-semibold': activeCharacterTab[{{ $character->id }}] === 'materials-{{ $character->id }}', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeCharacterTab[{{ $character->id }}] !== 'materials-{{ $character->id }}' }" class="whitespace-nowrap py-2 px-2 sm:px-3 border-b-2 font-medium focus:outline-none"><i class="fas fa-box mr-1"></i> 材料</button>@endcan
                                                @can('viewAny', App\Models\Task::class)<button @click="activeCharacterTab[{{ $character->id }}] = 'tasks-{{ $character->id }}'" :class="{ 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-semibold': activeCharacterTab[{{ $character->id }}] === 'tasks-{{ $character->id }}', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeCharacterTab[{{ $character->id }}] !== 'tasks-{{ $character->id }}' }" class="whitespace-nowrap py-2 px-2 sm:px-3 border-b-2 font-medium focus:outline-none"><i class="fas fa-tasks mr-1"></i> 工程 <span class="ml-1 text-xs">({{ $character->tasks->count() }})</span></button>@endcan
                                                @can('manageCosts', $project)<button @click="activeCharacterTab[{{ $character->id }}] = 'costs-{{ $character->id }}'" :class="{ 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-semibold': activeCharacterTab[{{ $character->id }}] === 'costs-{{ $character->id }}', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeCharacterTab[{{ $character->id }}] !== 'costs-{{ $character->id }}' }" class="whitespace-nowrap py-2 px-2 sm:px-3 border-b-2 font-medium focus:outline-none"><i class="fas fa-yen-sign mr-1"></i> コスト</button>@endcan
                                            </nav>
                                        </div>
                                        <div class="py-3">
                                            @can('manageMeasurements', $project)<div x-show="activeCharacterTab[{{ $character->id }}] === 'measurements-{{ $character->id }}'" id="measurements-content-{{ $character->id }}">@include('projects.partials.character-measurements-tailwind', ['character' => $character, 'project' => $project])</div>@endcan
                                            @can('manageMaterials', $project)<div x-show="activeCharacterTab[{{ $character->id }}] === 'materials-{{ $character->id }}'" id="materials-content-{{ $character->id }}" style="display: none;">@include('projects.partials.character-materials-tailwind', ['character' => $character, 'project' => $project])</div>@endcan
                                            @can('viewAny', App\Models\Task::class)<div x-show="activeCharacterTab[{{ $character->id }}] === 'tasks-{{ $character->id }}'" id="tasks-content-{{ $character->id }}" style="display: none;">@include('projects.partials.character-tasks-tailwind', ['tasks' => $character->tasks()->orderByRaw('ISNULL(start_date), start_date ASC, name ASC')->get(), 'project' => $project, 'character' => $character])</div>@endcan
                                            @can('manageCosts', $project)<div x-show="activeCharacterTab[{{ $character->id }}] === 'costs-{{ $character->id }}'" id="costs-content-{{ $character->id }}" style="display: none;" class="character-costs-list-container" data-character-id="{{ $character->id }}">@include('projects.partials.character-costs-tailwind', ['character' => $character, 'project' => $project])</div>@endcan
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
                        @include('projects.partials.projects-task-table', ['tasksToList' => $project->tasksWithoutCharacter()->orderByRaw('ISNULL(start_date), start_date ASC, name ASC')->get(), 'tableId' => 'project-tasks-table', 'projectForTable' => $project, 'isProjectTaskView' => true])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection