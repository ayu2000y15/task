<p>作業ログの手修正申請が届きました。</p>
<p>申請者: {{ $manualLog->user->name }}</p>
<p>タスク: {{ $manualLog->task->title ?? '-' }}</p>
<p>開始: {{ $manualLog->start_time }}</p>
<p>終了: {{ $manualLog->end_time }}</p>
<p>メモ: {{ $manualLog->memo }}</p>
<p>
    <a href="{{ url('/work-logs/'.$manualLog->id.'/manual-edit-approval') }}">承認・拒否画面へ</a>
</p>
