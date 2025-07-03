@component('mail::message')
# シフト変更申請のお知らせ

{{ $greeting }}

{{ $requesterName }} さんから以下の内容でシフト変更の申請がありました。
内容を確認し、承認または否認の処理を行ってください。

---

**申請者:** {{ $requesterName }}
**対象日:** {{ $requestDate }}

**申請内容:**
@switch($shiftRequest->requested_type)
    @case('work')
        時間変更: {{ \Carbon\Carbon::parse($shiftRequest->requested_start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shiftRequest->requested_end_time)->format('H:i') }}
        @break
    @case('location_only')
        場所変更: {{ $shiftRequest->requested_location === 'remote' ? '在宅' : '出勤' }}
        @break
    @case('full_day_off')
        全休: {{ $shiftRequest->requested_name }}
        @break
    @case('am_off')
        午前休: {{ $shiftRequest->requested_name }}
        @break
    @case('pm_off')
        午後休: {{ $shiftRequest->requested_name }}
        @break
    @case('clear')
        設定クリア
        @break
@endswitch

**申請理由:**
{{ $shiftRequest->reason }}

@if($shiftRequest->requested_notes)
**メモ:**
{{ $shiftRequest->requested_notes }}
@endif

---

@component('mail::button', ['url' => $url])
申請を確認する
@endcomponent

よろしくお願いいたします。
<br>
{{ config('app.name') }}
@endcomponent
