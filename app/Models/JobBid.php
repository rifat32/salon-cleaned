<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobBid extends Model
{
    use HasFactory;
    protected $fillable = [
        "garage_id",
        "pre_booking_id",
        "price",
        "offer_template",
        "job_start_date",
        "job_start_time",
        "job_end_time",
        "status"
    ];
    public function garage(){
        return $this->belongsTo(Garage::class,'garage_id', 'id')->withTrashed();
    }
    public function pre_booking(){
        return $this->belongsTo(PreBooking::class,'pre_booking_id', 'id');
    }
}
