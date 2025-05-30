<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class PruneOldActivityLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylog:prune-old {--days=365 : Delete records older than this number of days. Defaults to 365 days (1 year).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old activity log records from the database (older than 1 year by default).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        if ($days <= 0) {
            $this->error('The --days option must be a positive integer.');
            return Command::FAILURE;
        }

        $this->info("Pruning activity logs older than {$days} days...");

        $cutOffDate = Carbon::now()->subDays($days)->startOfDay();

        try {
            $deletedCount = Activity::where('created_at', '<', $cutOffDate)->delete();

            if ($deletedCount > 0) {
                $this->info("Successfully pruned {$deletedCount} old activity log records created before " . $cutOffDate->toDateString() . ".");
            } else {
                $this->info('No old activity log records found to prune.');
            }
        } catch (\Exception $e) {
            $this->error("An error occurred while pruning old activity logs: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
