@extends('layouts.app')
@section('title', 'シフト申請履歴')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">シフト申請履歴</h1>

    @if($requests->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center text-gray-500 dark:text-gray-400">
            申請履歴はありません。
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">対象日</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">申請内容</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ステータス</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">処理者/理由</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($requests as $request)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $request->date->format('n/j') }} ({{ $request->date->isoFormat('ddd') }})</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @switch($request->requested_type)
                                        @case('work') <div>時間: {{ \Carbon\Carbon::parse($request->requested_start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($request->requested_end_time)->format('H:i') }}</div> @break
                                        @case('location_only') <div>場所: {{ $request->requested_location === 'remote' ? '在宅' : '出勤' }}</div> @break
                                        @case('full_day_off') <div class="font-semibold">全休: {{ $request->requested_name }}</div> @break
                                        @case('am_off') <div class="font-semibold">午前休: {{ $request->requested_name }}</div> @break
                                        @case('pm_off') <div class="font-semibold">午後休: {{ $request->requested_name }}</div> @break
                                        @case('clear') <div class="text-gray-500">設定クリア</div> @break
                                    @endswitch
                                    <div class="text-xs text-gray-500 mt-1">理由: {{ $request->reason }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($request->status === 'approved')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">承認済み</span>
                                    @elseif($request->status === 'rejected')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">否認</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">保留中</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($request->approver)
                                        <div>{{ $request->approver->name }}</div>
                                    @endif
                                    @if($request->status === 'rejected' && $request->rejection_reason)
                                        <div class="text-xs text-red-600 mt-1" title="否認理由">{{ $request->rejection_reason }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    {{-- 保留中の申請のみ取り下げ可能 --}}
                                    @can('delete', $request)
                                        <form action="{{ route('shift-change-requests.destroy', $request) }}" method="POST" onsubmit="return confirm('この申請を取り下げますか？');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">取り下げ</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
             <div class="mt-4">
                {{ $requests->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
