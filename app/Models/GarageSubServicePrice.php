<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarageSubServicePrice extends Model
{
    use HasFactory;
    protected $fillable = [
        "garage_sub_service_id",
        "automobile_make_id",
        "price",
        "business_id",
        "expert_id"
    ];

    public function garage_sub_service(){
        return $this->belongsTo(GarageSubService::class,'garage_sub_service_id', 'id');
    }
    public function automobile_make(){
        return $this->belongsTo(SubService::class,'automobile_make_id', 'id')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'expert_id','id');
    }

    public function garage()
    {
        return $this->belongsTo(Garage::class, 'business_id','id');
    }

}
