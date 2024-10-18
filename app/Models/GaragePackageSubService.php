<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GaragePackageSubService extends Model
{
    use HasFactory;
    protected $fillable = [
        "sub_service_id",
        "garage_package_id",
    ];

    public function sub_service(){
        return $this->belongsTo(SubService::class,'sub_service_id', 'id');
    }
}
