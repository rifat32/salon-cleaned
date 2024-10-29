<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        "business_id",
        'notify_expert',
        'notify_customer',
        'notify_receptionist',
        'notify_business_owner'
    ];
}
