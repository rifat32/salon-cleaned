<?php


namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'date',
        'total_amount',
        'customer_id',
        "business_id",
        "created_by"
    ];

    protected $casts = [];


    public function user()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }
    public function salesItems()
    {
        return $this->hasMany(SalesItem::class, 'sale_id');
    }
}
