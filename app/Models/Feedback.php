<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    public const PRIORITY_HIGH = 1;
    public const PRIORITY_MEDIUM = 2;
    public const PRIORITY_LOW = 3;

    public const PRIORITY_OPTIONS = [
        self::PRIORITY_HIGH => '高',
        self::PRIORITY_MEDIUM => '中',
        self::PRIORITY_LOW => '低',
    ];

    protected $fillable = [
        'user_id',
        'user_name',
        'email',
        'phone',
        'feedback_category_id',
        'title',
        'content',
        'priority',
        'status',
        'assignee_text',
        'completed_at',
        'admin_memo',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'user_id' => 'integer',
        'feedback_category_id' => 'integer',
        'priority' => 'integer',
    ];

    public const STATUS_UNREAD = 'unread';
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_OPTIONS = [
        self::STATUS_UNREAD => '未読',
        self::STATUS_NOT_STARTED => '未着手',
        self::STATUS_IN_PROGRESS => '対応中',
        self::STATUS_COMPLETED => '対応済み',
        self::STATUS_CANCELLED => 'キャンセル',
        self::STATUS_ON_HOLD => '保留',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeedbackCategory::class, 'feedback_category_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(FeedbackFile::class);
    }

    public static function getStatusColorClass(string $status, string $type = 'text'): string
    {
        switch ($status) {
            case self::STATUS_UNREAD:
                return $type === 'badge' ? 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200' : 'text-gray-500 dark:text-gray-400';
            case self::STATUS_NOT_STARTED:
                return $type === 'badge' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100' : 'text-yellow-500 dark:text-yellow-400';
            case self::STATUS_IN_PROGRESS:
                return $type === 'badge' ? 'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100' : 'text-blue-500 dark:text-blue-400';
            case self::STATUS_COMPLETED:
                return $type === 'badge' ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100' : 'text-green-500 dark:text-green-400';
            case self::STATUS_CANCELLED:
                return $type === 'badge' ? 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100' : 'text-red-500 dark:text-red-400';
            case self::STATUS_ON_HOLD:
                return $type === 'badge' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-100' : 'text-indigo-500 dark:text-indigo-400';
            default:
                return $type === 'badge' ? 'bg-gray-100 text-gray-700 dark:bg-gray-500 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400';
        }
    }

    /**
     * Get the Tailwind CSS class string for the priority badge.
     *
     * @param int|null $priority  // ★★★ 型を ?int に変更 ★★★
     * @param string $type ('badge' or 'text')
     * @return string
     */
    public static function getPriorityColorClass(?int $priority, string $type = 'text'): string // ★★★ 型を ?int に変更 ★★★
    {
        // ★★★ nullの場合の処理を追加 ★★★
        if ($priority === null) {
            return $type === 'badge' ? 'bg-gray-100 text-gray-700 dark:bg-gray-500 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400';
        }

        switch ($priority) {
            case self::PRIORITY_HIGH:
                return $type === 'badge' ? 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-200' : 'text-red-500 dark:text-red-400';
            case self::PRIORITY_MEDIUM:
                return $type === 'badge' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200' : 'text-yellow-500 dark:text-yellow-400';
            case self::PRIORITY_LOW:
                return $type === 'badge' ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200' : 'text-green-500 dark:text-green-400';
            default: // null以外の未定義の数値の場合もデフォルトスタイルを適用
                return $type === 'badge' ? 'bg-gray-100 text-gray-700 dark:bg-gray-500 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400';
        }
    }
}
