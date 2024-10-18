<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GaragePackage extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        "name",
        "description",
        "price",
        "garage_id",
        "is_active"
    ];

    public function garage(){
        return $this->belongsTo(Garage::class,'garage_id', 'id')->withTrashed();
    }
    public function garage_package_sub_services(){
        return $this->hasMany(GaragePackageSubService::class,'garage_package_id', 'id');
    }

    public function sub_services(){
        return $this->belongsToMany(SubService::class,'garage_package_sub_services',"garage_package_id", 'sub_service_id');
    }





















}
