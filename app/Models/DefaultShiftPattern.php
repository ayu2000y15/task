<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultShiftPattern extends Model
{
    protected $fillable = [
        'user_id',
        'day_of_week',
        'is_workday',
        'start_time',
        'end_time',
        'break_minutes',
        'location'
    ];
}
