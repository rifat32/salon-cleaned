<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarageAutomobileModel extends Model
{
    use HasFactory;
    protected $fillable = [
        "garage_automobile_make_id",
        "automobile_model_id",
    ];

    public function garageAutomobileMake(){
        return $this->belongsTo(GarageAutomobileMake::class,'garage_automobile_make_id', 'id');
    }
    public function automobileModel(){
        return $this->belongsTo(AutomobileModel::class,'automobile_model_id', 'id');
    }

    
}
