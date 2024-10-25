<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarageTime extends Model
{
    use HasFactory;

    protected $fillable = [
        "day",
        "opening_time",
        "closing_time",
        "garage_id",
        "is_closed",
        "time_slots"
    ];

    protected  $casts = [
        'time_slots' => 'array',
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
    ];
}
