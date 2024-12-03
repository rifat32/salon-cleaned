<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingToJobRequest;
use App\Http\Requests\JobPaymentCreateRequest;
use App\Http\Requests\JobStatusChangeRequest;
use App\Http\Requests\JobUpdateRequest;
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
use App\Models\GaragePackage;
use App\Models\Job;
use App\Models\JobBid;
use App\Models\JobPackage;
use App\Models\JobPayment;
use App\Models\JobSubService;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\PreBooking;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class JobController extends Controller
{
    use ErrorUtil, GarageUtil, PriceUtil, UserActivityUtil, DiscountUtil, BasicUtil;




    /**
     * @OA\Get(
     *      path="/v1.0/jobs-payments-sum/{garage_id}",
     *      operationId="getJobPaymentsSum",
     *      tags={"job_management.payment"},
     *      security={
     *           {"bearerAuth": {}}
     *      },
     *      summary="This method is to get all job payments or payments for a specific job",
     *      description="This method is to get all job payments or payments for a specific job",
     *
     *      @OA\Parameter(
     *          name="booking_id",
     *          in="query",
     *          description="Optional Job ID to filter payments",
     *          required=false,
     *          example="1"
     *      ),
     *      @OA\Parameter(
     *          name="garage_id",
     *          in="path",
     *          description="Garage ID",
     *          required=true,
     *          example="1"
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Content",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found",
     *          @OA\JsonContent(),
     *      )
     * )
     */
    public function getJobPaymentsSum($garage_id, Request $request)
    {
        try {
            $this->storeActivity($request, "Fetching job payments");

            if (!$request->user()->hasPermissionTo('job_view')) {
                return response()->json([
                    "message" => "You cannot perform this action"
                ], 401);
            }



            // Check if job_id is provided
            $query = JobPayment::with(
                ["bookings" => function ($query) {
                    $query->select("bookings.id", "bookings.payment_method", "bookings.job_start_date");
                }]
            )
                ->whereHas("bookings", function ($query) use ($garage_id, $request) {
                    $query
                        ->when(request()->filled("status"), function ($query) {
                            $statusArray = explode(',', request()->input("status"));
                            // If status is provided, include the condition in the query
                            $query->whereIn("status", $statusArray);
                        })
                        ->where("bookings.garage_id", $garage_id)
                        ->when(auth()->user()->hasRole("business_experts"), function ($query) {
                            $query->where('bookings.expert_id', auth()->user()->id);
                        });
                    // Additional date filters using date_filter
                    if ($request->date_filter === 'today') {
                        $query = $query->whereDate('bookings.job_start_date', Carbon::today());
                    } elseif ($request->date_filter === 'this_week') {
                        $query = $query->whereBetween('bookings.job_start_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    } elseif ($request->date_filter === 'previous_week') {
                        $query = $query->whereBetween('bookings.job_start_date', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                    } elseif ($request->date_filter === 'next_week') {
                        $query = $query->whereBetween('bookings.job_start_date', [Carbon::now()->addWeek()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()]);
                    } elseif ($request->date_filter === 'this_month') {
                        $query = $query->whereMonth('bookings.job_start_date', Carbon::now()->month)
                            ->whereYear('bookings.job_start_date', Carbon::now()->year);
                    } elseif ($request->date_filter === 'previous_month') {
                        $query = $query->whereMonth('bookings.job_start_date', Carbon::now()->subMonth()->month)
                            ->whereYear('bookings.job_start_date', Carbon::now()->subMonth()->year);
                    } elseif ($request->date_filter === 'next_month') {
                        $query = $query->whereMonth('bookings.job_start_date', Carbon::now()->addMonth()->month)
                            ->whereYear('bookings.job_start_date', Carbon::now()->addMonth()->year);
                    }
                })
                ->when($request->has('booking_id'), function ($query) {
                    $query->where('booking_id', request()->input("booking_id"));
                });



            // Fetch payments
            $job_payments = $query->get()->sum("amount");



            return response()->json($job_payments, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     * @OA\Get(
     *      path="/v1.0/jobs-payments/{garage_id}",
     *      operationId="getJobPayments",
     *      tags={"job_management.payment"},
     *      security={
     *           {"bearerAuth": {}}
     *      },
     *      summary="This method is to get all job payments or payments for a specific job",
     *      description="This method is to get all job payments or payments for a specific job",
     *
     *      @OA\Parameter(
     *          name="job_id",
     *          in="query",
     *          description="Optional Job ID to filter payments",
     *          required=false,
     *          example="1"
     *      ),
     *      @OA\Parameter(
     *          name="garage_id",
     *          in="path",
     *          description="Garage ID",
     *          required=true,
     *          example="1"
     *      ),
     *  *      @OA\Parameter(
     *          name="date_filter",
     *          in="path",
     *          description="date_filter",
     *          required=true,
     *          example=""
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Content",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found",
     *          @OA\JsonContent(),
     *      )
     * )
     */
    public function getJobPayments($garage_id, Request $request)
    {
        try {
            $this->storeActivity($request, "Fetching job payments");

            if (!$request->user()->hasPermissionTo('job_view')) {
                return response()->json([
                    "message" => "You cannot perform this action"
                ], 401);
            }


            // Check if job_id is provided
            $payments = JobPayment::with([
                "bookings.customer",
                "bookings.sub_services"
            ])


                ->when(request()->filled("payment_type"), function ($query) {
                    $payment_typeArray = explode(',', request()->payment_type);
                    $query->whereIn("job_payments.payment_type", $payment_typeArray);
                })
                ->when(request()->filled('is_returning_customers'), function ($q) {
                    $isReturning = request()->boolean("is_returning_customers");

                    $q->whereHas("bookings", function ($query) use ($isReturning) {
                        // Separate subquery to count all bookings for each customer.
                        $query->whereIn('bookings.customer_id', function ($subquery) use ($isReturning) {
                            $subquery->select('customer_id')
                                ->from('bookings')
                                ->groupBy('customer_id')
                                ->having(DB::raw('COUNT(id)'), $isReturning ? '>' : '=', 1);
                        });
                    });
                })

                ->whereHas("bookings", function ($query) use ($garage_id, $request) {
                    $query

                        ->when(request()->filled("duration_in_minute"), function ($query) {
                            $durationInMinutes = request()->input("duration_in_minute");
                            $query->whereRaw("TIMESTAMPDIFF(MINUTE, job_start_time, job_end_time) = ?", [$durationInMinutes]);
                        })
                        ->where('bookings.garage_id', auth()->user()->business_id)
                        ->when(request()->filled("slots"), function ($query) {
                            $slotsArray = explode(',', request()->input("slots"));
                            $query->where(function ($subQuery) use ($slotsArray) {
                                foreach ($slotsArray as $slot) {
                                    $subQuery->orWhereRaw("JSON_CONTAINS(bookings.busy_slots, '\"$slot\"')");
                                }
                            });
                        })
                        ->when(request()->filled("status"), function ($query) {
                            $statusArray = explode(',', request()->input("status"));
                            // If status is provided, include the condition in the query
                            $query->whereIn("status", $statusArray);
                        })
                        ->when(request()->filled("booking_type"), function ($query) {
                            $booking_typeArray = explode(',', request()->input("booking_type"));
                            // If status is provided, include the condition in the query
                            $query->whereIn("booking_type", $booking_typeArray);
                        })

                        ->when(auth()->user()->hasRole("business_experts"), function ($query) {
                            $query->where('bookings.expert_id', auth()->user()->id);
                        })
                        ->when(request()->filled("expert_id"), function ($query) {
                            $query->where('bookings.expert_id', request()->input("expert_id"));
                        })
                        ->when(!empty($request->sub_service_ids), function ($query) use ($request) {

                            $sub_service_ids = explode(',', request()->sub_service_ids);

                            return $query->whereHas('sub_services', function ($query) use ($sub_service_ids) {
                                return $query->whereIn('sub_services.id', $sub_service_ids)
                                    ->when(!empty($request->service_ids), function ($query) {
                                        $service_ids = explode(',', request()->service_ids);

                                        return $query->whereHas('service', function ($query) use ($service_ids) {
                                            return $query->whereIn('services.id', $service_ids);
                                        });
                                    })
                                ;
                            });
                        })
                    ;

                    // Additional date filters using date_filter
                    if ($request->date_filter === 'today') {
                        $query->whereDate('bookings.job_start_date', Carbon::today());
                    } elseif ($request->date_filter === 'yesterday') {
                        $query->whereDate('bookings.job_start_date', Carbon::yesterday());
                    } elseif ($request->date_filter === 'this_week') {
                        $query->whereBetween('bookings.job_start_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    } elseif ($request->date_filter === 'previous_week') {
                        $query->whereBetween('bookings.job_start_date', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                    } elseif ($request->date_filter === 'next_week') {
                        $query->whereBetween('bookings.job_start_date', [Carbon::now()->addWeek()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()]);
                    } elseif ($request->date_filter === 'this_month') {
                        $query->whereMonth('bookings.job_start_date', Carbon::now()->month)
                            ->whereYear('bookings.job_start_date', Carbon::now()->year);
                    } elseif ($request->date_filter === 'previous_month') {
                        $query->whereMonth('bookings.job_start_date', Carbon::now()->subMonth()->month)
                            ->whereYear('bookings.job_start_date', Carbon::now()->subMonth()->year);
                    } elseif ($request->date_filter === 'next_month') {
                        $query->whereMonth('bookings.job_start_date', Carbon::now()->addMonth()->month)
                            ->whereYear('bookings.job_start_date', Carbon::now()->addMonth()->year);
                    }
                })
                ->when($request->has('booking_id'), function ($query) {
                    $query->where('booking_id', request()->input("booking_id"));
                })
                ->select('job_payments.*')
                ->join('bookings', 'bookings.id', '=', 'job_payments.booking_id') // Join the bookings table
                ->join('users', 'users.id', '=', 'bookings.customer_id') // Join the customers table
                ->addSelect(DB::raw("CASE
    WHEN users.is_walk_in_customer = 0 THEN 'app_customer'
    ELSE 'walk_customer'
END AS customer_type"))
                ->when($request->filled("id"), function ($query) use ($request) {
                    return $query
                        ->where("users.id", $request->input("id")) // Change to customers.id
                        ->first();
                }, function ($query) {
                    return $query->when(!empty(request()->per_page), function ($query) {
                        return $query->paginate(request()->per_page);
                    }, function ($query) {
                        return $query->get();
                    });
                });

            // Organize response data into a single collection
            $responseData = [
                "payments" => $payments
            ];


            return response()->json($responseData, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Patch(
     *      path="/v1.0/jobs/payment",
     *      operationId="addPayment",
     *      tags={"job_management.payment"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to add payment",
     *      description="This method is to add payment",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * *   required={"booking_id","garage_id","payments"},
     * *    @OA\Property(property="booking_id", type="number", format="number",example="1"),
     *  * *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     *  * *    @OA\Property(property="payments", type="string", format="array",example={
     * {"payment_type":"card","amount":50},
     *  * {"payment_type":"card","amount":60},
     * }),

     *
     *
     *         ),
     *  * *

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

    public function addPayment(JobPaymentCreateRequest $request)
    {


        try {
            $this->storeActivity($request, "");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('job_update')) {
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


                $booking  = Booking::where([
                    "id" => $request_data["booking_id"],
                    "garage_id" =>  $request_data["garage_id"]
                ])->first();

                if (!$booking) {
                    return response()->json([
                        "message" => "booking not found"
                    ], 404);
                }

                if ($booking->payment_status == "completed") {
                    return response()->json([
                        "message" => "Already paid"
                    ], 409);
                }


                $total_payable = $booking->final_price;


                $payments = collect($request_data["payments"]);

                $payment_amount =  $payments->sum("amount");

                $job_payment_amount =  JobPayment::where([
                    "booking_id" => $booking->id
                ])->sum("amount");

                $total_payment = $job_payment_amount + $payment_amount;

                // if ($total_payable < $total_payment) {
                //     return response([
                //         "message" =>  "payment is greater than payable"
                //     ], 409);
                // }

                foreach ($payments->all() as $payment) {

                    JobPayment::create([
                        "booking_id" => $booking->id,
                        "payment_type" => $payment["payment_type"],
                        "amount" => $payment["amount"],
                    ]);
                }


                if ($total_payable == $total_payment) {
                    Booking::where([
                        "id" => $request_data["booking_id"]
                    ])
                        ->update([
                            "payment_status" => "complete",
                            "payment_method" => "cash"
                        ]);
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
     * @OA\Delete(
     *      path="/v1.0/jobs/payment/{garage_id}{id}",
     *
     *      operationId="deletePaymentById",
     *      tags={"job_management.payment"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *  *  *      @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="6"
     *      ),
     *  *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to delete payment by id",
     *      description="This method is to delete payment by id",
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

    public function deletePaymentById($garage_id, $id, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            return  DB::transaction(function () use (&$id, &$garage_id, &$request) {

                if (!$request->user()->hasPermissionTo('job_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                if (!$this->garageOwnerCheck($garage_id)) {
                    return response()->json([
                        "message" => "you are not the owner of the garage or the requested garage does not exist."
                    ], 401);
                }

                $payment = JobPayment::leftJoin('jobs', 'job_payments.job_id', '=', 'jobs.id')
                    ->where([
                        "jobs.garage_id" => $garage_id,
                        "job_payments.id" => $id
                    ])
                    ->first();
                if (!$payment) {
                    return response()->json([
                        "message" => "payment not found"
                    ], 404);
                }

                Job::where([
                    "id" => $payment->job_id
                ])
                    ->update([
                        "payment_status" => "due"
                    ]);
                $payment->delete();


                return response()->json(["ok" => true], 200);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
}
