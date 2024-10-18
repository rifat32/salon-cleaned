<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarageSubService extends Model
{
    use HasFactory;

    protected $fillable = [
        "garage_service_id",
        "sub_service_id",
    ];

    public function garageService(){
        return $this->belongsTo(GarageService::class,'garage_service_id', 'id');
    }
    public function subService(){
        return $this->belongsTo(SubService::class,'sub_service_id', 'id');
    }

    public function garage_sub_service_prices(){
        return $this->hasMany(GarageSubServicePrice::class,'garage_sub_service_id', 'id');
    }
}
