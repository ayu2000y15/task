<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHoliday extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_holidays';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'date',
        'period_type',
    ];

    public const PERIOD_TYPES = [
        'all_day' => '終日',
        'am'      => '午前休',
        'pm'      => '午後休',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date', // dateカラムをCarbonオブジェクトとして扱います
    ];

    /**
     * この休日が属するユーザーを取得します。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
