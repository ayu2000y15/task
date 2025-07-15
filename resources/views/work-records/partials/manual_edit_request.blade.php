@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto mt-8 bg-white p-6 rounded shadow">
    <h2 class="text-lg font-bold mb-4">作業ログ手修正申請</h2>
    <form method="POST" action="{{ route('work-logs.manual-edit-request', $log->id) }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">開始時刻</label>
            <input type="datetime-local" name="start_time" value="{{ old('start_time', $log->start_time ? $log->start_time->format('Y-m-d\TH:i') : '') }}" class="form-input w-full" required>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">終了時刻</label>
            <input type="datetime-local" name="end_time" value="{{ old('end_time', $log->end_time ? $log->end_time->format('Y-m-d\TH:i') : '') }}" class="form-input w-full" required>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">メモ</label>
            <textarea name="memo" class="form-textarea w-full" rows="2">{{ old('memo', $log->memo) }}</textarea>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">申請する</button>
    </form>
</div>
@endsection
