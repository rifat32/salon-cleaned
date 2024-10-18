<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomobileModelVariant extends Model
{
    use HasFactory;


    protected $fillable = [
        "name",
        "description",
        "automobile_model_id"
        // "is_active",

    ];
    public function model(){
        return $this->belongsTo(AutomobileModel::class,'automobile_model_id', 'id');
    }
}
