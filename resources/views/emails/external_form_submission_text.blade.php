{{ $formCategory->form_title }} - 新規申請
{{ config('app.name') }}に新しい申請が届きました。

----------------------------------------

■ 申請情報
申請ID: #{{ $submission->id }}
フォーム種別: {{ $formCategory->display_name }}
申請日時: {{ $submission->created_at->format('Y年n月j日 H:i') }}
ステータス: @if($submission->status === 'pending')承認待ち@elseif($submission->status === 'approved')承認済み@elseif($submission->status === 'new')新規@elseif($submission->status === 'rejected')却下@else{{ $submission->status }}@endif

----------------------------------------

■ 申請内容
@if($submission->custom_field_data)
@foreach($submission->custom_field_data as $fieldName => $value)
@if($value)
{{ $fieldName }}:
@if(is_array($value))
{{ implode(', ', $value) }}
@else
{{ $value }}
@endif

@endif
@endforeach
@else
申請内容はありません。
@endif

----------------------------------------

この申請の詳細を確認するには、以下のURLから管理画面にログインしてください。
{{ route('admin.external-submissions.show', $submission) }}

このメールは {{ config('app.name') }} から自動送信されています。
