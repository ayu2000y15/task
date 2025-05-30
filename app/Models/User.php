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

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'access_id',
        'last_access',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // アクティビティログのオプション設定
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // ★ ユーザー作成時のモデルイベントログは、Registeredイベントリスナーで対応するため、
            // ★ ここでは 'created' イベントを明示的にログ対象から外すか、
            // ★ logOnly() で記録したいイベントのみを指定します。
            // ★ 今回は、updated と deleted イベントのみをログするようにします。
            ->logOnly(['name', 'email', 'access_id']) // ★ ログ記録する属性を明示的に指定
            // ->logAll() // もし全てのfillable属性を記録したい場合 (ただしlogExceptと併用)
            ->logExcept(['password', 'remember_token', 'email_verified_at', 'updated_at', 'last_access'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            // ★ useLogNameでログ名を指定可能 (例: 'user_activity')
            // ->useLogName('user_management')
            ->setDescriptionForEvent(fn(string $eventName) => "ユーザー「{$this->name}」(ID:{$this->id}) の情報が{$this->getEventDescription($eventName)}されました")
            // ★ createdイベントをログしたくない場合は、このメソッド内で明示的に
            // ★ ->dontLogEvents(['created']) のように指定するか、
            // ★ または、logOnly で created を含まないイベントを指定します。
            // ★ Registeredイベントリスナーで登録ログはカバーするので、モデルのcreatedは不要という判断。
            // ★ ただし、logOnly で属性を指定した場合、その属性の変更があった場合のみログが記録されます。
            // ★ 'updated' と 'deleted' イベントのみを対象とするなら、以下のようにします。
            // ★ (ただし、logOnly で属性指定をしない場合は、モデルのイベントのみで判断される)
            // ★ ->logOnlyDirty() と組み合わせることで、指定した属性に変更があった場合のみログが記録されます。
            // ★ 今回のケースでは、createdログの重複を避けることが主目的のため、
            // ★ リスナー側でcreatedのログは十分なので、モデル側ではupdated/deletedのみを対象とします。
            // ★ ただし、logOnly で指定した属性がないと updated も記録されません。
            // ★ なので、updatedで記録したい属性を logOnly で指定する必要があります。
            // ★ もし「どのイベントを記録するか」を制御したい場合は、
            // ★ モデルの $recordEvents プロパティ (static) を使う方法もあります。
            // protected static $recordEvents = ['updated', 'deleted'];
        ;
    }

    // ★ $recordEvents プロパティで記録するイベントを制御する場合
    // protected static $recordEvents = ['updated', 'deleted'];


    // イベント名を日本語に変換するヘルパーメソッド
    protected function getEventDescription(string $eventName): string
    {
        switch ($eventName) {
            case 'created': // このケースは上記の修正により呼ばれにくくなります
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
}
