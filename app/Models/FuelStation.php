<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelStation extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        "name",
        "address",
        "opening_time",
        "closing_time",
        "description",
        "lat",
        "long",
        "country",
        "city",
        "postcode",
        "additional_information",
        "address_line_1",
        "address_line_2",
        "is_active",
        "created_by",
    ];
    public function options(){
        return $this->hasMany(FuelStationOption::class,'fuel_station_id', 'id');
    }

    public function fuel_station_times(){
        return $this->hasMany(FuelStationTime::class,'fuel_station_id', 'id');
    }
}
