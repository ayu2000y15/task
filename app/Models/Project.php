<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'is_favorite',
        'color'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_favorite' => 'boolean',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function toggleFavorite()
    {
        $this->is_favorite = !$this->is_favorite;
        $this->save();
        return $this->is_favorite;
    }
}
