<?php



namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubServicePrice extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'sub_service_id',
        'price',
        'expert_id',
        'description',
        "business_id",
        "created_by"
    ];

    protected $casts = [



  ];





    public function sub_service()
    {
        return $this->belongsTo(SubService::class, 'sub_service_id','id');
    }





    public function user()
    {
        return $this->belongsTo(User::class, 'expert_id','id');
    }




}

