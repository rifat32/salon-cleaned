<?php

namespace App\Http\Controllers;

use App\Http\Requests\FuelStationServiceCreateRequest;
use App\Http\Requests\FuelStationServiceUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\FuelStationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FuelStationServiceController extends Controller
{
    use ErrorUtil,UserActivityUtil;

    /**
     *
     * @OA\Post(
     *      path="/v1.0/fuel-station-services",
     *      operationId="createFuelStationService",
     *      tags={"fuel_station_service_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store fuel station services",
     *      description="This method is to store fuel station servces",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","address","opening_time","closing_time","description"},
     *    @OA\Property(property="name", type="string", format="string",example="car"),
     *    @OA\Property(property="icon", type="string", format="string",example="fa fa-halua-kha"),

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

    public function createFuelStationService(FuelStationServiceCreateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('fuel_station_service_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $insertableData = $request->validated();

                $fuel_station =  FuelStationService::create($insertableData);




                return response($fuel_station, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }



        /**
     *
     * @OA\Put(
     *      path="/v1.0/fuel-station-services",
     *      operationId="updateFuelStationService",
     *      tags={"fuel_station_service_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update fuel station service",
     *      description="This method is to update fuel station service",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","name","icon"},
     *    @OA\Property(property="id", type="number", format="number", example="1"),
     *    @OA\Property(property="name", type="string", format="string",example="car"),
     *    @OA\Property(property="icon", type="string", format="string",example="fa fa-halua-kha"),

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

    public function updateFuelStationService(FuelStationServiceUpdateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('fuel_station_service_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $updatableData = $request->validated();



                $fuel_station_service  =  tap(FuelStationService::where(["id" => $updatableData["id"]]))->update(
                    collect($updatableData)->only([
                        "name",
                        "icon",

                    ])->toArray()
                )


                    ->first();

                    if(!$fuel_station_service){
                        return response()->json([
                            "message" => "no fuel station service found"
                        ],
                        404);
                    }








                return response($fuel_station_service, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }

     /**
     *
     * @OA\Get(
     *      path="/v1.0/fuel-station-services/{perPage}",
     *      operationId="getFuelStationServices",
     *      tags={"fuel_station_service_management"},
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
     *     *              @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
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

     *      summary="This method is to get fuel station services ",
     *      description="This method is to get fuel station services",
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

    public function getFuelStationServices($perPage, Request $request)
    {
        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('fuel_station_service_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $fuelStationServiceQuery = new FuelStationService();

            if (!empty($request->search_key)) {
                $fuelStationServiceQuery = $fuelStationServiceQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $fuelStationServiceQuery = $fuelStationServiceQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $fuelStationServiceQuery = $fuelStationServiceQuery->where('created_at', "<=", $request->end_date);
            }



            $fuelStationServices= $fuelStationServiceQuery
            ->orderByDesc("id")
            ->when(request()->filled("id"), function ($query) {
             return  $query->where("id", request()->input("id"))
               ->first();
            },
            function ($query) use($perPage) {
              return $query->paginate($perPage);
            },

        );


            return response()->json($fuelStationServices, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }

       /**
     *
     * @OA\Get(
     *      path="/v1.0/fuel-station-services/get/all",
     *      operationId="getFuelStationServicesAll",
     *      tags={"fuel_station_service_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
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

     *      summary="This method is to get fuel station services ",
     *      description="This method is to get fuel station services",
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

    public function getFuelStationServicesAll( Request $request)
    {
        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('fuel_station_service_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }



            $fuelStationServiceQuery = new FuelStationService();

            if (!empty($request->search_key)) {
                $fuelStationServiceQuery = $fuelStationServiceQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $fuelStationServiceQuery = $fuelStationServiceQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $fuelStationServiceQuery = $fuelStationServiceQuery->where('created_at', "<=", $request->end_date);
            }



            $fuelStationServices= $fuelStationServiceQuery

            ->orderByDesc("id")
            ->get();
            return response()->json($fuelStationServices, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }
      /**
     *
     * @OA\Get(
     *      path="/v1.0/client/fuel-station-services/get/all",
     *      operationId="getFuelStationServicesAllClient",
     *      tags={"client.fuel_station_service_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
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

     *      summary="This method is to get fuel station services client",
     *      description="This method is to get fuel station services client",
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

    public function getFuelStationServicesAllClient( Request $request)
    {
        try {
            $this->storeActivity($request,"");
            // if (!$request->user()->hasPermissionTo('fuel_station_service_view')) {
            //     return response()->json([
            //         "message" => "You can not perform this action"
            //     ], 401);
            // }



            $fuelStationServiceQuery = new FuelStationService();

            if (!empty($request->search_key)) {
                $fuelStationServiceQuery = $fuelStationServiceQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $fuelStationServiceQuery = $fuelStationServiceQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $fuelStationServiceQuery = $fuelStationServiceQuery->where('created_at', "<=", $request->end_date);
            }



            $fuelStationServices= $fuelStationServiceQuery

            ->orderByDesc("id")
            ->get();
            return response()->json($fuelStationServices, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }



     /**
     *
     *     @OA\Delete(
     *      path="/v1.0/fuel-station-services/{id}",
     *      operationId="deleteFuelStationServiceById",
     *      tags={"fuel_station_service_management"},
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
     *      summary="This method is to delete fuel station service by id",
     *      description="This method is to delete fuel station service by id",
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

    public function deleteFuelStationServiceById($id, Request $request)
    {

        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('fuel_station_service_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            FuelStationService::where([
                "id" => $id
            ])
                ->delete();

            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }

}
