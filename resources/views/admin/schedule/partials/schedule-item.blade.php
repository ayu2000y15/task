@php
    // (このPHPブロックは変更ありません)
    $iconClass = '';
    $displayText = '';
    $tooltipLines = [];
    switch ($schedule->type) {
        case 'work':
        case 'location_only':
            $locationText = $schedule->location === 'remote' ? '在宅' : '出勤';
            $tooltipLines['場所'] = $locationText;

            $iconClass = $schedule->location === 'remote' ? 'fa-home text-blue-500' : 'fa-building text-green-500';
            if ($schedule->type === 'work') {
                $timeText = \Carbon\Carbon::parse($schedule->start_time)->format('H:i') . '-' . \Carbon\Carbon::parse($schedule->end_time)->format('H:i');
                $displayText = $schedule->user->name . ': ' . $timeText;
                $tooltipLines['時間'] = $timeText;
            } else {
                $displayText = $schedule->user->name . ': 場所のみ変更';
                $tooltipLines['時間'] = 'デフォルト';
            }
            break;
        case 'full_day_off':
            $iconClass = 'fa-bed text-red-500';
            $displayText = $schedule->user->name . ': ' . ($schedule->name ?: '全休');
            $tooltipLines['種別'] = "全休";
            if ($schedule->name) {
                $tooltipLines['内容'] = $schedule->name;
            }
            break;
        case 'am_off':
        case 'pm_off':
            $typeText = $schedule->type === 'am_off' ? '午前休' : '午後休';
            $iconClass = 'fa-bed text-yellow-500 opacity-80';
            $displayText = $schedule->user->name . ': ' . ($schedule->name ?: $typeText);
            $tooltipLines['種別'] = $typeText;
            if ($schedule->name) {
                $tooltipLines['内容'] = $schedule->name;
            }
            break;
    }
    if (!empty($schedule->notes)) {
        $tooltipLines['メモ'] = $schedule->notes;
    }
@endphp

{{-- 外側のコンテナ --}}
<div x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false"
    class="relative w-full">

    {{-- 表示部分 --}}
    <div
        class="flex items-center w-full cursor-pointer px-1 py-0.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
        {{-- アイコン --}}
        <span class="flex-shrink-0 w-4 text-center">
            <i class="fas {{ $iconClass }}"></i>
        </span>
        {{-- 表示テキスト --}}
        <span class="ml-1.5 flex-grow truncate">
            {{ $displayText }}
        </span>
        {{-- メモアイコン --}}
        @if (!empty($schedule->notes))
            <span class="flex-shrink-0 ml-1">
                <i class="fas fa-comment-alt text-gray-400"></i>
            </span>
        @endif
    </div>

    {{-- ▼▼▼【ここから x-transition を削除】▼▼▼ --}}
    <div x-show="showTooltip" x-cloak
        class="absolute bottom-full left-1/2 z-20 mb-2 w-max max-w-xs -translate-x-1/2 rounded-md bg-gray-900 px-3 py-2 text-xs font-medium text-white shadow-lg dark:bg-black">
        {{-- ▲▲▲【修正ここまで】▲▲▲ --}}

        <div class="space-y-1">
            @foreach($tooltipLines as $label => $value)
                <div class="flex">
                    <span class="font-semibold mr-2 flex-shrink-0">{{ $label }}:</span>
                    <span class="whitespace-pre-wrap break-words">{{ $value }}</span>
                </div>
            @endforeach
        </div>
        {{-- ツールチップの矢印 --}}
        <div
            class="absolute left-1/2 top-full h-0 w-0 -translate-x-1/2 border-x-8 border-t-8 border-x-transparent border-t-gray-900 dark:border-t-black">
        </div>
    </div>
</div>