<?php



namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePrice extends Model
{
    use HasFactory, DefaultQueryScopesTrait;

    protected $fillable = [
                    'service_id',
                    'price',
                    'expert_id',
                    'business_id',
                  "is_active",
        "business_id",
        "created_by"
    ];

    protected $casts = [

  ];





    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id','id');
    }




    public function user()
    {
        return $this->belongsTo(User::class, 'expert_id','id');
    }



    public function garage()
    {
        return $this->belongsTo(Garage::class, 'business_id','id');
    }


}

