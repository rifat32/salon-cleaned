<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "about",
        "web_page",
        "phone",
        "email",
        "additional_information",
        "address_line_1",
        "address_line_2",
        "lat",
        "long",
        "country",
        "city",
        "postcode",
        "sku_prefix",
        "logo",
        "image",
        "status",
        // "is_active",
        "is_mobile_shop",
        "wifi_available",
        "labour_rate",
        "average_time_slot",
        "owner_id",
        "created_by",
    ];

    public function owner(){
        return $this->belongsTo(User::class,'owner_id', 'id');
    }
}
