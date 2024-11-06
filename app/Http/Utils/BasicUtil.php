<?php

namespace App\Http\Utils;

use App\Models\Booking;
use App\Models\BusinessSetting;
use App\Models\Coupon;
use App\Models\ExpertRota;
use App\Models\GarageTime;
use App\Models\JobPayment;
use App\Models\NotificationSetting;
use App\Models\ReviewNew;
use App\Models\Service;
use App\Models\SlotHold;
use App\Models\SubService;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

trait BasicUtil
{

    public function processRefund($booking){



        // Get the Stripe settings
        $stripeSetting = BusinessSetting::where('business_id', $booking->garage_id)->first();


        if (empty($stripeSetting)) {
            throw new Exception("No stripe seting found",403);

        }

        if (empty($stripeSetting->stripe_enabled)) {
            throw new Exception("Stripe is not enabled",403);

       }
        // Set Stripe API key
        $stripe = new \Stripe\StripeClient($stripeSetting->STRIPE_SECRET);

        // Find the payment intent or charge for the booking
        $paymentIntent = $booking->payment_intent_id;

        if (empty($paymentIntent)) {
            return response()->json([
                "message" => "No payment record found for this booking."
            ], 404);
        }

        // Create a refund for the payment intent
        try {
            $refund = $stripe->refunds->create([
                'payment_intent' => $paymentIntent, // Reference the payment intent
                'amount' => $booking->final_price * 100, // Refund full amount in cents
            ]);

            $booking->payment_status = 'refunded';
            $booking->save();
            JobPayment::where([
                "booking_id" => $booking->id,
            ])
            ->delete();
            return response()->json([
                "message" => "Refund successful",
                "refund_id" => $refund->id
            ], 200);
        } catch (Exception $e) {
            throw new Exception("Error processing refund: " . $e->getMessage(), 500);
        }
    }
public function get_appointment_trend_data($date, $expert_id){

    $data["revenue"] = JobPayment::whereHas("bookings", function($query) use($expert_id, $date) {
          $query->where("bookings.expert_id",$expert_id)
          ->whereDate("bookings.job_start_date",$date)
          ->where("bookings.status","converted_to_job")
          ;
    })
    ->sum("amount");

    $data["bookings"] = Booking::where("bookings.expert_id",$expert_id)
    ->whereDate("bookings.job_start_date",$date)
    ->where("bookings.status","converted_to_job")
    ->count();
}



    public function addCustomerData($user){

       


          $user->previous_bookings = Booking::with(
            "sub_services.service",
            "booking_packages.garage_package",
            "expert",
            "payments"
          )
          ->where("customer_id",$user->id)
          ->whereDate("job_start_date","<=",now())
          ->get();

          $user->upcoming_bookings = Booking::with(
            "sub_services.service",
            "booking_packages.garage_package",
            "expert",
            "payments"
          )
          ->where("customer_id",$user->id)
          ->whereDate("job_start_date",">",now())
          ->get();

          $user->reminder_bookings = Booking::with(
            "sub_services.service",
            "booking_packages.garage_package",
            "expert",
            "payments"
          )
          ->where("customer_id",$user->id)
          ->whereDate("next_visit_date",">=",now())
          ->get();


          $user->top_sub_services = SubService::
          withCount([
            'bookingSubServices as all_sales_count' => function ($query) use($user) {
                 $query->whereHas('booking', function ($query) use($user) {
                     $query
                     ->where("bookings.customer_id",$user->id)
                     ->where('bookings.status', 'converted_to_job') // Filter for converted bookings
                     ->when(auth()->user()->hasRole("business_experts"), function($query)  {
                         $query->where('bookings.expert_id', auth()->user()->id);
                    }); // Sales this month
                 });
             }
         ])
         ->with(['booking' => function ($query) {
            $query->with(['expert' => function ($query) {
                $query->select('users.id', 'users.first_Name', 'users.last_Name'); // Select expert details
            }]);
        }])
          ->whereHas("booking", function ($query) use($user) {
                $query->where("bookings.customer_id",$user->id);
          })
          ->orderBy('all_sales_count', 'desc')
          ->get();

        $user->top_experts = User::withCount([
            'expert_bookings as all_booking_count' => function ($query) use ($user) {
                    $query
                    ->where("bookings.customer_id",$user->id)
                    ->where('bookings.status', 'converted_to_job') // Only count converted bookings
                          ->when(auth()->user()->hasRole("business_experts"), function ($query) {
                              $query->where('bookings.expert_id', auth()->user()->id);
                          });

            }
        ])
        ->whereHas('expert_bookings', function ($query) use($user) {
            $query->where("bookings.customer_id",$user->id);
        })
        ->orderBy('all_booking_count', 'desc') // Order by the count of converted bookings
        ->get();






          return $user;

    }

