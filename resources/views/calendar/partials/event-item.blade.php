@php
    // 表示用のアイコンとテキスト、ツールチップの内容を事前に準備
    $iconClass = 'fa-question-circle text-gray-400';
    $displayText = '';
    $tooltipLines = [];
    $eventColor = $schedule->color ?? '#9ca3af';

    switch ($schedule->type) {
        case 'task':
            $iconClass = $schedule->type === 'milestone' ? 'fa-flag text-gray-300' : 'fa-tasks text-gray-300';
            $displayText = $schedule->name;
            $startDate = \Carbon\Carbon::parse($schedule->start_date);
            $endDate = $schedule->end_date ? \Carbon\Carbon::parse($schedule->end_date) : null;
            $dateString = $startDate->format('n/j H:i') . ' ～ ' . $endDate->format('n/j H:i');

            $tooltipLines['案件'] = $schedule->project_title;
            $tooltipLines['期間'] = $dateString;
            $tooltipLines['工程'] = $schedule->name;
            if ($schedule->character) {
                $tooltipLines['キャラクター'] = $schedule->character->name;
            }
            if ($schedule->assignees->isNotEmpty()) {
                $tooltipLines['担当者'] = $schedule->assignees->pluck('name')->join(', ');
            }
            $tooltipLines['ステータス'] = \App\Models\Task::STATUS_OPTIONS[$schedule->status] ?? '未設定';
            if ($schedule->notes) {
                $tooltipLines['メモ'] = $schedule->notes;
            }
            break;
        case 'milestone':
            $iconClass = 'fa-flag text-gray-300';
            $displayText = $schedule->name;
            $startDate = \Carbon\Carbon::parse($schedule->start_date);
            $endDate = $schedule->end_date ? \Carbon\Carbon::parse($schedule->end_date) : null;
            $dateString = $startDate->format('n/j H:i') . ' ～ ' . $endDate->format('n/j H:i');

            $tooltipLines['案件'] = $schedule->project_title;
            $tooltipLines['期間'] = $dateString;
            $tooltipLines['予定'] = $schedule->name;
            if ($schedule->notes) {
                $tooltipLines['メモ'] = $schedule->notes;
            }
            break;
        case 'holiday':
            $iconClass = 'fa-glass-cheers text-gray-300';
            $displayText = $schedule->name;
            $eventColor = '#e11d48'; // 祝日の色
            $tooltipLines['祝日'] = $schedule->name;
            break;

        case 'usershift':
            $iconClass = 'fa-bed text-gray-300';
            $typeText = '';
            switch ($schedule->type_original) {
                case 'full_day_off':
                    $typeText = '全休';
                    $eventColor = '#16a34a';
                    break;
                case 'am_off':
                    $typeText = '午前休';
                    $eventColor = '#f97316';
                    break;
                case 'pm_off':
                    $typeText = '午後休';
                    $eventColor = '#ca8a04';
                    break;
            }
            $displayText = $schedule->user->name . ': ' . $typeText;
            $tooltipLines['種別'] = $typeText;
            $tooltipLines['対象者'] = $schedule->user->name;
            if ($schedule->name) {
                $tooltipLines['内容'] = $schedule->name;
            }
            break;
    }

    $positionClass = '';
    $position = $schedule->position ?? 'single'; // デフォルトは単日扱い

    switch ($position) {
        case 'single':
            $positionClass = 'rounded';
            break;
        case 'start':
            $positionClass = 'rounded-l';
            break;
        case 'end':
            $positionClass = 'rounded-r';
            break;
        case 'middle':
            $positionClass = ''; // 角丸なし
            break;
    }
@endphp

<div x-data="{ showTooltip: false }" class="relative w-full">

    {{-- ▼▼▼【修正】表示するアイテム本体。背景色や角丸スタイルを適用 --}}
    <div @mouseenter="showTooltip = true" @mouseleave="showTooltip = false"
        class="flex items-center w-full cursor-pointer px-1 py-0.5 text-white dark:text-gray-800 {{ $positionClass }}"
        style="background-color: {{ $eventColor }}">

        <span class="flex-shrink-0 w-4 text-center">
            <i class="fas {{ $iconClass }} opacity-80"></i>
        </span>
        <span class="ml-1.5 flex-grow truncate">
            {{ $displayText }}
        </span>
        @if (!empty($schedule->notes))
            <span class="flex-shrink-0 ml-1">
                <i class="fas fa-comment-alt opacity-80"></i>
            </span>
        @endif
    </div>

    {{-- ▼▼▼【修正】ツールチップのデザインを微調整 ▼▼▼ --}}
    <div x-show="showTooltip" x-cloak
        class="absolute bottom-full left-1/2 z-20 mb-2 w-max max-w-xs -translate-x-1/2 rounded-md bg-gray-900 px-3 py-2 text-xs font-normal text-white shadow-lg dark:bg-black">

        <div class="space-y-1">
            @foreach($tooltipLines as $label => $value)
                <div class="flex">
                    <span class="font-semibold mr-2 flex-shrink-0 w-16 text-right">{{ $label }}:</span>
                    <span class="whitespace-pre-wrap break-words">{{ $value }}</span>
                </div>
            @endforeach
        </div>
        <div
            class="absolute left-1/2 top-full h-0 w-0 -translate-x-1/2 border-x-8 border-t-8 border-x-transparent border-t-gray-900 dark:border-t-black">
        </div>
    </div>
</div>