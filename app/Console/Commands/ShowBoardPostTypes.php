<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BoardPostType;

class ShowBoardPostTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'show:board-post-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show all board post types';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $postTypes = BoardPostType::all();

        $this->info('Board Post Types:');
        $this->table(['ID', 'Name', 'Display Name', 'Is Default'], $postTypes->map(function ($type) {
            return [
                $type->id,
                $type->name,
                $type->display_name,
                $type->is_default ? 'Yes' : 'No'
            ];
        })->toArray());

        return 0;
    }
}
