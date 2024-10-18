<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        "garage_id",
        "name",
        "code",
        "discount_type",
        "discount_amount",
        "min_total",
        "max_total",
        "redemptions",
        "coupon_start_date",
        "coupon_end_date",
        "is_auto_apply",
        "is_active",
    ];




}
