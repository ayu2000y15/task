<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

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
    // ▼▼▼【ここから日本語化】▼▼▼
    protected $description = '指定されたテーブルを元に`_bk`テーブルを削除・再作成し、全データをコピーします。';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tablesToBackup = Config::get('backup.tables_to_backup');

        if (empty($tablesToBackup)) {
            $this->info('config/backup.php にバックアップ対象のテーブルが設定されていません。処理をスキップします。');
            return Command::SUCCESS;
        }

        $this->info('テーブルのバックアップ処理を開始します (テーブル削除・再作成方式)...');
        Log::channel('schedule')->info('BackupTablesToBkCommand: 処理を開始します。');

        foreach ($tablesToBackup as $originalTable) {
            $backupTable = "{$originalTable}_bk";
            $this->line("テーブルのバックアップを処理中: {$originalTable} -> {$backupTable}");

            try {
                // 1. 元テーブルの存在チェック
                if (!Schema::hasTable($originalTable)) {
                    $this->error("  - 元テーブル '{$originalTable}' が存在しません。スキップします。");
                    Log::channel('schedule')->error("BackupTablesToBkCommand: 元テーブル '{$originalTable}' が見つかりませんでした。このテーブルのバックアップをスキップします。");
                    continue; // 次のテーブルへ
                }

                // 2. 既存のバックアップテーブルを削除
                $this->info("  - 既存のバックアップテーブル '{$backupTable}' を削除しています...");
                Schema::dropIfExists($backupTable);

                // 3. 元テーブルの構造をコピーしてバックアップテーブルを再作成
                $this->info("  - '{$originalTable}' の構造で '{$backupTable}' を再作成しています...");
                DB::statement("CREATE TABLE `{$backupTable}` LIKE `{$originalTable}`");

                // 4. 元テーブルからデータをコピー
                $this->info("  - '{$originalTable}' から '{$backupTable}' へデータをコピーしています...");
                DB::statement("INSERT INTO `{$backupTable}` SELECT * FROM `{$originalTable}`");

                $this->info("  - {$originalTable} のバックアップが正常に完了しました。");
                Log::channel('schedule')->info("BackupTablesToBkCommand: {$originalTable} から {$backupTable} へのバックアップが正常に完了しました。");
            } catch (\Exception $e) {
                // DROP, CREATE, INSERT のいずれかでエラーが発生した場合
                $this->error("  - {$originalTable} のバックアップに失敗しました: " . $e->getMessage());
                Log::channel('schedule')->error("BackupTablesToBkCommand: {$originalTable} のバックアップに失敗しました。", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('テーブルのバックアップ処理が完了しました。');
        Log::channel('schedule')->info('BackupTablesToBkCommand: 処理を終了しました。');

        return Command::SUCCESS;
    }
    // ▲▲▲【日本語化ここまで】▲▲▲
}
