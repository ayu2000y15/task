<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cost;

class BackfillCostProjectIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-cost-project-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfills the project_id for existing costs based on their character relationship.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to backfill project_id for costs...');

        // project_idがNULLで、character_idが設定されているコストを取得
        $costsToUpdate = Cost::whereNull('project_id')
            ->whereNotNull('character_id')
            ->with('character') // N+1問題を避けるためにリレーションを事前ロード
            ->get();

        if ($costsToUpdate->isEmpty()) {
            $this->info('No costs needed to be updated. All records seem to have a project_id.');
            return 0;
        }

        $this->info($costsToUpdate->count() . ' cost records will be updated.');

        $progressBar = $this->output->createProgressBar($costsToUpdate->count());
        $progressBar->start();

        foreach ($costsToUpdate as $cost) {
            // コストに紐づくキャラクターが存在し、そのキャラクターに案件が紐づいているか確認
            if ($cost->character && $cost->character->project_id) {
                // コストのproject_idを、キャラクターのproject_idで更新
                $cost->project_id = $cost->character->project_id;
                $cost->save();
            } else {
                $this->warn("Skipping Cost ID: {$cost->id} because its character or project link is missing.");
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Successfully backfilled project_ids for all applicable costs.');

        return 0;
    }
}
