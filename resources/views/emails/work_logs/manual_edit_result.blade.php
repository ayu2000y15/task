<p>作業ログ手修正申請の結果通知です。</p>
<p>タスク: {{ $manualLog->task->title ?? '-' }}</p>
<p>開始: {{ $manualLog->start_time }}</p>
<p>終了: {{ $manualLog->end_time }}</p>
<p>メモ: {{ $manualLog->memo }}</p>
@if($approved)
    <p style="color:green;">申請が承認されました。</p>
@else
    <p style="color:red;">申請が拒否されました。</p>
    <p>理由: {{ $manualLog->edit_reject_reason }}</p>
@endif
