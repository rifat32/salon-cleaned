<?php





namespace App\Http\Controllers;

use App\Http\Requests\SaleCreateRequest;
use App\Http\Requests\SaleUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Sale;
use App\Models\DisabledSale;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{

    use ErrorUtil, UserActivityUtil, BusinessUtil;


    /**
     *
     * @OA\Post(
     *      path="/v1.0/sales",
     *      operationId="createSale",
     *      tags={"sales"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store sales",
     *      description="This method is to store sales",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="date", type="string", format="string", example="date"),
     * @OA\Property(property="total_amount", type="string", format="string", example="total_amount"),
     * @OA\Property(property="customer_id", type="string", format="string", example="customer_id"),
     *      @OA\Property(
     *       property="sale_items",
     *       type="array",
     *       @OA\Items(
     *           type="object",
     *           @OA\Property(property="good_id", type="integer", example=101),
     *           @OA\Property(property="quantity", type="integer", example=3),
     *           @OA\Property(property="price_per_unit", type="number", format="float", *             example=50.00)
     *       )
     *   )
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

    public function createSale(SaleCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('sale_create')) {
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




                $sale =  Sale::create($request_data);

 // Handle Sale Items
 foreach ($request_data['sale_items'] as $item) {
    $sale->salesItems()->create([
        'good_id' => $item['good_id'],
        'quantity' => $item['quantity'],
        'price_per_unit' => $item['price_per_unit'],
        'total_price' => $item['quantity'] * $item['price_per_unit'],
    ]);
}


                return response($sale, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Put(
     *      path="/v1.0/sales",
     *      operationId="updateSale",
     *      tags={"sales"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update sales ",
     *      description="This method is to update sales ",
     *
   * @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *      @OA\Property(property="id", type="number", format="number", example="1"),
 *      @OA\Property(property="date", type="string", format="string", example="2023-01-01"),
 *      @OA\Property(property="total_amount", type="number", format="float", example=250.50),
 *      @OA\Property(property="customer_id", type="number", format="number", example=5),
 *      @OA\Property(property="sale_items", type="array",
 *          @OA\Items(
 *              @OA\Property(property="product_id", type="number", example=1),
 *              @OA\Property(property="quantity", type="number", example=3),
 *              @OA\Property(property="price_per_unit", type="number", example=50),
 *              @OA\Property(property="total_price", type="number", example=150)
 *          )
 *      )
 *     ),
 *  ),
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

    public function updateSale(SaleUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('sale_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                $sale_query_params = [
                    "id" => $request_data["id"],
                ];

                $sale = Sale::where($sale_query_params)->first();

                if ($sale) {
                    $sale->fill(collect($request_data)->only([

                        "date",
                        "total_amount",
                        "customer_id",
                        // "is_default",
                        // "is_active",
                        // "business_id",
                        // "created_by"
                    ])->toArray());
                    $sale->save();
                } else {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

  // Handle sale_items update
  if (isset($request_data['sale_items'])) {
    $sale->salesItems()->delete(); // Remove old items
    foreach ($request_data['sale_items'] as $item) {
        $sale->salesItems()->create($item);
    }
}


                return response($sale, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/sales",
     *      operationId="getSales",
     *      tags={"sales"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *         @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="start_date",
     *         required=true,
     *  example="6"
     *      ),
     *         @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="end_date",
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




     *      summary="This method is to get sales  ",
     *      description="This method is to get sales ",
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

    public function getSales(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('sale_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }



            $sales = Sale::where('sales.business_id', auth()->user()->business_id)





                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('sales.date', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('sales.date', "<=", ($request->end_date . ' 23:59:59'));
                })






                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query;
                    });
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->whereDate('sales.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->whereDate('sales.created_at', "<=", ($request->end_date));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("sales.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("sales.id", "DESC");
                })
                ->when($request->filled("id"), function ($query) use ($request) {
                    return $query
                        ->where("sales.id", $request->input("id"))
                        ->first();
                }, function ($query) {
                    return $query->when(!empty(request()->per_page), function ($query) {
                        return $query->paginate(request()->per_page);
                    }, function ($query) {
                        return $query->get();
                    });
                });

            if ($request->filled("id") && empty($sales)) {
                throw new Exception("No data found", 404);
            }


            return response()->json($sales, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/sales/{ids}",
     *      operationId="deleteSalesByIds",
     *      tags={"sales"},
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
     *      summary="This method is to delete sale by id",
     *      description="This method is to delete sale by id",
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

    public function deleteSalesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('sale_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = Sale::whereIn('id', $idsArray)
                ->where('sales.business_id', auth()->user()->business_id)

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





            Sale::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
