<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ExportActivityLogCommand; // ★ 追加
use App\Console\Commands\PruneOldActivityLogsCommand; // ★ 追加
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // ここに案件依頼コマンドクラスを追加することも可能ですが、
        // Laravel 10以降では通常、App\Console\Commandsディレクトリ内のコマンドは自動的に登録されます。
        // 明示的に登録する場合は以下のように記述します。
        // ExportActivityLogCommand::class,
        // PruneOldActivityLogsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->call(function () {
            Log::info('Test schedule ran at ' . now());
        })->everyMinute()->name('test-schedule');
        // ★ 毎日AM3:00に前日のログをCSVにエクスポートするスケジュール
        $schedule->command(ExportActivityLogCommand::class)
            ->dailyAt('03:00')
            ->onSuccess(function () {
                // 成功時の処理 (例: ログ出力)
                \Illuminate\Support\Facades\Log::channel('schedule')->info('ExportActivityLogCommand: Successfully completed.');
            })
            ->onFailure(function () {
                // 失敗時の処理 (例: エラーログ出力、通知など)
                \Illuminate\Support\Facades\Log::channel('schedule')->error('ExportActivityLogCommand: Failed.');
            });

        // ★ 毎日AM3:30に1年以上古いログをDBから削除するスケジュール (エクスポート後が良いでしょう)
        $schedule->command(PruneOldActivityLogsCommand::class, ['--days' => 365]) // デフォルトは365日だが明示
            ->dailyAt('03:30')
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::channel('schedule')->info('PruneOldActivityLogsCommand: Successfully completed.');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::channel('schedule')->error('PruneOldActivityLogsCommand: Failed.');
            });

        // 1分ごとにキューワーカーを起動し、キューが空なら停止、タイムアウト60秒
        $schedule->command('queue:work --stop-when-empty --tries=3 --timeout=60')->everyMinute()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
