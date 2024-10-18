<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubService extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        "name",
        "description",
        "business_id",

        "service_id",
        "default_price",
        "is_fixed_price",
        "service_time_in_minute"
        // "is_active",

    ];

     // Accessor for default_price
     public function getPriceAttribute($value)
     {
        $price = $this->default_price;
        if(!empty(request()->input("expert_id"))) {
             $subServicePrice = SubServicePrice::where([
               "sub_service_id" => $this->id,
               "expert_id" => request()->input("expert_id")
             ])->first();
             if(!empty($subServicePrice)) {
                $price = $subServicePrice->price;
             }
        }
         return number_format($price, 2); // Format as currency
     }

    public function service(){
        return $this->belongsTo(Service::class,'service_id', 'id');
    }

    public function bookingSubServices()
    {
        return $this->hasMany(BookingSubService::class,"sub_service_id","id");
    }

    public function translation(){
        return $this->hasMany(SubServiceTranslation::class,'sub_service_id', 'id');
    }

    public function expert_price(){
        return $this->hasMany(SubServicePrice::class,'sub_service_id', 'id');
    }

    public function garage_automobile_sub_services () {
        return $this->hasMany(GarageSubService::class,"sub_service_id","id");
   }

}
