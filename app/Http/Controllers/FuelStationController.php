<?php

namespace App\Http\Controllers;

use App\Http\Requests\FuelStationCreateRequest;
use App\Http\Requests\FuelStationUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\FuelStation;
use App\Models\FuelStationOption;
use App\Models\FuelStationTime;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\MultipleImageUploadRequest;


class FuelStationController extends Controller
{
    use ErrorUtil, UserActivityUtil;

    public function createFuelStationImageMultiple(MultipleImageUploadRequest $request)
    {
        try{
            $this->storeActivity($request,"");

            $insertableData = $request->validated();

            $location =  config("setup-config.fuel_station_gallery_location");

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

    public function getFuelStationSearchQuery(Request $request)
    {
        $fuelStationQuery = FuelStation::with(
            [
                "options.option",
                "fuel_station_times" => function ($query) {
                    // Get today's date
                    $today = Carbon::today();

                    // Get the day number (0-Sunday, 1-Monday, ..., 6-Saturday)
                    $dayNumber = $today->dayOfWeek;
                    $query
                        ->where([
                            "fuel_station_times.day" => $dayNumber
                        ])
                        ->select("fuel_station_times.id", "fuel_station_times.opening_time", "fuel_station_times.closing_time", "fuel_station_times.fuel_station_id");
                }

            ]
        )

            ->where('fuel_stations.is_active', true);


        if (!empty($request->search_key)) {
            $fuelStationQuery = $fuelStationQuery->where(function ($query) use ($request) {
                $term = $request->search_key;
                $query->where("fuel_stations.name", "like", "%" . $term . "%");
            });
        }

        if (!empty($request->start_date)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.created_at', ">=", $request->start_date);
        }
        if (!empty($request->end_date)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.created_at', "<=", $request->end_date);
        }

        $start_lat = $request->start_lat;
        $end_lat = $request->end_lat;
        $start_long = $request->start_long;
        $end_long = $request->end_long;

        if ($start_lat < 0 && $end_lat < 0) {
            // Handle case where both start and end latitude are negative
            $start_lat_temp = $start_lat;
            $start_lat = $end_lat;
            $end_lat = $start_lat_temp;
        }

        if ($start_long < 0 && $end_long < 0) {
            // Handle case where both start and end longitude are negative
            $start_long_temp = $start_long;
            $start_long = $end_long;
            $end_long = $start_long_temp;
        }




        if (!empty($start_lat)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.lat', ">=", $start_lat);
        }
        if (!empty($end_lat)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.lat', "<=", $end_lat);
        }
        if (!empty($start_long)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.long', ">=", $start_long);
        }
        if (!empty($end_long)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.long', "<=", $end_long);
        }




        if (!empty($request->time)) {
            $fuelStationQuery = $fuelStationQuery->where(function ($query) use ($request) {
                $term = $request->time;
                $query->whereTime("fuel_stations.opening_time", "<=", $term);
                $query->whereTime("fuel_stations.closing_time", ">", $term);
            });
        }


        if (!empty($request->country)) {
            $fuelStationQuery =   $fuelStationQuery->where("country", "like", "%" . $request->country . "%");
        }
        if (!empty($request->city)) {
            $fuelStationQuery =   $fuelStationQuery->where("city", "like", "%" . $request->city . "%");
        }
        if (!empty($request->address_line_1)) {
            $fuelStationQuery =   $fuelStationQuery->where("address_line_1", "like", "%" . $request->address_line_1 . "%");
        }

        if (!empty($request->active_option_ids)) {

            $null_filter = collect(array_filter($request->active_option_ids))->values();
            $active_option_ids =  $null_filter->all();


            if (count($active_option_ids)) {
                // Find the number of active option IDs
                $count = count($active_option_ids);

                $fuelStationQuery = $fuelStationQuery->whereHas('options', function ($query) use ($active_option_ids, $count) {
                    $query
                        ->where("is_active", 1)
                        ->selectRaw('fuel_station_id')
                        ->whereIn('option_id', $active_option_ids)
                        ->groupBy('fuel_station_id')
                        ->havingRaw('COUNT(DISTINCT option_id) = ?', [$count]);
                });
            }
        }
        return $fuelStationQuery;
    }

    public function getFuelStationSearchQuery2(Request $request)
    {
        $fuelStationQuery = FuelStation::where('fuel_stations.is_active', true);


        if (!empty($request->search_key)) {
            $fuelStationQuery = $fuelStationQuery->where(function ($query) use ($request) {
                $term = $request->search_key;
                $query->where("fuel_stations.name", "like", "%" . $term . "%");
            });
        }

        if (!empty($request->start_date)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.created_at', ">=", $request->start_date);
        }
        if (!empty($request->end_date)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.created_at', "<=", $request->end_date);
        }


        $start_lat = $request->start_lat;
        $end_lat = $request->end_lat;
        $start_long = $request->start_long;
        $end_long = $request->end_long;

        if ($start_lat < 0 && $end_lat < 0) {
            // Handle case where both start and end latitude are negative
            $start_lat_temp = $start_lat;
            $start_lat = $end_lat;
            $end_lat = $start_lat_temp;
        }

        if ($start_long < 0 && $end_long < 0) {
            // Handle case where both start and end longitude are negative
            $start_long_temp = $start_long;
            $start_long = $end_long;
            $end_long = $start_long_temp;
        }




        if (!empty($start_lat)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.lat', ">=", $start_lat);
        }
        if (!empty($end_lat)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.lat', "<=", $end_lat);
        }
        if (!empty($start_long)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.long', ">=", $start_long);
        }
        if (!empty($end_long)) {
            $fuelStationQuery = $fuelStationQuery->where('fuel_stations.long', "<=", $end_long);
        }

        if (!empty($request->time)) {
            $fuelStationQuery = $fuelStationQuery->where(function ($query) use ($request) {
                $term = $request->time;
                $query->whereTime("fuel_stations.opening_time", "<=", $term);
                $query->whereTime("fuel_stations.closing_time", ">", $term);
            });
        }


        if (!empty($request->country)) {
            $fuelStationQuery =   $fuelStationQuery->where("country", "like", "%" . $request->country . "%");
        }
        if (!empty($request->city)) {
            $fuelStationQuery =   $fuelStationQuery->where("city", "like", "%" . $request->city . "%");
        }
        if (!empty($request->address_line_1)) {
            $fuelStationQuery =   $fuelStationQuery->where("address_line_1", "like", "%" . $request->address_line_1 . "%");
        }

        if (!empty($request->active_option_ids)) {

            $null_filter = collect(array_filter($request->active_option_ids))->values();
            $active_option_ids =  $null_filter->all();


            if (count($active_option_ids)) {
                // Find the number of active option IDs
                $count = count($active_option_ids);

                $fuelStationQuery = $fuelStationQuery->whereHas('options', function ($query) use ($active_option_ids, $count) {
                    $query
                        ->where("is_active", 1)
                        ->selectRaw('fuel_station_id')
                        ->whereIn('option_id', $active_option_ids)
                        ->groupBy('fuel_station_id')
                        ->havingRaw('COUNT(DISTINCT option_id) = ?', [$count]);
                });
            }
        }
        return $fuelStationQuery;
    }
    /**
     *
     * @OA\Post(
     *      path="/v1.0/fuel-station",
     *      operationId="createFuelStation",
     *      tags={"fuel_station_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store fuel station",
     *      description="This method is to store fuel station",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","address","opening_time","closing_time","description"},
     *    @OA\Property(property="name", type="string", format="string",example="car"),
     *    @OA\Property(property="address", type="string", format="string",example="car"),
     *    @OA\Property(property="opening_time", type="string", format="string",example="10:10"),
     * *    @OA\Property(property="closing_time", type="string", format="string",example="10:10"),
     * *    @OA\Property(property="description", type="string", format="number",example="description"),
     *    *  * *    @OA\Property(property="lat", type="string", format="string",example="23.704263332849386"),
     *  * *    @OA\Property(property="long", type="string", format="string",example="90.44707059805279"),
     *
     *      *  * *  @OA\Property(property="country", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="city", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="postcode", type="string", format="string",example="1"),
     *     *  * *  @OA\Property(property="address_line_1", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="address_line_2", type="string", format="string",example="1"),
     *   *  * *  @OA\Property(property="additional_information", type="string", format="string",example="1"),
     *
     * *  * *    @OA\Property(property="options", type="string", format="array",example={
     * {"option_id":1,"is_active":true},
     *  * {"option_id":2,"is_active":true},
     * }),
     *
     *      *      *    @OA\Property(property="times", type="string", format="array",example={
     *
     *{"day":0,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":1,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":2,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":3,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":4,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":5,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":6,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true}
     *
     * }),
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

    public function createFuelStation(FuelStationCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('fuel_station_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $insertableData = $request->validated();

                $insertableData["created_by"] = $request->user()->id;

                $fuel_station =  FuelStation::create($insertableData);

                if (empty($insertableData["options"])) {
                    $insertableData["options"]  = [];
                }

                foreach ($insertableData["options"] as $option) {

                    FuelStationOption::create([
                        "fuel_station_id" => $fuel_station->id,
                        "option_id" => $option["option_id"],
                        "is_active" => $option["is_active"],
                    ]);
                }

                FuelStationTime::where([
                    "fuel_station_id" => $fuel_station->id
                ])
                    ->delete();
                $timesArray = collect($insertableData["times"])->unique("day");
                foreach ($timesArray as $garage_time) {
                    FuelStationTime::create([
                        "fuel_station_id" => $fuel_station->id,
                        "day" => $garage_time["day"],
                        "opening_time" => $garage_time["opening_time"],
                        "closing_time" => $garage_time["closing_time"],
                        "is_closed" => $garage_time["is_closed"],
                    ]);
                }

                $fuel_station = $fuel_station->load(["fuel_station_times"]);
                return response($fuel_station, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/fuel-station",
     *      operationId="updateFuelStation",
     *      tags={"fuel_station_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update fuel station",
     *      description="This method is to update fuel station",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","name","address","opening_time","closing_time","description"},
     *    @OA\Property(property="id", type="number", format="number", example="1"),
     *    @OA\Property(property="name", type="string", format="string",example="car"),
     *    @OA\Property(property="address", type="string", format="string",example="car"),
     *    @OA\Property(property="opening_time", type="string", format="string",example="10:10"),
     * *    @OA\Property(property="closing_time", type="string", format="string",example="10:10"),
     * *    @OA\Property(property="description", type="string", format="number",example="description"),
     *
     *  *    *  * *    @OA\Property(property="lat", type="string", format="string",example="23.704263332849386"),
     *  * *    @OA\Property(property="long", type="string", format="string",example="90.44707059805279"),
     *  *      *  * *  @OA\Property(property="country", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="city", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="postcode", type="string", format="string",example="1"),
     *     *  * *  @OA\Property(property="address_line_1", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="address_line_2", type="string", format="string",example="1"),
     *   *  * *  @OA\Property(property="additional_information", type="string", format="string",example="1"),
     *
     *   * *  * *    @OA\Property(property="options", type="string", format="array",example={
     * {"option_id":1,"is_active":true},
     *  * {"option_id":2,"is_active":true},
     * }),
     *
     *  @OA\Property(property="times", type="string", format="array",example={
     *
     *{"day":0,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":1,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":2,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":3,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":4,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":5,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":6,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true}
     *
     * }),
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

    public function updateFuelStation(FuelStationUpdateRequest $request)
    {
        try {
            $this->storeActivity($request, "");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('fuel_station_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();

                $fuelStationPrev = FuelStation::where([
                    "id" => $request_data["id"]
                ]);

                if (!$request->user()->hasRole('superadmin')) {
                    $fuelStationPrev =    $fuelStationPrev->where([
                        "created_by" => $request->user()->id
                    ]);
                }
                $fuelStationPrev = $fuelStationPrev->first();
                if (!$fuelStationPrev) {
                    return response()->json([
                        "message" => "you did not create this fuel station."
                    ], 404);
                }

                $fuel_station  =  tap(FuelStation::where(["id" => $request_data["id"]]))->update(
                    collect($request_data)->only([
                        "name",
                        "address",
                        "opening_time",
                        "closing_time",
                        "description",
                        "lat",
                        "long",
                        "country",
                        "city",
                        "postcode",
                        "additional_information",
                        "address_line_1",
                        "address_line_2",
                    ])->toArray()
                )
                    ->with("options", "fuel_station_times")

                    ->first();

                if (!$fuel_station) {
                    return response()->json(
                        [
                            "message" => "no fuel station found"
                        ],
                        404
                    );
                }


                FuelStationOption::where([
                    "fuel_station_id" => $fuel_station->id,
                ])
                    ->delete();


                if (empty($request_data["options"])) {
                    $request_data["options"]  = [];
                }

                foreach ($request_data["options"] as $option) {

                    FuelStationOption::create([
                        "fuel_station_id" => $fuel_station->id,
                        "option_id" => $option["option_id"],
                        "is_active" => $option["is_active"],
                    ]);
                }


                FuelStationTime::where([
                    "fuel_station_id" => $fuel_station->id
                ])
                    ->delete();
                $timesArray = collect($request_data["times"])->unique("day");
                foreach ($timesArray as $garage_time) {
                    FuelStationTime::create([
                        "fuel_station_id" => $fuel_station->id,
                        "day" => $garage_time["day"],
                        "opening_time" => $garage_time["opening_time"],
                        "closing_time" => $garage_time["closing_time"],
                        "is_closed" => $garage_time["is_closed"],
                    ]);
                }



                return response($fuel_station, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/fuel-station/toggle-active",
     *      operationId="toggleActiveFuelStation",
     *      tags={"fuel_station_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle fuel station",
     *      description="This method is to toggle fuel station",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
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

    public function toggleActiveFuelStation(GetIdRequest $request)
    {

        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('fuel_station_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $fuelStationQuery  = FuelStation::where(["id" => $request_data["id"]]);
            if (!auth()->user()->hasRole('superadmin')) {
                $fuelStationQuery = $fuelStationQuery->where(function ($query) {
                    $query->where('created_by', auth()->user()->id);
                });
            }

            $fuelStation =  $fuelStationQuery->first();


            if (!$fuelStation) {
                return response()->json([
                    "message" => "no fuel station found"
                ], 404);
            }


            $fuelStation->update([
                'is_active' => !$fuelStation->is_active
            ]);

            return response()->json(['message' => 'fuel station status updated successfully'], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/fuel-station/{perPage}",
     *      operationId="getFuelStations",
     *      tags={"fuel_station_management"},
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
     *      *              @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *
     *      *
     *   *              @OA\Parameter(
     *         name="time",
     *         in="query",
     *         description="current time",
     *         required=true,
     *  example="10:10"
     *      ),
     *
     *
     *     * *  @OA\Parameter(
     * name="country",
     * in="query",
     * description="country",
     * required=true,
     * example="country"
     * ),
     * *  @OA\Parameter(
     * name="city",
     * in="query",
     * description="city",
     * required=true,
     * example="city"
     * ),
     *
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
     * *  @OA\Parameter(
     * name="start_lat",
     * in="query",
     * description="start_lat",
     * required=true,
     * example="3"
     * ),
     * *  @OA\Parameter(
     * name="end_lat",
     * in="query",
     * description="end_lat",
     * required=true,
     * example="2"
     * ),
     * *  @OA\Parameter(
     * name="start_long",
     * in="query",
     * description="start_long",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="end_long",
     * in="query",
     * description="end_long",
     * required=true,
     * example="4"
     * ),
     *  @OA\Parameter(
     *      name="active_option_ids[]",
     *      in="query",
     *      description="active_option_ids",
     *      required=true,
     *      example="1,2"
     * ),

     *      *  * *  @OA\Property(property="country", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="city", type="string", format="string",example="1"),
     *
     *
     *      summary="This method is to get fuel stations ",
     *      description="This method is to get fuel stations",
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

    public function getFuelStations($perPage, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('fuel_station_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // $automobilesQuery = AutomobileMake::with("makes");

            $fuelStationQuery = FuelStation::with("options.option","fuel_station_times");

            if (!$request->user()->hasRole('superadmin')) {
                $fuelStationQuery =    $fuelStationQuery->where([
                    "created_by" => $request->user()->id
                ]);
            }


            if (!empty($request->search_key)) {
                $fuelStationQuery = $fuelStationQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("fuel_stations.name", "like", "%" . $term . "%");
                    $query->orWhere("fuel_stations.country", "like", "%" . $term . "%");
                    $query->orWhere("fuel_stations.city", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.created_at', "<=", $request->end_date);
            }


            $start_lat = $request->start_lat;
            $end_lat = $request->end_lat;
            $start_long = $request->start_long;
            $end_long = $request->end_long;

            if ($start_lat < 0 && $end_lat < 0) {
                // Handle case where both start and end latitude are negative
                $start_lat_temp = $start_lat;
                $start_lat = $end_lat;
                $end_lat = $start_lat_temp;
            }

            if ($start_long < 0 && $end_long < 0) {
                // Handle case where both start and end longitude are negative
                $start_long_temp = $start_long;
                $start_long = $end_long;
                $end_long = $start_long_temp;
            }




            if (!empty($start_lat)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.lat', ">=", $start_lat);
            }
            if (!empty($end_lat)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.lat', "<=", $end_lat);
            }
            if (!empty($start_long)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.long', ">=", $start_long);
            }
            if (!empty($end_long)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.long', "<=", $end_long);
            }

            if (!empty($request->time)) {
                $fuelStationQuery = $fuelStationQuery->where(function ($query) use ($request) {
                    $term = $request->time;
                    $query->whereTime("fuel_stations.opening_time", "<=", $term);
                    $query->whereTime("fuel_stations.closing_time", ">", $term);
                });
            }


            if (!empty($request->country)) {
                $fuelStationQuery =   $fuelStationQuery->where("country", "like", "%" . $request->country . "%");
            }
            if (!empty($request->city)) {
                $fuelStationQuery =   $fuelStationQuery->where("city", "like", "%" . $request->city . "%");
            }


            if (!empty($request->active_option_ids)) {

                $null_filter = collect(array_filter($request->active_option_ids))->values();
                $active_option_ids =  $null_filter->all();


                if (count($active_option_ids)) {
                    // Find the number of active option IDs
                    $count = count($active_option_ids);

                    $fuelStationQuery = $fuelStationQuery->whereHas('options', function ($query) use ($active_option_ids, $count) {
                        $query
                            ->where("is_active", 1)
                            ->selectRaw('fuel_station_id')
                            ->whereIn('option_id', $active_option_ids)
                            ->groupBy('fuel_station_id')
                            ->havingRaw('COUNT(DISTINCT option_id) = ?', [$count]);
                    });
                }
            }


            $fuelStations = $fuelStationQuery
                ->distinct("fuel_stations.id")
                ->select("fuel_stations.*")
                ->orderByDesc("fuel_stations.id")
                ->when(request()->filled("id"), function ($query) {
                    return  $query->where("id", request()->input("id"))
                      ->first();
                   },
                   function ($query) use($perPage) {
                     return $query->paginate($perPage);
                   },

               );
            return response()->json($fuelStations, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v2.0/fuel-station/single/{id}",
     *      operationId="getFuelStationById",
     *      tags={"fuel_station_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *    *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to get fuel station by id  ",
     *      description="This method is to get fuel station by id ",
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
    public function getFuelStationByIdV2($id,Request $request) {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('fuel_station_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // $automobilesQuery = AutomobileMake::with("makes");

            $fuelStationQuery = FuelStation::with("options.option","fuel_station_times","fuelStationGalleries");

            if (!$request->user()->hasRole('superadmin')) {
                $fuelStationQuery =    $fuelStationQuery->where([
                    "created_by" => $request->user()->id
                ]);
            }


            if (!empty($request->search_key)) {
                $fuelStationQuery = $fuelStationQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("fuel_stations.name", "like", "%" . $term . "%");
                    $query->orWhere("fuel_stations.country", "like", "%" . $term . "%");
                    $query->orWhere("fuel_stations.city", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.created_at', "<=", $request->end_date);
            }


            $start_lat = $request->start_lat;
            $end_lat = $request->end_lat;
            $start_long = $request->start_long;
            $end_long = $request->end_long;

            if ($start_lat < 0 && $end_lat < 0) {
                // Handle case where both start and end latitude are negative
                $start_lat_temp = $start_lat;
                $start_lat = $end_lat;
                $end_lat = $start_lat_temp;
            }

            if ($start_long < 0 && $end_long < 0) {
                // Handle case where both start and end longitude are negative
                $start_long_temp = $start_long;
                $start_long = $end_long;
                $end_long = $start_long_temp;
            }




            if (!empty($start_lat)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.lat', ">=", $start_lat);
            }
            if (!empty($end_lat)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.lat', "<=", $end_lat);
            }
            if (!empty($start_long)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.long', ">=", $start_long);
            }
            if (!empty($end_long)) {
                $fuelStationQuery = $fuelStationQuery->where('fuel_stations.long', "<=", $end_long);
            }

            if (!empty($request->time)) {
                $fuelStationQuery = $fuelStationQuery->where(function ($query) use ($request) {
                    $term = $request->time;
                    $query->whereTime("fuel_stations.opening_time", "<=", $term);
                    $query->whereTime("fuel_stations.closing_time", ">", $term);
                });
            }


            if (!empty($request->country)) {
                $fuelStationQuery =   $fuelStationQuery->where("country", "like", "%" . $request->country . "%");
            }
            if (!empty($request->city)) {
                $fuelStationQuery =   $fuelStationQuery->where("city", "like", "%" . $request->city . "%");
            }


            if (!empty($request->active_option_ids)) {

                $null_filter = collect(array_filter($request->active_option_ids))->values();
                $active_option_ids =  $null_filter->all();


                if (count($active_option_ids)) {
                    // Find the number of active option IDs
                    $count = count($active_option_ids);

                    $fuelStationQuery = $fuelStationQuery->whereHas('options', function ($query) use ($active_option_ids, $count) {
                        $query
                            ->where("is_active", 1)
                            ->selectRaw('fuel_station_id')
                            ->whereIn('option_id', $active_option_ids)
                            ->groupBy('fuel_station_id')
                            ->havingRaw('COUNT(DISTINCT option_id) = ?', [$count]);
                    });
                }
            }


            $fuelStations = $fuelStationQuery
                ->distinct("fuel_stations.id")
                ->select("fuel_stations.*")
                ->orderByDesc("fuel_stations.id")
                ->when(request()->filled("id"), function ($query) {
                    return  $query->where("id", request()->input("id"))
                      ->first();
                   },
                   function ($query) use($perPage) {
                     return $query->paginate($perPage);
                   },

               );
            return response()->json($fuelStations, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }



    }

    /**
     *
     * @OA\Get(
     *      path="/v3.0/client/fuel-station/{perPage}",
     *      operationId="getFuelStationsClientV3",
     *      tags={"client.fuel_station_management"},
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
     *
     *   *              @OA\Parameter(
     *         name="time",
     *         in="query",
     *         description="current time",
     *         required=true,
     *  example="10:10"
     *      ),
     *  *   *              @OA\Parameter(
     *         name="location",
     *         in="query",
     *         description="location",
     *         required=true,
     *  example="location"
     *      ),
     *
     *
     *     * *  @OA\Parameter(
     * name="country",
     * in="query",
     * description="country",
     * required=true,
     * example="country"
     * ),
     * *  @OA\Parameter(
     * name="city",
     * in="query",
     * description="city",
     * required=true,
     * example="city"
     * ),
     *
     *

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
     * *  @OA\Parameter(
     * name="start_lat",
     * in="query",
     * description="start_lat",
     * required=true,
     * example="3"
     * ),
     * *  @OA\Parameter(
     * name="end_lat",
     * in="query",
     * description="end_lat",
     * required=true,
     * example="2"
     * ),
     * *  @OA\Parameter(
     * name="start_long",
     * in="query",
     * description="start_long",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="end_long",
     * in="query",
     * description="end_long",
     * required=true,
     * example="4"
     * ),
     *  @OA\Parameter(
     *      name="active_option_ids[]",
     *      in="query",
     *      description="active_option_ids",
     *      required=true,
     *      example="1,2"
     * ),
     *      summary="This method is to get fuel stations client ",
     *      description="This method is to get fuel stations client",
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


    public function getFuelStationsClientV3($perPage, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            $info = [];

            $ip = $request->ip(); // Get the user's IP address
            $location = Http::get("https://ipinfo.io/{$ip}/json");

            if ($location->successful()) {
                $data = $location->json();

                // Extract country and city
                $country = $data['country'] ?? '';
                $city = $data['city'] ?? '';
            }


            if (!empty($country) && !empty($city) && empty(request()->start_lat) && empty(request()->end_lat)  && empty(request()->start_long) && empty(request()->end_long)) {
                $fuel_stations = $this->getFuelStationSearchQuery($request)
                    ->where("fuel_stations.city", $city)
                    ->groupBy("fuel_stations.id")

                    ->orderByDesc("fuel_stations.id")
                    ->select("fuel_stations.*")
                    ->paginate($perPage);

                $info["country"] = $country;
                $info["city"] = $city;

                $info["is_result_by_city"] = true;
                $info["is_result_by_country"] = false;

                if (count($fuel_stations->items()) == 0) {
                    $info["is_result_by_city"] = false;
                    $info["is_result_by_country"] = true;

                    $fuel_stations = $this->getFuelStationSearchQuery($request)
                        ->where("fuel_stations.country", $country)
                        ->groupBy("fuel_stations.id")

                        ->orderByDesc("fuel_stations.id")
                        ->select("fuel_stations.*")
                        ->paginate($perPage);
                }
                if (count($fuel_stations->items()) == 0) {
                    $info["is_result_by_city"] = false;
                    $info["is_result_by_country"] = false;
                }
            } else {

                array_splice($info, 0);
                $fuel_stations = $this->getFuelStationSearchQuery($request)
                    ->groupBy("fuel_stations.id")
                    ->orderByDesc("fuel_stations.id")
                    ->select("fuel_stations.*")
                    ->paginate($perPage);
            }






            return response()->json([
                "info" => $info,

                "data" => $fuel_stations
            ], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/fuel-station/{perPage}",
     *      operationId="getFuelStationsClient",
     *      tags={"client.fuel_station_management"},
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
     *
     *   *              @OA\Parameter(
     *         name="time",
     *         in="query",
     *         description="current time",
     *         required=true,
     *  example="10:10"
     *      ),
     *  *   *              @OA\Parameter(
     *         name="location",
     *         in="query",
     *         description="location",
     *         required=true,
     *  example="location"
     *      ),
     *
     *
     *     * *  @OA\Parameter(
     * name="country",
     * in="query",
     * description="country",
     * required=true,
     * example="country"
     * ),
     * *  @OA\Parameter(
     * name="city",
     * in="query",
     * description="city",
     * required=true,
     * example="city"
     * ),
     *
     *

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
     * *  @OA\Parameter(
     * name="start_lat",
     * in="query",
     * description="start_lat",
     * required=true,
     * example="3"
     * ),
     * *  @OA\Parameter(
     * name="end_lat",
     * in="query",
     * description="end_lat",
     * required=true,
     * example="2"
     * ),
     * *  @OA\Parameter(
     * name="start_long",
     * in="query",
     * description="start_long",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="end_long",
     * in="query",
     * description="end_long",
     * required=true,
     * example="4"
     * ),
     *  @OA\Parameter(
     *      name="active_option_ids[]",
     *      in="query",
     *      description="active_option_ids",
     *      required=true,
     *      example="1,2"
     * ),
     *      summary="This method is to get fuel stations client ",
     *      description="This method is to get fuel stations client",
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


    public function getFuelStationsClient($perPage, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            $info = [];


            if (!empty($request->location)) {
                $fuel_stations = $this->getFuelStationSearchQuery($request)
                    ->where("fuel_stations.city", $request->location)
                    ->groupBy("fuel_stations.id")

                    ->orderByDesc("fuel_stations.id")
                    ->select("fuel_stations.*")
                    ->paginate($perPage);

                $info["is_result_by_city"] = true;
                $info["is_result_by_country"] = false;

                if (count($fuel_stations->items()) == 0) {
                    $info["is_result_by_city"] = false;
                    $info["is_result_by_country"] = true;

                    $fuel_stations = $this->getFuelStationSearchQuery($request)
                        ->where("fuel_stations.country", $request->location)
                        ->groupBy("fuel_stations.id")

                        ->orderByDesc("fuel_stations.id")
                        ->select("fuel_stations.*")
                        ->paginate($perPage);
                }
                if (count($fuel_stations->items()) == 0) {
                    $info["is_result_by_city"] = false;
                    $info["is_result_by_country"] = false;
                }
            } else {

                array_splice($info, 0);
                $fuel_stations = $this->getFuelStationSearchQuery($request)
                    ->groupBy("fuel_stations.id")
                    ->orderByDesc("fuel_stations.id")
                    ->select("fuel_stations.*")
                    ->paginate($perPage);
            }






            return response()->json([
                "info" => $info,

                "data" => $fuel_stations
            ], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v2.0/client/fuel-station",
     *      operationId="getFuelStationsClient2",
     *      tags={"client.fuel_station_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *
     *   *              @OA\Parameter(
     *         name="time",
     *         in="query",
     *         description="current time",
     *         required=true,
     *  example="10:10"
     *      ),
     *  *   *              @OA\Parameter(
     *         name="location",
     *         in="query",
     *         description="location",
     *         required=true,
     *  example="location"
     *      ),
     *
     *
     *     * *  @OA\Parameter(
     * name="country",
     * in="query",
     * description="country",
     * required=true,
     * example="country"
     * ),
     * *  @OA\Parameter(
     * name="city",
     * in="query",
     * description="city",
     * required=true,
     * example="city"
     * ),
     *
     *

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
     * *  @OA\Parameter(
     * name="start_lat",
     * in="query",
     * description="start_lat",
     * required=true,
     * example="3"
     * ),
     * *  @OA\Parameter(
     * name="end_lat",
     * in="query",
     * description="end_lat",
     * required=true,
     * example="2"
     * ),
     * *  @OA\Parameter(
     * name="start_long",
     * in="query",
     * description="start_long",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="end_long",
     * in="query",
     * description="end_long",
     * required=true,
     * example="4"
     * ),
     *  @OA\Parameter(
     *      name="active_option_ids[]",
     *      in="query",
     *      description="active_option_ids",
     *      required=true,
     *      example="1,2"
     * ),
     *      summary="This method is to get fuel stations client ",
     *      description="This method is to get fuel stations client",
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


    public function getFuelStationsClient2(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            $info = [];

            $ip = $request->ip(); // Get the user's IP address
            // Make an HTTP request to the ipinfo.io API
            $location = Http::get("https://ipinfo.io/{$ip}/json");

            if ($location->successful()) {
                $data = $location->json();
                // Extract country and city
                $country = $data['country'] ?? '';
                $city = $data['city'] ?? '';
            }

            $info["city"] = $city;
            $info["country"] = $country;


            if (!empty($country) && !empty($city) && empty(request()->start_lat) && empty(request()->end_lat)  && empty(request()->start_long) && empty(request()->end_long)) {
                $fuel_stations = $this->getFuelStationSearchQuery2($request)
                    ->where("fuel_stations.city", $city)
                    ->groupBy("fuel_stations.id")

                    ->orderByDesc("fuel_stations.id")
                    ->select(
                        "fuel_stations.id",
                        "fuel_stations.name",
                        "fuel_stations.lat",
                        "fuel_stations.long",
                    )
                    ->get();


                $info["is_result_by_city"] = true;
                $info["is_result_by_country"] = false;

                $info["city"] = $city;
                $info["country"] = $country;

                if ($fuel_stations->count() == 0) {
                    $info["is_result_by_city"] = false;
                    $info["is_result_by_country"] = true;

                    $fuel_stations = $this->getFuelStationSearchQuery2($request)
                        ->where("fuel_stations.country", $country)
                        ->groupBy("fuel_stations.id")

                        ->orderByDesc("fuel_stations.id")
                        ->select(
                            "fuel_stations.id",
                            "fuel_stations.name",
                            "fuel_stations.lat",
                            "fuel_stations.long",

                        )
                        ->get();
                }
                if ($fuel_stations->count() == 0) {
                    $info["is_result_by_city"] = false;
                    $info["is_result_by_country"] = false;
                }
            } else {

                //  array_splice($info, 0);
                $fuel_stations = $this->getFuelStationSearchQuery2($request)
                    ->groupBy("fuel_stations.id")
                    ->orderByDesc("fuel_stations.id")
                    ->select(

                        "fuel_stations.id",
                        "fuel_stations.name",
                        "fuel_stations.lat",
                        "fuel_stations.long",
                        "fuel_stations.country",
                        "fuel_stations.city",
                    )
                    ->get();
            }



            return response()->json([
                "info" => $info,

                "data" => $fuel_stations
            ], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/fuel-station/get/single/{id}",
     *      operationId="getFuelStationByIdClient",
     *      tags={"client.fuel_station_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *    *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to get fuel station by id client ",
     *      description="This method is to get fuel station by id client",
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

    public function getFuelStationByIdClient($id, Request $request)
    {
        try {
            $this->storeActivity($request, "");

            $fuelStation = FuelStation::with("options.option", "fuel_station_times")
                ->where([
                    "id" => $id
                ])
                ->where('fuel_stations.is_active', true)
                ->first();

            if (!$fuelStation) {
                return response()->json(
                    [
                        "message" => "no fuel station found"
                    ],
                    404
                );
            }

            return response()->json($fuelStation, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/fuel-station/{id}",
     *      operationId="deleteFuelStationById",
     *      tags={"fuel_station_management"},
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
     *      summary="This method is to delete fuel station by id",
     *      description="This method is to delete fuel station by id",
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

    public function deleteFuelStationById($id, Request $request)
    {

        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('fuel_station_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $fuelStationQuery =   FuelStation::where([
                "id" => $id
            ]);
            if (!$request->user()->hasRole('superadmin')) {
                $fuelStationQuery =    $fuelStationQuery->where([
                    "created_by" => $request->user()->id
                ]);
            }

            $fuelStation = $fuelStationQuery->first();

            $fuelStation->delete();


            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
