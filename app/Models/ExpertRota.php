<?php



namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpertRota extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
                    'expert_id',
                    'date',
                    'busy_slots',
                  "is_active",
        "business_id",
        "created_by"
    ];

    protected $casts = [
    'busy_slots' => 'array',
  ];





    public function user()
    {
        return $this->belongsTo(User::class, 'expert_id','id');
    }



}

