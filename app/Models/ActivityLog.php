<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $connection = 'logs';
    protected $fillable = [
        "api_url",
        "user",
        "user_id",
        "activity",
        "ip_address",
        "request_method"
    ];
}
