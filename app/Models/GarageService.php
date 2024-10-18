<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarageService extends Model
{
    use HasFactory;

    protected $fillable = [
        "garage_id",
        "service_id",
    ];

    public function garageSubServices(){
        return $this->hasMany(GarageSubService::class,'garage_service_id', 'id');
    }
    public function garage(){
        return $this->belongsTo(Garage::class,'garage_id', 'id')->withTrashed();
    }
    public function service(){
        return $this->belongsTo(Service::class,'service_id', 'id');
    }




    public function sub_services() {
        return $this->belongsToMany(SubService::class, 'garage_sub_services', 'garage_service_id', 'sub_service_id') ->withPivot([]);
    }
}