    public static function getNotificationRecipients($booking)
{
    $recipientEmails = [];

    // Retrieve the notification setting
    $notification_setting = NotificationSetting::where([
        "business_id" => $booking->id
    ])->first();

    if (!$notification_setting) {
        return $recipientEmails; // Return empty if no settings found
    }

    // Notify customer
    if (!empty($notification_setting->notify_customer) &&
        $booking->customer &&
        !empty($booking->customer->email)) {

        $recipientEmails[] = $booking->customer->email;
    }

    // Notify receptionist(s)
    if (!empty($notification_setting->notify_receptionist)) {
        $receptionists = User::role('business_receptionist')
            ->where("business_id", $booking->garage_id)
            ->pluck('email')
            ->toArray();

        $recipientEmails = array_merge($recipientEmails, $receptionists);
    }

    // Notify business owner
    if (!empty($notification_setting->notify_business_owner) &&
        $booking->garage &&
        !empty($booking->garage->owner->email)) {

        $recipientEmails[] = $booking->garage->owner->email;
    }

    return $recipientEmails;
}




    public function calculateAverageRating($expert_id)
    {
        // Get the total count of reviews and sum of rates for approved reviews with the specified expert
        $reviewsQuery = ReviewNew::whereHas("booking", function ($query) use ($expert_id) {
            $query->where("bookings.expert_id", $expert_id);
        })
            ->where("status", "approved");

        // Count of total reviews
        $totalReviews = $reviewsQuery->count();

        // Sum of all rates
        $totalRate = $reviewsQuery->sum('rate');

        // Calculate the average rating out of 5
        $averageRating = $totalReviews > 0 ? ($totalRate / $totalReviews) : 0;

        // Round the average rating to a specific number of decimal places (optional)
        $averageRating = round($averageRating, 2); // rounds to 2 decimal places
        return $averageRating;
    }

    public function blockedSlots($date, $expert_id)
    {
        // Get all bookings for the provided date except the rejected ones
        $bookings = Booking::with([
            "customer" => function ($query) {
                $query->select("users.id", "users.first_Name", "users.last_Name");
            }
        ])
            ->whereDate("job_start_date", $date)
            ->whereNotIn("status", ["rejected_by_client", "rejected_by_garage_owner"])
            ->where([
                "expert_id" => $expert_id
            ])
            ->select("id", "booked_slots", "customer_id", "status")
            ->get();

        // Get all the booked slots as a flat array

        $data["bookings"] = $bookings;
        $data["booking_slots"] = $bookings->pluck('booked_slots')->flatten()->toArray();

        // Get all bookings for the provided date except the rejected ones
        $check_in_bookings = Booking::whereDate("job_start_date", $date)
            ->whereIn("status", ["check_in"])
            ->where([
                "expert_id" => $expert_id
            ])
            ->get();

        $data["check_in_slots"]  = $check_in_bookings->pluck('booked_slots')->flatten()->toArray();



        $expertRota = ExpertRota::where([
            "expert_id" =>  $expert_id
        ])
            ->whereDate("date", $date)
            ->first();
        if (!empty($expertRota)) {
            $expertRota->busy_slots;
        }
        $data["busy_slots"] = [];
        // If expertRota exists, merge its busy_slots with the booked slots
        if (!empty($expertRota)) {
            $data["busy_slots"] = $expertRota->busy_slots;
        }

        $currentHeldSlots = SlotHold::where('expert_id', $expert_id)
            ->where('held_until', '>', Carbon::now())
            ->get();

        $held_slots  = $currentHeldSlots->pluck('held_slots')->flatten()->toArray();

        $data["busy_slots"] = array_merge($data["busy_slots"], $held_slots);

        $data["all_blocked_slots"] = array_merge(
            $data["booking_slots"],
            $data["busy_slots"]
        );

        return $data;
    }

