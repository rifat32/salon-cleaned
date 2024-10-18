<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomobileFuelType extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        "description",
        "automobile_model_variant_id"
        // "is_active",

    ];
    public function model_variant(){
        return $this->belongsTo(AutomobileModelVariant::class,'automobile_model_variant_id', 'id');
    }
}
