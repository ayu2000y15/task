<?php

if (!function_exists('format_seconds_to_hms')) {
    /**
     * 秒数を H:i:s 形式の文字列に変換します。
     * 24時間以上の時間も正しく表示します。
     *
     * @param int $seconds 変換する秒数
     * @return string フォーマットされた時間文字列 (例: "25:30:10")
     */
    function format_seconds_to_hms(int $seconds): string
    {
        if ($seconds < 0) {
            return '00:00:00';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $secs = $seconds % 60;

        // sprintf を使用して、時間(無制限)、分(0埋め)、秒(0埋め)の形式で出力
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