    public function convertToHoursOnly(array $times)
    {
        $hoursOnly = [];

        foreach ($times as $time) {
            // Convert the time string to a Carbon instance
            $carbonTime = Carbon::createFromFormat('g:i A', $time);

            // Extract hours and minutes
            $hours = $carbonTime->hour;
            $minutes = $carbonTime->minute;

            // Convert the time to hours in decimal (e.g., 9:30 AM becomes 9.5)
            $hoursOnly[] = $hours + ($minutes / 60);
        }

        return $hoursOnly;
    }

    // Method to convert decimal hours back to "g:i A" format
    public function convertToTimeFormat(array $decimalHours)
    {
        $timeFormat = [];

        foreach ($decimalHours as $decimalHour) {
            // Separate hours and minutes
            $hours = floor($decimalHour);  // Get the integer part (hours)
            $minutes = ($decimalHour - $hours) * 60;  // Get the decimal part and convert to minutes

            // Create a Carbon instance for the current time (no date)
            $carbonTime = Carbon::createFromTime($hours, $minutes);

            // Format the time as 'g:i A'
            $timeFormat[] = $carbonTime->format('g:i A');
        }

        return $timeFormat;
    }

    public function validateBookingSlots($id, $customer_id, $slots, $date, $expert_id, $total_time)
    {
        // Get all bookings for the provided date except the rejected ones
        $bookings = Booking::when(!empty($id), function ($query) use ($id) {
            $query->whereNotIn("id", [$id]);
        })
            ->whereDate("job_start_date", $date)
            ->whereNotIn("status", ["rejected_by_client", "rejected_by_garage_owner"])
            ->where([
                "expert_id" => $expert_id
            ])
            ->get();

        // Get all bookings for the provided date except the rejected ones
        $my_bookings = Booking::when(!empty($id), function ($query) use ($id) {
            $query->whereNotIn("id", [$id]);
        })
            ->whereDate("job_start_date", $date)
            ->whereNotIn("status", ["rejected_by_client", "rejected_by_garage_owner"])
            ->where([
                "customer_id" => $customer_id
            ])
            ->get();

        // $allBusySlots = $my_bookings->pluck('booked_slots')->flatten()->toArray();
        $allBusySlots = [];

        $booked_slots = $bookings->pluck('booked_slots')->flatten()->toArray();

        $allBusySlots = array_merge($allBusySlots, $booked_slots);

        $expertRota = ExpertRota::where([
            "expert_id" =>  $expert_id
        ])
            ->whereDate("date", $date)
            ->first();
        if (!empty($expertRota)) {
            $expertRota->busy_slots;
        }


        // If expertRota exists, merge its busy_slots with the booked slots
        if (!empty($expertRota) && !empty($expertRota->busy_slots)) {
            $allBusySlots = array_merge($allBusySlots, $expertRota->busy_slots);
        }

        $currentHeldSlots = SlotHold::where('expert_id', $expert_id)
            ->where('held_until', '>', Carbon::now())
            ->get();

        $held_slots  = $currentHeldSlots->pluck('held_slots')->flatten()->toArray();


        $allBusySlots = array_merge($allBusySlots, $held_slots);

        // Find overlapping slots between the input slots and the combined allBusySlots
        $overlappingSlots = array_intersect($slots, $allBusySlots);

        // If there are overlaps, return them or throw an error
        if (!empty($overlappingSlots)) {
            return [
                'status' => 'error',
                'message' => 'Some slots are already booked.',
                'overlapping_slots' => $overlappingSlots
            ];
        }

        $slot_numbers = ceil($total_time / 15);
        if (count($slots) != $slot_numbers) {
            return [
                'status' => 'error',
                'message' => ("You need exactly " . $slot_numbers . "slots."),
            ];
        }



        // If no overlaps, return success
        return [
            'status' => 'success',
            'message' => 'All slots are available.'
        ];
    }

