<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubServiceTranslation extends Model
{
    use HasFactory;


    protected $fillable = [
        "sub_service_id",
        "language",
        "name_translation",
        "description_translation"
    ];



}
