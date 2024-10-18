<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutomobileMake extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        "name",
        "description",
        "automobile_category_id"
        // "is_active",

    ];
    public function category(){
        return $this->belongsTo(AutomobileCategory::class,'automobile_category_id', 'id');
    }
    public function models(){
        return $this->hasMany(AutomobileModel::class,'automobile_make_id', 'id');
    }
    public function automobile_models(){
        return $this->hasMany(AutomobileModel::class,'automobile_make_id', 'id');
    }

    public function garageAutoMobileMake(){
        return $this->belongsTo(GarageAutomobileMake::class,'id','automobile_make_id');
    }



}
