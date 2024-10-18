<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\AutomobileModel;
use App\Models\Garage;
use App\Models\GarageAffiliation;
use App\Models\GarageAutomobileMake;
use App\Models\GarageAutomobileModel;
use App\Models\GarageService;
use App\Models\GarageSubService;
use App\Models\ReviewValueNew;
use App\Models\Star;
use App\Models\SubService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ClientBasicController extends Controller
{
    use ErrorUtil, UserActivityUtil, BasicUtil;


    public function getGarageSearchQuery(Request $request)
    {

        $garagesQuery = Garage::with(
            [
                "owner" => function ($query) {

                    $query->select("users.*");
                },
                "garage_times" => function ($query) {
                    // Get today's date
                    $today = Carbon::today();

                    // Get the day number (0-Sunday, 1-Monday, ..., 6-Saturday)
                    $dayNumber = $today->dayOfWeek;
                    $query
                        ->where([
                            "garage_times.day" => $dayNumber
                        ])
                        ->select("garage_times.id", "garage_times.opening_time", "garage_times.closing_time", "garage_times.garage_id");
                }

            ]

            // "garageAutomobileMakes.automobileMake",
            // "garageAutomobileMakes.garageAutomobileModels.automobileModel",
            // "garageServices.service",
            // "garageServices.garageSubServices.garage_sub_service_prices",
            // "garageServices.garageSubServices.subService",
            // "garage_times",
            // "garageGalleries",
            // "garage_packages",

        )
            ->leftJoin('garage_automobile_makes', 'garage_automobile_makes.garage_id', '=', 'garages.id')
            ->leftJoin('garage_automobile_models', 'garage_automobile_models.garage_automobile_make_id', '=', 'garage_automobile_makes.id')



            ->leftJoin('garage_times', 'garage_times.garage_id', '=', 'garages.id')
            ->where('garages.is_active', true);

        if (!empty($request->search_key)) {
            $garagesQuery = $garagesQuery->where(function ($query) use ($request) {
                $term = $request->search_key;
                $query->where("garages.name", "like", "%" . $term . "%");
                $query->orWhere("garages.phone", "like", "%" . $term . "%");
                $query->orWhere("garages.email", "like", "%" . $term . "%");
                $query->orWhere("garages.city", "like", "%" . $term . "%");
                $query->orWhere("garages.postcode", "like", "%" . $term . "%");
            });
        }







        if (!empty($request->country)) {
            $garagesQuery =   $garagesQuery->where("country", "like", "%" . $request->country . "%");
        }
        if (!empty($request->city)) {
            $garagesQuery =   $garagesQuery->where("city", "like", "%" . $request->city . "%");
        }
        if (!empty($request->address_line_1)) {
            $garagesQuery =   $garagesQuery->where("address_line_1", "like", "%" . $request->address_line_1 . "%");
        }


        if (!empty($request->is_mobile_garage)) {

            if ($request->is_mobile_garage == '1' || $request->is_mobile_garage == 'true') {
                $garagesQuery = $garagesQuery->where("garages.is_mobile_garage", true);
            }
        }
        if (!empty($request->wifi_available)) {

            if ($request->wifi_available == '1' || $request->wifi_available == 'true') {
                $garagesQuery = $garagesQuery->where("garages.wifi_available", true);
            }
        }



        if (!empty($request->automobile_make_ids)) {
            $null_filter = collect(array_filter($request->automobile_make_ids))->values();
            $automobile_make_ids =  $null_filter->all();
            if (count($automobile_make_ids)) {
                $garagesQuery =   $garagesQuery->whereIn("garage_automobile_makes.automobile_make_id", $automobile_make_ids);
            }
        }
        if (!empty($request->automobile_model_ids)) {

            $null_filter = collect(array_filter($request->automobile_model_ids))->values();
            $automobile_model_ids =  $null_filter->all();
            if (count($automobile_model_ids)) {
                $garagesQuery =   $garagesQuery->whereIn("garage_automobile_models.automobile_model_id", $automobile_model_ids);
            }
        }

        if (!empty($request->service_ids)) {

            $null_filter = collect(array_filter($request->service_ids))->values();
            $service_ids =  $null_filter->all();

            $count = count($service_ids); // Number of service IDs to match

            $garagesQuery = $garagesQuery->whereHas('garageServices', function ($query) use ($service_ids, $count) {
                $query
                    ->whereIn('service_id', $service_ids) // Filter to only the specified service IDs
                    ->selectRaw('garage_id')              // Select the garage_id (essential for grouping)
                    ->groupBy('garage_id')               // Group by garage_id
                    ->havingRaw('COUNT(DISTINCT service_id) = ?', [$count]); // Ensure the count of distinct services matches the expected count
            });
        }


        if (!empty($request->sub_service_ids)) {
            $null_filter = collect(array_filter($request->sub_service_ids))->values();
            $sub_service_ids =  $null_filter->all();
            if (count($sub_service_ids)) {

                $count = count($sub_service_ids);
                $garagesQuery = $garagesQuery->whereHas('garageServices.garageSubServices', function ($query) use ($sub_service_ids, $count) {
                    $query
                        ->whereIn('sub_service_id', $sub_service_ids) // Filter to only the specified sub_service_ids
                        ->selectRaw('garage_service_id')           // Select the garage_service_id (essential for grouping)
                        ->groupBy('garage_service_id')            // Group by garage_service_id
                        ->havingRaw('COUNT(DISTINCT sub_service_id) = ?', [$count]); // Ensure the count of distinct sub_service_ids matches the expected count
                });
            }
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
            $garagesQuery = $garagesQuery->where('lat', ">=", $start_lat);
        }
        if (!empty($end_lat)) {
            $garagesQuery = $garagesQuery->where('lat', "<=", $end_lat);
        }
        if (!empty($start_long)) {
            $garagesQuery = $garagesQuery->where('long', ">=", $start_long);
        }
        if (!empty($end_long)) {
            $garagesQuery = $garagesQuery->where('long', "<=", $end_long);
        }



        if (!empty($request->open_time)) {
            $date = Carbon::createFromFormat('Y-m-d H:i', $request->open_time);
            $dayOfWeek = $date->dayOfWeek; // 6 (0 for Sunday, 1 for Monday, 2 for Tuesday, etc.)
            $time = $date->format('H:i');
            $garagesQuery = $garagesQuery->where('garage_times.day', "=", $dayOfWeek)
                ->whereTime('garage_times.opening_time', "<=", $time)
                ->whereTime('garage_times.closing_time', ">", $time);
        }

        return $garagesQuery;
    }

    public function getGarageSearchQuery2(Request $request)
    {

        $garagesQuery = Garage::
            leftJoin('garage_automobile_makes', 'garage_automobile_makes.garage_id', '=', 'garages.id')
            ->leftJoin('garage_automobile_models', 'garage_automobile_models.garage_automobile_make_id', '=', 'garage_automobile_makes.id')



            ->leftJoin('garage_times', 'garage_times.garage_id', '=', 'garages.id')
            ->where('garages.is_active', true);

        if (!empty($request->search_key)) {
            $garagesQuery = $garagesQuery->where(function ($query) use ($request) {
                $term = $request->search_key;
                $query->where("garages.name", "like", "%" . $term . "%");
                $query->orWhere("garages.phone", "like", "%" . $term . "%");
                $query->orWhere("garages.email", "like", "%" . $term . "%");
                $query->orWhere("garages.city", "like", "%" . $term . "%");
                $query->orWhere("garages.postcode", "like", "%" . $term . "%");
            });
        }

        if (!empty($request->country)) {
            $garagesQuery =   $garagesQuery->where("country", "like", "%" . $request->country . "%");
        }
        if (!empty($request->city)) {
            $garagesQuery =   $garagesQuery->where("city", "like", "%" . $request->city . "%");
        }
        if (!empty($request->address_line_1)) {
            $garagesQuery =   $garagesQuery->where("address_line_1", "like", "%" . $request->address_line_1 . "%");
        }


        if (!empty($request->is_mobile_garage)) {

            if ($request->is_mobile_garage == '1' || $request->is_mobile_garage == 'true') {
                $garagesQuery = $garagesQuery->where("garages.is_mobile_garage", true);
            }
        }
        if (!empty($request->wifi_available)) {

            if ($request->wifi_available == '1' || $request->wifi_available == 'true') {
                $garagesQuery = $garagesQuery->where("garages.wifi_available", true);
            }
        }



        if (!empty($request->automobile_make_ids)) {
            $null_filter = collect(array_filter($request->automobile_make_ids))->values();
            $automobile_make_ids =  $null_filter->all();
            if (count($automobile_make_ids)) {
                $garagesQuery =   $garagesQuery->whereIn("garage_automobile_makes.automobile_make_id", $automobile_make_ids);
            }
        }
        if (!empty($request->automobile_model_ids)) {

            $null_filter = collect(array_filter($request->automobile_model_ids))->values();
            $automobile_model_ids =  $null_filter->all();
            if (count($automobile_model_ids)) {
                $garagesQuery =   $garagesQuery->whereIn("garage_automobile_models.automobile_model_id", $automobile_model_ids);
            }
        }

        if (!empty($request->service_ids)) {

            $null_filter = collect(array_filter($request->service_ids))->values();
            $service_ids =  $null_filter->all();

            $count = count($service_ids); // Number of service IDs to match

            $garagesQuery = $garagesQuery->whereHas('garageServices', function ($query) use ($service_ids, $count) {
                $query
                    ->whereIn('service_id', $service_ids) // Filter to only the specified service IDs
                    ->selectRaw('garage_id')              // Select the garage_id (essential for grouping)
                    ->groupBy('garage_id')               // Group by garage_id
                    ->havingRaw('COUNT(DISTINCT service_id) = ?', [$count]); // Ensure the count of distinct services matches the expected count
            });
        }


        if (!empty($request->sub_service_ids)) {
            $null_filter = collect(array_filter($request->sub_service_ids))->values();
            $sub_service_ids =  $null_filter->all();
            if (count($sub_service_ids)) {

                $count = count($sub_service_ids);
                $garagesQuery = $garagesQuery->whereHas('garageServices.garageSubServices', function ($query) use ($sub_service_ids, $count) {
                    $query
                        ->whereIn('sub_service_id', $sub_service_ids) // Filter to only the specified sub_service_ids
                        ->selectRaw('garage_service_id')           // Select the garage_service_id (essential for grouping)
                        ->groupBy('garage_service_id')            // Group by garage_service_id
                        ->havingRaw('COUNT(DISTINCT sub_service_id) = ?', [$count]); // Ensure the count of distinct sub_service_ids matches the expected count
                });
            }
        }

        if (!empty($request->start_lat)) {
            $garagesQuery = $garagesQuery->where('lat', ">=", $request->start_lat);
        }
        if (!empty($request->end_lat)) {
            $garagesQuery = $garagesQuery->where('lat', "<=", $request->end_lat);
        }
        if (!empty($request->start_long)) {
            $garagesQuery = $garagesQuery->where('long', ">=", $request->start_long);
        }
        if (!empty($request->end_long)) {
            $garagesQuery = $garagesQuery->where('long', "<=", $request->end_long);
        }

        if (!empty($request->open_time)) {
            $date = Carbon::createFromFormat('Y-m-d H:i', $request->open_time);
            $dayOfWeek = $date->dayOfWeek; // 6 (0 for Sunday, 1 for Monday, 2 for Tuesday, etc.)
            $time = $date->format('H:i');
            $garagesQuery = $garagesQuery->where('garage_times.day', "=", $dayOfWeek)
                ->whereTime('garage_times.opening_time', "<=", $time)
                ->whereTime('garage_times.closing_time', ">", $time);
        }

        return $garagesQuery;
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/garages/{perPage}",
     *      operationId="getGaragesClient",
     *      tags={"client.basics"},
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

     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     * *  @OA\Parameter(
     * name="country",
     * in="query",
     * description="country",
     * required=true,
     * example="country"
     * ),
     * *  @OA\Parameter(
     * name="address",
     * in="query",
     * description="address",
     * required=true,
     * example="address"
     * ),
     * *  @OA\Parameter(
     * name="city",
     * in="query",
     * description="city",
     * required=true,
     * example="city"
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
     *      name="automobile_make_ids[]",
     *      in="query",
     *      description="automobile_make_ids",
     *      required=true,
     *      example="1,2"
     * ),
     *  @OA\Parameter(
     *      name="automobile_model_ids[]",
     *      in="query",
     *      description="automobile_model_id",
     *      required=true,
     *      example="1,2"
     * ),
     *  @OA\Parameter(
     *      name="service_ids[]",
     *      in="query",
     *      description="service_id",
     *      required=true,
     *      example="1,2"
     * ),
     *  @OA\Parameter(
     *      name="sub_service_ids[]",
     *      in="query",
     *      description="sub_service_id",
     *      required=true,
     *      example="1,2"
     * ),
     * *  @OA\Parameter(
     * name="wifi_available",
     * in="query",
     * description="wifi_available",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="is_mobile_garage",
     * in="query",
     * description="is_mobile_garage",
     * required=true,
     * example="1"
     * ),

     * *  @OA\Parameter(
     * name="open_time",
     * in="query",
     * description="2019-06-29 22:00",
     * required=true,
     * example="1"
     * ),
     *
     *      summary="This method is to get garages by client",
     *      description="This method is to get garages by client",
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

    public function getGaragesClient($perPage, Request $request)
    {



        try {
            $this->storeActivity($request, "");

            $info = [];


            if (!empty($request->address)) {
                $garages = $this->getGarageSearchQuery($request)
                    ->where("garages.city", $request->address)
                    ->groupBy("garages.id")

                    ->orderByDesc("garages.id")
                    ->select("garages.*")
                    ->paginate($perPage);

                $info["is_result_by_city"] = true;
                $info["is_result_by_country"] = false;

                if (count($garages->items()) == 0) {
                    $info["is_result_by_city"] = false;
                    $info["is_result_by_country"] = true;

                    $garages = $this->getGarageSearchQuery($request)
                        ->where("garages.country", $request->address)
                        ->groupBy("garages.id")

                        ->orderByDesc("garages.id")
                        ->select(
                            "garages.*",
                            DB::raw('CASE
                        WHEN (SELECT COUNT(garage_packages.id) FROM garage_packages WHERE garage_packages.garage_id = garages.id AND garage_packages.deleted_at IS NULL) = 0 THEN 0
                        ELSE 1
                    END AS is_package_available')
                        )
                        ->paginate($perPage);
                }
                if (count($garages->items()) == 0) {
                    $info["is_result_by_city"] = false;
                    $info["is_result_by_country"] = false;
                }
            } else {

                array_splice($info, 0);

                $garages = $this->getGarageSearchQuery($request)


                    ->groupBy("garages.id")

                    ->orderByDesc("garages.id")
                    ->select(
                        "garages.*",
                        DB::raw('CASE
                        WHEN (SELECT COUNT(garage_packages.id) FROM garage_packages WHERE garage_packages.garage_id = garages.id AND garage_packages.deleted_at IS NULL) = 0 THEN 0
                        ELSE 1
                    END AS is_package_available')
                    )

                    ->paginate($perPage);
            }


            foreach ($garages->items() as $key => $value) {
                $totalCount = 0;
                $totalRating = 0;

                foreach (Star::get() as $star) {

                    $data2["star_" . $star->value . "_selected_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                        ->where([
                            "review_news.garage_id" => $garages->items()[$key]->id,
                            "star_id" => $star->id,
                            // "review_news.guest_id" => NULL
                        ])
                        ->distinct("review_value_news.review_id", "review_value_news.question_id");
                    if (!empty($request->start_date) && !empty($request->end_date)) {

                        $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->whereBetween('review_news.created_at', [
                            $request->start_date,
                            $request->end_date
                        ]);
                    }
                    $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->count();

                    $totalCount += $data2["star_" . $star->value . "_selected_count"] * $star->value;

                    $totalRating += $data2["star_" . $star->value . "_selected_count"];
                }
                if ($totalCount > 0) {
                    $data2["average_rating"] = $totalCount / $totalRating;
                } else {
                    $data2["average_rating"] = 0;
                }
                $garages->items()[$key]->average_rating = $data2["average_rating"];
                $garages->items()[$key]->total_rating_count = $totalCount;
            }



            return response()->json([
                "info" => $info,

                "data" => $garages
            ], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v2.0/client/garages/{perPage}",
     *      operationId="getGaragesClient2",
     *      tags={"client.basics"},
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

     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     * *  @OA\Parameter(
     * name="country",
     * in="query",
     * description="country",
     * required=true,
     * example="country"
     * ),
     * *  @OA\Parameter(
     * name="address",
     * in="query",
     * description="address",
     * required=true,
     * example="address"
     * ),
     * *  @OA\Parameter(
     * name="city",
     * in="query",
     * description="city",
     * required=true,
     * example="city"
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
     *      name="automobile_make_ids[]",
     *      in="query",
     *      description="automobile_make_ids",
     *      required=true,
     *      example="1,2"
     * ),
     *  @OA\Parameter(
     *      name="automobile_model_ids[]",
     *      in="query",
     *      description="automobile_model_id",
     *      required=true,
     *      example="1,2"
     * ),
     *  @OA\Parameter(
     *      name="service_ids[]",
     *      in="query",
     *      description="service_id",
     *      required=true,
     *      example="1,2"
     * ),
     *  @OA\Parameter(
     *      name="sub_service_ids[]",
     *      in="query",
     *      description="sub_service_id",
     *      required=true,
     *      example="1,2"
     * ),
     * *  @OA\Parameter(
     * name="wifi_available",
     * in="query",
     * description="wifi_available",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="is_mobile_garage",
     * in="query",
     * description="is_mobile_garage",
     * required=true,
     * example="1"
     * ),

     * *  @OA\Parameter(
     * name="open_time",
     * in="query",
     * description="2019-06-29 22:00",
     * required=true,
     * example="1"
     * ),
     *
     *      summary="This method is to get garages by client",
     *      description="This method is to get garages by client",
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

    public function getGaragesClient2($perPage, Request $request)
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

            // $location = $this->getCountryAndCity($request->lat, $request->long);

            if (!empty($country) && !empty($city) && empty(request()->start_lat) && empty(request()->end_lat)  && empty(request()->start_long) && empty(request()->end_long) ) {


                $garages = $this->getGarageSearchQuery($request)
                    ->where("garages.city", $city)
                    ->groupBy("garages.id")
                    ->orderByDesc("garages.id")
                    ->select("garages.*")
                    ->paginate($perPage);


                $info["city"] = $city;
                $info["is_result_by_city"] = true;
                $info["is_result_by_country"] = false;

                if (count($garages->items()) == 0) {
                    $info["is_result_by_city"] = false;
                    $info["is_result_by_country"] = true;

                    $garages = $this->getGarageSearchQuery($request)
                        ->where("garages.country", $country)
                        ->groupBy("garages.id")

                        ->orderByDesc("garages.id")
                        ->select(
                            "garages.id",
                            "garages.lat",
                            "garages.long",
                            "garages.name",
                            "garages.phone",
                            "garages.email",
                            "garages.address_line_1",
                            "garages.logo",
                            "garages.image",
                            "garages.status",
                            "garages.is_active",
                            "garages.is_mobile_garage",
                            "garages.wifi_available",
                            "garages.time_format",
                            "garages.created_at",
                            DB::raw('CASE
                            WHEN (SELECT COUNT(garage_packages.id) FROM garage_packages WHERE garage_packages.garage_id = garages.id AND garage_packages.deleted_at IS NULL) = 0 THEN 0
                            ELSE 1
                        END AS is_package_available')

                        )
                        ->paginate($perPage);
                }
                if (count($garages->items()) == 0) {
                    $info["is_result_by_city"] = false;
                    $info["is_result_by_country"] = false;
                }
            } else {

                array_splice($info, 0);

                $garages = $this->getGarageSearchQuery($request)


                    ->groupBy("garages.id")

                    ->orderByDesc("garages.id")
                    ->select(
                        "garages.id",
                        "garages.lat",
                        "garages.long",
                        "garages.name",

                        "garages.phone",
                        "garages.email",
                        "garages.address_line_1",
                        "garages.logo",
                        "garages.image",
                        "garages.status",
                        "garages.is_active",
                        "garages.is_mobile_garage",
                        "garages.wifi_available",
                        "garages.time_format",
                        "garages.created_at",
                        DB::raw('CASE
                            WHEN (SELECT COUNT(garage_packages.id) FROM garage_packages WHERE garage_packages.garage_id = garages.id AND garage_packages.deleted_at IS NULL) = 0 THEN 0
                            ELSE 1
                        END AS is_package_available')

                    )

                    ->paginate($perPage);
            }


            foreach ($garages->items() as $key => $value) {
                $totalCount = 0;
                $totalRating = 0;

                foreach (Star::get() as $star) {

                    $data2["star_" . $star->value . "_selected_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                        ->where([
                            "review_news.garage_id" => $garages->items()[$key]->id,
                            "star_id" => $star->id,
                            // "review_news.guest_id" => NULL
                        ])
                        ->distinct("review_value_news.review_id", "review_value_news.question_id");
                    if (!empty($request->start_date) && !empty($request->end_date)) {

                        $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->whereBetween('review_news.created_at', [
                            $request->start_date,
                            $request->end_date
                        ]);
                    }
                    $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->count();

                    $totalCount += $data2["star_" . $star->value . "_selected_count"] * $star->value;

                    $totalRating += $data2["star_" . $star->value . "_selected_count"];
                }
                if ($totalCount > 0) {
                    $data2["average_rating"] = $totalCount / $totalRating;
                } else {
                    $data2["average_rating"] = 0;
                }
                $garages->items()[$key]->average_rating = $data2["average_rating"];
                $garages->items()[$key]->total_rating_count = $totalCount;
            }



            return response()->json([
                "info" => $info,

                "data" => $garages
            ], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v3.0/client/garages",
     *      operationId="getGaragesClient3",
     *      tags={"client.basics"},
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

     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     * *  @OA\Parameter(
     * name="country",
     * in="query",
     * description="country",
     * required=true,
     * example="country"
     * ),
     * *  @OA\Parameter(
     * name="address",
     * in="query",
     * description="address",
     * required=true,
     * example="address"
     * ),
     * *  @OA\Parameter(
     * name="city",
     * in="query",
     * description="city",
     * required=true,
     * example="city"
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
     *      name="automobile_make_ids[]",
     *      in="query",
     *      description="automobile_make_ids",
     *      required=true,
     *      example="1,2"
     * ),
     *  @OA\Parameter(
     *      name="automobile_model_ids[]",
     *      in="query",
     *      description="automobile_model_id",
     *      required=true,
     *      example="1,2"
     * ),
     *  @OA\Parameter(
     *      name="service_ids[]",
     *      in="query",
     *      description="service_id",
     *      required=true,
     *      example="1,2"
     * ),
     *  @OA\Parameter(
     *      name="sub_service_ids[]",
     *      in="query",
     *      description="sub_service_id",
     *      required=true,
     *      example="1,2"
     * ),
     * *  @OA\Parameter(
     * name="wifi_available",
     * in="query",
     * description="wifi_available",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="is_mobile_garage",
     * in="query",
     * description="is_mobile_garage",
     * required=true,
     * example="1"
     * ),

     * *  @OA\Parameter(
     * name="open_time",
     * in="query",
     * description="2019-06-29 22:00",
     * required=true,
     * example="1"
     * ),
     *
     *      summary="This method is to get garages by client",
     *      description="This method is to get garages by client",
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

     public function getGaragesClient3(Request $request)
     {

         try {
             $this->storeActivity($request, "");

             $info = [];


             if (!empty($request->address)) {
                 $garages = $this->getGarageSearchQuery2($request)
                     ->where("garages.city", $request->address)
                     ->groupBy("garages.id")

                     ->orderByDesc("garages.id")
                     ->select(
                        "garages.id",
                        "garages.name",
                        "garages.lat",
                        "garages.long",
                     )
                     ->get();

                 $info["is_result_by_city"] = true;
                 $info["is_result_by_country"] = false;

                 if (count($garages->items()) == 0) {
                     $info["is_result_by_city"] = false;
                     $info["is_result_by_country"] = true;

                     $garages = $this->getGarageSearchQuery2($request)
                         ->where("garages.country", $request->address)
                         ->groupBy("garages.id")

                         ->orderByDesc("garages.id")
                         ->select(
                             "garages.id",
                             "garages.name",
                             "garages.lat",
                             "garages.long",

                         )
                         ->get();
                 }
                 if (count($garages->items()) == 0) {
                     $info["is_result_by_city"] = false;
                     $info["is_result_by_country"] = false;
                 }
             } else {

                 array_splice($info, 0);

                 $garages = $this->getGarageSearchQuery2($request)


                     ->groupBy("garages.id")

                     ->orderByDesc("garages.id")
                     ->select(
                        "garages.id",
                        "garages.name",
                        "garages.lat",
                        "garages.long",
                     )

                     ->get();
             }

             return response()->json([
                 "info" => $info,
                 "data" => $garages
             ], 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/garages/single/{id}",
     *      operationId="getGarageByIdClient",
     *      tags={"client.basics"},
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
     *      summary="This method is to get garage by id",
     *      description="This method is to get garage by id",
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

    public function getGarageByIdClient($id, Request $request)
    {

        try {
            $this->storeActivity($request, "");
            $garagesQuery = Garage::with(
                "owner",
                "garageAutomobileMakes.automobileMake",
                "garageAutomobileMakes.garageAutomobileModels.automobileModel",
                "garageServices.service",
                "garageServices.garageSubServices.garage_sub_service_prices",
                "garageServices.garageSubServices.subService",
                "garage_times",
                "garageGalleries",
                "garage_packages",
                "garage_affiliations.affiliation"

            );



            $garage = $garagesQuery->where([
                "id" => $id,
                "is_active" => 1
            ])
                ->first();


            if (!$garage) {


                return response()->json([
                    "message" => "no garage found"
                ], 404);
            }


            $garage_automobile_make_ids =  GarageAutomobileMake::where(["garage_id" => $garage->id])->pluck("automobile_make_id");
            $garage_service_ids =   GarageService::where(["garage_id" => $garage->id])->pluck("service_id");

            $data["garage"] = $garage;
            $data["garage_automobile_make_ids"] = $garage_automobile_make_ids;
            $data["garage_service_ids"] = $garage_service_ids;



            $totalCount = 0;
            $totalRating = 0;

            foreach (Star::get() as $star) {

                $data2["star_" . $star->value . "_selected_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                    ->where([
                        "review_news.garage_id" => $garage->id,
                        "star_id" => $star->id,
                        // "review_news.guest_id" => NULL
                    ])
                    ->distinct("review_value_news.review_id", "review_value_news.question_id");

                if (!empty($request->start_date) && !empty($request->end_date)) {

                    $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->whereBetween('review_news.created_at', [
                        $request->start_date,
                        $request->end_date
                    ]);
                }

                $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->count();

                $totalCount += $data2["star_" . $star->value . "_selected_count"] * $star->value;

                $totalRating += $data2["star_" . $star->value . "_selected_count"];
            }
            if ($totalCount > 0) {
                $data2["average_rating"] = $totalCount / $totalRating;
            } else {
                $data2["average_rating"] = 0;
            }
            $data["garage"]->average_rating = $data2["average_rating"];
            $data["garage"]->total_rating_count = $totalCount;



            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v2.0/client/garages/single/{id}",
     *      operationId="getGarageByIdClient2",
     *      tags={"client.basics"},
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
     *      summary="This method is to get garage by id",
     *      description="This method is to get garage by id",
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

    public function getGarageByIdClient2($id, Request $request)
    {

        try {
            $this->storeActivity($request, "");
            $garagesQuery = Garage::with([
                'owner' => function ($query) {
                    $query->select('users.id', 'users.first_name', 'users.last_name', 'users.image', 'users.phone', 'users.email');
                },
                'automobile_makes' => function ($query) {
                    $query->select("automobile_makes.*");
                },
                'services' => function ($query) {
                    $query->select('services.*');
                },
                'garage_times' => function ($query) {
                    $query->select('id', 'garage_id', 'day', 'opening_time', 'closing_time', 'is_closed');
                },
                'garageGalleries' => function ($query) {
                    $query->select('garage_galleries.*');
                },
                'garage_packages' => function ($query) {
                    $query->select('garage_packages.*',);
                },
                'garage_packages.sub_services' => function ($query) {
                    $query->select('sub_services.id');
                },

                'garage_affiliations' => function ($query) {
                    $query->select('garage_affiliations.*');
                }
            ]);


            $garage = $garagesQuery->where([
                "id" => $id,
                "is_active" => 1
            ])
                ->select(
                    'garages.id',
                    'garages.name',
                    'garages.about',
                    'garages.web_page',
                    'garages.phone',
                    'garages.email',
                    'garages.additional_information',
                    'garages.address_line_1',
                    'garages.address_line_2',
                    'garages.lat',
                    'garages.long',
                    'garages.country',
                    'garages.city',
                    'garages.postcode',
                    'garages.currency',
                    'garages.logo',
                    'garages.image',
                    'garages.background_image',
                    'garages.status',
                    'garages.is_active',
                    'garages.is_mobile_garage',
                    'garages.wifi_available',
                    'garages.labour_rate',
                    'garages.time_format',
                    'garages.owner_id',
                )
                ->first();


            $sub_services = SubService::with("translation")->whereHas("service.garageService.garage", function ($query) use ($id) {
                $query->where([
                    "garages.id" => $id
                ]);
            })->get();
            $garage->sub_services = $sub_services;

            $automobile_models = AutomobileModel::whereHas("make.garageAutoMobileMake.garage", function ($query) use ($id) {
                $query->where([
                    "garages.id" => $id
                ]);
            })->get();
            $garage->sub_services = $sub_services;
            $garage->automobile_models = $automobile_models;

            if (!$garage) {

                return response()->json([
                    "message" => "no garage found"
                ], 404);
            }




            $data["garage"] = $garage;




            $totalCount = 0;
            $totalRating = 0;

            foreach (Star::get() as $star) {

                $data2["star_" . $star->value . "_selected_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                    ->where([
                        "review_news.garage_id" => $garage->id,
                        "star_id" => $star->id,
                        // "review_news.guest_id" => NULL
                    ])
                    ->distinct("review_value_news.review_id", "review_value_news.question_id");
                if (!empty($request->start_date) && !empty($request->end_date)) {

                    $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->whereBetween('review_news.created_at', [
                        $request->start_date,
                        $request->end_date
                    ]);
                }
                $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->count();

                $totalCount += $data2["star_" . $star->value . "_selected_count"] * $star->value;

                $totalRating += $data2["star_" . $star->value . "_selected_count"];
            }
            if ($totalCount > 0) {
                $data2["average_rating"] = $totalCount / $totalRating;
            } else {
                $data2["average_rating"] = 0;
            }
            $data["garage"]->average_rating = $data2["average_rating"];
            $data["garage"]->total_rating_count = $totalCount;



            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }





    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/garages/service-model-details/{garage_id}",
     *      operationId="getGarageServiceModelDetailsByIdClient",
     *      tags={"client.basics"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to get garage service-model-details by garage id by id",
     *      description="This method is to get garage service-model-details by garage id by id",
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

    public function getGarageServiceModelDetailsByIdClient($garage_id, Request $request)
    {

        try {
            $this->storeActivity($request, "");
            $garage = Garage::where([
                "id" => $garage_id
            ])->first();


            if (!$garage) {


                return response()->json([
                    "message" => "no garage found"
                ], 404);
            }
            $data["garage_services"] = GarageService::with("service")
                ->where([
                    "garage_id" => $garage->id
                ])
                ->get();

            $data["garage_sub_services"] = GarageSubService::with("subService")
                ->leftJoin('garage_services', 'garage_sub_services.garage_service_id', '=', 'garage_services.id')
                ->where([
                    "garage_services.garage_id" => $garage->id
                ])
                ->select(
                    "garage_sub_services.*"
                )

                ->get();

            $data["garage_automobile_makes"] = GarageAutomobileMake::with("automobileMake")
                ->where([
                    "garage_id" => $garage->id
                ])
                ->get();

            $data["garage_automobile_models"] = GarageAutomobileModel::with("automobileModel")
                ->leftJoin('garage_automobile_makes', 'garage_automobile_models.garage_automobile_make_id', '=', 'garage_automobile_makes.id')
                ->where([
                    "garage_automobile_makes.garage_id" => $garage->id
                ])
                ->select(
                    "garage_automobile_models.*"
                )
                ->get();




            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/garages/garage-automobile-models/{garage_id}/{automobile_make_id}",
     *      operationId="getGarageAutomobileModelsByAutomobileMakeId",
     *      tags={"client.basics"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="1"
     *      ),
     *   *              @OA\Parameter(
     *         name="automobile_make_id",
     *         in="path",
     *         description="automobile_make_id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to get garage service-model-details by garage id by id",
     *      description="This method is to get garage service-model-details by garage id by id",
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

    public function getGarageAutomobileModelsByAutomobileMakeId($garage_id, $automobile_make_id, Request $request)
    {

        try {
            $this->storeActivity($request, "");
            $garage = Garage::where([
                "id" => $garage_id
            ])->first();


            if (!$garage) {

                return response()->json([
                    "message" => "no garage found"
                ], 404);
            }
            $data = GarageAutomobileModel::with("automobileModel")
                ->leftJoin('garage_automobile_makes', 'garage_automobile_models.garage_automobile_make_id', '=', 'garage_automobile_makes.id')
                ->where([
                    "garage_automobile_makes.automobile_make_id" => $automobile_make_id,
                    "garage_automobile_makes.garage_id" => $garage->id
                ])
                ->select(
                    "garage_automobile_models.*"
                )
                ->get();




            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }





    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/garage-affiliations/get/all/{garage_id}",
     *      operationId="getGarageAffiliationsAllByGarageIdClient",
     *      tags={"client.basics"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="1"
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
     *      summary="This method is to get garage affiliations ",
     *      description="This method is to get garage affiliations",
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

    public function getGarageAffiliationsAllByGarageIdClient($garage_id, Request $request)
    {
        try {
            $this->storeActivity($request, "");










            // $automobilesQuery = AutomobileMake::with("makes");

            $affiliationQuery =  GarageAffiliation::with("affiliation", "garage")
                ->leftJoin('affiliations', 'affiliations.id', '=', 'garage_affiliations.affiliation_id')
                ->where([
                    "garage_id" => $garage_id
                ]);

            if (!empty($request->search_key)) {
                $affiliationQuery = $affiliationQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("affiliations.name", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $affiliationQuery = $affiliationQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $affiliationQuery = $affiliationQuery->where('created_at', "<=", $request->end_date);
            }

            $affiliations = $affiliationQuery->orderByDesc("garage_affiliations.id")->get();


            return response()->json($affiliations, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }








    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/favourite-sub-services/{perPage}",
     *      operationId="getFavouriteSubServices",
     *      tags={"client.basics"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   *              @OA\Parameter(
     *         name="perPage",
     *         in="path",
     *         description="perPage",
     *         required=true,
     *  example="6"
     *      ),

     *      summary="This method is to get favourite jobs",
     *      description="This method is to get favourite jobs",
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

    public function getFavouriteSubServices($perPage, Request $request)
    {

        try {
            $this->storeActivity($request, "");
            $user = $request->user();
            $data = SubService::
            with("translation")->
            select(
                "sub_services.*",
                DB::raw('(SELECT COUNT(job_sub_services.sub_service_id)
            FROM
            job_sub_services
            LEFT JOIN jobs ON job_sub_services.job_id = jobs.id


            WHERE jobs.customer_id = '
                    .
                    $user->id
                    .
                    '
            AND
            job_sub_services.sub_service_id = sub_services.id

            ) AS sub_service_id_count'),
            )
                ->orderByRaw('sub_service_id_count desc')
                ->havingRaw('sub_service_id_count > 0')
                ->paginate($perPage);




            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }
}
