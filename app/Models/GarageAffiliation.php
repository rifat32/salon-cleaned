<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarageAffiliation extends Model
{
    use HasFactory;
    protected $fillable = [
        "garage_id",
        "affiliation_id",
        "start_date",
        "end_date",
    ];



    public function garage(){
        return $this->belongsTo(Garage::class,'garage_id', 'id')->withTrashed();
    }
    public function affiliation(){
        return $this->belongsTo(Affiliation::class,'affiliation_id', 'id');
    }
}
