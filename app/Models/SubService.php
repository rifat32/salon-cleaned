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
        "discounted_price",
        "is_fixed_price",
        "number_of_slots"
        // "is_active",

    ];

    public function goods()
    {
        return $this->belongsToMany(Good::class, 'service_goods', 'sub_service_id', 'good_id')
                    ->withPivot('quantity_used')  // Include any additional fields from the pivot table
                    ->withTimestamps();  // This ensures that created_at and updated_at are maintained
    }
     // Accessor for default_price
     public function getPriceAttribute()
     {
        $price = !empty($this->discounted_price)?$this->discounted_price:$this->default_price;

        if(request()->filled("expert_id")) {
            $user = User::where("id", request()->input("expert_id"))->first();

          if(empty($user)){
            return number_format($price, 2); // Format as
          }

            $businessSetting = BusinessSetting::where([
                "business_id" => $user->business_id
            ])->first();

            if(empty($businessSetting)) {
                return number_format($price, 2); // Format as
            }

            if(empty($businessSetting->is_expert_price)) {
                return number_format($price, 2); // Format as
            }


                $subServicePrice = SubServicePrice::where([
                    "sub_service_id" => $this->id,
                    "expert_id" => request()->input("expert_id")
                  ])->first();
                  if(!empty($subServicePrice)) {
                     $price = $subServicePrice->price;
                  }

        }
         return $price; // Format as currency
     }



    public function service(){
        return $this->belongsTo(Service::class,'service_id', 'id');
    }

    public function bookingSubServices()
    {
        return $this->hasMany(BookingSubService::class,"sub_service_id","id");
    }
    public function booking()
    {
        return $this->belongsToMany(Booking::class,"booking_sub_services",'sub_service_id','booking_id');
    }


    public function translation(){
        return $this->hasMany(SubServiceTranslation::class,'sub_service_id', 'id');
    }

    public function expert_price(){
        return $this->hasMany(SubServicePrice::class,'sub_service_id', 'id');
    }

   





































}
