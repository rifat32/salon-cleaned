<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutomobileCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "name"
        // "is_active",

    ];
    public function makes(){
        return $this->hasMany(AutomobileMake::class,'automobile_category_id', 'id');
    }

}
