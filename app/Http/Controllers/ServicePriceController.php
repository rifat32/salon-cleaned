<?php





namespace App\Http\Controllers;

use App\Http\Requests\ServicePriceCreateRequest;
use App\Http\Requests\ServicePriceUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Requests\ServicePriceBulkUpdateRequest;
use App\Http\Requests\ServicePriceMultipleCreateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\ServicePrice;
use App\Models\DisabledServicePrice;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicePriceController extends Controller
{

    use ErrorUtil, UserActivityUtil, BusinessUtil;

/**
 * @OA\Post(
 *      path="/v1.0/service-prices/multiple",
 *      operationId="createMultipleServicePrices",
 *      tags={"service_prices"},
 *      security={
 *           {"bearerAuth": {}}
 *       },
 *      summary="This method is to store multiple service prices",
 *      description="This method is to store multiple service prices",
 *
 *  @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *              @OA\Property(
 *                  property="service_prices",
 *                  type="array",
 *                  @OA\Items(
 *                      @OA\Property(property="service_id", type="string", example="service_id"),
 *                      @OA\Property(property="price", type="string", example="price"),
 *                      @OA\Property(property="expert_id", type="string", example="expert_id"),
 *                      @OA\Property(property="business_id", type="string", example="business_id")
 *                  )
 *              )
 *         ),
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *       @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 *       @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Unprocessable Content",
 *       @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=403,
 *          description="Forbidden",
 *       @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=400,
 *          description="Bad Request",
 *       @OA\JsonContent(),
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Not found",
 *       @OA\JsonContent(),
 *      )
 * )
 */
public function createMultipleServicePrices(ServicePriceMultipleCreateRequest $request)
{
    try {
        $this->storeActivity($request, "DUMMY activity", "DUMMY description");

        return DB::transaction(function () use ($request) {
            if (!auth()->user()->hasPermissionTo('garage_service_price_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $servicePrices = [];
            foreach ($request->validated()['service_prices'] as $servicePriceData) {
                $servicePriceData["is_active"] = 1;
                $servicePriceData["created_by"] = auth()->user()->id;
                $servicePriceData["business_id"] = auth()->user()->business_id ?? null;

                if (empty($servicePriceData["business_id"]) && auth()->user()->hasRole('superadmin')) {
                    $servicePriceData["is_default"] = 1;
                }

                $servicePrices[] = ServicePrice::create($servicePriceData);
            }

            return response()->json($servicePrices, 201);
        });
    } catch (Exception $e) {
        return $this->sendError($e, 500, $request);
    }
}

    /**
     *
     * @OA\Post(
     *      path="/v1.0/service-prices",
     *      operationId="createServicePrice",
     *      tags={"service_prices"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store service prices",
     *      description="This method is to store service prices",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="service_id", type="string", format="string", example="service_id"),
     * @OA\Property(property="price", type="string", format="string", example="price"),
     * @OA\Property(property="expert_id", type="string", format="string", example="expert_id"),
     * @OA\Property(property="business_id", type="string", format="string", example="business_id"),
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

    public function createServicePrice(ServicePriceCreateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('garage_service_price_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["is_active"] = 1;





                $request_data["created_by"] = auth()->user()->id;
                $request_data["business_id"] = auth()->user()->business_id;

                if (empty(auth()->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if (auth()->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }




                $service_price =  ServicePrice::create($request_data);




                return response($service_price, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/service-prices",
     *      operationId="updateServicePrice",
     *      tags={"service_prices"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update service prices ",
     *      description="This method is to update service prices ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="1"),
     * @OA\Property(property="service_id", type="string", format="string", example="service_id"),
     * @OA\Property(property="price", type="string", format="string", example="price"),
     * @OA\Property(property="expert_id", type="string", format="string", example="expert_id"),
     * @OA\Property(property="business_id", type="string", format="string", example="business_id"),
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

    public function updateServicePrice(ServicePriceUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('service_price_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                $service_price_query_params = [
                    "id" => $request_data["id"],
                ];

                $service_price = ServicePrice::where($service_price_query_params)->first();

                if ($service_price) {
                    $service_price->fill(collect($request_data)->only([

                        "service_id",
                        "price",
                        "expert_id",
                        "business_id",
                        // "is_default",
                        // "is_active",
                        // "business_id",
                        // "created_by"
                    ])->toArray());
                    $service_price->save();
                } else {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }




                return response($service_price, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

/**
 * @OA\Put(
 *      path="/v1.0/service-prices/bulk-update",
 *      operationId="bulkUpdateServicePrices",
 *      tags={"service_prices"},
 *      security={
 *           {"bearerAuth": {}}
 *       },
 *      summary="This method is for bulk updating service prices",
 *      description="This method allows bulk updating service prices in an array format",
 *
 *      @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *              @OA\Property(
 *                  property="services",
 *                  type="array",
 *                  @OA\Items(
 *                      @OA\Property(property="id", type="number", format="number", example="1"),
 *                      @OA\Property(property="service_id", type="string", format="string", example="service_id"),
 *                      @OA\Property(property="price", type="string", format="string", example="price"),
 *                      @OA\Property(property="expert_id", type="string", format="string", example="expert_id"),
 *                      @OA\Property(property="business_id", type="string", format="string", example="business_id")
 *                  )
 *              )
 *         ),
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *          @OA\JsonContent(),
 *       ),
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
 *          @OA\JsonContent()
 *      ),
 *      @OA\Response(
 *          response=400,
 *          description="Bad Request",
 *          @OA\JsonContent()
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Not Found",
 *          @OA\JsonContent()
 *      )
 * )
 */
public function bulkUpdateServicePrices(ServicePriceBulkUpdateRequest $request)
{
    try {
        $this->storeActivity($request, "Bulk Update", "Bulk update of service prices");

        return DB::transaction(function () use ($request) {
            if (!auth()->user()->hasPermissionTo('service_price_update')) {
                return response()->json([
                    "message" => "You do not have permission to perform this action"
                ], 401);
            }

            $request_data = $request->validated();
            $updatedRecords = [];

            foreach ($request_data['services'] as $data) {
                $service_price_query_params = [
                    "id" => $data['id'],
                    "business_id" => auth()->user()->business_id
                ];

                $service_price = ServicePrice::where($service_price_query_params)->first();

                if ($service_price) {
                    $service_price->update([
                        "service_id" => $data['service_id'],
                        "price" => $data['price'],
                        "expert_id" => $data['expert_id'],
                        "business_id" => $data['business_id']
                    ]);
                    $updatedRecords[] = $service_price;
                } else {
                    return response()->json([
                        "message" => "Service price with ID {$data['id']} not found"
                    ], 404);
                }
            }

            return response()->json($updatedRecords, 200);
        });
    } catch (Exception $e) {
        return $this->sendError($e, 500, $request);
    }
}

    /**
     *
     * @OA\Put(
     *      path="/v1.0/service-prices/toggle-active",
     *      operationId="toggleActiveServicePrice",
     *      tags={"service_prices"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle service prices",
     *      description="This method is to toggle service prices",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

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

    public function toggleActiveServicePrice(GetIdRequest $request)
    {

        try {

            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('service_price_activate')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $service_price =  ServicePrice::where([
                "id" => $request_data["id"],
            ])
                ->first();
            if (!$service_price) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            $service_price->update([
                'is_active' => !$service_price->is_active
            ]);




            return response()->json(['message' => 'service price status updated successfully'], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/service-prices",
     *      operationId="getServicePrices",
     *      tags={"service_prices"},
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

     *     @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *     @OA\Parameter(
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
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     * *  @OA\Parameter(
     * name="id",
     * in="query",
     * description="id",
     * required=true,
     * example="ASC"
     * ),




     *      summary="This method is to get service prices  ",
     *      description="This method is to get service prices ",
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

    public function getServicePrices(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('service_price_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }



            $service_prices = ServicePrice::where('service_prices.business_id', auth()->user()->business_id)









                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query;
                    });
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('service_prices.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('service_prices.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("service_prices.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("service_prices.id", "DESC");
                })
                ->when($request->filled("id"), function ($query) use ($request) {
                    return $query
                        ->where("service_prices.id", $request->input("id"))
                        ->first();
                }, function ($query) {
                    return $query->when(!empty(request()->per_page), function ($query) {
                        return $query->paginate(request()->per_page);
                    }, function ($query) {
                        return $query->get();
                    });
                });

            if ($request->filled("id") && empty($service_prices)) {
                throw new Exception("No data found", 404);
            }


            return response()->json($service_prices, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/service-prices/{ids}",
     *      operationId="deleteServicePricesByIds",
     *      tags={"service_prices"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         description="ids",
     *         required=true,
     *  example="1,2,3"
     *      ),
     *      summary="This method is to delete service price by id",
     *      description="This method is to delete service price by id",
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

    public function deleteServicePricesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('service_price_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = ServicePrice::whereIn('id', $idsArray)
                ->where('service_prices.business_id', auth()->user()->business_id)

                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();
            $nonExistingIds = array_diff($idsArray, $existingIds);

            if (!empty($nonExistingIds)) {

                return response()->json([
                    "message" => "Some or all of the specified data do not exist."
                ], 404);
            }





            ServicePrice::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
