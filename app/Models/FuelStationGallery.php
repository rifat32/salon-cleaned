<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelStationGallery extends Model
{
    use HasFactory;

    protected $fillable = [
        "image",
        "fuel_station_id",
    ];

}
