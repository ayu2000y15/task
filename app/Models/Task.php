<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'duration',
        'assignee',
        'parent_id',
        'is_milestone',
        'is_folder',
        'progress',
        'status',
        'color',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_milestone' => 'boolean',
        'is_folder' => 'boolean',
    ];

    /**
     * このタスクが属するプロジェクト
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * このタスクの親タスク
     */
    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * このタスクの子タスク
     */
    public function children()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    /**
     * このタスクが指定されたタスクの祖先かどうかを判定
     */
    public function isAncestorOf(Task $task)
    {
        if ($this->id === $task->id) {
            return true;
        }

        $currentTask = $task;
        while ($currentTask->parent_id) {
            if ($currentTask->parent_id === $this->id) {
                return true;
            }

            $currentTask = $currentTask->parent;
            if (!$currentTask) {
                break;
            }
        }

        return false;
    }
}
