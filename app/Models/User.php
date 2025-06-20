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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

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
        'default_transportation_departure',
        'default_transportation_destination',
        'default_transportation_amount',
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

    /**
     * ユーザーの勤怠ログとのリレーション
     */
    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * 現在の勤怠ステータスを取得する
     *
     * @return string working, on_break, on_away, clocked_out
     */
    public function getCurrentAttendanceStatus(): string
    {
        // パフォーマンス向上のため、ステータスを短時間キャッシュする
        return Cache::remember('attendance_status_' . $this->id, 60, function () {
            $lastLog = $this->attendanceLogs()
                ->latest('timestamp')
                ->first();

            if (!$lastLog) {
                return 'clocked_out'; // ログがなければ未出勤
            }

            switch ($lastLog->type) {
                case 'clock_in':
                case 'break_end':
                case 'away_end':
                    return 'working'; // 出勤中
                case 'break_start':
                    return 'on_break'; // 休憩中
                case 'away_start':
                    return 'on_away'; // 中抜け中
                case 'clock_out':
                default:
                    return 'clocked_out'; // 退勤済み
            }
        });
    }
    /**
     * ユーザーが実行中の作業ログを持っているかを確認する
     */
    public function hasActiveWorkLog(): bool
    {
        // 'status'が'active'のWorkLogが存在すればtrueを返す
        return $this->workLogs()->where('status', 'active')->exists();
    }

    /**
     * ▼▼▼【ここから追加】エラー解決のための不足していたメソッド ▼▼▼
     *
     * 指定された月に適用される時給のリストを取得します。
     * (月の開始前の最新のレート + 月の途中で変更される全てのレート)
     *
     * @param Carbon $targetMonth 対象月
     * @return Collection
     */
    public function getApplicableRatesForMonth(Carbon $targetMonth): Collection
    {
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        // 1. 月の開始日時点で有効な最新の時給を1件取得
        $baseRate = $this->hourlyRates()
            ->where('effective_date', '<=', $startDate)
            ->orderBy('effective_date', 'desc')
            ->first();

        // 2. 月の途中で有効になる時給を全て取得 (初日は除く)
        $ratesStartingInMonth = $this->hourlyRates()
            ->whereBetween('effective_date', [$startDate->copy()->addDay(), $endDate])
            ->orderBy('effective_date', 'asc')
            ->get();

        // 3. 上記2つを結合して、その月に適用される時給リストを作成
        $applicableRates = collect();
        if ($baseRate) {
            $applicableRates->push($baseRate);
        }

        return $applicableRates->merge($ratesStartingInMonth);
    }
}
