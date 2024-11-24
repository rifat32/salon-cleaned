<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'good_id',
        'quantity',
        'cost_per_unit',
    ];

    // Define the relationship to PurchaseOrder
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // Define the relationship to Good
    public function good()
    {
        return $this->belongsTo(Good::class);
    }
}
