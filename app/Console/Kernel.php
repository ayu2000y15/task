<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ExportActivityLogCommand; // ★ 追加
use App\Console\Commands\PruneOldActivityLogsCommand; // ★ 追加
use App\Console\Commands\BackupTablesToBkCommand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;

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
        $logPath = storage_path('logs/schedule.log'); // ★ ログパスを変数に格納

        // ★ 6時間ごとに指定テーブルのバックアップを実行するスケジュール
        $schedule->command(BackupTablesToBkCommand::class)
            ->everySixHours()
            ->before(function () {
                Log::channel('schedule')->info('BackupTablesToBkCommand: 処理を開始します。');
            })
            ->onSuccess(function (Stringable $output) {
                Log::channel('schedule')->info('BackupTablesToBkCommand: 処理が正常に完了しました。');
            })
            ->onFailure(function (Stringable $output) {
                Log::channel('schedule')->error('BackupTablesToBkCommand: 処理が失敗しました。');
            })
            ->appendOutputTo($logPath); // ★ ログパスを統一


        // ★ 毎日AM3:00に前日のログをCSVにエクスポートするスケジュール
        $schedule->command(ExportActivityLogCommand::class)
            ->dailyAt('03:00')
            ->before(function () {
                Log::channel('schedule')->info('ExportActivityLogCommand: 処理を開始します。');
            })
            ->onSuccess(function (Stringable $output) {
                Log::channel('schedule')->info('ExportActivityLogCommand: 処理が正常に完了しました。');
            })
            ->onFailure(function (Stringable $output) {
                Log::channel('schedule')->error('ExportActivityLogCommand: 処理が失敗しました。');
            })
            ->appendOutputTo($logPath); // ★ ログパスを統一


        // ★ 毎日AM3:30に1年以上古いログをDBから削除するスケジュール
        $schedule->command(PruneOldActivityLogsCommand::class, ['--days' => 365])
            ->dailyAt('03:30')
            ->before(function () {
                Log::channel('schedule')->info('PruneOldActivityLogsCommand: 処理を開始します。');
            })
            ->onSuccess(function (Stringable $output) {
                Log::channel('schedule')->info('PruneOldActivityLogsCommand: 処理が正常に完了しました。');
            })
            ->onFailure(function (Stringable $output) {
                Log::channel('schedule')->error('PruneOldActivityLogsCommand: 処理が失敗しました。');
            })
            ->appendOutputTo($logPath); // ★ ログパスを統一

        // (↓これ以降のスケジュールは変更なし)
        $schedule->command('queue:work --stop-when-empty --tries=3 --timeout=60')->everyMinute()->withoutOverlapping();
        $schedule->command('emails:process-bounces')->hourly()->withoutOverlapping();
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
