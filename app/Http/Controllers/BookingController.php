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
use App\Mail\DynamicMail;
use App\Models\Booking;
use App\Models\BookingPackage;
use App\Models\BookingSubService;
use App\Models\Coupon;
use App\Models\Garage;
use App\Models\GarageAutomobileMake;
use App\Models\GarageAutomobileModel;
use App\Models\GaragePackage;
use App\Models\GarageSubService;
use App\Models\GarageTime;
use App\Models\Job;
use App\Models\JobBid;
use App\Models\JobPayment;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\PreBooking;
use App\Models\StripeSetting;
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
        $stripeSetting = StripeSetting::where('business_id', $booking->garage_id)->first();

        if (!$stripeSetting) {
            return response()->json([
                "message" => "Stripe is not enabled"
            ], 403);
        }

        // Set Stripe client
        $stripe = new \Stripe\StripeClient($stripeSetting->STRIPE_SECRET);

        $discount = $this->canculate_discounted_price($booking->price, $booking->discount_type, $booking->discount_amount);
        $coupon_discount = $this->canculate_discounted_price($booking->price, $booking->coupon_discount_type, $booking->coupon_discount_amount);

        $total_discount = $discount + $coupon_discount;


        // Prepare payment intent data
        $paymentIntentData = [
            'amount' => ($booking->price) * 100, // Adjusted amount in cents
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
        $stripeSetting = StripeSetting::where('business_id', $booking->garage_id)->first();

        if (!$stripeSetting) {
            return response()->json([
                "message" => "Stripe is not enabled"
            ], 403);
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
                "booking_id" => $booking->id,

            ])
            ->delete();
            return response()->json([
                "message" => "Refund successful",
                "refund_id" => $refund->id
            ], 200);
        } catch (\Exception $e) {
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
        } else if ($booking->price < 0 || $booking->final_price < 0){
            return response()->json([
                "message" => "You booking price is zero. it's a software error."
            ], 409);
        }

        if ($booking->payment_status == "completed") {
            return response()->json([
                "message" => "Already paid"
            ], 409);
        }



        $stripeSetting = StripeSetting::where([
            "business_id" => $booking->garage_id
        ])
            ->first();

        if (!$stripeSetting) {
            return response()->json([
                "message" => "Stripe is not enabled"
            ], 403);
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

        $discount = $this->canculate_discounted_price($booking->price, $booking->discount_type, $booking->discount_amount);
        $coupon_discount = $this->canculate_discounted_price($booking->price, $booking->coupon_discount_type, $booking->coupon_discount_amount);

        $total_discount = $discount + $coupon_discount;



        $session_data = [
            'payment_method_types' => ['card'],
            'metadata' => [
                'our_url' => route('stripe.webhook'),
                "booking_id" => $booking->id

            ],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'GBP',
                        'product_data' => [
                            'name' => 'Your Service set up amount',
                        ],
                        'unit_amount' => $booking->price * 100, // Amount in cents
                    ],
                    'quantity' => 1,
                ]
            ],

            'customer' => $user->stripe_id  ?? null,

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



            $insertableData = $request->validated();


            if(empty($insertableData["customer_id"])) {
                $walkInCustomer = new User(); // Assuming you are using the User model for walk-in customers
                $walkInCustomer->business_id = auth()->user()->business_id;
                $walkInCustomer->first_Name = $insertableData['first_Name'];
                $walkInCustomer->last_Name = $insertableData['last_Name'];
                $walkInCustomer->phone = $insertableData['phone'];
                $walkInCustomer->email = $insertableData['email'];
                $walkInCustomer->address_line_1 = $insertableData['address_line_1'];
                $walkInCustomer->address_line_2 = $insertableData['address_line_2'];
                $walkInCustomer->country = $insertableData['country'];
                $walkInCustomer->city = $insertableData['city'];
                $walkInCustomer->postcode = $insertableData['postcode'];
                $walkInCustomer->is_active = true; // Assuming walk-in customers are active by default

                // Set a dummy password
                $dummyPassword = 'dummyPassword'; // You can change this to any default string
                $walkInCustomer->password = Hash::make($dummyPassword); // Hash the dummy password

                $walkInCustomer->save();

                $insertableData["customer_id"] = $walkInCustomer->id;
            }



            if (!$this->garageOwnerCheck($insertableData["garage_id"])) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }



            $insertableData["status"] = "pending";
            $insertableData["created_by"] = $request->user()->id;
            $insertableData["created_from"] = "garage_owner_side";
            $insertableData["payment_status"] = "pending";
            $insertableData["payment_method"] = "cash";






            $booking =  Booking::create($insertableData);


            $total_price = 0;
            $total_time = 0;
            foreach ($insertableData["booking_sub_service_ids"] as $index => $sub_service_id) {
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

                $price = $this->getPrice($sub_service, $insertableData["expert_id"]);

                $total_time += $sub_service->service_time_in_minute;


                $total_price += $price;

                $booking->booking_sub_services()->create([
                    "sub_service_id" => $sub_service->id,
                    "price" => $price
                ]);
            }

            $slotValidation =  $this->validateBookingSlots($booking->id, $request["booked_slots"], $request["job_start_date"], $request["expert_id"], $total_time);

            if ($slotValidation['status'] === 'error') {
                // Return a JSON response with the overlapping slots and a 422 Unprocessable Entity status code
                return response()->json($slotValidation, 422);
            }


            foreach ($insertableData["booking_garage_package_ids"] as $index => $garage_package_id) {
                $garage_package =  GaragePackage::where([
                    "garage_id" => $insertableData["garage_id"],
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

                $booking->booking_packages()->create([
                    "garage_package_id" => $garage_package->id,
                    "price" => $garage_package->price
                ]);
            }



            $booking->price = $total_price;
            $booking->save();

            if (!empty($insertableData["coupon_code"])) {

                $coupon_discount = $this->getCouponDiscount(
                    $insertableData["garage_id"],
                    $insertableData["coupon_code"],
                    $total_price
                );

                if (empty($coupon_discount["success"])) {
                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => ["coupon_code" => [$coupon_discount["message"]]]
                    ];
                    throw new Exception(json_encode($error), 422);
                    // $booking->coupon_discount_type = $coupon_discount["discount_type"];
                    // $booking->coupon_discount_amount = $coupon_discount["discount_amount"];
                    // $booking->coupon_code = $insertableData["coupon_code"];

                    // $booking->save();

                    // Coupon::where([
                    //     "code" => $booking->coupon_code,
                    //     "garage_id" => $booking->garage_id
                    // ])->update([
                    //     "customer_redemptions" => DB::raw("customer_redemptions + 1")
                    // ]);
                }
            }

            $booking->final_price = $booking->price;

            $booking->final_price -= $this->canculate_discounted_price($booking->price, $booking->discount_type, $booking->discount_amount);

            $booking->final_price -= $this->canculate_discounted_price($booking->price, $booking->coupon_discount_type, $booking->coupon_discount_amount);

            $booking->save();


            $notification_template = NotificationTemplate::where([
                "type" => "booking_created_by_garage_owner"
            ])
                ->first();
            if (!$notification_template) {
                throw new Exception("notification template error");
            }

            Notification::create([
                "sender_id" =>  $booking->garage->owner_id,
                "receiver_id" => $booking->customer_id,
                "customer_id" => $booking->customer_id,
                "garage_id" => $booking->garage_id,
                "booking_id" => $booking->id,
                "notification_template_id" => $notification_template->id,
                "status" => "unread",
            ]);
            // if (env("SEND_EMAIL") == true) {
            //     Mail::to($booking->customer->email)->send(new DynamicMail(
            //         $booking,
            //         "booking_created_by_garage_owner"
            //     ));
            // }

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
                $updatableData = $request->validated();
                if (!$this->garageOwnerCheck($updatableData["garage_id"])) {
                    return response()->json([
                        "message" => "you are not the owner of the garage or the requested garage does not exist."
                    ], 401);
                }

                $booking = Booking::where([
                    "id" => $updatableData["id"],
                    "garage_id" =>  $updatableData["garage_id"]
                ])->first();

                if (!$booking) {
                    return response()->json([
                        "message" => "booking not found"
                    ], 404);
                }





                if ($booking->status === "converted_to_job") {
                    // Return an error response indicating that the status cannot be updated
                    return response()->json(["message" => "Status cannot be updated because it is converted_to_job"], 422);
                }


                $booking->update(collect($updatableData)->only([
                    "status",
                    "job_start_date",
                    "discount_type",
                    "discount_amount",
                    "expert_id",
                    "booked_slots",
                    "reason",
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

                    $total_time += $sub_service->service_time_in_minute;


                    $total_price += $price;

                    $booking->booking_sub_services()->create([
                        "sub_service_id" => $sub_service->id,
                        "price" => $price
                    ]);
                }

                $slotValidation =  $this->validateBookingSlots($booking->id, $request["booked_slots"], $request["job_start_date"], $request["expert_id"], $total_time);

                if ($slotValidation['status'] === 'error') {
                    // Return a JSON response with the overlapping slots and a 422 Unprocessable Entity status code
                    return response()->json($slotValidation, 422);
                }

                foreach ($updatableData["booking_garage_package_ids"] as $index => $garage_package_id) {
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

                    $booking->booking_packages()->create([
                        "garage_package_id" => $garage_package->id,
                        "price" => $garage_package->price
                    ]);
                }

                // $booking->price = (!empty($updatableData["price"]?$updatableData["price"]:$total_price));
                $booking->price = $total_price;






                // if(!empty($updatableData["coupon_code"])){
                //     $coupon_discount = $this->getCouponDiscount(
                //         $updatableData["garage_id"],
                //         $updatableData["coupon_code"],
                //         $booking->price
                //     );

                //     if($coupon_discount) {

                //         $booking->coupon_discount_type = $coupon_discount["discount_type"];
                //         $booking->coupon_discount_amount = $coupon_discount["discount_amount"];


                //     }
                // }


                $booking->final_price = $booking->price;
                $booking->final_price -= $this->canculate_discounted_price($booking->price, $booking->discount_type, $booking->discount_amount);
                $booking->final_price -= $this->canculate_discounted_price($booking->price, $booking->coupon_discount_type, $booking->coupon_discount_amount);
                $booking->save();

                $notification_template = NotificationTemplate::where([
                    "type" => "booking_updated_by_garage_owner"
                ])
                    ->first();
                Notification::create([
                    "sender_id" =>  $booking->garage->owner_id,
                    "receiver_id" => $booking->customer_id,
                    "customer_id" => $booking->customer_id,
                    "garage_id" => $booking->garage_id,
                    "booking_id" => $booking->id,
                    "notification_template_id" => $notification_template->id,
                    "status" => "unread",
                ]);
                // if (env("SEND_EMAIL") == true) {
                //     Mail::to($booking->customer->email)->send(new DynamicMail(
                //         $booking,
                //         "booking_updated_by_garage_owner"
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
                $updatableData = $request->validated();
                if (!$this->garageOwnerCheck($updatableData["garage_id"])) {
                    return response()->json([
                        "message" => "you are not the owner of the garage or the requested garage does not exist."
                    ], 401);
                }
                $booking = Booking::where([
                    "id" => $updatableData["id"],
                    "garage_id" =>  $updatableData["garage_id"]
                ])->first();
                if (!$booking) {
                    return response()->json([
                        "message" => "booking not found"
                    ], 404);
                }
                if ($booking->status === "converted_to_job") {
                    // Return an error response indicating that the status cannot be updated
                    return response()->json(["message" => "Status cannot be updated because it is 'converted_to_job'"], 422);
                }

                if ($booking->status == "rejected_by_garage_owner" ||  $booking->status == "rejected_by_client") {
                    // Return an error response indicating that the status cannot be updated
                    return response()->json(["message" => "Status cannot be updated because it is in cancelled status"], 422);
                }



                $booking->reason = $updatableData["reason"] ?? NULL;
                $booking->status = $updatableData["status"];
                $booking->update(collect($updatableData)->only(["status", "reason"])->toArray());


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
                    Notification::create([
                        "sender_id" =>  $booking->garage->owner_id,
                        "receiver_id" => $booking->customer_id,
                        "customer_id" => $booking->customer_id,
                        "garage_id" => $booking->garage_id,
                        "booking_id" => $booking->id,
                        "notification_template_id" => $notification_template->id,
                        "status" => "unread",
                    ]);
                } else {
                    $notification_template = NotificationTemplate::where([
                        "type" => "booking_status_changed_by_garage_owner"
                    ])
                        ->first();
                    Notification::create([
                        "sender_id" =>  $booking->garage->owner_id,
                        "receiver_id" => $booking->customer_id,
                        "customer_id" => $booking->customer_id,
                        "garage_id" => $booking->garage_id,
                        "booking_id" => $booking->id,
                        "notification_template_id" => $notification_template->id,
                        "status" => "unread",
                    ]);
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

                $notificationTemplate = NotificationTemplate::where('type', $notificationTemplateType)->first();

                Notification::create([
                    'sender_id' => $booking->garage->owner_id,
                    'receiver_id' => $booking->customer_id,
                    'customer_id' => $booking->customer_id,
                    'garage_id' => $booking->garage_id,
                    'booking_id' => $booking->id,
                    'notification_template_id' => $notificationTemplate->id,
                    'status' => 'unread',
                ]);
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
                $updatableData = $request->validated();
                if (!$this->garageOwnerCheck($updatableData["garage_id"])) {
                    return response()->json([
                        "message" => "you are not the owner of the garage or the requested garage does not exist."
                    ], 401);
                }

                $updatableData["status"] = "confirmed";
                $booking = Booking::where([
                    "id" => $updatableData["id"],
                    "garage_id" =>  $updatableData["garage_id"]
                ])->first();
                if (!$booking) {
                    return response()->json([
                        "message" => "booking not found"
                    ], 404);
                }
                if ($booking->status === "converted_to_job") {
                    // Return an error response indicating that the status cannot be updated
                    return response()->json(["message" => "Status cannot be updated because it is 'converted_to_job'"], 422);
                }


                $booking->update(collect($updatableData)->only([
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
                Notification::create([
                    "sender_id" =>  $booking->garage->owner_id,
                    "receiver_id" => $booking->customer_id,
                    "customer_id" => $booking->customer_id,
                    "garage_id" => $booking->garage_id,
                    "booking_id" => $booking->id,
                    "notification_template_id" => $notification_template->id,
                    "status" => "unread",
                ]);
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


            $bookingQuery = Booking::with(
                "sub_services.service",
                "booking_packages.garage_package",
                "customer",
                "garage",
                "expert"

            )
                ->when(!auth()->user()->hasRole("garage_owner") && !auth()->user()->hasRole("business_receptionist"), function ($query) {
                    $query->where([
                        "expert_id" => auth()->user()->id
                    ]);
                })
                ->where([
                    "garage_id" => auth()->user()->business_id
                ])
                ->when(request()->input("expert_id"), function ($query) {
                    $query->where([
                        "expert_id" => request()->input("expert_id")
                    ]);
                });

            // Apply the existing status filter if provided in the request
            if (!empty($request->status)) {
                $statusArray = explode(',', request()->status);
                // If status is provided, include the condition in the query
                $bookingQuery->whereIn("status", $statusArray);
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

            // Additional date filters using date_filter
            if ($request->date_filter === 'today') {
                $bookingQuery = $bookingQuery->whereDate('job_start_date', Carbon::today());
            } elseif ($request->date_filter === 'this_week') {
                $bookingQuery = $bookingQuery->whereBetween('job_start_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            } elseif ($request->date_filter === 'previous_week') {
                $bookingQuery = $bookingQuery->whereBetween('job_start_date', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
            } elseif ($request->date_filter === 'next_week') {
                $bookingQuery = $bookingQuery->whereBetween('job_start_date', [Carbon::now()->addWeek()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()]);
            } elseif ($request->date_filter === 'this_month') {
                $bookingQuery = $bookingQuery->whereMonth('job_start_date', Carbon::now()->month)
                    ->whereYear('job_start_date', Carbon::now()->year);
            } elseif ($request->date_filter === 'previous_month') {
                $bookingQuery = $bookingQuery->whereMonth('job_start_date', Carbon::now()->subMonth()->month)
                    ->whereYear('job_start_date', Carbon::now()->subMonth()->year);
            } elseif ($request->date_filter === 'next_month') {
                $bookingQuery = $bookingQuery->whereMonth('job_start_date', Carbon::now()->addMonth()->month)
                    ->whereYear('job_start_date', Carbon::now()->addMonth()->year);
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

                    $booked_slots = $expert_booking->booked_slots;

                    // Convert time strings into Carbon objects
                    $booked_times = array_map(function ($time) {
                        return Carbon::parse($time);
                    }, $booked_slots);

                    // Get the smallest time
                    $smallest_time = min($booked_times);

                    // Get the current time or the input "current_slot"
                    $current_time = request()->input("current_slot")
                        ? Carbon::parse(request()->input("current_slot"))
                        : Carbon::now(); // Use the current time if no input is provided

                    // Compare the smallest booked time with the current time
                    if ($smallest_time->greaterThan($current_time)) {
                        $upcoming_bookings->push($upcoming_bookings);
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
                "booking_packages.garage_package",
                "customer",
                "garage",
                "expert"
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

            if ($booking->status === "converted_to_job") {
                // Return an error response indicating that the status cannot be updated
                return response()->json(["message" => "can not be deleted if status is converted_to_job"], 422);
            }



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
            Notification::create([
                "sender_id" =>  $booking->garage->owner_id,
                "receiver_id" => $booking->customer_id,
                "customer_id" => $booking->customer_id,
                "garage_id" => $booking->garage_id,
                "booking_id" => $booking->id,
                "notification_template_id" => $notification_template->id,
                "status" => "unread",
            ]);
            // if (env("SEND_EMAIL") == true) {
            //     Mail::to($booking->customer->email)->send(new DynamicMail(
            //         $booking,
            //         "booking_deleted_by_garage_owner"
            //     ));
            // }
            $booking->delete();
            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
