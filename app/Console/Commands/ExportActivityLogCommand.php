<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExportActivityLogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylog:export-csv {--date= : Export logs for a specific date (YYYY-MM-DD). Defaults to yesterday.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export activity logs to a CSV file on a daily basis (defaults to yesterday\'s logs).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dateOption = $this->option('date');
        // ★ ターゲット日付のタイムゾーンを考慮（特に指定がない場合はシステムのデフォルトタイムゾーンで解釈される）
        $targetDate = $dateOption ? Carbon::parse($dateOption)->startOfDay() : Carbon::yesterday()->startOfDay();

        $this->info("Exporting activity logs for: " . $targetDate->toDateString());

        // ★ DBのcreated_atはUTCなので、指定された日付のUTC範囲で検索
        $startOfDayUTC = $targetDate->copy()->setTimezone('UTC');
        $endOfDayUTC = $targetDate->copy()->endOfDay()->setTimezone('UTC');


        $activities = Activity::with(['causer', 'subject'])
            ->whereBetween('created_at', [$startOfDayUTC, $endOfDayUTC]) // ★ UTCで範囲指定
            ->orderBy('created_at', 'asc')
            ->get();

        if ($activities->isEmpty()) {
            $this->info('No activities found for ' . $targetDate->toDateString() . '. Nothing to export.');
            return Command::SUCCESS;
        }

        // ★ ファイル名はローカル日付基準で良い
        $fileName = $targetDate->format('Ymd') . '_tasktool.csv';
        $directory = 'logs/activity_exports';

        Storage::disk('local')->makeDirectory($directory);
        $filePath = Storage::disk('local')->path("{$directory}/{$fileName}");

        try {
            $file = fopen($filePath, 'w');
            if ($file === false) {
                $this->error("Failed to open file for writing: {$filePath}");
                return Command::FAILURE;
            }

            fputcsv($file, [
                'ID',
                'Log Name',
                'Description',
                'Event',
                'Subject Type',
                'Subject ID',
                'Causer Type',
                'Causer ID',
                'Causer Name',
                'Properties (JSON)',
                'Batch UUID',
                'Created At (JST)', // ★ ヘッダー変更
                'Updated At (JST)', // ★ ヘッダー変更
            ]);

            foreach ($activities as $activity) {
                $subjectTypeShort = $activity->subject_type ? class_basename($activity->subject_type) : null;
                $causerTypeShort = $activity->causer_type ? class_basename($activity->causer_type) : null;

                fputcsv($file, [
                    $activity->id,
                    $activity->log_name,
                    $activity->description,
                    $activity->event,
                    $subjectTypeShort,
                    $activity->subject_id,
                    $causerTypeShort,
                    $activity->causer_id,
                    $activity->causer->name ?? ($causerTypeShort ? 'N/A (System or Deleted User)' : 'System'),
                    $activity->properties->isNotEmpty() ? $activity->properties->toJson() : null,
                    $activity->batch_uuid,
                    // ★ 日本時間に変換して出力
                    $activity->created_at ? $activity->created_at->setTimezone('Asia/Tokyo')->format('Y-m-d H:i:s') : null,
                    $activity->updated_at ? $activity->updated_at->setTimezone('Asia/Tokyo')->format('Y-m-d H:i:s') : null,
                ]);
            }

            fclose($file);
            $this->info("Successfully exported {$activities->count()} log entries to: {$filePath}");
        } catch (\Exception $e) {
            $this->error("An error occurred during CSV export: " . $e->getMessage());
            if (isset($file) && $file !== false) {
                fclose($file);
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
