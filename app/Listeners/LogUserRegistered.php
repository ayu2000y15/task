<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;

class LogUserRegistered
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Registered  $event
     * @return void
     */
    public function handle(Registered $event): void
    {
        if ($event->user) {
            // 新規登録時は、操作者も登録されたユーザー自身とするか、
            // システムによる操作として扱うかなどを検討できます。
            // ここでは登録されたユーザーを操作者として記録します。
            activity()
                ->causedBy($event->user) // もしシステム操作としたい場合は ->by(null) や特定のシステムユーザーを指定
                ->performedOn($event->user) // 操作対象も登録されたユーザー
                ->log('新規ユーザー「' . $event->user->name . '」(ID:' . $event->user->id . ') が登録されました。');
        }
    }
}
