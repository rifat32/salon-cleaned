<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceGood extends Model
{
    use HasFactory;


    protected $fillable = [
        'sub_service_id',
        'good_id',
        'quantity_used',
    ];

    // You can also define relationships if needed
    public function subService()
    {
        return $this->belongsTo(SubService::class);
    }

    public function good()
    {
        return $this->belongsTo(Good::class);
    }


}
