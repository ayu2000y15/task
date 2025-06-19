<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportationExpense extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'project_id',
        'cost_id',
        'departure',
        'destination',
        'amount',
        'notes'
    ];
    protected $casts = ['date' => 'date'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
