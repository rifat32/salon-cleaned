<?php


namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
                    'supplier_id',
                    'order_date',
                    'status',
                    'total_amount',
                    'received_date',

        "business_id",
        "created_by"
    ];

    protected $casts = [


  ];

  public function goods()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }



    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id','id');
    }




}

