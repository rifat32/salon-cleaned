<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobSubService extends Model
{
    use HasFactory;
    protected $fillable = [
        "job_id",
        "sub_service_id",
        "price"
    ];
    public function sub_service(){
        return $this->belongsTo(SubService::class,'sub_service_id', 'id')->withTrashed();
    }
}
