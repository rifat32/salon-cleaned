<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        "name",
        "icon",
        "description",
        "image",
        "automobile_category_id",
        "business_id"
        // "is_active",

    ];

    public function category(){
        return $this->belongsTo(AutomobileCategory::class,'automobile_category_id', 'id');
    }
    public function subServices(){
        return $this->hasMany(SubService::class,'service_id', 'id');
    }

    public function translation(){
        return $this->hasMany(ServiceTranslation::class,'service_id', 'id');
    }





    public function automobile_sub_services(){
        return $this->hasMany(SubService::class,'service_id', 'id');
    }

    public function garageService(){
        return $this->belongsTo(GarageService::class,'id','service_id');
    }









}
