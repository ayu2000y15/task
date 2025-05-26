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
        'character_id', // ★ 追加
        'name',
        'description',
        'start_date',
        'end_date',
        'duration',
        'status',
        'progress',
        'assignee',
        'is_milestone',
        'is_folder',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'progress' => 'integer',
        'is_milestone' => 'boolean',
        'is_folder' => 'boolean',
    ];

    /**
     * この工程が属する衣装案件
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * この工程に紐づくファイルを取得します。
     */
    public function files()
    {
        return $this->hasMany(\App\Models\TaskFile::class);
    }

    /**
     * この工程が属するキャラクター (nullable)
     */
    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * この工程の親工程
     */
    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * この工程の子工程
     */
    public function children()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    /**
     * 工程の期間（日数）を取得
     */
    public function getDurationAttribute()
    {
        if (is_null($this->start_date) || is_null($this->end_date)) {
            return null;
        }
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * 工程の階層レベルを取得
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
     * 工程の全子孫を取得
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
     * 指定された工程が自身の子孫であるか（自身が祖先であるか）をチェック
     *
     * @param Task $otherTask
     * @return boolean
     */
    public function isAncestorOf(Task $otherTask): bool
    {
        $parent = $otherTask->parent;
        while ($parent) {
            if ($parent->id === $this->id) {
                return true;
            }
            $parent = $parent->parent;
        }
        return false;
    }
}
