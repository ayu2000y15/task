<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema; // ★ 追加

class BackupTablesToBkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup-tables-to-bk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backs up specified tables to corresponding `_bk` tables, creating them if they do not exist.'; // 説明を更新

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tablesToBackup = Config::get('backup.tables_to_backup');

        if (empty($tablesToBackup)) {
            $this->info('No tables are configured for backup in config/backup.php. Skipping.');
            return Command::SUCCESS;
        }

        $this->info('Starting table backup process...');
        Log::channel('schedule')->info('BackupTablesToBkCommand: Starting.');

        foreach ($tablesToBackup as $originalTable) {
            $backupTable = "{$originalTable}_bk";
            $this->line("Processing backup for: {$originalTable} -> {$backupTable}");

            // ★★★ ここからが追加・変更されたロジック ★★★
            try {
                // バックアップテーブルが存在しない場合、元のテーブルと同じ構造で作成する
                if (!Schema::hasTable($backupTable)) {
                    $this->info("  - Backup table '{$backupTable}' not found. Creating it now...");
                    // 元のテーブルが存在するか確認
                    if (!Schema::hasTable($originalTable)) {
                        $this->error("  - Source table '{$originalTable}' does not exist. Cannot create backup table. Skipping.");
                        Log::channel('schedule')->error("BackupTablesToBkCommand: Source table '{$originalTable}' not found. Skipping backup for it.");
                        continue; // 次のテーブルへ
                    }
                    DB::statement("CREATE TABLE `{$backupTable}` LIKE `{$originalTable}`");
                    $this->info("  - Table '{$backupTable}' created successfully.");
                    Log::channel('schedule')->info("BackupTablesToBkCommand: Created new backup table '{$backupTable}'.");
                }
            } catch (\Exception $e) {
                $this->error("  - FAILED to create backup table '{$backupTable}': " . $e->getMessage());
                Log::channel('schedule')->error("BackupTablesToBkCommand: FAILED to create '{$backupTable}'.", [
                    'error' => $e->getMessage(),
                ]);
                continue; // テーブル作成に失敗したら、このテーブルの処理は中断して次へ
            }
            // ★★★ ここまでが追加・変更されたロジック ★★★


            // テーブルのTRUNCATEとデータコピー処理
            DB::beginTransaction();
            try {
                // バックアップ先テーブルをTRUNCATE
                $this->info("  - Deleting all records from {$backupTable}...");
                DB::table($backupTable)->delete();

                // オリジナルテーブルからデータをコピー
                $this->info("  - Copying data from {$originalTable} to {$backupTable}...");
                DB::statement("INSERT INTO `{$backupTable}` SELECT * FROM `{$originalTable}`");

                DB::commit();
                $this->info("  - Successfully backed up {$originalTable}.");
                Log::channel('schedule')->info("BackupTablesToBkCommand: Successfully backed up {$originalTable}.");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  - FAILED to back up {$originalTable}: " . $e->getMessage());
                Log::channel('schedule')->error("BackupTablesToBkCommand: FAILED to back up {$originalTable}.", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info('Table backup process finished.');
        Log::channel('schedule')->info('BackupTablesToBkCommand: Finished.');

        return Command::SUCCESS;
    }
}
