<?php

namespace App\Models;

use App\Http\Utils\BasicUtil;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory,SoftDeletes, BasicUtil;

    protected $appends = ['main_price'];



    protected $fillable = [
        "job_start_time",
        "job_end_time",
        "receptionist_note",
        "expert_note",
        "booking_type",
        "next_visit_date",
        "send_notification",
        "payment_status",
        "payment_method",
        "expert_id",
        // "booked_slots",
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
        "tip_type",
        "tip_amount",
        "created_by",
        "created_from",
        'booking_from'
    ];

      protected $casts = [
        'booked_slots' => 'array',
      ];

      public function getBookedSlotsAttribute()
      {
        $businessSetting = $this->get_business_setting(auth()->user()->business_id);

          return  $this->generateSlots($businessSetting->slot_duration, $this->job_start_time, $this->job_end_time);
      }

      public function getMainPriceAttribute()
      {
          $finalPrice = $this->price;
          $tipAmount = $this->tip_amount;
          $tipType = $this->tip_type;

          if ($tipType === 'percentage') {
              $calculatedTip = $finalPrice * ($tipAmount / 100);
          } elseif ($tipType === 'fixed') {
              $calculatedTip = $tipAmount;
          } else {
              $calculatedTip = 0; // Default to 0 if tip type is unknown
          }

          // Calculate the main price by subtracting the tip
          $mainPrice = $finalPrice - $calculatedTip;

          return $mainPrice;
      }


    public function garage(){
        return $this->belongsTo(Garage::class,'garage_id', 'id')->withTrashed();
    }

    public function expert(){
        return $this->belongsTo(User::class,'expert_id', 'id')->withTrashed();
    }

    public function customer(){
        return $this->belongsTo(User::class,'customer_id', 'id')->withTrashed();
    }
    public function booking_payments(){
        return $this->hasMany(JobPayment::class,'booking_id', 'id');
    }

    public function payments(){
        return $this->hasMany(JobPayment::class,'booking_id', 'id');
    }
    public function feedbacks()
    {
        return $this->hasMany(ReviewNew::class, 'booking_id', 'id');
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
