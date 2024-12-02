<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingConfirmRequest;
use App\Http\Requests\BookingCreateRequest;
use App\Http\Requests\BookingStatusChangeRequest;

use App\Http\Requests\BookingUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\DiscountUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\GarageUtil;
use App\Http\Utils\PriceUtil;
use App\Http\Utils\UserActivityUtil;
use App\Mail\BookingCreateMail;
use App\Mail\BookingStatusUpdateMail;
use App\Mail\BookingUpdateMail;
use App\Mail\DynamicMail;
use App\Models\Booking;
use App\Models\BookingPackage;
use App\Models\BookingSubService;

use App\Models\GaragePackage;

use App\Models\Holiday;
use App\Models\Job;
use App\Models\JobBid;
use App\Models\JobPayment;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\PreBooking;
use App\Models\BusinessSetting;
use App\Models\SubService;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\WebhookEndpoint;
use Illuminate\Support\Facades\Hash;


class BookingController extends Controller
{
    use ErrorUtil, GarageUtil, PriceUtil, UserActivityUtil, DiscountUtil, BasicUtil;

    public function createPaymentIntent(Request $request)
    {
        // Retrieve booking or relevant object if necessary
        $bookingId = $request->booking_id;
        $booking = Booking::findOrFail($bookingId);

        // Stripe settings retrieval based on business or garage ID
        $stripeSetting = $this->get_business_setting($booking->garage_id);


        if (empty($stripeSetting)) {
            throw new Exception("No stripe seting found", 403);
        }

        if (empty($stripeSetting->stripe_enabled)) {
            throw new Exception("Stripe is not enabled", 403);
        }

        // Set Stripe client
        $stripe = new \Stripe\StripeClient($stripeSetting->STRIPE_SECRET);

        $discount = $this->canculate_discount_amount($booking->price, $booking->discount_type, $booking->discount_amount);
        $coupon_discount = $this->canculate_discount_amount($booking->price, $booking->coupon_discount_type, $booking->coupon_discount_amount);
        $total_discount = $discount + $coupon_discount;

        $totalTip = $this->canculate_discount_amount(
            $booking->price,
            $booking->tip_type,
            $booking->tip_amount
        );


        // Prepare payment intent data
        $paymentIntentData = [
            'amount' => ($booking->price + $totalTip + ($booking->vat_amount ?? 0)) * 100, // Adjusted amount in cents
            'currency' => 'usd',
            'payment_method_types' => ['card'],
            'metadata' => [
                'booking_id' => $booking->id,
                'our_url' => route('stripe.webhook'), // Webhook URL for tracking
            ],
        ];

        // Handle discounts (if applicable)
        if ($total_discount > 0) {
            $coupon = $stripe->coupons->create([
                'amount_off' => $total_discount * 100, // Amount in cents
                'currency' => 'usd',
                'duration' => 'once',
                'name' => 'Discount',
            ]);

            $paymentIntentData['discounts'] = [
                [
                    'coupon' => $coupon->id,
                ],
            ];
        }

        // Create payment intent
        $paymentIntent = $stripe->paymentIntents->create($paymentIntentData);

        JobPayment::create([
            "booking_id" => $booking->id,
            "amount" => $booking->final_price,
            "payment_type" => "stripe"
        ]);

        Booking::where([
            "id" => $booking->id
        ])
            ->update([
                "payment_status" => "complete",
                "payment_method" => "stripe"
            ]);

        // Save the payment intent ID to the booking record
        $booking->payment_intent_id = $paymentIntent->id; // Assuming there's a `payment_intent_id` column in the `bookings` table
        $booking->save();

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret
        ]);
    }

    public function createRefund(Request $request)
    {
        $bookingId = $request->booking_id;
        $booking = Booking::findOrFail($bookingId);

        // Get the Stripe settings
        $stripeSetting = $this->get_business_setting($booking->garage_id);


        if (empty($stripeSetting)) {
            throw new Exception("No stripe seting found", 403);
        }

        if (empty($stripeSetting->stripe_enabled)) {
            throw new Exception("Stripe is not enabled", 403);
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

            // Update the booking or any other record to reflect the refund
            $booking->payment_status = 'refunded';
            $booking->save();
            JobPayment::where([
                "booking_id" => $booking->id
            ])
                ->delete();
            return response()->json([
                "message" => "Refund successful",
                "refund_id" => $refund->id
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "message" => "Refund failed: " . $e->getMessage()
            ], 500);
        }
    }






    public function redirectUserToStripe(Request $request)
    {
        $id = $request->id;
        $trimmed_id =   $request->id;

        // Check if the string is at least 20 characters long to ensure it has enough characters to remove
        if (empty($trimmed_id)) {
            // Remove the first ten characters and the last ten characters
            throw new Exception("invalid id");
        }

        $booking = Booking::findOrFail($trimmed_id);

        if (empty($booking->price) || empty($booking->final_price)) {
            return response()->json([
                "message" => "You booking price is zero. it's a software error."
            ], 409);
        } else if ($booking->price < 0 || $booking->final_price < 0) {
            return response()->json([
                "message" => "You booking price is zero. it's a software error."
            ], 409);
        }

        if ($booking->payment_status == "completed") {
            return response()->json([
                "message" => "Already paid"
            ], 409);
        }

        $stripeSetting = $this->get_business_setting($booking->garage_id);


        if (empty($stripeSetting)) {
            throw new Exception("No stripe seting found", 403);
        }

        if (empty($stripeSetting->stripe_enabled)) {
            throw new Exception("Stripe is not enabled", 403);
        }

        Stripe::setApiKey($stripeSetting->STRIPE_SECRET);
        Stripe::setClientId($stripeSetting->STRIPE_KEY);

        // Retrieve all webhook endpoints from Stripe
        $webhookEndpoints = WebhookEndpoint::all();

        // Check if a webhook endpoint with the desired URL already exists
        $existingEndpoint = collect($webhookEndpoints->data)->first(function ($endpoint) {
            return $endpoint->url === route('stripe.webhook'); // Replace with your actual endpoint URL
        });

        if (!$existingEndpoint) {
            // Create the webhook endpoint
            $webhookEndpoint = WebhookEndpoint::create([
                'url' => route('stripe.webhook'),
                'enabled_events' => ['checkout.session.completed'], // Specify the events you want to listen to
            ]);
        }

        $user = User::where([
            "id" => $booking->customer_id
        ])->first();

        if (empty($user->stripe_id)) {
            $stripe_customer = \Stripe\Customer::create([
                'email' => $user->email,
                'name' => $user->first_Name . " " . $user->last_Name,
            ]);
            $user->stripe_id = $stripe_customer->id;
            $user->save();
        }

        $discount = $this->canculate_discount_amount($booking->price, $booking->discount_type, $booking->discount_amount);

        $coupon_discount = $this->canculate_discount_amount($booking->price, $booking->coupon_discount_type, $booking->coupon_discount_amount);

        $total_discount = $discount + $coupon_discount;

        $totalTip = $this->canculate_discount_amount(
            $booking->price,
            $booking->tip_type,
            $booking->tip_amount
        );

        $vatAmount = $booking->vat_amount ?? 0;

        $lineItems = [];

        // Loop through each sub-service and add its price as a line item
        foreach ($booking->sub_services as $subService) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'GBP',
                    'product_data' => [
                        'name' => $subService->name, // Name of the sub-service
                    ],
                    'unit_amount' => $subService->price * 100, // Amount in cents
                ],
                'quantity' => 1,
            ];
        }

        if($vatAmount > 0) {
 // Add VAT as a separate line item
 $lineItems[] = [
    'price_data' => [
        'currency' => 'GBP',
        'product_data' => [
            'name' => 'VAT',
        ],
        'unit_amount' => $vatAmount * 100, // Amount in cents
    ],
    'quantity' => 1,
];
        }

        if($totalTip > 0) {
            // Add VAT as a separate line item
            $lineItems[] = [
               'price_data' => [
                   'currency' => 'GBP',
                   'product_data' => [
                       'name' => 'VAT',
                   ],
                   'unit_amount' => $vatAmount * 100, // Amount in cents
               ],
               'quantity' => 1,
           ];
                   }





        $session_data = [
            'payment_method_types' => ['card'],
            'metadata' => [
                'our_url' => route('stripe.webhook'),
                "booking_id" => $booking->id
            ],
            'line_items' => $lineItems,
            'customer' => $user->stripe_id ?? null,
            'mode' => 'payment',
            'success_url' => env("FRONT_END_URL") . "/bookings",
            'cancel_url' => env("FRONT_END_URL") . "/bookings",
        ];


        // Add discount line item only if discount amount is greater than 0 and not null
        if (!empty($total_discount)) {

            $coupon = \Stripe\Coupon::create([
                'amount_off' => $total_discount * 100, // Amount in cents
                'currency' => 'GBP', // The currency
                'duration' => 'once', // Can be once, forever, or repeating
                'name' => "Discount", // Coupon name
            ]);

            $session_data["discounts"] =  [ // Add the discount information here
                [
                    'coupon' => $coupon->id, // Use coupon ID if created
                ],
            ];
        }

        $session = Session::create($session_data);

        return redirect()->to($session->url);
    }

    /**
     *
     * @OA\Post(
     *      path="/v1.0/bookings",
     *      operationId="createBooking",
     *      tags={"booking_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store booking",
     *      description="This method is to store booking",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"garage_id","coupon_code","automobile_make_id","automobile_model_id","car_registration_no","car_registration_year","booking_sub_service_ids","booking_garage_package_ids"},
     *
     *      *    @OA\Property(property="customer_id", type="number", format="number",example="1"),
     *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     *   *    @OA\Property(property="coupon_code", type="string", format="string",example="123456"),
     *     *       @OA\Property(property="reason", type="string", format="string",example="pending"),
     *    @OA\Property(property="automobile_make_id", type="number", format="number",example="1"),
     *    @OA\Property(property="automobile_model_id", type="number", format="number",example="1"),
     * * *    @OA\Property(property="car_registration_no", type="string", format="string",example="r-00011111"),
     *     * * *    @OA\Property(property="car_registration_year", type="string", format="string",example="2019-06-29"),
     *
     *   * *    @OA\Property(property="additional_information", type="string", format="string",example="r-00011111"),
     *
     *  *   * *    @OA\Property(property="transmission", type="string", format="string",example="transmission"),
     *         @OA\Property(property="fuel", type="string", format="string",example="Fuel"),
     *  * *    @OA\Property(property="booking_sub_service_ids", type="string", format="array",example={1,2,3,4}),
     *  *  * *    @OA\Property(property="booking_garage_package_ids", type="string", format="array",example={1,2,3,4}),
     * *  *
     * *
     * *     * *   *    @OA\Property(property="price", type="number", format="number",example="30"),
     *  *  * *    @OA\Property(property="discount_type", type="string", format="string",example="percentage"),
     * *  * *    @OA\Property(property="discount_amount", type="number", format="number",example="10"),
     *  * @OA\Property(property="job_start_date", type="string", format="string",example="2019-06-29"),
     *
     * * @OA\Property(property="job_start_time", type="string", format="string",example="08:10"),

     *  * *    @OA\Property(property="job_end_time", type="string", format="string",example="10:10"),
     *
     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function createBooking(BookingCreateRequest $request)
    {
        try {
            DB::beginTransaction();
            $this->storeActivity($request, "");

            if (!$request->user()->hasPermissionTo('booking_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $request_data = $request->validated();

            if (auth()->user()->hasRole("business_expert")) {
                unset($request_data["receptionist_note"]);
                if(auth()->user()->id !== $request_data["expert_id"]) {
                    unset($request_data["expert_note"]);
                }

            } else {
                unset($request_data["expert_note"]);
            }


            $holidays = Holiday::whereDate("start_date", "<=", $request_data["job_start_date"])
                ->whereDate("end_date", ">=", $request_data["job_start_date"])
                ->get();

            if ($holidays->count()) {
                return response()->json([
                    "message" => "some off days are exists",
                    "conflicted_holidays" => $holidays
                ], 409);
            }
            $request_data["booking_type"] = "admin_panel_booking";

            if (empty($request_data["customer_id"])) {
                $request_data["booking_type"] = "walk_in_customer_booking";

                $walkInCustomer = new User(); // Assuming you are using the User model for walk-in customers
                $walkInCustomer->business_id = auth()->user()->business_id;
                $walkInCustomer->first_Name = !empty($request_data['first_Name']) ? $request_data['first_Name'] : null;
                $walkInCustomer->last_Name = !empty($request_data['last_Name']) ? $request_data['last_Name'] : null;
                $walkInCustomer->phone = !empty($request_data['phone']) ? $request_data['phone'] : null;
                $walkInCustomer->email = !empty($request_data['email']) ? $request_data['email'] : null;
                $walkInCustomer->is_walk_in_customer = 1;
                $walkInCustomer->address_line_1 = !empty($request_data['address_line_1']) ? $request_data['address_line_1'] : null;
                $walkInCustomer->address_line_2 = !empty($request_data['address_line_2']) ? $request_data['address_line_2'] : null;
                $walkInCustomer->country = !empty($request_data['country']) ? $request_data['country'] : null;
                $walkInCustomer->city = !empty($request_data['city']) ? $request_data['city'] : null;
                $walkInCustomer->postcode = !empty($request_data['postcode']) ? $request_data['postcode'] : null;
                $walkInCustomer->is_active = true; // Assuming walk-in customers are active by default

                // Set a dummy password
                $dummyPassword = 'dummyPassword'; // You can change this to any default string
                $walkInCustomer->password = Hash::make($dummyPassword); // Hash the dummy password

                $walkInCustomer->save();

                $request_data["customer_id"] = $walkInCustomer->id;
            }



            if (!$this->garageOwnerCheck($request_data["garage_id"])) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }



            $request_data["status"] = "pending";
            $request_data["created_by"] = $request->user()->id;
            $request_data["created_from"] = "garage_owner_side";
            $request_data["payment_status"] = "pending";



            $booking =  Booking::create($request_data);
            $businessSetting = $this->get_business_setting($booking->garage_id);


            $total_price = 0;
            $total_time = 0;
            foreach ($request_data["booking_sub_service_ids"] as $index => $sub_service_id) {
                $sub_service =  SubService::where([
                    "business_id" => auth()->user()->business_id,
                    "id" => $sub_service_id
                ])
                    ->first();

                if (!$sub_service) {
                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => [("booking_sub_service_ids[" . $index . "]") => ["invalid service"]]
                    ];
                    throw new Exception(json_encode($error), 422);
                }

                $price = $this->getPrice($sub_service, $request_data["expert_id"]);

                $total_time += $sub_service->number_of_slots * $businessSetting->slot_duration;


                $total_price += $price;

                $booking->booking_sub_services()->create([
                    "sub_service_id" => $sub_service->id,
                    "price" => $price
                ]);
            }
            foreach ($request_data["booking_garage_package_ids"] as $index => $garage_package_id) {
                $garage_package =  GaragePackage::where([
                    "garage_id" => $request_data["garage_id"],
                    "id" => $garage_package_id
                ])
                    ->first();

                if (!$garage_package) {

                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => [("booking_garage_package_ids[" . $index . "]") => ["invalid package"]]
                    ];
                    throw new Exception(json_encode($error), 422);
                }


                $total_price += $garage_package->price;

                    $total_time += $garage_package->number_of_slots * $businessSetting->slot_duration;


                $booking->booking_packages()->create([
                    "garage_package_id" => $garage_package->id,
                    "price" => $garage_package->price
                ]);
            }




            $slotValidation =  $this->validateBookingSlots($businessSetting,$booking->id, $booking->customer_id, $request["booked_slots"], $request["job_start_date"], $request["expert_id"], $total_time);


            if ($slotValidation['status'] === 'error') {
                // Return a JSON response with the overlapping slots and a 422 Unprocessable Entity status code
                throw new Exception($slotValidation["message"],422);

            }

            $processedSlotInformation =  $this->processSlots($businessSetting->slot_duration,$request["booked_slots"]);
            if (count($processedSlotInformation) > 1 || count($processedSlotInformation) == 0) {
                // Return a JSON response with the overlapping slots and a 422 Unprocessable Entity status code
                throw new Exception("Slots must be continuous");
            }

            $booking->job_start_time = $processedSlotInformation[0]["start_time"];
            $booking->job_end_time = $processedSlotInformation[0]["end_time"];

            $this->validateGarageTimes($booking->garage_id,$booking->job_start_date, $booking->job_start_time, $booking->job_end_time);




            $booking->price = $total_price;
            $booking->save();


            if (!empty($request_data["coupon_code"])) {
                $coupon = $this->getCouponDiscount(
                    $request_data["garage_id"],
                    $request_data["coupon_code"],
                    $total_price
                );
                $booking = $this->applyCoupon($request_data, $booking, $coupon);
            }



            $booking->final_price = $booking->price;

            $booking->final_price -= $this->canculate_discount_amount($booking->price, $booking->discount_type, $booking->discount_amount);

            $booking->final_price -= $this->canculate_discount_amount($booking->price, $booking->coupon_discount_type, $booking->coupon_discount_amount);

            $vat_information = $this->calculate_vat(
                $booking->final_price,
                $booking->garage_id,
            );

            $booking->vat_percentage = $vat_information["vat_percentage"];
            $booking->vat_amount = $vat_information["vat_amount"];

            $booking->final_price += $vat_information["vat_amount"];

            $booking->final_price += $this->canculate_discount_amount(
                $booking->price,
                $booking->tip_type,
                $booking->tip_amount
            );

            $booking->save();


            $notification_template = NotificationTemplate::where([
                "type" => "booking_created_by_garage_owner"
            ])
                ->first();
            if (!$notification_template) {
                throw new Exception("notification template error");
            }

            $recipientIds = [$booking->customer->id];

            //  // Retrieve emails of users with the role 'business_receptionist'
            //  $receptionists = User::role('business_receptionist')
            //  ->where("business_id",$booking->garage_id)
            //  ->pluck('id')->toArray();

            //  // Merge the two arrays
            //  $recipientIds = array_merge($recipientIds, $receptionists);

            foreach ($recipientIds as $recipientId) {
                Notification::create([
                    "sender_id" => $request->user()->id,
                    "receiver_id" => $recipientId,
                    "customer_id" => $booking->customer->id,
                    "business_id" => $booking->garage_id,
                    "garage_id" => $booking->garage_id,
                    "booking_id" => $booking->id,
                    "entity_name" => "booking",
                    "entity_id" => $booking->id,
                    "entity_ids" => json_encode([]),
                    "notification_title" => 'Booking Created',
                    "notification_description" => "A new booking has been created for booking ID: {$booking->id}.",
                    "notification_link" => null,
                    "is_system_generated" => false,
                    "notification_template_id" => $notification_template->id,
                    "status" => "unread",
                    "start_date" => now(),
                    "end_date" => null,
                ]);
            }


            // if (env("SEND_EMAIL") == true) {
            //     Mail::to($booking->customer->email)->send(new DynamicMail(
            //         $booking,
            //         "booking_created_by_garage_owner"
            //     ));
            // }
            if (env("SEND_EMAIL") == true) {
                $recipientEmails = $this->getNotificationRecipients($booking);

                if (!empty($recipientEmails)) {
                    Mail::to($recipientEmails)->send(new BookingCreateMail($booking));
                }
            }

            $booking = $booking->load(["payments"]);
            DB::commit();
            return response($booking, 201);
        } catch (Exception $e) {
            DB::rollBack();


            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/bookings",
     *      operationId="updateBooking",
     *      tags={"booking_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update booking",
     *      description="This method is to update booking",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","garage_id","coupon_code","price","automobile_make_id","automobile_model_id","car_registration_no","car_registration_year","booking_sub_service_ids","booking_garage_package_ids","job_start_time","job_end_time"},
     * *    @OA\Property(property="id", type="number", format="number",example="1"),
     *  * *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     *  * *    @OA\Property(property="discount_type", type="string", format="string",example="percentage"),
     * *  * *    @OA\Property(property="discount_amount", type="number", format="number",example="10"),
     *
     *     * *   *    @OA\Property(property="price", type="number", format="number",example="30"),
     *    @OA\Property(property="automobile_make_id", type="number", format="number",example="1"),
     *    @OA\Property(property="automobile_model_id", type="number", format="number",example="1"),

     * *    @OA\Property(property="car_registration_no", type="string", format="string",example="r-00011111"),
     * *     * * *    @OA\Property(property="car_registration_year", type="string", format="string",example="2019-06-29"),
     *  * *    @OA\Property(property="booking_sub_service_ids", type="string", format="array",example={1,2,3,4}),
     * *  * *    @OA\Property(property="booking_garage_package_ids", type="string", format="array",example={1,2,3,4}),
     *
     *
     *  *  * * *   *    @OA\Property(property="status", type="string", format="string",example="pending"),
     *       @OA\Property(property="reason", type="string", format="string",example="pending"),
     *
     *
     *  * @OA\Property(property="job_start_date", type="string", format="string",example="2019-06-29"),
     *
     * * @OA\Property(property="job_start_time", type="string", format="string",example="08:10"),

     *  * *    @OA\Property(property="job_end_time", type="string", format="string",example="10:10"),
     *
     *
     *
     *     *  *   * *    @OA\Property(property="transmission", type="string", format="string",example="transmission"),
     *
     *    *  *   * *    @OA\Property(property="fuel", type="string", format="string",example="Fuel"),
     *
     *
     *
     *
     *
     *         ),

     *
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function updateBooking(BookingUpdateRequest $request)
    {
        try {
            $this->storeActivity($request, "");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('booking_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();

                if (auth()->user()->hasRole("business_expert")) {
                    unset($request_data["receptionist_note"]);
                    if(auth()->user()->id !== $request_data["expert_id"]) {
                        unset($request_data["expert_note"]);
                    }
                } else {
                    unset($request_data["expert_note"]);
                }



                if (!$this->garageOwnerCheck($request_data["garage_id"])) {
                    return response()->json([
                        "message" => "you are not the owner of the garage or the requested garage does not exist."
                    ], 401);
                }


                $booking = Booking::where([
                    "id" => $request_data["id"],
                    "garage_id" =>  $request_data["garage_id"]
                ])->first();

                if (!$booking) {
                    return response()->json([
                        "message" => "booking not found"
                    ], 404);
                }

                $businessSetting = $this->get_business_setting($booking->garage_id);

                if(!auth()->user()->hasRole("garage_owner")) {
                    if ($booking->status == "converted_to_job" && $booking->payment_status == "complete") {
                        // Return an error response indicating that the status cannot be updated
                        return response()->json(["message" => "Unable to change the appointment status because it is already complete."], 422);
                    }
                }



                if ($request_data["status"] == "converted_to_job" && ($booking->status != "check_in" && $booking->status != "converted_to_job")) {
                    return response()->json(["message" => "You cannot check out before check in."], 422);
                }
                if ($request_data["status"] == "check_in") {
                    $check_in_booking = Booking::where([
                            "expert_id" => $request_data["expert_id"],
                            "status" => "check_in"
                        ])
                        ->whereNotIn("bookings.id", [$booking->id])
                        ->first();

                    if (!empty($check_in_booking)) {
                        // Return an error response indicating that the expert already has a check-in
                        return response()->json([
                            "message" => "Expert " . ($check_in_booking->expert->first_Name . " " . $check_in_booking->expert->last_Name) . " already has a booking checked in on " . $check_in_booking->job_start_date . " at " . $check_in_booking->job_start_time . " (Booking ID: " . $check_in_booking->id . "). Please complete that booking to start a new one.",
                            "current_booking_id" => $check_in_booking->id,
                        ], 409);
                    }
                }

                $holidays = Holiday::whereDate("start_date", "<=", $request_data["job_start_date"])
                    ->whereDate("end_date", ">=", $request_data["job_start_date"])
                    ->get();

                if ($holidays->count()) {
                    return response()->json([
                        "message" => "some off days are exists",
                        "conflicted_holidays" => $holidays
                    ], 409);
                }




                $booking->fill(collect($request_data)->only([
                    "receptionist_note",
                    "expert_note",
                    "status",
                    "job_start_date",
                    "discount_type",
                    "discount_amount",
                    "expert_id",
                    "booked_slots",
                    "reason",
                    "next_visit_date",
                    "send_notification",
                    'booking_from'
                ])->toArray());

                BookingSubService::where([
                    "booking_id" => $booking->id
                ])->delete();
                BookingPackage::where([
                    "booking_id" => $booking->id
                ])->delete();

                $total_price = 0;
                $total_time = 0;
                foreach ($request["booking_sub_service_ids"] as $index => $sub_service_id) {
                    $sub_service =  SubService::where([
                        "business_id" => auth()->user()->business_id,
                        "id" => $sub_service_id
                    ])
                        ->first();

                    if (!$sub_service) {
                        $error =  [
                            "message" => "The given data was invalid.",
                            "errors" => [("booking_sub_service_ids[" . $index . "]") => ["invalid service"]]
                        ];
                        throw new Exception(json_encode($error), 422);
                    }

                    $price = $this->getPrice($sub_service, $request["expert_id"]);

                    $total_time += $sub_service->number_of_slots * $businessSetting->slot_duration;


                    $total_price += $price;

                    $booking->booking_sub_services()->create([
                        "sub_service_id" => $sub_service->id,
                        "price" => $price
                    ]);
                }
                foreach ($request_data["booking_garage_package_ids"] as $index => $garage_package_id) {
                    $garage_package =  GaragePackage::where([
                        "garage_id" => $booking->garage_id,
                        "id" => $garage_package_id
                    ])
                        ->first();

                    if (!$garage_package) {
                        $error =  [
                            "message" => "The given data was invalid.",
                            "errors" => [("booking_garage_package_ids[" . $index . "]") => ["invalid package"]]
                        ];
                        throw new Exception(json_encode($error), 422);
                    }


                    $total_price += $garage_package->price;
                    $total_time += $garage_package->number_of_slots * $businessSetting->slot_duration;

                    $booking->booking_packages()->create([
                        "garage_package_id" => $garage_package->id,
                        "price" => $garage_package->price
                    ]);
                }


                // $slotValidation =  $this->validateBookingSlots($booking->id,$booking->customer_id, $request["booked_slots"], $request["job_start_date"], $request["expert_id"], $total_time);

                // if ($slotValidation['status'] === 'error') {
                //     // Return a JSON response with the overlapping slots and a 422 Unprocessable Entity status code
                //     return response()->json($slotValidation, 422);
                // }

                $processedSlotInformation =  $this->processSlots($businessSetting->slot_duration,$booking->booked_slots);

                if (count($processedSlotInformation) > 1 || count($processedSlotInformation) == 0) {
                    // Return a JSON response with the overlapping slots and a 422 Unprocessable Entity status code
                    throw new Exception("Slots must be continuous");
                }

                $booking->job_start_time = $processedSlotInformation[0]["start_time"];
                $booking->job_end_time = $processedSlotInformation[0]["end_time"];

                $this->validateGarageTimes($booking->garage_id,$booking->job_start_date, $booking->job_start_time, $booking->job_end_time);




                // $booking->price = (!empty($request_data["price"]?$request_data["price"]:$total_price));
                $booking->price = $total_price;



                // if(!empty($request_data["coupon_code"])){
                //     $coupon_discount = $this->getCouponDiscount(
                //         $request_data["garage_id"],
                //         $request_data["coupon_code"],
                //         $booking->price
                //     );

                //     if($coupon_discount) {

                //         $booking->coupon_discount_type = $coupon_discount["discount_type"];
                //         $booking->coupon_discount_amount = $coupon_discount["discount_amount"];


                //     }
                // }


                $booking->final_price = $booking->price;
                $booking->final_price -= $this->canculate_discount_amount($booking->price, $booking->discount_type, $booking->discount_amount);
                $booking->final_price -= $this->canculate_discount_amount($booking->price, $booking->coupon_discount_type, $booking->coupon_discount_amount);
                $vat_information = $this->calculate_vat(
                    $booking->final_price,
                    $booking->garage_id,
                );
                $booking->vat_percentage = $vat_information["vat_percentage"];
                $booking->vat_amount = $vat_information["vat_amount"];
                $booking->final_price += $vat_information["vat_amount"];
                $booking->final_price += $this->canculate_discount_amount(
                    $booking->price,
                    $booking->tip_type,
                    $booking->tip_amount
                );
                $booking->save();


                $notification_template = NotificationTemplate::where([
                    "type" => "booking_updated_by_garage_owner"
                ])
                    ->first();
                // Get the customer's email
                $recipientIds = [$booking->customer->id];

                //  // Retrieve emails of users with the role 'business_receptionist'
                //  $receptionists = User::role('business_receptionist')
                //  ->where("business_id",$booking->garage_id)
                //  ->pluck('id')->toArray();

                //  // Merge the two arrays
                //  $recipientIds = array_merge($recipientIds, $receptionists);

                foreach ($recipientIds as $recipientId) {
                    Notification::create([
                        "sender_id" => $request->user()->id,
                        "receiver_id" => $recipientId,
                        "customer_id" => $booking->customer->id,
                        "business_id" => $booking->garage_id,
                        "garage_id" => $booking->garage_id,
                        "booking_id" => $booking->id,
                        "entity_name" => "booking",
                        "entity_id" => $booking->id,
                        "entity_ids" => json_encode([]),
                        "notification_title" => 'Booking Updated',
                        "notification_description" => "The details of booking ID: {$booking->id} have been updated.",
                        "notification_link" => null,
                        "is_system_generated" => false,
                        "notification_template_id" => $notification_template->id,
                        "status" => "unread",
                        "start_date" => now(),
                        "end_date" => null,
                    ]);
                }

                // if (env("SEND_EMAIL") == true) {
                //     Mail::to($booking->customer->email)->send(new DynamicMail(
                //         $booking,
                //         "booking_updated_by_garage_owner"
                //     ));
                // }

                if (!empty($request_data["payments"])) {
                    $total_payable = $booking->final_price;


                    $payments = collect($request_data["payments"]);

                    $payment_amount =  $payments->sum("amount");

                    $job_payment_amount =  JobPayment::where([
                        "booking_id" => $booking->id
                    ])->sum("amount");

                    $total_payment = $job_payment_amount + $payment_amount;

                    // if ($total_payable < $total_payment) {
                    //     return response([
                    //         "message" => "payment is greater than payable. You need to pay ".$total_payable." You are paying ". $total_payment
                    //     ], 409);
                    // }

                    foreach ($payments->all() as $payment) {
                        JobPayment::create([
                            "booking_id" => $booking->id,
                            "payment_type" => $payment["payment_type"],
                            "amount" => $payment["amount"],
                        ]);
                    }


                    if ($total_payable <= $total_payment) {
                        if ($total_payable < $total_payment) {
                            JobPayment::create([
                                "booking_id" => $booking->id,
                                "payment_type" => "change",
                                "amount" => ($total_payable - $total_payment),
                            ]);
                        }
                        Booking::where([
                            "id" => $booking->id
                        ])
                            ->update([
                                "payment_status" => "complete",
                                "payment_method" => "cash"
                            ]);
                    }
                }
                if (env("SEND_EMAIL") == true) {

                    $recipientEmails = $this->getNotificationRecipients($booking);

                    if (!empty($recipientEmails)) {
                        Mail::to($recipientEmails)->send(new BookingUpdateMail($booking));
                    }
                }

                $booking = $booking->load(["payments"]);

                return response($booking, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/bookings/change-status",
     *      operationId="changeBookingStatus",
     *      tags={"booking_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to change booking status",
     *      description="This method is to change booking status",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","garage_id","status"},
     * *    @OA\Property(property="id", type="number", format="number",example="1"),
     * @OA\Property(property="garage_id", type="number", format="number",example="1"),
     * @OA\Property(property="status", type="string", format="string",example="pending"),
     *      *       @OA\Property(property="reason", type="string", format="string",example="pending")

     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function changeBookingStatus(BookingStatusChangeRequest $request)
    {
        try {
            $this->storeActivity($request, "");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('booking_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();
                if (!$this->garageOwnerCheck($request_data["garage_id"])) {
                    return response()->json([
                        "message" => "you are not the owner of the garage or the requested garage does not exist."
                    ], 401);
                }
                $booking = Booking::where([
                    "id" => $request_data["id"],
                    "garage_id" =>  $request_data["garage_id"]
                ])->first();
                if (!$booking) {
                    return response()->json([
                        "message" => "booking not found"
                    ], 404);
                }
                if ($booking->status === "converted_to_job") {
                    // Return an error response indicating that the status cannot be updated
                    return response()->json(["message" => "Unable to change the appointment status because it is already complete."], 422);
                }

                if ($booking->status == "rejected_by_garage_owner" ||  $booking->status == "rejected_by_client") {
                    // Return an error response indicating that the status cannot be updated
                    return response()->json(["message" => "Unable to change the appointment status because it is already cancelled."], 422);
                }

                if ($request_data["status"] == "converted_to_job" && $booking->status != "check_in") {
                    return response()->json(["message" => "You cannot check out before check in."], 422);
                }

                if ($request_data["status"] == "check_in") {
                    $check_in_booking = Booking::where([
                        "expert_id" => $booking->expert_id,
                        "status" => "check_in"
                    ])
                        ->whereNotIn("bookings.id", [$booking->id])

                        ->first();

                    if (!empty($check_in_booking)) {
                        // Return an error response indicating that the expert already has a check-in
                        return response()->json([
                            "message" => "Expert " . ($check_in_booking->expert->first_Name . " " . $check_in_booking->expert->last_Name) . " already has a booking checked in on " . $check_in_booking->job_start_date . " at " . $check_in_booking->job_start_time . " (Booking ID: " . $check_in_booking->id . "). Please complete that booking to start a new one.",
                            "current_booking_id" => $check_in_booking->id,
                        ], 409);
                    }
                }




                $booking->reason = $request_data["reason"] ?? NULL;
                $booking->status = $request_data["status"];
                $booking->update(collect($request_data)->only(["status", "reason"])->toArray());


                // if ($booking->status != "confirmed") {
                //     return response()->json([
                //         "message" => "you can only accecpt or reject only a confirmed booking"
                //     ], 409);
                // }


                if ($booking->status == "rejected_by_garage_owner") {
                    if ($booking->pre_booking_id) {
                        $prebooking  =  PreBooking::where([
                            "id" => $booking->pre_booking_id
                        ])
                            ->first();
                        JobBid::where([
                            "id" => $prebooking->selected_bid_id
                        ])
                            ->update([
                                "status" => "canceled_after_booking"
                            ]);
                        $prebooking->status = "pending";
                        $prebooking->selected_bid_id = NULL;
                        $prebooking->save();
                    }
                    $notification_template = NotificationTemplate::where([
                        "type" => "booking_rejected_by_garage_owner"
                    ])
                        ->first();
                    // Get the customer's email
                    $recipientIds = [$booking->customer->id];

                    //  // Retrieve emails of users with the role 'business_receptionist'
                    //  $receptionists = User::role('business_receptionist')
                    //  ->where("business_id",$booking->garage_id)
                    //  ->pluck('id')->toArray();

                    //  // Merge the two arrays
                    //  $recipientIds = array_merge($recipientIds, $receptionists);

                    foreach ($recipientIds as $recipientId) {
                        Notification::create([
                            "sender_id" => $request->user()->id,
                            "receiver_id" => $recipientId,
                            "customer_id" => $booking->customer->id,
                            "business_id" => $booking->garage_id,
                            "garage_id" => $booking->garage_id,
                            "booking_id" => $booking->id,
                            "entity_name" => "booking",
                            "entity_id" => $booking->id,
                            "entity_ids" => json_encode([]),
                            "notification_title" => 'Booking Status Changed',
                            "notification_description" => "The status of booking ID: {$booking->id} has been updated.",
                            "notification_link" => null,
                            "is_system_generated" => false,
                            "notification_template_id" => $notification_template->id,
                            "status" => "unread",
                            "start_date" => now(),
                            "end_date" => null,
                        ]);
                    }
                } else {
                    $notification_template = NotificationTemplate::where([
                        "type" => "booking_status_changed_by_garage_owner"
                    ])
                        ->first();
                    // Get the customer's email
                    $recipientIds = [$booking->customer->id];

                    //  // Retrieve emails of users with the role 'business_receptionist'
                    //  $receptionists = User::role('business_receptionist')
                    //  ->where("business_id",$booking->garage_id)
                    //  ->pluck('id')->toArray();

                    //  // Merge the two arrays
                    //  $recipientIds = array_merge($recipientIds, $receptionists);

                    foreach ($recipientIds as $recipientId) {
                        Notification::create([
                            "sender_id" => $request->user()->id,
                            "receiver_id" => $recipientId,
                            "customer_id" => $booking->customer->id,
                            "business_id" => $booking->garage_id,
                            "garage_id" => $booking->garage_id,
                            "booking_id" => $booking->id,
                            "entity_name" => "booking",
                            "entity_id" => $booking->id,
                            "entity_ids" => json_encode([]),
                            "notification_title" => 'Booking Status Changed',
                            "notification_description" => "The status of booking ID: {$booking->id} has been updated.",
                            "notification_link" => null,
                            "is_system_generated" => false,
                            "notification_template_id" => $notification_template->id,
                            "status" => "unread",
                            "start_date" => now(),
                            "end_date" => null,
                        ]);
                    }
                }

                if (env("SEND_EMAIL") == true) {
                    // Get the customer's email

                    $recipientEmails = $this->getNotificationRecipients($booking);
                    if (!empty($recipientEmails)) {
                        Mail::to($recipientEmails)->send(new BookingStatusUpdateMail($booking));
                    }

                    //  // Retrieve emails of users with the role 'business_receptionist'
                    //  $receptionists = User::role('business_receptionist')
                    //  ->where("business_id",$booking->garage_id)
                    //  ->pluck('email')->toArray();

                    //  // Merge the two arrays
                    //  $recipientEmails = array_merge($recipientEmails, $receptionists);

                }
                // if (env("SEND_EMAIL") == true) {
                //     Mail::to($booking->customer->email)->send(new DynamicMail(
                //         $booking,
                //         "booking_status_changed_by_garage_owner"
                //     ));
                // }
                return response($booking, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     * @OA\Put(
     *      path="/v1.0/bookings/change-statuses",
     *      operationId="changeMultipleBookingStatuses",
     *      tags={"booking_management"},
     *      security={{"bearerAuth": {}}},
     *      summary="This method is to change multiple booking statuses",
     *      description="This method is to change multiple booking statuses",
     *
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"ids", "garage_id", "status"},
     *              @OA\Property(
     *                  property="ids",
     *                  type="array",
     *                  @OA\Items(type="number", example="1")
     *              ),
     *              @OA\Property(property="garage_id", type="number", example="1"),
     *              @OA\Property(property="status", type="string", example="pending"),
     *              @OA\Property(property="reason", type="string", nullable=true, example="some reason")
     *          )
     *      ),
     *
     *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent()),
     *      @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent()),
     *      @OA\Response(response=422, description="Unprocessable Content", @OA\JsonContent()),
     *      @OA\Response(response=403, description="Forbidden", @OA\JsonContent()),
     *      @OA\Response(response=400, description="Bad Request", @OA\JsonContent()),
     *      @OA\Response(response=404, description="Not Found", @OA\JsonContent())
     * )
     */
    public function changeMultipleBookingStatuses(Request $request)
    {
        $this->validate($request, [
            'ids' => 'required|array',
            'ids.*' => 'required|numeric',
            'garage_id' => 'required|numeric',
            'status' => 'required|string|in:pending,rejected_by_garage_owner,check_in,arrived,converted_to_job',
            'reason' => 'nullable|string',
        ]);

        try {
            $this->storeActivity($request, "");

            return DB::transaction(function () use ($request) {
                $ids = $request->input('ids');
                $garage_id = $request->input('garage_id');
                $status = $request->input('status');
                $reason = $request->input('reason');

                $updatedBookings = [];

                if (!$request->user()->hasPermissionTo('booking_update')) {
                    return response()->json(["message" => "You cannot perform this action"], 401);
                }

                if (!$this->garageOwnerCheck($garage_id)) {
                    return response()->json(["message" => "You are not the owner of the garage or the garage does not exist"], 401);
                }


                foreach ($ids as $id) {

                    $booking = Booking::where(['id' => $id, 'garage_id' => $garage_id])->first();

                    if (!$booking) {
                        return response()->json(["message" => "Booking with ID {$id} not found"], 404);
                    }

                    if (in_array($booking->status, ["converted_to_job", "rejected_by_garage_owner", "rejected_by_client"])) {
                        return response()->json(["message" => "Status cannot be updated for booking ID: {$id}"], 422);
                    }

                    $booking->update([
                        'status' => $status,
                        'reason' => $reason,
                    ]);

                    $updatedBookings[] = $booking;

                    // Handle notifications
                    $notificationTemplateType = $status == "rejected_by_garage_owner"
                        ? "booking_rejected_by_garage_owner"
                        : "booking_status_changed_by_garage_owner";

                    $notification_template = NotificationTemplate::where('type', $notificationTemplateType)->first();

                    // Get the customer's email
                    $recipientIds = [$booking->customer->id];

                    //  // Retrieve emails of users with the role 'business_receptionist'
                    //  $receptionists = User::role('business_receptionist')
                    //  ->where("business_id",$booking->garage_id)
                    //  ->pluck('id')->toArray();

                    //  // Merge the two arrays
                    //  $recipientIds = array_merge($recipientIds, $receptionists);

                    foreach ($recipientIds as $recipientId) {
                        Notification::create([
                            "sender_id" => $request->user()->id,
                            "receiver_id" => $recipientId,
                            "customer_id" => $booking->customer->id,
                            "business_id" => $booking->garage_id,
                            "garage_id" => $booking->garage_id,
                            "booking_id" => $booking->id,
                            "entity_name" => "booking",
                            "entity_id" => $booking->id,
                            "entity_ids" => json_encode([]),
                            "notification_title" => 'Booking Status Changed',
                            "notification_description" => "The status of booking ID: {$booking->id} has been updated.",
                            "notification_link" => null,
                            "is_system_generated" => false,
                            "notification_template_id" => $notification_template->id,
                            "status" => "unread",
                            "start_date" => now(),
                            "end_date" => null,
                        ]);
                    }
                }

                return response()->json($updatedBookings, 200);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }








    /**
     *
     * @OA\Put(
     *      path="/v1.0/bookings/confirm",
     *      operationId="confirmBooking",
     *      tags={"booking_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to confirm booking",
     *      description="This method is to confirm booking",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","garage_id","job_start_time","job_end_time"},
     * *    @OA\Property(property="id", type="number", format="number",example="1"),
     * @OA\Property(property="garage_id", type="number", format="number",example="1"),
     * *     * *   *    @OA\Property(property="price", type="number", format="number",example="30"),
     *  *  * *    @OA\Property(property="discount_type", type="string", format="string",example="percentage"),
     * *  * *    @OA\Property(property="discount_amount", type="number", format="number",example="10"),
     *  * @OA\Property(property="job_start_date", type="string", format="string",example="2019-06-29"),
     *
     * * @OA\Property(property="job_start_time", type="string", format="string",example="08:10"),

     *  * *    @OA\Property(property="job_end_time", type="string", format="string",example="10:10"),



     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function confirmBooking(BookingConfirmRequest $request)
    {
        try {
            $this->storeActivity($request, "");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('booking_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();
                if (!$this->garageOwnerCheck($request_data["garage_id"])) {
                    return response()->json([
                        "message" => "you are not the owner of the garage or the requested garage does not exist."
                    ], 401);
                }

                $request_data["status"] = "confirmed";
                $booking = Booking::where([
                    "id" => $request_data["id"],
                    "garage_id" =>  $request_data["garage_id"]
                ])->first();
                if (!$booking) {
                    return response()->json([
                        "message" => "booking not found"
                    ], 404);
                }
                if ($booking->status === "converted_to_job") {
                    // Return an error response indicating that the status cannot be updated
                    return response()->json(["message" => "Unable to change the appointment status because it is already complete."], 422);
                }


                $booking->update(collect($request_data)->only([
                    "job_start_date",
                    "job_start_time",
                    "job_end_time",
                    "status",
                    "price",
                    "discount_type",
                    "discount_amount",
                ])->toArray());



                $discount_amount = 0;
                if (!empty($booking->discount_type) && !empty($booking->discount_amount)) {
                    $discount_amount += $this->calculateDiscountPriceAmount($booking->price, $booking->discount_amount, $booking->discount_type);
                }
                if (!empty($booking->coupon_discount_type) && !empty($booking->coupon_discount_amount)) {
                    $discount_amount += $this->calculateDiscountPriceAmount($booking->price, $booking->coupon_discount_amount, $booking->coupon_discount_type);
                }

                $booking->final_price = $booking->price - $discount_amount;

                $booking->save();


                $notification_template = NotificationTemplate::where([
                    "type" => "booking_confirmed_by_garage_owner"
                ])
                    ->first();
                // Get the customer's email
                $recipientIds = [$booking->customer->id];

                //  // Retrieve emails of users with the role 'business_receptionist'
                //  $receptionists = User::role('business_receptionist')
                //  ->where("business_id",$booking->garage_id)
                //  ->pluck('id')->toArray();

                //  // Merge the two arrays
                //  $recipientIds = array_merge($recipientIds, $receptionists);

                foreach ($recipientIds as $recipientId) {
                    Notification::create([
                        "sender_id" => $request->user()->id,
                        "receiver_id" => $recipientId,
                        "customer_id" => $booking->customer->id,
                        "business_id" => $booking->garage_id,
                        "garage_id" => $booking->garage_id,
                        "booking_id" => $booking->id,
                        "entity_name" => "booking",
                        "entity_id" => $booking->id,
                        "entity_ids" => json_encode([]),
                        "notification_title" => 'Booking Status Changed',
                        "notification_description" => "The status of booking ID: {$booking->id} has been updated.",
                        "notification_link" => null,
                        "is_system_generated" => false,
                        "notification_template_id" => $notification_template->id,
                        "status" => "unread",
                        "start_date" => now(),
                        "end_date" => null,
                    ]);
                }
                // if (env("SEND_EMAIL") == true) {
                //     Mail::to($booking->customer->email)->send(new DynamicMail(
                //         $booking,
                //         "booking_confirmed_by_garage_owner"
                //     ));
                // }



                // if the booking was created by garage owner it will directly converted to job



                if ($booking->created_from == "garage_owner_side") {

                    $job = Job::create([
                        "booking_id" => $booking->id,
                        "garage_id" => $booking->garage_id,
                        "customer_id" => $booking->customer_id,

                        "additional_information" => $booking->additional_information,
                        "job_start_date" => $booking->job_start_date,


                        "coupon_discount_type" => $booking->coupon_discount_type,
                        "coupon_discount_amount" => $booking->coupon_discount_amount,


                        "discount_type" => $booking->discount_type,
                        "discount_amount" => $booking->discount_amount,
                        "price" => $booking->price,
                        "final_price" => $booking->final_price,
                        "status" => "pending",
                        "payment_status" => "due",



                    ]);

                    //     $total_price = 0;

                    //     foreach (BookingSubService::where([
                    //             "booking_id" => $booking->id
                    //         ])->get()
                    //         as
                    //         $booking_sub_service) {
                    //         $job->job_sub_services()->create([
                    //             "sub_service_id" => $booking_sub_service->sub_service_id,
                    //             "price" => $booking_sub_service->price
                    //         ]);
                    //         $total_price += $booking_sub_service->price;

                    //     }

                    //     foreach (BookingPackage::where([
                    //         "booking_id" => $booking->id
                    //     ])->get()
                    //     as
                    //     $booking_package) {
                    //     $job->job_packages()->create([
                    //         "garage_package_id" => $booking_package->garage_package_id,
                    //         "price" => $booking_package->price
                    //     ]);
                    //     $total_price += $booking_package->price;

                    // }




                    // $job->price = $total_price;
                    // $job->save();
                    $booking->status = "converted_to_job";
                    $booking->save();
                    // $booking->delete();


                }
                return response($booking, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/bookings/{garage_id}/{perPage}",
     *      operationId="getBookings",
     *      tags={"booking_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="6"
     *      ),
     *              @OA\Parameter(
     *         name="perPage",
     *         in="path",
     *         description="perPage",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     * * *  @OA\Parameter(
     * name="start_price",
     * in="query",
     * description="start_price"
     * ),
     *  * * *  @OA\Parameter(
     * name="end_price",
     * in="query",
     * description="end_price"
     * ),
     *      summary="This method is to get  bookings ",
     *      description="This method is to get bookings",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBookings($garage_id, $perPage, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('booking_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $dateRange = $this->getDateRange(request()->input("date_filter"));
            $start = $dateRange['start'];
            $end = $dateRange['end'];

            $bookingQuery = Booking::with(
                "sub_services.service",
                "booking_packages.garage_package",
                "booking_sub_services.sub_service",
                "customer",
                "garage",
                "expert",
                "payments"
            )

                ->when(!auth()->user()->hasRole("garage_owner") && !auth()->user()->hasRole("business_receptionist"), function ($query) {
                    $query->where([
                        "expert_id" => auth()->user()->id
                    ]);
                })
                ->where([
                    "garage_id" => auth()->user()->business_id
                ])
                ->when((!empty($start) && !empty($end)), function($query) use($start,$end) {
                    $query ->whereBetween('bookings.job_start_date', [$start, $end]);
                  })
                ->when(request()->filled("booking_id"), function ($query) {
                    $query->where([
                        "id" => request()->input("booking_id")
                    ]);
                })

                ->when(request()->filled("expert_id"), function ($query) {
                    $query->where([
                        "expert_id" => request()->input("expert_id")
                    ]);
                })
                ->when(request()->filled("expert_ids"), function ($query) {
                    $expert_ids = explode(',', request()->expert_ids);
                    $query->whereIn("expert_id", $expert_ids);
                })
                ->when(request()->filled("customer_ids"), function ($query) {
                    $customer_ids = explode(',', request()->customer_ids);
                    $query->whereIn("customer_id", $customer_ids);
                })


                ->when(request()->filled("start_price"), function ($query) {
                    $query->where("bookings.final_price", ">=", request()->input("start_price"));
                })
                ->when(request()->filled("end_price"), function ($query) {
                    $query->where("bookings.final_price", "<=", request()->input("end_price"));
                })
                ->when(request()->filled("customer_id"), function ($query) {
                    $query->where([
                        "customer_id" => request()->input("customer_id")
                    ]);
                })
                ->when(!empty(request()->sub_service_ids), function ($query) {
                    $sub_service_ids = explode(',', request()->sub_service_ids);

                    return $query->whereHas('sub_services', function ($query) use ($sub_service_ids) {
                        $query->whereIn('sub_services.id', $sub_service_ids)
                            ->when(!empty(request()->service_ids), function ($query) {
                                $service_ids = explode(',', request()->service_ids);

                                return $query->whereHas('service', function ($query) use ($service_ids) {
                                    return $query->whereIn('services.id', $service_ids);
                                });
                            });
                    });
                });

                if (request()->boolean("includes_vat")) {
                    $bookingQuery->whereNotNull('bookings.vat_amount')
                                 ->where('bookings.vat_amount', '>', 0) // vat_amount should not be 0
                                 ->whereNotNull('bookings.vat_percentage')
                                 ->where('bookings.vat_percentage', '>', 0); // vat_percentage should not be 0
                }


            // Apply the existing status filter if provided in the request
            if (!empty($request->status)) {
                $statusArray = explode(',', request()->status);
                // If status is provided, include the condition in the query
                $bookingQuery->whereIn("status", $statusArray);
            }
            if (!empty($request->booking_type)) {
                $booking_typeArray = explode(',', request()->booking_type);
                // If status is provided, include the condition in the query
                $bookingQuery->whereIn("booking_type", $booking_typeArray);
            }


            if (!empty($request->payment_status)) {
                $statusArray = explode(',', request()->payment_status);
                // If status is provided, include the condition in the query
                $bookingQuery->whereIn("payment_status", $statusArray);
            }

            if (!empty($request->search_key)) {
                $bookingQuery = $bookingQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("car_registration_no", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $bookingQuery = $bookingQuery->where('job_start_date', '>=', $request->start_date);
            }
            if (!empty($request->end_date)) {
                $bookingQuery = $bookingQuery->where('job_start_date', '<=', $request->end_date);
            }




            $bookings = $bookingQuery->orderByDesc("job_start_date")->paginate($perPage);

            return response()->json($bookings, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/customers",
     *      operationId="getCustomers",
     *      tags={"booking_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page",
     *         required=true,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Filter by user creation start date (YYYY-MM-DD)",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Filter by user creation end date (YYYY-MM-DD)",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="search_key",
     *         in="query",
     *         description="Keyword for searching users by name, email, or phone",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="rating",
     *         in="query",
     *         description="Filter by review rating",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="frequency_visit",
     *         in="query",
     *         description="Visit frequency category (New, Regular, VIP)",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="review_start_date",
     *         in="query",
     *         description="Filter reviews by start date (YYYY-MM-DD)",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="review_end_date",
     *         in="query",
     *         description="Filter reviews by end date (YYYY-MM-DD)",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="review_keyword",
     *         in="query",
     *         description="Keyword to search within review comments",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="frequency_visit",
     *         in="query",
     *         description="Filter users based on visit frequency",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Comma-separated list of booking statuses",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="payment_status",
     *         in="query",
     *         description="Comma-separated list of payment statuses",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="sub_service_ids",
     *         in="query",
     *         description="Comma-separated list of sub-service IDs",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="duration_in_minute",
     *         in="query",
     *         description="Filter by booking duration in minutes",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="booking_type",
     *         in="query",
     *         description="Comma-separated list of booking types",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="date_filter",
     *         in="query",
     *         description="Filter bookings by date (e.g., today, this_week, previous_week, this_month, etc.)",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filter users by name",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Filter users by email",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="Filter users by phone number",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="last_visited_date",
     *         in="query",
     *         description="Filter users by last visited date (YYYY-MM-DD)",
     *         required=false,
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         name="order_by",
     *         in="query",
     *         description="Sort order for users (ASC or DESC)",
     *         required=false,
     *         example=""
     *     ),
     *      summary="This method is to get  bookings ",
     *      description="This method is to get bookings",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getCustomers(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('booking_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $businessSetting = $this->get_business_setting(auth()->user()->business_id);

            $dateRange = $this->getDateRange($request->date_filter);
            $start = $dateRange['start'];
            $end = $dateRange['end'];

            $users = User::withCount([
                    'bookings as completed_booking_count' => function ($query) {
                        $query
                            ->where('bookings.garage_id', auth()->user()->business_id)
                            ->where('bookings.status', 'converted_to_job');  // Adjust 'status' according to your actual status field
                    },
                    'bookings as cancelled_booking_count' => function ($query) {
                        $query
                            ->where('bookings.garage_id', auth()->user()->business_id)
                            ->whereIn('bookings.status', ['rejected_by_client', 'rejected_by_garage_owner']);
                        // Adjust 'status' according to your actual status field
                    }
                ])
                ->with([
                    "completedBookings.expert",
                    "completedBookings.feedbacks",
                    "feedbacks" => function($query) {
                        $query->whereHas("booking", function($query) {
                              $query->where("bookings.garage_id",auth()->user()->business_id);
                        });
                    },
                    "feedbacks.booking.sub_services",
                    "feedbacks"
                    ])
                // Filter by rating if provided
                ->when(request()->filled('rating'), function ($q) {
                    $q->whereHas('reviews', function ($query) {
                        $query->where('review_news.rate', request()->input('rating'));
                    });
                })
                ->when(request()->filled('review_start_date') && request()->filled('review_end_date'), function ($q) {
                    $q->whereHas('reviews', function ($query) {
                        $query->whereBetween('review_news.updated_at', [
                            request()->input('review_start_date'),
                            request()->input('review_end_date')
                        ]);
                    });
                })
                ->when(request()->filled('review_keyword'), function ($q) {
                    $q->whereHas('reviews', function ($query) {
                        $query->where('review_news.comment', 'like', '%' . request('review_keyword') . '%');
                    });
                })
                ->when(request()->filled('frequency_visit'), function ($q) {
                    $frequency = request()->input('frequency_visit');
                    $query_param = '='; // Default value for the comparison operator.
                    $min_count = 1;     // Minimum booking count.
                    $max_count = 1;     // Maximum booking count (for regular customers).

                    if ($frequency == "New") {
                        // For new customers, the count should be exactly 1.
                        $query_param = '=';
                        $min_count = 1;
                        $max_count = 1;
                    } elseif ($frequency == "Regular") {
                        // For regular customers, the count should be between 2 and 5.
                        $query_param = 'BETWEEN';
                        $min_count = 2;
                        $max_count = 5;
                    } elseif ($frequency == "VIP") {
                        // For VIP customers, the count should be 5 or more.
                        $query_param = '>=';
                        $min_count = 5;
                        $max_count = null; // No upper limit for VIP.
                    } else {
                        // Default case or other logic can be applied here.
                        $query_param = '=';
                        $min_count = 1;
                        $max_count = 1;
                    }

                    $q->whereHas("bookings", function ($query) use ($query_param, $min_count, $max_count) {
                        // Separate subquery to count all bookings for each customer.
                        $query->whereIn('bookings.customer_id', function ($subquery) use ($query_param, $min_count, $max_count) {
                            $subquery->select('customer_id')
                                ->from('bookings')
                                ->groupBy('customer_id')
                                ->when($query_param == 'BETWEEN', function ($subquery) use ($min_count, $max_count) {
                                    // If the condition is BETWEEN, use BETWEEN operator.
                                    $subquery->having(DB::raw('COUNT(id)'), 'BETWEEN', [$min_count, $max_count]);
                                })
                                ->when($query_param != 'BETWEEN', function ($subquery) use ($query_param, $min_count) {
                                    // If the condition is '=', '>=', or other operators.
                                    $subquery->having(DB::raw('COUNT(id)'), $query_param, $min_count);
                                });
                        });
                    });
                })

                ->whereHas("bookings", function ($query) use ($request,$businessSetting,$start,$end) {
                    $query->where("bookings.garage_id", auth()->user()->business_id)

                        ->when(request()->filled("expert_id"), function ($query) {
                            $query->where([
                                "expert_id" => request()->input("expert_id")
                            ]);
                        })
                        ->when(request()->filled("payment_type"), function ($query) {
                            $query
                                ->whereHas("booking_payments", function ($query) {
                                    $payment_typeArray = explode(',', request()->payment_type);
                                    $query->whereIn("job_payments.payment_type", $payment_typeArray);
                                });
                        })
                        ->when(request()->filled("discount_applied"), function ($query) {
                            if (request()->boolean("discount_applied")) {
                                $query->where(function ($query) {
                                    $query->where("discount_amount", ">", 0)
                                        ->orWhere("coupon_discount_amount", ">", 0);
                                });
                            } else {
                                $query->where(function ($query) {
                                    $query->where("discount_amount", "<=", 0)
                                        ->orWhere("coupon_discount_amount", "<=", 0);
                                });
                            }
                        })
                        ->when(!empty($request->status), function ($query) use ($request) {
                            $statusArray = explode(',', $request->status);
                            return $query->whereIn("status", $statusArray);
                        })
                        ->when(!empty($request->payment_status), function ($query) use ($request) {
                            $statusArray = explode(',', $request->payment_status);
                            return $query->whereIn("payment_status", $statusArray);
                        })
                        ->when(!empty($request->expert_id), function ($query) use ($request) {
                            return
                                $query->where('bookings.expert_id', request()->input("expert_id"));
                        })

                        ->when(!empty(request()->sub_service_ids), function ($query) {
                            $sub_service_ids = explode(',', request()->sub_service_ids);

                            return $query->whereHas('sub_services', function ($query) use ($sub_service_ids) {
                                $query->whereIn('sub_services.id', $sub_service_ids)
                                    ->when(!empty(request()->service_ids), function ($query) {
                                        $service_ids = explode(',', request()->service_ids);

                                        return $query->whereHas('service', function ($query) use ($service_ids) {
                                            return $query->whereIn('services.id', $service_ids);
                                        });
                                    });
                            });
                        })
                        ->when(request()->filled("duration_in_minute"), function ($query) {
                            $durationInMinutes = request()->input("duration_in_minute");
                            $query->whereRaw("TIMESTAMPDIFF(MINUTE, job_start_time, job_end_time) = ?", [$durationInMinutes]);
                        })
                        // ->when(request()->filled("duration_in_minute"), function ($query) use($businessSetting) {
                        //     $total_slots = request()->input("duration_in_minute") / $businessSetting->slot_duration;
                        //     $query->having('total_booked_slots', '>', $total_slots);
                        // })
                        ->when(!empty($request->booking_type), function ($query) use ($request) {
                            $booking_typeArray = explode(',', $request->booking_type);
                            $query->whereIn("booking_type", $booking_typeArray);
                        })
                        ->when((!empty($start) && !empty($end)), function ($query) use ($start, $end) {
                            $query->whereBetween('bookings.job_start_date', [$start, $end]);
                        })
                      ;
                })
                ->when(!empty($request->name), function ($query) use ($request) {
                    $name = $request->name;
                    return $query->where(function ($subQuery) use ($name) {
                        $subQuery->where("first_Name", "like", "%" . $name . "%")
                            ->orWhere("last_Name", "like", "%" . $name . "%");
                    });
                })
                ->when(!empty($request->email), function ($query) use ($request) {
                    return $query->where('users.email', 'like', '%' . $request->email . '%');
                })
                ->when(!empty($request->phone), function ($query) use ($request) {
                    return $query->where('users.phone', 'like', '%' . $request->phone . '%');
                })
                ->when(!empty($request->last_visited_date), function ($query) use ($request) {
                    return $query->whereHas('lastBooking', function ($query) {
                        return $query->where('bookings.job_start_date', request()->input("last_visited_date"));
                    });
                })

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query;
                    });
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('users.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('users.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("users.first_Name", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("users.first_Name", "DESC");
                })
                ->when($request->filled("id"), function ($query) use ($request) {
                    return $query
                        ->where("users.id", $request->input("id"))
                        ->first();
                }, function ($query) {
                    return $query->when(!empty(request()->per_page), function ($query) {
                        return $query->paginate(request()->per_page);
                    }, function ($query) {
                        return $query->get();
                    });
                });

            if ($request->filled("id") && empty($users)) {
                throw new Exception("No data found", 404);
            }

            if ($request->filled("id")) {
                $users =  $this->addCustomerData($users);
            } else {
                foreach ($users as $user) {
                    $user = $this->addCustomerData($user);
                }
            }


            return response()->json($users, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }





    /**
     *
     * @OA\Get(
     *      path="/v2.0/customers",
     *      operationId="getCustomersV2",
     *      tags={"booking_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *         @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *      summary="This method is to get  bookings ",
     *      description="This method is to get bookings",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getCustomersV2(Request $request)
    {
        try {
            $this->storeActivity($request, "");

            if (!$request->user()->hasPermissionTo('booking_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $users = User::with([
                "lastBooking",
                "services"

            ])
                ->whereHas("bookings", function ($query) use ($request) {
                    $query->where("bookings.garage_id", auth()->user()->business_id)
                        ->when(request()->filled("expert_id"), function ($query) {
                            $query->where([
                                "expert_id" => request()->input("expert_id")
                            ]);
                        })
                        ->when(!empty($request->status), function ($query) use ($request) {
                            $statusArray = explode(',', $request->status);
                            return $query->whereIn("status", $statusArray);
                        })
                        ->when(!empty($request->payment_status), function ($query) use ($request) {
                            $statusArray = explode(',', $request->payment_status);
                            return $query->whereIn("payment_status", $statusArray);
                        })
                        ->when($request->date_filter === 'today', function ($query) {
                            return $query->whereDate('bookings.job_start_date', Carbon::today());
                        })
                        ->when($request->date_filter === 'this_week', function ($query) {
                            return $query->whereBetween('bookings.job_start_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                        })
                        ->when($request->date_filter === 'previous_week', function ($query) {
                            return $query->whereBetween('bookings.job_start_date', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                        })
                        ->when($request->date_filter === 'next_week', function ($query) {
                            return $query->whereBetween('bookings.job_start_date', [Carbon::now()->addWeek()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()]);
                        })
                        ->when($request->date_filter === 'this_month', function ($query) {
                            return $query->whereMonth('bookings.job_start_date', Carbon::now()->month)
                                ->whereYear('bookings.job_start_date', Carbon::now()->year);
                        })
                        ->when($request->date_filter === 'previous_month', function ($query) {
                            return $query->whereMonth('bookings.job_start_date', Carbon::now()->subMonth()->month)
                                ->whereYear('bookings.job_start_date', Carbon::now()->subMonth()->year);
                        })
                        ->when($request->date_filter === 'next_month', function ($query) {
                            return $query->whereMonth('bookings.job_start_date', Carbon::now()->addMonth()->month)
                                ->whereYear('bookings.job_start_date', Carbon::now()->addMonth()->year);
                        });
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('users.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('users.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query;
                    });
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('users.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('users.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("users.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("users.id", "DESC");
                })
                ->when($request->filled("id"), function ($query) use ($request) {
                    return $query
                        ->where("users.id", $request->input("id"))
                        ->first();
                }, function ($query) {
                    return $query->when(!empty(request()->per_page), function ($query) {
                        return $query->paginate(request()->per_page);
                    }, function ($query) {
                        return $query->get();
                    });
                });

            if ($request->filled("id") && empty($users)) {
                throw new Exception("No data found", 404);
            }

            return response()->json($users, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/upcoming-bookings",
     *      operationId="getUpcomingBookings",
     *      tags={"booking_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="6"
     *      ),
     *              @OA\Parameter(
     *         name="perPage",
     *         in="path",
     *         description="perPage",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *      summary="This method is to get  bookings ",
     *      description="This method is to get bookings",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getUpcomingBookings(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('booking_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            if (!request()->filled("current_slot")) {
                return response()->json([
                    "message" => "current slot field is required"
                ], 401);
            }

            $experts = User::with("translation")
                ->where("users.is_active", 1)
                ->whereHas('roles', function ($query) {
                    $query->where('roles.name', 'business_experts');
                })
                ->where("business_id", auth()->user()->business_id)
                ->get();


            foreach ($experts as $expert) {

                $upcoming_bookings = collect();

                // Get all bookings for the provided date except the rejected ones
                $expert_bookings = Booking::whereDate("job_start_date", today())
                    ->whereIn("status", ["pending"])
                    ->where([
                        "expert_id" => $expert->id
                    ])
                    ->get();

                foreach ($expert_bookings as $expert_booking) {

                   // Parse the job start time
$job_start_time = Carbon::parse($expert_booking->job_start_time);

// Compare the job start time with the current time
if ($job_start_time->greaterThan(now())) {
    // Add the booking to the upcoming bookings collection
    $upcoming_bookings->push($expert_booking);
}

                }

                $expert["upcoming_bookings_today"] = $upcoming_bookings->toArray();

                // Get all upcoming bookings for future dates except the rejected ones
                $expert["upcoming_bookings"] = Booking::whereDate("job_start_date", '>', today())
                    ->whereIn("status", ["pending"])
                    ->where("expert_id", $expert->id)
                    ->get();
            }




            return response()->json($experts, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/bookings/single/{garage_id}/{id}",
     *      operationId="getBookingById",
     *      tags={"booking_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="6"
     *      ),
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to  get booking by id",
     *      description="This method is to get booking by id",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBookingById($garage_id, $id, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('booking_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            if (!$this->garageOwnerCheck($garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }


            $booking = Booking::with(
                "sub_services.service",
                "booking_sub_services.sub_service",
                "booking_packages.garage_package",
                "customer",
                "garage",
                "expert",
                "payments",
             "feedbacks.value"
            )
                ->where([
                    "garage_id" => $garage_id,
                    "id" => $id
                ])
                ->first();
            if (!$booking) {
                return response()->json([
                    "message" => "booking not found"
                ], 404);
            }


            return response()->json($booking, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Delete(
     *      path="/v1.0/bookings/{garage_id}/{id}",
     *      operationId="deleteBookingById",
     *      tags={"booking_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="6"
     *      ),
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to  delete booking by id",
     *      description="This method is to delete booking by id",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function deleteBookingById($garage_id, $id, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('booking_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            if (!$this->garageOwnerCheck($garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }


            $booking = Booking::where([
                "garage_id" => $garage_id,
                "id" => $id
            ])
                ->first();

            if (!$booking) {
                return response()->json([
                    "message" => "booking not found"
                ], 404);
            }
            $response_message = "";

            if ($booking->payment_method == "stripe") { // Check if card payment is associated
                // Check if the 'is_refund' query parameter is provided

                if (!request()->filled("is_refund")) {
                    return response()->json(['message' => 'Bad Request: The is_refund parameter is required when the payment method is stripe.'], 400);
                }

                if (request()->boolean("is_refund")) {
                    // Handle the refund logic here
                    $this->processRefund($booking);
                    $response_message = 'The payment has been refunded and the booking has been deleted.';
                } else {
                    $response_message =  'The booking has been deleted without refund.';
                }
            }




            // if ($booking->status === "converted_to_job") {
            //     // Return an error response indicating that the status cannot be updated
            //     return response()->json(["message" => "can not be deleted if status is converted_to_job"], 422);
            // }



            if ($booking->pre_booking_id) {
                $prebooking  =  PreBooking::where([
                    "id" => $booking->pre_booking_id
                ])
                    ->first();
                JobBid::where([
                    "id" => $prebooking->selected_bid_id
                ])
                    ->update([
                        "status" => "canceled_after_booking"
                    ]);
                $prebooking->status = "pending";
                $prebooking->selected_bid_id = NULL;
                $prebooking->save();
            }


            $notification_template = NotificationTemplate::where([
                "type" => "booking_deleted_by_garage_owner"
            ])
                ->first();

            $recipientIds = [$booking->customer->id];

            // Retrieve emails of users with the role 'business_receptionist'
            $receptionists = User::role('business_receptionist')
                ->where("business_id", $booking->garage_id)
                ->pluck('id')->toArray();

            // Merge the two arrays
            $recipientIds = array_merge($recipientIds, $receptionists);

            foreach ($recipientIds as $recipientId) {
                Notification::create([
                    "sender_id" => $request->user()->id,
                    "receiver_id" => $recipientId,
                    "customer_id" => $booking->customer->id,
                    "business_id" => $booking->garage_id,
                    "garage_id" => $booking->garage_id,
                    "booking_id" => $booking->id,
                    "entity_name" => "booking",
                    "entity_id" => $booking->id,
                    "entity_ids" => json_encode([]),
                    "notification_title" => 'Booking Deleted',
                    "notification_description" => "Booking ID: {$booking->id} has been deleted.",
                    "notification_link" => null,
                    "is_system_generated" => false,
                    "notification_template_id" => $notification_template->id,
                    "status" => "unread",
                    "start_date" => now(),
                    "end_date" => null,
                ]);
            }
            // if (env("SEND_EMAIL") == true) {
            //     Mail::to($booking->customer->email)->send(new DynamicMail(
            //         $booking,
            //         "booking_deleted_by_garage_owner"
            //     ));
            // }
            $booking->delete();
            return response()->json([
                "ok" => true,
                "message" => $response_message
            ], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
