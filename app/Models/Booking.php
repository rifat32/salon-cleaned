<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        "payment_status",
        "payment_method",
        "expert_id",
        "booked_slots",
        "reason",
        "pre_booking_id",
        "garage_id",
        "booking_id",
        "customer_id",
        "additional_information",
        "status",
        "coupon_code",
        "job_start_date",
        "price",
        "discount_type",
        "discount_amount",
        "created_by",
        "created_from"

    ];
    protected $casts = [
        'booked_slots' => 'array',
      ];



    public function garage(){
        return $this->belongsTo(Garage::class,'garage_id', 'id')->withTrashed();
    }

    public function expert(){
        return $this->belongsTo(User::class,'expert_id', 'id')->withTrashed();
    }

    public function customer(){
        return $this->belongsTo(User::class,'customer_id', 'id')->withTrashed();
    }


    public function booking_sub_services(){
        return $this->hasMany(BookingSubService::class,'booking_id', 'id');
    }

    public function sub_services(){
        return $this->belongsToMany(SubService::class, "booking_sub_services",'booking_id', 'sub_service_id');
    }

    public function booking_packages(){
        return $this->hasMany(BookingPackage::class,'booking_id', 'id');
    }







}
