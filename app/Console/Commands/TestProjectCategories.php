<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProjectCategory;

class TestProjectCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:project-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test project categories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $categories = ProjectCategory::all();
        $this->info('Project Categories:');
        foreach ($categories as $cat) {
            $this->line($cat->id . ' - ' . $cat->name . ' (' . $cat->display_name . ')');
        }

        $this->info('');
        $this->info('Projects with categories:');
        $projects = \App\Models\Project::whereNotNull('project_category_id')->get();
        foreach ($projects as $project) {
            $this->line($project->id . ' - ' . $project->title . ' (category: ' . $project->project_category_id . ')');
        }

        $this->info('');
        $this->info('Projects without categories:');
        $projects = \App\Models\Project::whereNull('project_category_id')->get();
        foreach ($projects as $project) {
            $this->line($project->id . ' - ' . $project->title . ' (category: null)');
        }

        return 0;
    }
}
