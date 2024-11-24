<?php



namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Good extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
                    'name',
                    'sku',
                    'product_category_id',
                    'preferred_supplier_id',
                    'cost_price',
                    'retail_price',
                    'barcode',
                    'current_stock',
                    'min_stock_level',

                  "is_active",



        "business_id",
        "created_by"
    ];

    protected $casts = [

  ];

  public function subServices()
  {
      return $this->belongsToMany(SubService::class, 'service_goods', 'good_id', 'sub_service_id')
                  ->withPivot('quantity_used')  // Include any additional fields from the pivot table
                  ->withTimestamps();  // Ensure created_at and updated_at are maintained
  }





    public function product_category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id','id');
    }




    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id','id');
    }





















}

