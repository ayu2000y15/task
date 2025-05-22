<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'parent_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'duration',
        'progress',
        'assignee',
        'is_milestone',
        'is_folder',
        'color',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'progress' => 'integer',
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
     * このタスクに関連するファイル
     */
    public function files()
    {
        return $this->hasMany(TaskFile::class);
    }

    /**
     * タスクの期間（日数）を取得
     */
    public function getDurationAttribute()
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * タスクの階層レベルを取得
     */
    public function getLevelAttribute()
    {
        $level = 0;
        $parent = $this->parent;

        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }

        return $level;
    }

    /**
     * タスクの全子孫を取得
     */
    public function getAllDescendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * このタスクが指定されたタスクの祖先かどうかを確認
     *
     * @param Task $task 確認対象のタスク
     * @return bool
     */
    public function isAncestorOf(Task $task)
    {
        if ($task->parent_id === $this->id) {
            return true;
        }

        foreach ($this->children as $child) {
            if ($child->isAncestorOf($task)) {
                return true;
            }
        }

        return false;
    }
}
