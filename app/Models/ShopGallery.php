<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopGallery extends Model
{
    use HasFactory;

    protected $fillable = [
        "image",
        "shop_id",
    ];
}
