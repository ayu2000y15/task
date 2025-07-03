@extends('layouts.app')
@section('title', 'シフト変更申請一覧')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">シフト変更申請一覧 (未処理)</h1>

    @if($requests->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center text-gray-500 dark:text-gray-400">
            現在、未処理の申請はありません。
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">申請者</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">対象日</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">申請内容</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">申請理由</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($requests as $request)
                            <tr x-data="{ showRejectForm: false }">
                                <td class="px-6 py-4 whitespace-nowrap">{{ $request->user->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $request->date->format('n/j') }} ({{ $request->date->isoFormat('ddd') }})</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @switch($request->requested_type)
                                        @case('work')
                                            <div>時間変更: {{ \Carbon\Carbon::parse($request->requested_start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($request->requested_end_time)->format('H:i') }}</div>
                                            @break
                                        @case('location_only')
                                            <div>場所変更: {{ $request->requested_location === 'remote' ? '在宅' : '出勤' }}</div>
                                            @break
                                        @case('full_day_off')
                                            <div class="font-semibold text-red-600">全休: {{ $request->requested_name }}</div>
                                            @break
                                        @case('am_off')
                                        @case('pm_off')
                                            <div class="font-semibold text-yellow-600">{{ $request->requested_type === 'am_off' ? '午前休' : '午後休' }}: {{ $request->requested_name }}</div>
                                            @break
                                        @case('clear')
                                            <div class="text-gray-500">設定クリア</div>
                                            @break
                                    @endswitch
                                    @if($request->requested_notes)
                                        <div class="text-xs text-gray-500 mt-1">メモ: {{ $request->requested_notes }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 max-w-xs break-words">{{ $request->reason }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end items-center gap-2">
                                        <form action="{{ route('shift-change-requests.approve', $request) }}" method="POST" onsubmit="return confirm('この申請を承認しますか？');">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded-md text-xs hover:bg-green-700">承認</button>
                                        </form>
                                        <button @click="showRejectForm = !showRejectForm" class="px-3 py-1 bg-red-600 text-white rounded-md text-xs hover:bg-red-700">否認</button>
                                    </div>
                                    <div x-show="showRejectForm" class="mt-2 p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700" style="display: none;">
                                        <form action="{{ route('shift-change-requests.reject', $request) }}" method="POST">
                                            @csrf
                                            <label for="rejection_reason_{{ $request->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300 text-left">否認理由 (必須)</label>
                                            <textarea name="rejection_reason" id="rejection_reason_{{ $request->id }}" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-600" required></textarea>
                                            <div class="mt-2 flex justify-end gap-2">
                                                <button type="button" @click="showRejectForm = false" class="px-3 py-1 bg-gray-500 text-white rounded-md text-xs hover:bg-gray-600">中止</button>
                                                <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded-md text-xs hover:bg-red-700">送信</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection