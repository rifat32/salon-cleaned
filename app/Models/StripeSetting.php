<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeSetting extends Model
{
    use HasFactory;


    protected $fillable = [
        'STRIPE_KEY',
        "STRIPE_SECRET",
        "business_id"
    ];

    protected $hidden = [
        'STRIPE_KEY',
        "STRIPE_SECRET"
    ];


}
