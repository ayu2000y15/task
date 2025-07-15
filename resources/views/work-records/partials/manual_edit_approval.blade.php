<div class="max-w-xl mx-auto mt-8 bg-white p-6 rounded shadow">
    <h2 class="text-lg font-bold mb-4">作業ログ手修正 承認/拒否</h2>
    <div class="mb-4">
        <div><b>ユーザー:</b> {{ $manualLog->user->name }}</div>
        <div><b>タスク:</b> {{ $manualLog->task->title ?? '-' }}</div>
        <div><b>開始:</b> {{ $manualLog->start_time }}</div>
        <div><b>終了:</b> {{ $manualLog->end_time }}</div>
        <div><b>メモ:</b> {{ $manualLog->memo }}</div>
    </div>
    <form method="POST" action="{{ route('work-logs.manual-edit-approve', $manualLog->id) }}" class="inline-block mr-2">
        @csrf
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">承認</button>
    </form>
    <form method="POST" action="{{ route('work-logs.manual-edit-reject', $manualLog->id) }}" class="inline-block">
        @csrf
        <input type="text" name="reason" placeholder="拒否理由" class="form-input mr-2" required>
        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">拒否</button>
    </form>
</div>