<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewValueNew extends Model
{
    use HasFactory;
    protected $fillable = [
        "question_id",
        'tag_id' ,
        'star_id',
        'review_id',

    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function tag() {
        return $this->belongsTo(Tag::class,'tag_id','id');
    }
    public function star() {
        return $this->belongsTo(Star::class,'star_id','id');
    }
    public function question() {
        return $this->belongsTo(Question::class,'question_id','id');
    }
}
