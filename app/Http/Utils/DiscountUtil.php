<?php

namespace App\Http\Utils;

use App\Models\Coupon;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

trait DiscountUtil
{

    public function applyCoupon($request_data, $total_price, $booking)
    {
        if (empty($request_data["coupon_code"])) {
            return $booking; // No coupon to process
        }

        $coupon_discount = $this->getCouponDiscount(
            $request_data["garage_id"],
            $request_data["coupon_code"],
            $total_price
        );

        if ($coupon_discount["success"]) {
            $booking->coupon_discount_type = $coupon_discount["discount_type"];
            $booking->coupon_discount_amount = $coupon_discount["discount_amount"];
            $booking->coupon_code = $request_data["coupon_code"];
            $booking->save();

            // Increment customer redemptions for the coupon
            Coupon::where([
                "code" => $booking->coupon_code,
                "garage_id" => $booking->garage_id
            ])->update([
                "customer_redemptions" => DB::raw("customer_redemptions + 1")
            ]);
        } else {
            $error = [
                "message" => "The given data was invalid.",
                "errors" => ["coupon_code" => [$coupon_discount["message"]]]
            ];
            throw new Exception(json_encode($error), 422);
        }

        return $booking;
    }
    // this function do all the task and returns transaction id or -1
    public function getCouponDiscount($garage_id,$code,$amount)
    {

     $coupon =  Coupon::where([
        "garage_id" => $garage_id,
            "code" => $code,
            "is_active" => 1,

        ])

        // ->where('coupon_start_date', '<=', Carbon::now()->subDay())
        // ->where('coupon_end_date', '>=', Carbon::now()->subDay())
        ->first();

        if(!$coupon){
         return [
                "success" =>false,
                "message" => "no coupon is found",
            ];
        }


        if(!empty($coupon->min_total) && ($coupon->min_total > $amount )){
            return [
                "success" =>false,
                "message" => "minimim limit is " . $coupon->min_total,
            ];
        }
        if(!empty($coupon->max_total) && ($coupon->max_total < $amount)){
            return [
                "success" =>false,
                "message" => "maximum limit is " . $coupon->max_total,
            ];
        }

        if(!empty($coupon->redemptions) && $coupon->redemptions == $coupon->customer_redemptions){
            return [
                "success" =>false,
                "message" => "maximum people reached",
            ];
        }



        return [
            "success" =>true,
            "discount_type" => $coupon->discount_type,
            "discount_amount" => $coupon->discount_amount
        ];


    }


    function calculateFinalPrice($price, $discountAmount, $discountType)
    {
        if ($discountType === 'fixed') {
            // Calculate the final price with fixed discount
            $finalPrice = $price - $discountAmount;
        } elseif ($discountType === 'percentage') {
            // Calculate the discount amount
            $discountPercentage = $discountAmount / 100;
            $discountAmount = $price * $discountPercentage;

            // Calculate the final price with percentage discount
            $finalPrice = $price - $discountAmount;
        } else {
            // Invalid discount type
            return null;
        }

        // Round the final price to 2 decimal places
        $finalPrice = round($finalPrice, 2);

        return $finalPrice;
    }

    function calculateDiscountPriceAmount($price, $discountAmount, $discountType)
    {
        if ($discountType === 'fixed') {
            // Calculate the final price with fixed discount
            // $discount = $discountAmount;
        } elseif ($discountType === 'percentage') {
            // Calculate the discount amount
            $discountPercentage = $discountAmount / 100;
            $discountAmount = $price * $discountPercentage;

            // Calculate the final price with percentage discount
            // $discount =  $discountAmount;
        } else {
            // Invalid discount type
            return null;
        }

        // Round the final price to 2 decimal places
        $discount = round($discountAmount, 2);

        return $discount;
    }





}
