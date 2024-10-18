<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        "booking_id",
        "job_id",
        "payment_type_id",
        "amount",
    ];


    public function job(){
        return $this->belongsTo(Job::class,'job_id', 'id');
    }
    public function bookings(){
        return $this->belongsTo(Booking::class,'booking_id', 'id');
    }



}
