<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelStationOption extends Model
{
    use HasFactory;
    protected $fillable = [
        "fuel_station_id",
        "option_id",
        "is_active",
    ];



    public function option(){
        return $this->hasOne(FuelStationService::class,'id', 'option_id');
    }





}
