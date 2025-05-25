<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'character_name',
        'series_title',
        'client_name',
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

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(Cost::class);
    }
}
