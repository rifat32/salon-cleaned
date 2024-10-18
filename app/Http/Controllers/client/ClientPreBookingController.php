<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\MultipleImageUploadRequest;
use App\Http\Requests\PreBookingConfirmRequestClient;
use App\Http\Requests\PreBookingCreateRequestClient;
use App\Http\Requests\PreBookingUpdateRequestClient;
use App\Http\Requests\VideoUploadRequest;
use App\Http\Utils\DiscountUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\PriceUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\AutomobileMake;
use App\Models\AutomobileModel;
use App\Models\Booking;
use App\Models\GarageSubService;
use App\Models\Job;
use App\Models\JobBid;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\PreBooking;
use App\Models\PreBookingSubService;
use App\Models\SubService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ClientPreBookingController extends Controller
{
    use ErrorUtil,UserActivityUtil,PriceUtil;


       /**
        *
     * @OA\Post(
     *      path="/v1.0/client/pre-bookings-video",
     *      operationId="createPreBookingVideoClient",
     *      tags={"client.prebooking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store pre booking video ",
     *      description="This method is to store pre booking video",
     *
   *  @OA\RequestBody(
        *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"video"},
*         @OA\Property(
*             description="video to upload",
*             property="video",
*             type="file",
*             collectionFormat="multi",
*         )
*     )
* )



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

     public function createPreBookingVideoClient(VideoUploadRequest $request)
     {
         try{
             $this->storeActivity($request,"");
             // if(!$request->user()->hasPermissionTo('user_create')){
             //      return response()->json([
             //         "message" => "You can not perform this action"
             //      ],401);
             // }

             $insertableData = $request->validated();

             $location =  config("setup-config.temporary_files_location");

             $new_file_name = time() . '_' . str_replace(' ', '_', $insertableData["video"]->getClientOriginalName());

             $insertableData["video"]->move(public_path($location), $new_file_name);


             return response()->json(["video" => $new_file_name,"location" => $location,"full_location"=>("/".$location."/".$new_file_name)], 200);


         } catch(Exception $e){
             error_log($e->getMessage());
         return $this->sendError($e,500,$request);
         }
     }

 /**
        *
     * @OA\Post(
     *      path="/v1.0/client/pre-bookings-image-multiple",
     *      operationId="createPreBookingImageMultipleClient",
     *      tags={"client.prebooking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to store pre booking images",
     *      description="This method is to store pre booking images",
     *
   *  @OA\RequestBody(
        *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"images[]"},
*         @OA\Property(
*             description="array of images to upload",
*             property="images[]",
*             type="array",
*             @OA\Items(
*                 type="file"
*             ),
*             collectionFormat="multi",
*         )
*     )
* )



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

     public function createPreBookingImageMultipleClient(MultipleImageUploadRequest $request)
     {
         try{

             $this->storeActivity($request,"");
             $insertableData = $request->validated();

             $location =  config("setup-config.temporary_files_location");

             $images = [];
             if(!empty($insertableData["images"])) {
                 foreach($insertableData["images"] as $image){
                     $new_file_name = time() . '_' . str_replace(' ', '_', $image->getClientOriginalName());
                     $image->move(public_path($location), $new_file_name);

                     array_push($images,("/".$location."/".$new_file_name));




                     // GarageGallery::create([
                     //     "image" => ("/".$location."/".$new_file_name),
                     //     "garage_id" => $garage_id
                     // ]);




                 }
             }


             return response()->json(["images" => $images], 201);


         } catch(Exception $e){
             error_log($e->getMessage());
         return $this->sendError($e,500,$request);
         }
     }




    /**
     *
     * @OA\Post(
     *      path="/v1.0/client/pre-bookings",
     *      operationId="createPreBookingClient",
     *      tags={"client.prebooking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store pre  booking",
     *      description="This method is to store pre booking",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"automobile_make_id","automobile_model_id","car_registration_no","car_registration_year","pre_booking_sub_service_ids","job_end_date"},


     *
     *    @OA\Property(property="automobile_make_id", type="number", format="number",example="1"),
     *    @OA\Property(property="automobile_model_id", type="number", format="number",example="1"),
     * * *    @OA\Property(property="car_registration_no", type="string", format="string",example="r-00011111"),
     * *     * * *    @OA\Property(property="car_registration_year", type="string", format="string",example="2019-06-29"),
     *   * *    @OA\Property(property="additional_information", type="string", format="string",example="r-00011111"),
     *
     *  *   * *    @OA\Property(property="transmission", type="string", format="string",example="transmission"),
     *    *  *   * *    @OA\Property(property="fuel", type="string", format="string",example="Fuel"),

     *
     *
     * @OA\Property(property="job_start_date", type="string", format="string",example="2019-06-29"),
     *  * @OA\Property(property="job_start_time", type="string", format="string",example="10:10"),
     *  * @OA\Property(property="job_end_date", type="string", format="string",example="2019-07-29"),
     *
     *     *  * @OA\Property(property="images", type="string", format="array",example={"a.mp4","b.mp4"}),
 *     *  * @OA\Property(property="videos", type="string", format="array",example={"a.jpg","b.jpg"}),
 *  *     *  * @OA\Property(property="file_links", type="string", format="array",example={"a.jpg","b.jpg"}),

     *  * *    @OA\Property(property="pre_booking_sub_service_ids", type="string", format="array",example={1,2,3,4}),
     *
     *

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

    public function createPreBookingClient(PreBookingCreateRequestClient $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                $this->storeActivity($request,"");

                $insertableData = $request->validated();

                $insertableData["customer_id"] = auth()->user()->id;
                $insertableData["status"] = "pending";


                $temporary_files_location =  config("setup-config.temporary_files_location");
                $location =  config("setup-config.pre_booking_file_location");

                foreach(array_merge($insertableData["images"],$insertableData["videos"]) as $temp_file_path) {



                    if (File::exists(public_path($temp_file_path))) {

                        $destination_path = public_path(str_replace($temporary_files_location, $location, $temp_file_path));

    // Create the destination directory if it doesn't exist
    if (!File::isDirectory($destination_path)) {
        File::makeDirectory($destination_path, 0755, true); // Create directory with permissions
    }

    try {
        File::move(public_path($temp_file_path), $destination_path);
    } catch (\Exception $e) {
        // Handle the exception
        Log::error('File move failed: ' . $e->getMessage());
        // Or throw a custom exception with more specific information
        throw new \Exception('Error moving file: ' . $e->getMessage());
    }


                    } else {

                        throw new Exception(("no file exists"));
                        // Handle the case where the file does not exist (e.g., log an error or take appropriate action)
                    }
                }











$insertableData["images"] = json_encode($insertableData["images"]) ;
$insertableData["videos"] = json_encode($insertableData["videos"]) ;
$insertableData["file_links"] = json_encode($insertableData["file_links"]) ;



                $automobile_make = AutomobileMake::where([
                    "id" =>  $insertableData["automobile_make_id"]
                ])
                    ->first();
                if (!$automobile_make) {

                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => ["automobile_make_id"=>["invalid automobile make id"]]
                 ];
                    throw new Exception(json_encode($error),422);
                }
                $automobile_model = AutomobileModel::where([
                    "id" => $insertableData["automobile_model_id"],
                    "automobile_make_id" => $automobile_make->id
                ])
                    ->first();
                if (!$automobile_model) {
                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => ["automobile_model_id"=>["invalid automobile model id"]]
                 ];
                    throw new Exception(json_encode($error),422);
                }






                $pre_booking =  PreBooking::create($insertableData);




                foreach ($insertableData["pre_booking_sub_service_ids"] as $index=>$sub_service_id) {
                    $sub_service =  SubService::where([
                            "id" => $sub_service_id,
                        ])
                        ->first();

                    if (!$sub_service) {

                        $error =  [
                            "message" => "The given data was invalid.",
                            "errors" => [("pre_booking_sub_service_ids[".$index."]")=>["invalid service"]]
                     ];
                        throw new Exception(json_encode($error),422);
                    }






                    $pre_booking->pre_booking_sub_services()->create([
                        "sub_service_id" => $sub_service->id,
                    ]);
                }







                return response($pre_booking, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e,500,$request);
        }
    }




    /**
     *
     * @OA\Put(
     *      path="/v1.0/client/pre-bookings",
     *      operationId="updatePreBookingClient",
     *      tags={"client.prebooking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update pre booking",
     *      description="This method is to update pre booking",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","automobile_make_id","automobile_model_id","car_registration_no","car_registration_year","pre_booking_sub_service_ids"},
     * *    @OA\Property(property="id", type="number", format="number",example="1"),

     *    @OA\Property(property="automobile_make_id", type="number", format="number",example="1"),
     *    @OA\Property(property="automobile_model_id", type="number", format="number",example="1"),
     * *    @OA\Property(property="car_registration_no", type="string", format="string",example="r-00011111"),
     * *     * * *    @OA\Property(property="car_registration_year", type="string", format="string",example="2019-06-29"),
     *  * *    @OA\Property(property="pre_booking_sub_service_ids", type="string", format="array",example={1,2,3,4}),
     *         ),
     *    * @OA\Property(property="job_start_date", type="string", format="string",example="2019-06-29"),
     *  * @OA\Property(property="job_start_time", type="string", format="string",example="10:10"),
     *  * @OA\Property(property="job_end_date", type="string", format="string",example="2019-07-29"),
     *   *
     *     *  * @OA\Property(property="images", type="string", format="array",example={"a.mp4","b.mp4"}),
 *     *  * @OA\Property(property="videos", type="string", format="array",example={"a.jpg","b.jpg"}),
 *  *     *  * @OA\Property(property="file_links", type="string", format="array",example={"a.jpg","b.jpg"}),
 *
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

    public function updatePreBookingClient(PreBookingUpdateRequestClient $request)
    {
        try {
            return  DB::transaction(function () use ($request) {
                $this->storeActivity($request,"");

                $updatableData = $request->validated();


                $temporary_files_location =  config("setup-config.temporary_files_location");
                $location =  config("setup-config.pre_booking_file_location");
                foreach(array_merge($updatableData["images"],$updatableData["videos"]) as $temp_file_path) {



                    if (File::exists(public_path($temp_file_path))) {

                        // Move the file from the temporary location to the permanent location
                        File::move(public_path($temp_file_path), public_path(str_replace($temporary_files_location, $location, $temp_file_path)));
                    } else {

                        // throw new Exception(("no file exists"));
                        // Handle the case where the file does not exist (e.g., log an error or take appropriate action)
                    }
                }



                $updatableData["images"] = json_encode($updatableData["images"]) ;
                $updatableData["videos"] = json_encode($updatableData["videos"]) ;
                $updatableData["file_links"] = json_encode($updatableData["file_links"]) ;



                $automobile_make = AutomobileMake::where([
                    "id" =>  $updatableData["automobile_make_id"]
                ])
                    ->first();
                if (!$automobile_make) {
                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => ["automobile_make_id"=>["invalid automobile make id"]]
                 ];
                    throw new Exception(json_encode($error),422);
                }
                $automobile_model = AutomobileModel::where([
                    "id" => $updatableData["automobile_model_id"],
                    "automobile_make_id" => $automobile_make->id
                ])
                    ->first();
                if (!$automobile_model) {
                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => ["automobile_model_id"=>["invalid automobile model id"]]
                 ];
                    throw new Exception(json_encode($error),422);
                }






                $pre_booking  =  tap(PreBooking::where(["id" => $updatableData["id"]]))->update(
                    collect($updatableData)->only([
                        "automobile_make_id",
                        "automobile_model_id",
                        "car_registration_no",
                        "car_registration_year",
                        "additional_information",
                        "job_start_date",
                        "job_start_time",
                        "job_end_date",
                        "coupon_code",
                        'pre_booking_sub_service_ids',
                        'pre_booking_sub_service_ids.*',
                        "fuel",
                        "transmission",
                        "images",
                        "videos",
                        "file_links"




                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$pre_booking) {
                    return response()->json([
                        "message" => "pre booking not found"
                    ], 404);
                }
                PreBookingSubService::where([
                    "pre_booking_id" => $pre_booking->id
                ])->delete();



                foreach ($updatableData["pre_booking_sub_service_ids"] as $index=>$sub_service_id) {
                    $sub_service =  SubService::where([
                            "id" => $sub_service_id,

                        ])

                        ->first();

                    if (!$sub_service) {
                        $error =  [
                            "message" => "The given data was invalid.",
                            "errors" => [("pre_booking_sub_service_ids[".$index."]")=>["invalid service"]]
                     ];
                        throw new Exception(json_encode($error),422);
                    }






                    $pre_booking->pre_booking_sub_services()->create([
                        "sub_service_id" => $sub_service->id,
                    ]);
                }






                return response($pre_booking, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e,500,$request);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/pre-bookings/{perPage}",
     *      operationId="getPreBookingsClient",
     *      tags={"client.prebooking"},
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
     *      *      *      * *  @OA\Parameter(
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
     *      summary="This method is to get pre bookings ",
     *      description="This method is to get pre bookings",
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

    public function getPreBookingsClient($perPage, Request $request)
    {
        try {
            $this->storeActivity($request,"");
            $preBookingQuery = PreBooking::with("pre_booking_sub_services.sub_service", "job_bids.garage", "automobile_make", "automobile_model")
            ->leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
            ->where([
                    "pre_bookings.customer_id" => $request->user()->id
                ]);

            if (!empty($request->search_key)) {
                $preBookingQuery = $preBookingQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("pre_bookings.car_registration_no", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $preBookingQuery = $preBookingQuery->where('pre_bookings.created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $preBookingQuery = $preBookingQuery->where('pre_bookings.created_at', "<=", $request->end_date);
            }

            if (!empty($request->status)) {
                $status   = $request->status;
                if($status == "completed") {
                    $status = "job_completed";
                }
                $preBookingQuery = $preBookingQuery->where('pre_bookings.status', $status);
            }





            $pre_bookings = $preBookingQuery
            ->select(
                "pre_bookings.*",



                'users.address_line_1',
                'users.address_line_2',
                'users.country',
                'users.city',
                'users.postcode',
                "users.lat",
                "users.long",

            )
            ->groupBy("pre_bookings.id")
            ->orderByDesc("pre_bookings.id")

            ->paginate($perPage);

            return response()->json($pre_bookings, 200);
        } catch (Exception $e) {

            return $this->sendError($e,500,$request);
        }
    }





    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/pre-bookings/single/{id}",
     *      operationId="getPreBookingByIdClient",
     *      tags={"client.prebooking"},
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
     *      summary="This method is to get pre  booking by id ",
     *      description="This method is to get pre booking by id",
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

    public function getPreBookingByIdClient($id, Request $request)
    {
        try {
            $this->storeActivity($request,"");
            $pre_booking = PreBooking::with("pre_booking_sub_services.sub_service", "job_bids.garage", "automobile_make", "automobile_model")
            ->leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
                ->where([
                    "id" => $id,
                    "pre_bookings.customer_id" => $request->user()->id
                ])
                ->select("pre_bookings.*",

                'users.address_line_1',
                'users.address_line_2',
                'users.country',
                'users.city',
                'users.postcode',
                "users.lat",
                "users.long",
                )
                ->first();

            if (!$pre_booking) {
                return response()->json([
                    "message" => "booking not found"
                ], 404);
            }


            return response()->json($pre_booking, 200);
        } catch (Exception $e) {

            return $this->sendError($e,500,$request);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/client/pre-bookings/confirm",
     *      operationId="confirmPreBookingClient",
     *      tags={"client.prebooking"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to confirm pre  booking",
     *      description="This method is to confirm pre booking",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"pre_booking_id","job_bid_id"},


     *
     *    @OA\Property(property="pre_booking_id", type="number", format="number",example="1"),
     *    @OA\Property(property="job_bid_id", type="number", format="number",example="1"),
     *
     *  *    @OA\Property(property="is_confirmed", type="boolean", format="boolean",example="true"),
     *
     *
     *
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

    public function confirmPreBookingClient(PreBookingConfirmRequestClient $request)
    {
        try {
            $this->storeActivity($request,"");
            return DB::transaction(function () use ($request) {

                $insertableData = $request->validated();

                $pre_booking  = PreBooking::where([
                    "id" => $insertableData["pre_booking_id"],
                    "customer_id" => auth()->user()->id,

                ])
                    ->first();


                if (!$pre_booking) {
                    return response()->json([
                        "message" => "pre booking not found for user id " . auth()->user()->id
                    ], 404);
                }
                if ($pre_booking->status !== "pending" && $insertableData["is_confirmed"]) {
                    return response()->json([
                        "message" => "you can only confirm pending pre bookings"
                    ], 409);
                }


                $job_bid  = JobBid::where([
                    "id" => $insertableData["job_bid_id"],
                    "pre_booking_id" => $pre_booking->id,
                ])
                    ->first();


                if (!$job_bid) {
                    return response()->json([
                        "message" => "job bid not found"
                    ], 404);
                }

                if (!$insertableData["is_confirmed"]) {

$job_bid->status = "rejected";
$job_bid->save();

                    $notification_template = NotificationTemplate::where([
                        "type" => "bid_rejected_by_client"
                    ])
                        ->first();

                    Notification::create([
                        "sender_id" => $request->user()->id,
                        "receiver_id" => $job_bid->garage->owner_id,
                        "customer_id" => $job_bid->pre_booking->customer_id,
                        "garage_id" => $job_bid->garage_id,
                        "bid_id" => $job_bid->id,
                        "pre_booking_id" => $job_bid->pre_booking->id,
                        "notification_template_id" => $notification_template->id,
                        "status" => "unread",
                    ]);
                } else {


                    $insertableData["customer_id"] = auth()->user()->id;
                    $insertableData["created_by"] = $request->user()->id;
                    $insertableData["created_from"] = "customer_side";

                    $pre_booking->status = "booked";
                    $pre_booking->selected_bid_id =  $job_bid->id;
                    $pre_booking->save();

                    $job_bid->status = "accepted";
                    $job_bid->save();




                    $booking = Booking::create([
                        "pre_booking_id" => $pre_booking->id,
                        "garage_id" => $job_bid->garage_id,
                        "customer_id" => $pre_booking->customer_id,

                        "additional_information" => $pre_booking->additional_information,
                        "job_start_date" => $job_bid->job_start_date,

                        // "coupon_discount_type" => $pre_booking->coupon_discount_type,
                        // "coupon_discount_amount" => $pre_booking->coupon_discount_amount,


                        "discount_type" => "fixed",
                        "discount_amount" => 0,
                        "price" => $job_bid->price,
                        "final_price" => $job_bid->price,
                        "status" => "pending",
                        "payment_status" => "due",
                        "created_by" => $insertableData["created_by"]



                    ]);

                    $total_price = 0;

                    foreach (PreBookingSubService::where([
                        "pre_booking_id" => $pre_booking->id
                    ])->get()
                        as
                        $pre_booking_sub_service) {
                        $garage_sub_service =  GarageSubService::leftJoin('garage_services', 'garage_sub_services.garage_service_id', '=', 'garage_services.id')
                            ->where([
                                "garage_services.garage_id" => $job_bid->garage_id,
                                "garage_sub_services.sub_service_id" => $pre_booking_sub_service->sub_service_id
                            ])
                            ->select(
                                "garage_sub_services.id",
                                "garage_sub_services.sub_service_id",
                                "garage_sub_services.garage_service_id"
                            )
                            ->first();

                        if (!$garage_sub_service) {
                            throw new Exception("invalid sub service for garage id " . $job_bid->garage_id . " sub service id " . $pre_booking_sub_service->sub_service_id);
                        }

                        $price = $this->getPrice($pre_booking_sub_service->sub_service_id, $garage_sub_service->id, $pre_booking->automobile_make_id);

                        $booking->booking_sub_services()->create([
                            "sub_service_id" => $pre_booking_sub_service->sub_service_id,
                            "price" => $price
                        ]);
                        $total_price += $price;
                    }



                    // $job->price = $total_price;

                    $booking->save();


                    $notification_template = NotificationTemplate::where([
                        "type" => "bid_accepted_by_client"
                    ])
                        ->first();

                    Notification::create([
                        "sender_id" => $request->user()->id,
                        "receiver_id" => $job_bid->garage->owner_id,
                        "customer_id" => $job_bid->pre_booking->customer_id,
                        "garage_id" => $job_bid->garage_id,
                        "bid_id" => $job_bid->id,
                        "pre_booking_id" => $job_bid->pre_booking->id,
                        "booking_id" => $booking->id,
                        "notification_template_id" => $notification_template->id,
                        "status" => "unread",
                    ]);
                    // $pre_booking_sub_service->delete();


                }






                return response([
                    "ok" => true
                ], 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e,500,$request);
        }
    }





    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/client/pre-bookings/{id}",
     *      operationId="deletePreBookingByIdClient",
     *      tags={"client.prebooking"},
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
     *      summary="This method is to delete pre booking by id",
     *      description="This method is to delete pre booking by id",
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

    public function deletePreBookingByIdClient($id, Request $request)
    {

        try {
            $this->storeActivity($request,"");
            $pre_booking = PreBooking::where([
                "id" => $id,
                "customer_id" => $request->user()->id
            ])
                ->first();

            if (!$pre_booking) {
                return response()->json([
                    "message" => "no pre-booking not found"
                ], 404);
            }

            if ($pre_booking->status != "pending") {
                // Return an error response indicating that the status cannot be updated
                return response()->json(["message" => "only pending job can be deleted"], 422);
            }

            $pre_booking->delete();

            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {
            return $this->sendError($e,500,$request);
        }
    }
}
