<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use HasFactory,SoftDeletes;


    protected $fillable = [
        "booking_id",
        "garage_id",
        "customer_id",
        "status",
        "payment_status",
        "additional_information",
        "discount_type",
        "discount_amount",
        "coupon_discount_type",
        "coupon_discount_amount",
        "price",
        "final_price",
        "job_start_date",
         "coupon_code",
    ];
    public function booking(){
        return $this->belongsTo(Booking::class,'booking_id', 'id')->withTrashed();
    }

    public function garage(){
        return $this->belongsTo(Garage::class,'garage_id', 'id')->withTrashed();
    }

    public function customer(){
        return $this->belongsTo(User::class,'customer_id', 'id')->withTrashed();
    }

    public function job_sub_services(){
        return $this->hasMany(JobSubService::class,'job_id', 'id');
    }
    public function job_packages(){
        return $this->hasMany(JobPackage::class,'job_id', 'id');
    }
    public function job_payments(){
        return $this->hasMany(JobPayment::class,'job_id', 'id');
    }

    
}
