<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingCreateRequestClient;
use App\Http\Requests\BookingStatusChangeRequestClient;
use App\Http\Requests\BookingUpdateRequestClient;
use App\Http\Requests\HoldSlotRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\DiscountUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\PriceUtil;
use App\Http\Utils\UserActivityUtil;
use App\Mail\BookingCreateMail;
use App\Mail\BookingStatusUpdateMail;
use App\Mail\BookingUpdateMail;
use App\Mail\DynamicMail;
use App\Models\Booking;
use App\Models\BookingPackage;
use App\Models\BookingSubService;
use App\Models\Coupon;
use App\Models\ExpertRota;
use App\Models\Garage;

use App\Models\GaragePackage;
use App\Models\GarageSubService;
use App\Models\GarageTime;
use App\Models\Holiday;
use App\Models\Job;
use App\Models\JobBid;
use App\Models\JobPayment;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\PreBooking;
use App\Models\BusinessSetting;
use App\Models\ExpertRotaTime;
use App\Models\SlotHold;
use App\Models\SubService;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ClientBookingController extends Controller
{
    use ErrorUtil, DiscountUtil, PriceUtil, UserActivityUtil, BasicUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/client/bookings",
     *      operationId="createBookingClient",
     *      tags={"client.booking"},
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
     *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     *   *    @OA\Property(property="coupon_code", type="string", format="string",example="123456"),
     *
     *    @OA\Property(property="automobile_make_id", type="number", format="number",example="1"),
     *    @OA\Property(property="automobile_model_id", type="number", format="number",example="1"),
     * * *    @OA\Property(property="car_registration_no", type="string", format="string",example="r-00011111"),
     *     * * *    @OA\Property(property="car_registration_year", type="string", format="string",example="2019-06-29"),
     *
     *   * *    @OA\Property(property="additional_information", type="string", format="string",example="r-00011111"),
     *      *       @OA\Property(property="reason", type="string", format="string",example="pending"),
     *
     *  *   * *    @OA\Property(property="transmission", type="string", format="string",example="transmission"),
     *    *  *   * *    @OA\Property(property="fuel", type="string", format="string",example="Fuel"),
     *

     *
     *
     * @OA\Property(property="job_start_date", type="string", format="string",example="2019-06-29"),


     *  * *    @OA\Property(property="booking_sub_service_ids", type="string", format="array",example={1,2,3,4}),
     *  *  * *    @OA\Property(property="booking_garage_package_ids", type="string", format="array",example={1,2,3,4}),
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

    public function createBookingClient(BookingCreateRequestClient $request)
    {
        try {
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                $request_data = $request->validated();

                $request_data["customer_id"] = auth()->user()->id;
                $request_data["status"] = "pending";
                $request_data["created_by"] = $request->user()->id;
                $request_data["created_from"] = "customer_side";




                $request_data["payment_status"] = "pending";
                $request_data["booking_type"] = "self_booking";





                $garage = Garage::where([
                    "id" => $request_data["garage_id"]
                ])
                    ->first();

                if (!$garage) {
                    return response()
                        ->json(
                            [
                                "message" => "garage not found."
                            ],
                            404
                        );
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



                $booking =  Booking::create($request_data);

                $businessSetting = $this->get_business_setting($booking->garage_id);


                $total_price = 0;
                $total_time = 0;
                if (!empty($request_data["booking_sub_service_ids"])) {
                    foreach ($request_data["booking_sub_service_ids"] as $index => $sub_service_id) {
                        $sub_service = SubService::where([
                            "business_id" => $booking->garage_id,
                            "id" => $sub_service_id
                        ])->first();

                        if (!$sub_service) {
                            $error = [
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

                    BookingPackage::create([
                        "garage_package_id" => $garage_package->id,
                        "price" => $garage_package->price,
                        "booking_id" =>$booking->id
                    ]);

                }

                $slotValidation =  $this->validateBookingSlots($businessSetting, $booking->id, $booking->customer_id, $request["booked_slots"], $request["job_start_date"], $request["expert_id"], $total_time);


                if ($slotValidation['status'] === 'error') {
                    // Return a JSON response with the overlapping slots and a 422 Unprocessable Entity status code
                    return response()->json($slotValidation, 422);
                }

                $processedSlotInformation =  $this->processSlots($businessSetting->slot_duration, $request["booked_slots"]);


                if (count($processedSlotInformation) > 1 || count($processedSlotInformation) == 0) {
                    // Return a JSON response with the overlapping slots and a 422 Unprocessable Entity status code
                    throw new Exception("Slots must be continuous");
                }

                $booking->job_start_time = $processedSlotInformation[0]["start_time"];
                $booking->job_end_time = $processedSlotInformation[0]["end_time"];


                $this->validateGarageTimes($booking->garage_id, $booking->job_start_date, $booking->job_start_time, $booking->job_end_time);


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

                $booking->final_price -= $this->canculate_discount_amount(
                    $booking->price,
                    $booking->coupon_discount_type,
                    $booking->coupon_discount_amount
                );

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
                    "type" => "booking_created_by_client"
                ])
                    ->first();
                // Get the customer's email
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


                // if(env("SEND_EMAIL") == true) {
                //     Mail::to($booking->customer->email)->send(new DynamicMail(
                //     $booking,
                //     "booking_created_by_client"
                // ));
                // }

                if ($booking->payment_method == "stripe") {
                    // Stripe settings retrieval based on business or garage ID
                    $stripeSetting = $this->get_business_setting($booking->garage_id);

                    if (empty($stripeSetting)) {
                        throw new Exception("No stripe seting found", 403);
                    }

                    if (empty($stripeSetting->stripe_enabled)) {
                        throw new Exception("Stripe not enabled", 403);
                    }

                    // Set Stripe client
                    $stripe = new \Stripe\StripeClient($stripeSetting->STRIPE_SECRET);

                    $discount = $this->canculate_discount_amount($booking->price, $booking->discount_type, $booking->discount_amount);
                    $coupon_discount = $this->canculate_discount_amount($booking->price, $booking->coupon_discount_type, $booking->coupon_discount_amount);

                    $total_discount = $discount + $coupon_discount;

                    $tipAmount = $this->canculate_discount_amount(
                        $booking->price,
                        $booking->tip_type,
                        $booking->tip_amount
                    );

                    // Prepare payment intent data
                    $paymentIntentData = [
                        'amount' => ($booking->price + $tipAmount + ($booking->vat_amount ?? 0)) * 100, // Adjusted amount in cents
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

                    $booking->clientSecret = $paymentIntent->client_secret;
                }

                if (env("SEND_EMAIL") == true) {
                    // Get the customer's email
                    $recipientEmails = $this->getNotificationRecipients($booking);
                    Mail::to(
                        $recipientEmails
                    )
                        ->send(new BookingCreateMail($booking));
                }

                $booking = $booking->load(["payments"]);
                return response($booking, 201);
            });
        } catch (Exception $e) {


            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Patch(
     *      path="/v1.0/client/bookings/change-status",
     *      operationId="changeBookingStatusClient",
     *      tags={"client.booking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to change booking status",
     *      description="This method is to change booking status.
     * if status is accepted. the booking will be converted to a job.  and the status of the job will be pending ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","status"},
     * *    @OA\Property(property="id", type="number", format="number",example="1"),
     * @OA\Property(property="status", type="string", format="string",example="pending"),
     *      *       @OA\Property(property="reason", type="string", format="string",example="pending"),

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

    public function changeBookingStatusClient(BookingStatusChangeRequestClient $request)
    {
        try {
            $this->storeActivity($request, "");
            return  DB::transaction(function () use ($request) {

                $request_data = $request->validated();

                $booking = Booking::where([
                    "id" => $request_data["id"],
                    "customer_id" =>  auth()->user()->id
                ])
                    ->first();
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

                $jobStartDate = Carbon::parse($booking->job_start_date);

                if (Carbon::now()->gte($jobStartDate) || Carbon::now()->diffInHours($jobStartDate, false) < 24) {
                    return response()->json(['error' => 'Booking status cannot be changed within 24 hours of the job start time or if the time has already passed'], 409);
                }





                $booking->status = $request_data["status"];
                $booking->status = $request_data["reason"] ?? NULL;

                $booking->save();


                $notification_template = NotificationTemplate::where([
                    "type" => "booking_status_changed_by_garage_owner"
                ])
                    ->first();
                // Get the customer's email
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

                // if(env("SEND_EMAIL") == true) {
                //     Mail::to($booking->customer->email)->send(new DynamicMail(
                //     $booking,
                //     "booking_rejected_by_client"
                // ));
                // }

                if (env("SEND_EMAIL") == true) {

                    $recipientEmails = $this->getNotificationRecipients($booking);

                    Mail::to(
                        $recipientEmails

                    )->send(new BookingStatusUpdateMail($booking));
                }


                return response([
                    "ok" => true
                ], 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/client/bookings",
     *      operationId="updateBookingClient",
     *      tags={"client.booking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update booking",
     *      description="This method is to update booking",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","garage_id","coupon_code","automobile_make_id","automobile_model_id","car_registration_no","car_registration_year","booking_sub_service_ids","booking_garage_package_ids"},
     * *    @OA\Property(property="id", type="number", format="number",example="1"),
     *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     * *   *    @OA\Property(property="coupon_code", type="string", format="string",example="123456"),
     *    @OA\Property(property="automobile_make_id", type="number", format="number",example="1"),
     *    @OA\Property(property="automobile_model_id", type="number", format="number",example="1"),
     * *    @OA\Property(property="car_registration_no", type="string", format="string",example="r-00011111"),
     * *     * * *    @OA\Property(property="car_registration_year", type="string", format="string",example="2019-06-29"),
     *
     *    *  *   * *    @OA\Property(property="transmission", type="string", format="string",example="transmission"),
     *    *  *   * *    @OA\Property(property="fuel", type="string", format="string",example="Fuel"),
     *      *       @OA\Property(property="reason", type="string", format="string",example="pending"),
     *
     *
     *
     *  * *    @OA\Property(property="booking_sub_service_ids", type="string", format="array",example={1,2,3,4}),
     *   *  * *    @OA\Property(property="booking_garage_package_ids", type="string", format="array",example={1,2,3,4}),
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

    public function updateBookingClient(BookingUpdateRequestClient $request)
    {
        try {
            $this->storeActivity($request, "");
            return  DB::transaction(function () use ($request) {

                $request_data = $request->validated();

                $garage = Garage::where([
                    "id" => $request_data["garage_id"]
                ])
                    ->first();

                if (!$garage) {
                    return response()
                        ->json(
                            [
                                "message" => "garage not found."
                            ],
                            404
                        );
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
                $booking = Booking::where(["id" => $request_data["id"]])->first();

                if (!$booking) {
                    return response()->json([
                        "message" => "booking not found"
                    ], 404);
                }
                $businessSetting = $this->get_business_setting($booking->garage_id);
                if ($booking->status != "pending") {
                    // Return an error response indicating that the status cannot be updated
                    return response()->json(["message" => "only pending booking can be deleted"], 422);
                }


                $booking->fill(collect($request_data)->only([
                    "garage_id",
                    "additional_information",
                    'booking_from',
                    "coupon_code",
                    "expert_id",
                    "booked_slots",
                    "reason",
                ])->toArray());

                BookingSubService::where([
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

                $processedSlotInformation =  $this->processSlots($businessSetting->slot_duration, $booking->booked_slots);
                if (count($processedSlotInformation) > 1 || count($processedSlotInformation) == 0) {
                    // Return a JSON response with the overlapping slots and a 422 Unprocessable Entity status code
                    throw new Exception("Slots must be continuous");
                }
                $booking->job_start_time = $processedSlotInformation[0]["start_time"];
                $booking->job_end_time = $processedSlotInformation[0]["end_time"];

                $this->validateGarageTimes($booking->garage_id, $booking->job_start_date, $booking->job_start_time, $booking->job_end_time);


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
                    "type" => "booking_updated_by_client"
                ])
                    ->first();
                // Get the customer's email
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

                // if(env("SEND_EMAIL") == true) {
                //     Mail::to($booking->customer->email)->send(new DynamicMail(
                //     $booking,
                //     "booking_updated_by_client"
                // ));}


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
                    //       "message" =>  "payment is greater than payable"
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
                    Mail::to($recipientEmails)->send(new BookingUpdateMail($booking));
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
     * @OA\Get(
     *      path="/v1.0/client/bookings/{perPage}",
     *      operationId="getBookingsClient",
     *      tags={"client.booking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="perPage",
     *         in="path",
     *         description="perPage",
     *         required=true,
     *  example="6"
     *      ),
     *      *      * *  @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example="pending"
     * ),
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

    public function getBookingsClient($perPage, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            $bookingQuery = Booking::with(
                    "feedbacks",
                    "sub_services.translation",
                    "sub_services.service",
                    "sub_services.service.translation",
                    "booking_packages.garage_package",
                    "booking_sub_services.sub_service",
                    "customer.translation",
                    "garage",
                    "expert.translation",
                    "payments",
                )
                ->where([
                    "customer_id" => $request->user()->id
                ])
                ->when(request()->filled("expert_id"), function ($query) {
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
     *      path="/v1.0/client/available-experts",
     *      operationId="getAvailableExpertsClient",
     *      tags={"client.booking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *
     *      * *  @OA\Parameter(
     * name="business_id",
     * in="query",
     * description="business_id",
     * required=true,
     * example=""
     * ),
     *      * *  @OA\Parameter(
     * name="date",
     * in="query",
     * description="date",
     * required=true,
     * example=""
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

    public function getAvailableExpertsClient(Request $request)
    {
        try {
            $this->storeActivity($request, "");

            if (!request()->filled("date")) {
                return response()->json([
                    "message" => "Date field is required"
                ], 401);
            }
            if (!request()->filled("business_id")) {
                return response()->json([
                    "message" => "business_id field is required"
                ], 401);
            }

            if (!request()->filled("slots")) {
                return response()->json([
                    "message" => "slots field is required"
                ], 401);
            }

            $slots = explode(',', request()->input("slots"));
            // Get available experts
            $response = $this->getAvailableExperts(request()->input("date"), request()->input("business_id"), $slots);

            if ($response['status'] === 'error') {
                return response()->json($response, 422);
            }


            return response()->json($response, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/blocked-dates",
     *      operationId="getBlockedDatesClient",
     *      tags={"client.booking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="expert_id",
     *         in="query",
     *         description="expert_id",
     *         required=true,
     *  example=""
     *      ),

     *      *      * *  @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example=""
     * ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example=""
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example=""
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example=""
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

    public function getBlockedDatesClient(Request $request)
    {
        try {
            $this->storeActivity($request, "");


            $dates = [];
            $available_dates = collect();
            $blocked_dates = collect();
            for ($i = 0; $i <= 30; $i++) {
                $dates[] = Carbon::today()->addDays($i)->toDateString();
            }
            $experts = User::with("translation")
                ->where("users.is_active", 1)
                ->when(request()->filled("expert_id"), function ($query) {
                    $query->where("users.id", request()->input("expert_id"));
                })
                ->whereHas('roles', function ($query) {
                    $query->where('roles.name', 'business_experts');
                })
                ->when(request()->filled("business_id"), function ($query) {
                    $query->where("business_id", request()->input("business_id"));
                })
                ->get();

            $total_experts_count = $experts->count();

            $total_slots_in_one_day = $total_experts_count * 53;

            $total_busy_slots_in_day = collect();

            foreach ($dates as $date) {

                // $totalBookedSlots = Booking::
                // where([
                //     "garage_id" => auth()->user()->business_id
                // ])
                // ->whereDate("job_start_date", $date)
                // ->whereNotIn("status", ["rejected_by_client", "rejected_by_garage_owner"])
                // ->selectRaw('SUM(json_length(booked_slots)) as total_slots')
                // ->value('total_slots');


                // $totalBusySlots = ExpertRota::
                // where([
                //     'expert_rotas.business_id' => auth()->user()->business_id,
                //     "expert_rotas.is_active" => 1
                //     ])
                // ->whereDate("date", $date)
                // ->selectRaw('SUM(json_length(busy_slots)) as total_slots')
                // ->value('total_slots');

                // $total_busy_slots = $totalBookedSlots + $totalBusySlots;

                $total_expert_busy_slots = ExpertRota::selectRaw('COALESCE(SUM(json_length(busy_slots)), 0) as total_busy_slots')
                    ->when(request()->filled("expert_id"), function ($query) {
                        $query->where("expert_id", request()->input("expert_id"));
                    })

                    ->where('is_active', 1)
                    ->whereDate('date', $date)
                    ->value("total_busy_slots");

                $total_booked_slots =  Booking::selectRaw('COALESCE(SUM(json_length(booked_slots)), 0) as total_booked_slots')
                    ->when(request()->filled("expert_id"), function ($query) {
                        $query->where("expert_id", request()->input("expert_id"));
                    })
                    ->when(request()->filled("business_id"), function ($query) {
                        $query->where("garage_id", request()->input("business_id"));
                    })
                    ->whereDate('job_start_date', $date)
                    ->whereNotIn('status', ['rejected_by_client', 'rejected_by_garage_owner'])
                    ->value("total_booked_slots");


                $total_busy_slots = $total_expert_busy_slots + $total_booked_slots;
                // $total_busy_slots_in_day->push([
                //     "date" => $date,
                //     "total_busy_slots" => $total_busy_slots
                // ]);
                if ($total_busy_slots < $total_slots_in_one_day) {
                    $available_dates->push($date);
                } else {
                    $blocked_dates->push($date);
                }
            }

            // Get all bookings for the provided date except the rejected ones




            return response()->json([
                "available_dates" => $available_dates->toArray(),
                "blocked_dates" => $blocked_dates->toArray(),
                //   "total_experts_count" => $total_experts_count,
                //   "total_slots_in_one_day" => $total_slots_in_one_day,
                //  "total_busy_slots_in_day" => $total_busy_slots_in_day->toArray()

            ], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v2.0/client/blocked-dates",
     *      operationId="getBlockedDatesClientV2",
     *      tags={"client.booking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="expert_id",
     *         in="query",
     *         description="expert_id",
     *         required=true,
     *  example=""
     *      ),

     *      *      * *  @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example=""
     * ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example=""
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example=""
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example=""
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

    public function getBlockedDatesClientV2(Request $request)
    {
        try {
            $this->storeActivity($request, "");

            $validator = Validator::make($request->all(), [
                'business_id' => 'required|numeric|exists:businesses,id',
            ], [
                '*.required' => 'The :attribute field is required.',
                '*.string' => 'The :attribute must be a valid string.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            $businessSetting = $this->get_business_setting(request()->input("business_id"));

            $dates = [];
            $available_dates = collect();
            $blocked_dates = collect();
            for ($i = 0; $i <= 30; $i++) {
                $dates[] = Carbon::today()->addDays($i)->toDateString();
            }
            $experts = User::with("translation")
                ->where("users.is_active", 1)
                ->when(request()->filled("expert_id"), function ($query) {
                    $query->where("users.id", request()->input("expert_id"));
                })
                ->whereHas('roles', function ($query) {
                    $query->where('roles.name', 'business_experts');
                })
                ->when(request()->filled("business_id"), function ($query) {
                    $query->where("business_id", request()->input("business_id"));
                })
                ->get();

            $total_experts_count = $experts->count();





            // Get all garage times for the specified business_id in one query
            $garageTimes = GarageTime::where("garage_id", request()->input("business_id"))->get();

            foreach ($dates as $date) {
                $date = Carbon::parse($date);
                $dayOfWeek = $date->dayOfWeek;

                // Filter the collection for the current day of the week and check if it's not closed
                $garage_time = $garageTimes->firstWhere('day', $dayOfWeek);

                if (empty($garage_time) || $garage_time->is_closed) {
                    $blocked_dates->push($date);
                    continue;
                }



                // Parse opening and closing times for the garage on this day
                $openingTime = Carbon::parse($garage_time->opening_time);
                $closingTime = Carbon::parse($garage_time->closing_time);

                // Calculate the total minutes from now until the closing time
                $minutesUntilClose = Carbon::now()->diffInMinutes($closingTime);

                // Get the highest time divisible by the slot duration
                $highestDivisibleTime = floor($minutesUntilClose / $businessSetting->slot_duration) * $businessSetting->slot_duration;

                // Check if the highest divisible time is 0 (i.e., no valid slot duration remaining)
                if ($highestDivisibleTime === 0) {
                    $blocked_dates->push($date);
                    continue;
                }
                // Calculate the extra (remaining) time after the highest divisible time
                $extraTime = $minutesUntilClose - $highestDivisibleTime;

                // Add the extra time to the current time
                $adjustedCurrentTime = Carbon::now()->addMinutes($extraTime);

                $total_available_times = $total_experts_count * $highestDivisibleTime;
                $total_busy_time = 0;

                // $booking->booked_slots =  $this->generateSlots($businessSetting->slot_duration, $booking->job_start_time, $booking->job_end_time);

                $expert_rota_times = ExpertRotaTime::whereHas("rota", function ($query) use ($date) {
                    $query->when(request()->filled("expert_id"), function ($query) {
                        $query->where("expert_rotas.expert_id", request()->input("expert_id"));
                    })
                        ->where('expert_rotas.is_active', 1)
                        ->whereDate('expert_rotas.date', $date);
                })
                    ->where('end_time', '>', $adjustedCurrentTime)
                    ->get();

                $total_busy_time += $this->calculateTotalMinutes($expert_rota_times, "start_time", "end_time", $adjustedCurrentTime);


                $bookings =  Booking::when(request()->filled("expert_id"), function ($query) {
                    $query->where("expert_id", request()->input("expert_id"));
                })
                    ->when(request()->filled("business_id"), function ($query) {
                        $query->where("garage_id", request()->input("business_id"));
                    })
                    ->whereDate('job_start_date', $date)
                    ->whereNotIn('status', ['rejected_by_client', 'rejected_by_garage_owner'])
                    ->get();


                $total_busy_time += $this->calculateTotalMinutes($bookings, "job_start_time", "job_end_time", $adjustedCurrentTime);
                // $total_busy_slots_in_day->push([
                //     "date" => $date,
                //     "total_busy_slots" => $total_busy_slots
                // ]);

                if (($total_available_times - $total_busy_time) > ($businessSetting->slot_duration * $total_experts_count)) {
                    $available_dates->push($date);
                } else {
                    $blocked_dates->push($date);
                }
            }

            // Get all bookings for the provided date except the rejected ones



            return response()->json([
                "available_dates" => $available_dates->toArray(),
                "blocked_dates" => $blocked_dates->toArray(),
                //   "total_experts_count" => $total_experts_count,
                //   "total_slots_in_one_day" => $total_slots_in_one_day,
                //  "total_busy_slots_in_day" => $total_busy_slots_in_day->toArray()

            ], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Post(
     *     path="/v1.0/hold-slot",
     *     operationId="holdSlot",
     *     tags={"slot.management"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     summary="Hold slots for a customer",
     *     description="This method allows a customer to hold slots for 90 seconds.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"held_slots", "customer_id", "expert_id"},
     *             @OA\Property(property="held_slots", type="array", @OA\Items(type="integer"), description="Array of slot IDs"),
     *             @OA\Property(property="customer_id", type="integer", description="ID of the customer"),
     *             @OA\Property(property="expert_id", type="integer", description="ID of the expert")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slots held successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slots held successfully"),
     *             @OA\Property(property="held_until", type="string", format="date-time", example="2024-10-30T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict, user already has slots held",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You already have slots held")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function holdSlot(HoldSlotRequest $request)
    {
        $request_data = $request->validated();

        $request_data["customer_id"] = auth()->user()->id;
        $request_data["held_until"] = Carbon::now()->addSeconds(47);

        // Delete all slots for the customer, including expired ones
        SlotHold::where('customer_id', $request_data["customer_id"])
            ->orWhere('held_until', '<=', Carbon::now())
            ->delete();




        // Hold the slots for 90 seconds
        $heldUntil =  SlotHold::create($request_data);

        return response()->json(['message' => 'Slots held successfully', 'held_until' => $heldUntil]);
    }

    /**
     *
     * @OA\Post(
     *     path="/v1.0/release-slot",
     *     operationId="releaseSlot",
     *     tags={"slot.management"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     summary="Hold slots for a customer",
     *     description="This method allows a customer to hold slots for 90 seconds.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"held_slots", "customer_id", "expert_id"},
     *             @OA\Property(property="held_slots", type="array", @OA\Items(type="integer"), description="Array of slot IDs"),
     *             @OA\Property(property="customer_id", type="integer", description="ID of the customer"),
     *             @OA\Property(property="expert_id", type="integer", description="ID of the expert")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slots held successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slots held successfully"),
     *             @OA\Property(property="held_until", type="string", format="date-time", example="2024-10-30T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict, user already has slots held",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You already have slots held")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, validation error",
     *         @OA\JsonContent(

     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function releaseSlot(Request $request)
    {
        $request_data = $request->toArray();

        $request_data["customer_id"] = auth()->user()->id;


        // Delete all slots for the customer, including expired ones
        SlotHold::where('customer_id', $request_data["customer_id"])
            ->orWhere('held_until', '<=', Carbon::now())
            ->delete();


        return response()->json(['message' => 'Slots released successfully']);
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/blocked-slots/{expert_id}",
     *      operationId="getBlockedSlotsClient",
     *      tags={"client.booking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="perPage",
     *         in="path",
     *         description="perPage",
     *         required=true,
     *  example="6"
     *      ),
     *      *      * *  @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example="pending"
     * ),
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

    public function getBlockedSlotsClient($expert_id, Request $request)
    {
        try {
            $this->storeActivity($request, "");

            if (!request()->filled("date")) {
                return response()->json([
                    "message" => "Date field is required"
                ], 401);
            }

            $data = $this->blockedSlots(request()->input("date"), $expert_id);



            // else {
            //     return response()->json([
            //             "message" => "No slots are available"
            //     ], 400);
            // }

            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v2.0/client/blocked-slots/{expert_id}",
     *      operationId="getBlockedSlotsClientV2",
     *      tags={"client.booking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="perPage",
     *         in="path",
     *         description="perPage",
     *         required=true,
     *  example="6"
     *      ),
     *      *      * *  @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example="pending"
     * ),
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

    public function getBlockedSlotsClientV2($expert_id, Request $request)
    {
        try {
            $this->storeActivity($request, "");

            if (!request()->filled("date")) {
                return response()->json([
                    "message" => "Date field is required"
                ], 401);
            }


            $data = $this->blockedSlotsV2(request()->input("date"), $expert_id);



            // else {
            //     return response()->json([
            //             "message" => "No slots are available"
            //     ], 400);
            // }

            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }





    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/bookings/single/{id}",
     *      operationId="getBookingByIdClient",
     *      tags={"client.booking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get  booking by id ",
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

    public function getBookingByIdClient($id, Request $request)
    {
        try {
            $this->storeActivity($request, "");

            $booking = Booking::with(
                "sub_services.translation",
                "sub_services.service",
                "sub_services.service.translation",
                "booking_packages.garage_package",
                "customer",
                "garage",
                "expert",
                "payments",
                "feedbacks.value"
            )
                ->where([
                    "id" => $id,
                    "customer_id" => $request->user()->id
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
     *     @OA\Delete(
     *      path="/v1.0/client/bookings/{id}",
     *      operationId="deleteBookingByIdClient",
     *      tags={"client.booking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to delete booking by id",
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

    public function deleteBookingByIdClient($id, Request $request)
    {

        try {
            $this->storeActivity($request, "");

            if (!$request->user()->hasPermissionTo('booking_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $booking =  Booking::where([
                "id" => $id,
                "customer_id" => $request->user()->id
            ])->first();
            if (!$booking) {
                return response()->json(
                    [
                        "message" => "no booking found"
                    ],
                    404
                );
            }


            if ($booking->status != "pending") {
                // Return an error response indicating that the status cannot be updated
                return response()->json(["message" => "only pending booking can be deleted"], 422);
            }

            $jobStartDate = Carbon::parse($booking->job_start_date);

            if (Carbon::now()->gte($jobStartDate) || Carbon::now()->diffInHours($jobStartDate, false) < 24) {
                return response()->json(['error' => 'Booking cannot be deleted within 24 hours of the job start time or if the time has already passed'], 409);
            }


            $booking->delete();

            $notification_template = NotificationTemplate::where([
                "type" => "booking_deleted_by_client"
            ])
                ->first();
            // Get the customer's email
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


            //     if(env("SEND_EMAIL") == true) {
            //         Mail::to($booking->customer->email)->send(new DynamicMail(
            //         $booking,
            //         "booking_deleted_by_client"
            //     ));
            // }

            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/client/bookings-stripe-error/{id}",
     *      operationId="deleteBookingStripeErrorByIdClient",
     *      tags={"client.booking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to delete booking by id",
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

    public function deleteBookingStripeErrorByIdClient($id, Request $request)
    {

        try {
            $this->storeActivity($request, "");
            $booking =  Booking::where([
                "id" => $id,
                "customer_id" => $request->user()->id
            ])->first();
            if (!$booking) {
                return response()->json(
                    [
                        "message" => "no booking found"
                    ],
                    404
                );
            }


            if ($booking->status != "pending") {
                // Return an error response indicating that the status cannot be updated
                return response()->json(["message" => "only pending booking can be deleted"], 422);
            }



            $booking->delete();



            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }
}
