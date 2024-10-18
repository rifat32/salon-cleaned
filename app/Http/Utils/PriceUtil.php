<?php

namespace App\Http\Utils;

use App\Models\Coupon;
use App\Models\GarageSubServicePrice;
use App\Models\Service;
use App\Models\SubService;
use App\Models\SubServicePrice;
use Carbon\Carbon;
use Exception;

trait PriceUtil
{
    // this function do all the task and returns transaction id or -1
    public function getPrice($sub_service, $expert_id)
    {

        $price = $sub_service->default_price;


        $sub_service_price = SubServicePrice::where([
            "id" => $sub_service->id,
            "expert_id" => $expert_id
        ])
        ->first();
        if(!empty($sub_service_price)) {
            $price = $sub_service_price->price;
        }





        return number_format($price, 2); // Format as currency
    }
}
