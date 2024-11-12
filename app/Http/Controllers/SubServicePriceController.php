<?php





namespace App\Http\Controllers;

use App\Http\Requests\BulkSubServicePriceRequest;
use App\Http\Requests\BulkSubServicePriceUpdateRequest;
use App\Http\Requests\SubServicePriceCreateRequest;
use App\Http\Requests\SubServicePriceUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\SubServicePrice;
use App\Models\DisabledSubServicePrice;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubServicePriceController extends Controller
{

    use ErrorUtil, UserActivityUtil, BusinessUtil;


    /**
     *
     * @OA\Post(
     *      path="/v1.0/sub-service-prices",
     *      operationId="createSubServicePrice",
     *      tags={"sub_service_prices"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store sub service prices",
     *      description="This method is to store sub service prices",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="sub_service_id", type="string", format="string", example="sub_service_id"),
     * @OA\Property(property="price", type="string", format="string", example="price"),
     * @OA\Property(property="expert_id", type="string", format="string", example="expert_id"),
     * @OA\Property(property="description", type="string", format="string", example="description"),
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

    public function createSubServicePrice(SubServicePriceCreateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('sub_service_price_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();






                $request_data["created_by"] = auth()->user()->id;
                $request_data["business_id"] = auth()->user()->business_id;

                if (empty(auth()->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if (auth()->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }


                $price_exists = SubServicePrice::where([
                    "sub_service_id" => $request_data["sub_service_id"],
                    "expert_id" => $request_data["expert_id"],
                ])
                ->exists();

                if(!empty($price_exists)) {
                    return response()->json([
                        "message" => "Price already defined for this service."
                    ],409);
                }

                $sub_service_price =  SubServicePrice::create($request_data);


                return response($sub_service_price, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
 * @OA\Post(
 *      path="/v1.0/sub-service-prices/bulk",
 *      operationId="bulkCreateSubServicePrice",
 *      tags={"sub_service_prices"},
 *      security={
 *           {"bearerAuth": {}}
 *      },
 *      summary="This method is to store multiple sub service prices",
 *      description="This method is to store multiple sub service prices",
 *      @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="sub_service_prices", type="array", @OA\Items(
 *                 @OA\Property(property="sub_service_id", type="string", format="string", example="sub_service_id"),
 *                 @OA\Property(property="price", type="string", format="string", example="price"),
 *                 @OA\Property(property="expert_id", type="string", format="string", example="expert_id"),
 *                 @OA\Property(property="description", type="string", format="string", example="description")
 *             )),
 *         ),
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
public function bulkCreateSubServicePrice(BulkSubServicePriceRequest $request)
{
    try {
        return DB::transaction(function () use ($request) {
            if (!auth()->user()->hasPermissionTo('sub_service_price_create')) {
                return response()->json([
                    "message" => "You cannot perform this action"
                ], 401);
            }

            $createdPrices = [];
            foreach ($request->sub_service_prices as $priceData) {
                $priceData['created_by'] = auth()->user()->id;
                $priceData['business_id'] = auth()->user()->business_id;

                if (empty(auth()->user()->business_id)) {
                    $priceData['business_id'] = null;
                    if (auth()->user()->hasRole('superadmin')) {
                        $priceData['is_default'] = 1;
                    }
                }

               // Define the conditions to check for existing price data
$conditions = [
    'sub_service_id' => $priceData['sub_service_id'],
    'expert_id' => $priceData['expert_id'],
];

// Attempt to find the existing record
$existingPrice = SubServicePrice::where($conditions)->first();

if ($existingPrice) {
    // Update the existing record
    $existingPrice->update($priceData);

    $createdPrices[] = $existingPrice;
} else {
    // Create a new record since it doesn't exist
    $createdPrices[] = SubServicePrice::create($priceData);
}
            }

            return response($createdPrices, 201);
        });
    } catch (Exception $e) {
        return $this->sendError($e, 500, $request);
    }
}

    /**
     *
     * @OA\Put(
     *      path="/v1.0/sub-service-prices",
     *      operationId="updateSubServicePrice",
     *      tags={"sub_service_prices"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update sub service prices ",
     *      description="This method is to update sub service prices ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="1"),
     * @OA\Property(property="sub_service_id", type="string", format="string", example="sub_service_id"),
     * @OA\Property(property="price", type="string", format="string", example="price"),
     * @OA\Property(property="expert_id", type="string", format="string", example="expert_id"),
     * @OA\Property(property="description", type="string", format="string", example="description"),
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

    public function updateSubServicePrice(SubServicePriceUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('sub_service_price_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                $sub_service_price_query_params = [
                    "id" => $request_data["id"],
                ];

                $sub_service_price = SubServicePrice::where($sub_service_price_query_params)->first();

                if ($sub_service_price) {
                    $sub_service_price->fill(collect($request_data)->only([

                        "sub_service_id",
                        "price",
                        "expert_id",
                        "description",
                        // "is_default",
                        // "is_active",
                        // "business_id",
                        // "created_by"
                    ])->toArray());
                    $sub_service_price->save();
                } else {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }




                return response($sub_service_price, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
/**
 * @OA\Put(
 *      path="/v1.0/sub-service-prices/bulk",
 *      operationId="bulkUpdateSubServicePrices",
 *      tags={"sub_service_prices"},
 *      security={
 *           {"bearerAuth": {}}
 *      },
 *      summary="This method is to store multiple sub service prices",
 *      description="This method is to store multiple sub service prices",
 *      @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *  * @OA\Property(property="expert_id", type="string", format="string", example="expert_id"),
 *             @OA\Property(property="sub_service_prices", type="array", @OA\Items(
 *                 @OA\Property(property="sub_service_id", type="string", format="string", example="sub_service_id"),
 *                 @OA\Property(property="price", type="string", format="string", example="price"),
 *                 @OA\Property(property="description", type="string", format="string", example="description")
 *             )),
 *         ),
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
    public function bulkUpdateSubServicePrices(BulkSubServicePriceUpdateRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('sub_service_price_update')) {
                    return response()->json([
                        "message" => "You cannot perform this action"
                    ], 401);
                }
                $request_data = $request->validated();

                SubServicePrice::where([
                    "business_id" => auth()->user()->business_id,
                  "expert_id" => $request_data["expert_id"]
                ])->delete();

                $updatedPrices = [];
                foreach ($request_data["sub_service_prices"] as $priceData) {
                    $priceData["expert_id"] = $request_data["expert_id"];
                    $priceData["business_id"] = auth()->user()->business_id;
                    $updatedPrices[] =  SubServicePrice::create($priceData);
                }

                return response($updatedPrices, 200);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/sub-service-prices",
     *      operationId="getSubServicePrices",
     *      tags={"sub_service_prices"},
     *       security={
     *           {"bearerAuth": {}}
     *       },




     *         @OA\Parameter(
     *         name="description",
     *         in="query",
     *         description="description",
     *         required=true,
     *  example="6"
     *      ),



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




     *      summary="This method is to get sub service prices  ",
     *      description="This method is to get sub service prices ",
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

    public function getSubServicePrices(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('sub_service_price_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }



            $sub_service_prices = SubServicePrice::where('sub_service_prices.business_id', auth()->user()->business_id)








                ->when(!empty($request->description), function ($query) use ($request) {
                    return $query->where('sub_service_prices.description', $request->description);
                })




                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query

                            ->orWhere("sub_service_prices.description", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('sub_service_prices.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('sub_service_prices.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("sub_service_prices.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("sub_service_prices.id", "DESC");
                })
                ->when($request->filled("id"), function ($query) use ($request) {
                    return $query
                        ->where("sub_service_prices.id", $request->input("id"))
                        ->first();
                }, function ($query) {
                    return $query->when(!empty(request()->per_page), function ($query) {
                        return $query->paginate(request()->per_page);
                    }, function ($query) {
                        return $query->get();
                    });
                });

            if ($request->filled("id") && empty($sub_service_prices)) {
                throw new Exception("No data found", 404);
            }


            return response()->json($sub_service_prices, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/sub-service-prices/{ids}",
     *      operationId="deleteSubServicePricesByIds",
     *      tags={"sub_service_prices"},
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
     *      summary="This method is to delete sub service price by id",
     *      description="This method is to delete sub service price by id",
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

    public function deleteSubServicePricesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('sub_service_price_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = SubServicePrice::whereIn('id', $idsArray)
                ->where('sub_service_prices.business_id', auth()->user()->business_id)

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





            SubServicePrice::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
