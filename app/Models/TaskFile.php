<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'project_id',
        'original_name',
        'filename',
        'path',
        'mime_type',
        'size',
    ];

    /**
     * このファイルが属するタスク
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * このファイルが属するプロジェクト
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * ファイルサイズを人間が読みやすい形式にフォーマット
     */
    public function formatSize()
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * ファイルタイプに応じたアイコンクラスを取得
     */
    public function getIconClass()
    {
        $mimeType = $this->mime_type;
        $extension = pathinfo($this->original_name, PATHINFO_EXTENSION);

        // 画像
        if (strpos($mimeType, 'image/') === 0) {
            return 'fa-file-image';
        }

        // PDF
        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return 'fa-file-pdf';
        }

        // Word
        if (
            strpos($mimeType, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') === 0 ||
            strpos($mimeType, 'application/msword') === 0 ||
            in_array($extension, ['doc', 'docx'])
        ) {
            return 'fa-file-word';
        }

        // Excel
        if (
            strpos($mimeType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') === 0 ||
            strpos($mimeType, 'application/vnd.ms-excel') === 0 ||
            in_array($extension, ['xls', 'xlsx'])
        ) {
            return 'fa-file-excel';
        }

        // PowerPoint
        if (
            strpos($mimeType, 'application/vnd.openxmlformats-officedocument.presentationml.presentation') === 0 ||
            strpos($mimeType, 'application/vnd.ms-powerpoint') === 0 ||
            in_array($extension, ['ppt', 'pptx'])
        ) {
            return 'fa-file-powerpoint';
        }

        // テキスト
        if (strpos($mimeType, 'text/') === 0 || in_array($extension, ['txt', 'md', 'log'])) {
            return 'fa-file-alt';
        }

        // 圧縮ファイル
        if (
            strpos($mimeType, 'application/zip') === 0 ||
            strpos($mimeType, 'application/x-rar') === 0 ||
            strpos($mimeType, 'application/x-7z-compressed') === 0 ||
            in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz'])
        ) {
            return 'fa-file-archive';
        }

        // 音声
        if (strpos($mimeType, 'audio/') === 0 || in_array($extension, ['mp3', 'wav', 'ogg', 'flac'])) {
            return 'fa-file-audio';
        }

        // 動画
        if (strpos($mimeType, 'video/') === 0 || in_array($extension, ['mp4', 'avi', 'mov', 'wmv'])) {
            return 'fa-file-video';
        }

        // コード
        if (in_array($extension, ['html', 'css', 'js', 'php', 'py', 'java', 'c', 'cpp', 'cs', 'rb', 'go', 'swift'])) {
            return 'fa-file-code';
        }

        // その他
        return 'fa-file';
    }
}
