<?php

namespace App\Http\Utils;

use App\Models\BusinessSetting;
use App\Models\Coupon;

use App\Models\SubServicePrice;
use App\Models\User;
use Carbon\Carbon;
use Exception;

trait PriceUtil
{
    // this function do all the task and returns transaction id or -1
    public function getPrice($sub_service, $expert_id)
    {


        $price = !empty($sub_service->discounted_price)?$sub_service->discounted_price:$sub_service->default_price;


        $user = User::where("id", $expert_id)->first();

        if(empty($user)){
          return $price; // Format as
        }

        $businessSetting = BusinessSetting::where([
            "business_id" => $user->business_id
        ])->first();


        if(empty($businessSetting)) {
            return $price; // Format as
        }

        if(empty($businessSetting->is_expert_price)) {
            return $price; // Format as
        }



        $sub_service_price = SubServicePrice::where([
            "id" => $sub_service->id,
            "expert_id" => $expert_id
        ])
        ->first();

        if(!empty($sub_service_price)) {
            $price = $sub_service_price->price;
        }



        return $price; // Format as currency
    }
}