    public function calculate_vat($total_price,$business_id){
        $business_setting = BusinessSetting::where([
            "business_id" => $business_id
        ])
        ->first();
        if(empty($business_setting) || empty($business_setting->vat_enabled)) {
           return [
            "vat_percentage" => 0,
            "vat_amount" => 0
           ];
        }
        return [
            "vat_percentage" => $business_setting->vat_percentage,
            "vat_amount" => ($total_price / 100) * $business_setting->vat_percentage
           ];
        return ;
    }


    public function canculate_discount_amount($total_price, $discount_type, $discount_amount)
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




    public function getMainRoleId($user = NULL)
    {
        // Retrieve the authenticated user
        if (empty($user)) {
            $user = auth()->user();
        }


        // Get all roles of the authenticated user
        $roles = $user->roles;

        // Extract the role IDs
        $roleIds = $roles->pluck('id');

        // Find the minimum role ID
        $minRoleId = $roleIds->min();

        return $minRoleId;
    }

    public function getCountryAndCity($latitude, $longitude)
    {
        if (empty($latitude) && empty($longitude)) {
            return null;
        }
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
            'latlng' => "{$latitude},{$longitude}",
            'key' => $apiKey,
        ]);

        if ($response->successful()) {
            $results = $response->json()['results'];
            if (count($results) > 0) {
                $addressComponents = $results[0]['address_components'];
                $country = null;
                $city = null;

                foreach ($addressComponents as $component) {
                    if (in_array('country', $component['types'])) {
                        $country = $component['long_name'];
                    }
                    if (in_array('locality', $component['types'])) {
                        $city = $component['long_name'];
                    }
                }

                return [
                    'country' => $country,
                    'city' => $city,
                ];
            }
        }

        return null;
    }


    public function validateGarageTimes($garage_id, $dayOfWeek, $job_start_time, $job_end_time = null)
    {
        $garage_time = GarageTime::where([
            "garage_id" => $garage_id
        ])
            ->where('garage_times.day', "=", $dayOfWeek)
            ->where('garage_times.is_closed', "=", 0)
            ->first();

        if (empty($garage_time)) {
            throw new Exception("Garage is not open on this day.");
        }

        $jobStartTime = Carbon::createFromFormat('H:i', $job_start_time)->format('H:i:s');
        $jobStartTime = Carbon::parse($jobStartTime);
        $openingTime = Carbon::parse($garage_time->opening_time);
        $closingTime = Carbon::parse($garage_time->closing_time);

        if ($jobStartTime->lessThan($openingTime) || $jobStartTime->greaterThanOrEqualTo($closingTime)) {
            throw new Exception('The job start time is outside of garage operating hours.', 401);
        }

        if ($job_end_time) {
            $jobEndTime = Carbon::createFromFormat('H:i', $job_end_time)->format('H:i:s');
            $jobEndTime = Carbon::parse($jobEndTime);

            if ($jobEndTime->lessThan($openingTime) || $jobEndTime->greaterThanOrEqualTo($closingTime)) {
                throw new Exception('The job end time is outside of garage operating hours.', 401);
            }
        }
    }




}
