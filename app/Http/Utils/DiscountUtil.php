<?php

namespace App\Http\Utils;

use App\Models\BookingSubService;
use App\Models\Coupon;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

trait DiscountUtil
{

    public function applyCoupon($request_data, $booking, $coupon)
    {
        if (empty($request_data["coupon_code"])) {
            return $booking; // No coupon to process
        }
        if ($request_data["coupon_code"] == $booking->coupon_code) {
            return $booking; // No coupon to process
        }

 // Increment customer redemptions for the coupon
 Coupon::where([
    "code" => $booking->coupon_code,
    "garage_id" => $booking->garage_id
])->update([
    "customer_redemptions" => DB::raw("customer_redemptions + 1")
]);

        $discount_type = $coupon->discount_type;
        $discount_amount = $coupon->discount_amount;
        if($coupon->discount_type == "fixed") {
            $booking->coupon_type = $discount_type;
            $booking->coupon_amount = $discount_amount;
        } else if ($coupon->discount_type == "percentage") {


            $coupon_sub_service_ids = $coupon->sub_services->pluck("id");
             $booking_sub_services = BookingSubService::where([
                 "booking_id" => $booking->id
             ])->get();

             $total_discount = 0;

             foreach ($booking_sub_services as $booking_sub_service) {
                if ($coupon_sub_service_ids->contains($booking_sub_service->sub_service_id)) {
                    // Apply discount logic here
                    // For example, add a discount to the booking or modify booking_sub_service
                    $discount_amount = $this->canculate_discount($booking_sub_service->price, "percentage",$coupon->discount_amount);

                    $booking_sub_service->discount_percentage =   $coupon->discount_amount;
                    $booking_sub_service->discounted_price_to_show = $booking_sub_service->price - $discount_amount;

                    $booking_sub_service->save();
                    $total_discount += $discount_amount;
                }
            }
            $booking->coupon_type = "fixed";
            $booking->coupon_amount = $total_discount;

        }

        $booking->coupon_code = $request_data["coupon_code"];
        $booking->save();



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

        if (!$coupon) {
            $error = [
                "message" => "The given data was invalid.",
                "errors" => ["coupon_code" => "no coupon is found"]
            ];
            throw new Exception(json_encode($error), 422);
        }

        if(!empty($coupon->min_total) && ($coupon->min_total > $amount )){
            $error = [
                "message" => "The given data was invalid.",
                "errors" => ["coupon_code" => "minimim limit is " . $coupon->min_total]
            ];
            throw new Exception(json_encode($error), 422);
        }
        if(!empty($coupon->max_total) && ($coupon->max_total < $amount)){
            $error = [
                "message" => "The given data was invalid.",
                "errors" => "maximum limit is " . $coupon->max_total
            ];
            throw new Exception(json_encode($error), 422);
        }

        if(!empty($coupon->redemptions) && $coupon->redemptions == $coupon->customer_redemptions){
            $error = [
                "message" => "The given data was invalid.",
                "errors" => "maximum people reached"
            ];
            throw new Exception(json_encode($error), 422);
        }
        return $coupon;

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


    public function canculate_discount($total_price, $discount_type, $discount_amount)
    {
        if (!empty($discount_type) && !empty($discount_amount)) {
            if ($discount_type == "fixed") {
                return $discount_amount;
            } else if ($discount_type == "percentage") {
                return ($total_price / 100) * $discount_amount;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }


}
