<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarageAutomobileMake extends Model
{
    use HasFactory;

    protected $fillable = [
        "garage_id",
        "automobile_make_id",
    ];

    public function garage(){
        return $this->belongsTo(Garage::class,'garage_id', 'id')->withTrashed();
    }
    public function automobileMake(){
        return $this->belongsTo(AutomobileMake::class,'automobile_make_id', 'id');
    }

    public function garageAutomobileModels(){
        return $this->hasMany(GarageAutomobileModel::class,'garage_automobile_make_id', 'id');
    }
}
