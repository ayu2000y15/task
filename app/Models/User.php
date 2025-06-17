<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity;
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $timestamps = true;

    // ステータス用の定数
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_RETIRED = 'retired';
    const STATUS_SHARED = 'shared';


    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'access_id',
        'last_access',
        'status', // statusカラムをfillableに追加
        'hourly_rate',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * このユーザーの作業ログ
     */
    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }

    /**
     * このユーザーの現在アクティブな（実行中または一時停止中）作業ログ
     */
    public function activeWorkLog()
    {
        return $this->hasOne(WorkLog::class)->whereIn('status', ['active', 'paused']);
    }

    public function activeWorkLogs()
    {
        // 'status' が 'active' のものだけを取得するように修正
        return $this->hasMany(WorkLog::class)->where('status', 'active');
    }

    // アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'access_id', 'status']) // statusもログ対象に
            ->logExcept(['password', 'remember_token', 'email_verified_at', 'updated_at', 'last_access'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "ユーザー「{$this->name}」(ID:{$this->id}) の情報が{$this->getEventDescription($eventName)}されました")
        ;
    }

    // イベント名を日本語に変換するヘルパーメソッド
    protected function getEventDescription(string $eventName): string
    {
        switch ($eventName) {
            case 'created':
                return '作成';
            case 'updated':
                return '更新';
            case 'deleted':
                return '削除';
            default:
                return $eventName;
        }
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_access' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasPermissionTo(string $permissionName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->permissions->contains('name', $permissionName)) {
                return true;
            }
        }
        return false;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * このユーザーが担当する工程
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_user');
    }

    /**
     * このユーザーが投稿した掲示板の投稿
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function boardPosts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\BoardPost::class);
    }

    /**
     * ▼▼▼【ここを追加】ユーザーが登録した休日を取得します ▼▼▼
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function holidays()
    {
        return $this->hasMany(UserHoliday::class);
    }

    /**
     * このユーザーが作成した依頼
     */
    public function createdRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'requester_id');
    }

    /**
     * このユーザーに割り当てられた依頼 (複数)
     */
    public function assignedRequests(): BelongsToMany
    {
        return $this->belongsToMany(Request::class, 'request_assignees');
    }

    /**
     * このユーザーの時給履歴を取得します。
     */
    public function hourlyRates(): HasMany
    {
        return $this->hasMany(HourlyRate::class)->orderBy('effective_date', 'desc');
    }

    /**
     * 特定の日付における有効な時給を取得します。
     *
     * @param Carbon|null $date 対象日
     * @return float|null
     */
    public function getHourlyRateForDate(?Carbon $date): ?float
    {
        if (!$date) {
            return $this->hourlyRates()->first()->rate ?? null;
        }

        // 対象日以前で最も新しい適用日のレートを取得する
        $rate = $this->hourlyRates()
            ->where('effective_date', '<=', $date->format('Y-m-d'))
            ->first();

        return $rate ? (float)$rate->rate : null;
    }

    // ▼▼▼【追加】カテゴリを指定して最新の時給レコードを取得するメソッド ▼▼▼
    public function getLatestHourlyRateForCategory(string $category = 'payroll'): ?\App\Models\HourlyRate
    {
        return $this->hourlyRates()
            ->where('category', $category)
            ->where('effective_date', '<=', now())
            ->orderBy('effective_date', 'desc')
            ->first();
    }
}
