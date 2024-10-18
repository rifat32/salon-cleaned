<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPackage extends Model
{
    use HasFactory;
    protected $fillable = [
        "job_id",
        "garage_package_id",
        "price"
    ];
    public function garage_package(){
        return $this->belongsTo(GaragePackage::class,'garage_package_id', 'id')->withTrashed();
    }
}
