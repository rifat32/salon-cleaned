<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutomobileModel extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        "name",
        "description",
        "automobile_make_id"
        // "is_active",

    ];
    public function make(){
        return $this->belongsTo(AutomobileMake::class,'automobile_make_id', 'id');
    }

    public function garage_automobile_models () {
         return $this->hasMany(GarageAutomobileModel::class,"automobile_model_id","id");
    }
}
