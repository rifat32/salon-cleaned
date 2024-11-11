<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpertRotaTime extends Model
{
    use HasFactory;
    protected $fillable = [
        'expert_rota_id',
        'start_time',
        'end_time',
    ];
}
