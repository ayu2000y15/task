<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // ★ Logファサードをuse

class PruneOldActivityLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylog:prune-old {--days=30 : 指定した日数より古いレコードを削除します。デフォルトは30日です。}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '古いアクティビティログのレコードをデータベースから削除します。';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        if ($days <= 0) {
            $this->error('--days オプションには正の整数を指定してください。');
            return Command::FAILURE;
        }

        $startMessage = "PruneOldActivityLogsCommand: {$days}日より古いアクティビティログを削除します...";
        $this->info($startMessage);
        Log::channel('schedule')->info($startMessage); // ★ ログ出力追加

        $cutOffDate = Carbon::now()->subDays($days)->startOfDay();

        try {
            $deletedCount = Activity::where('created_at', '<', $cutOffDate)->delete();

            if ($deletedCount > 0) {
                $successMessage = "PruneOldActivityLogsCommand: {$cutOffDate->toDateString()} より前に作成された {$deletedCount} 件の古いアクティビティログを正常に削除しました。";
                $this->info($successMessage);
                Log::channel('schedule')->info($successMessage); // ★ ログ出力追加
            } else {
                $noRecordsMessage = 'PruneOldActivityLogsCommand: 削除対象の古いアクティビティログは見つかりませんでした。';
                $this->info($noRecordsMessage);
                Log::channel('schedule')->info($noRecordsMessage); // ★ ログ出力追加
            }
        } catch (\Exception $e) {
            $errorMessage = "PruneOldActivityLogsCommand: 古いアクティビティログの削除中にエラーが発生しました: " . $e->getMessage();
            $this->error($errorMessage);
            Log::channel('schedule')->error($errorMessage); // ★ ログ出力追加
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
