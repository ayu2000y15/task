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


    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'access_id',
        'last_access',
        'status', // statusカラムをfillableに追加
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
}
