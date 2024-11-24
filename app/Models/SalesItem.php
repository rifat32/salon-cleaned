<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'sale_id',
        'good_id',
        'quantity',
        'price_per_unit',
        'total_price',
    ];
}
