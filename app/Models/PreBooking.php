<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreBooking extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        "customer_id",
        "automobile_make_id",
        "automobile_model_id",
        "car_registration_no",
        "car_registration_year",
        "additional_information",
        "status",
        "job_start_date",
        "job_start_time",
        "job_end_date",
        "fuel",
        "transmission",

        "images",
        "videos",
        "file_links"


    ];
    public function garage(){
        return $this->belongsTo(Garage::class,'garage_id', 'id')->withTrashed();
    }
    public function customer(){
        return $this->belongsTo(User::class,'customer_id', 'id')->withTrashed();
    }
    public function automobile_make(){
        return $this->belongsTo(AutomobileMake::class,'automobile_make_id', 'id')->withTrashed();
    }
    public function automobile_model(){
        return $this->belongsTo(AutomobileModel::class,'automobile_model_id', 'id')->withTrashed();
    }
    public function pre_booking_sub_services(){
        return $this->hasMany(PreBookingSubService::class,'pre_booking_id', 'id');
    }
    public function job_bids(){
        return $this->hasMany(JobBid::class,'pre_booking_id', 'id');
    }
}
