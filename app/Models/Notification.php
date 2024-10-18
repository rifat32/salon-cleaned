<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        "sender_id",
        "receiver_id",
        "customer_id",
        "garage_id",
        "bid_id",
        "pre_booking_id",
        "booking_id",
        "job_id",
        "notification_template_id",
        "status",

    ];




    public function template(){
        return $this->belongsTo(NotificationTemplate::class,'notification_template_id', 'id');
    }
    public function customer(){
        return $this->belongsTo(User::class,'customer_id', 'id')->withTrashed();
    }
    public function garage(){
        return $this->belongsTo(Garage::class,'garage_id', 'id')->withTrashed();
    }

    public function booking(){
        return $this->belongsTo(Booking::class,'booking_id', 'id')->withTrashed();
    }

}
