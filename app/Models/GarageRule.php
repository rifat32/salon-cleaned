<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarageRule extends Model
{
    use HasFactory;


    protected $fillable = [
        "garage_id",
        "standard_lead_time",
        "booking_accept_start_time",
        "booking_accept_end_time",
        "block_out_days"
    ];

    public function garage(){
        return $this->belongsTo(Garage::class,'garage_id', 'id')->withTrashed();
    }
}
