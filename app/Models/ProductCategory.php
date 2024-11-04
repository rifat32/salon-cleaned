<?php


namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
                    'name',
                    'description',

                  "is_active",



        "business_id",
        "created_by"
    ];

    protected $casts = [






  ];



















}

