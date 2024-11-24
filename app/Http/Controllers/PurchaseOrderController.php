<?php





namespace App\Http\Controllers;

use App\Http\Requests\PurchaseOrderCreateRequest;
use App\Http\Requests\PurchaseOrderUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\PurchaseOrder;
use App\Models\DisabledPurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{

    use ErrorUtil, UserActivityUtil, BusinessUtil;


/**
 * @OA\Post(
 *      path="/v1.0/purchase-orders",
 *      operationId="createPurchaseOrder",
 *      tags={"purchase_orders"},
 *      security={
 *           {"bearerAuth": {}}
 *      },
 *      summary="This method is to store purchase orders",
 *      description="This method is to store purchase orders",
 *      @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="supplier_id", type="string", example="supplier_id"),
 *             @OA\Property(property="order_date", type="string", example="order_date"),
 *             @OA\Property(property="status", type="string", example="status"),
 *             @OA\Property(property="total_amount", type="string", example="total_amount"),
 *             @OA\Property(property="received_date", type="string", example="received_date"),
 *             @OA\Property(
 *                 property="purchase_items",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="good_id", type="integer", example="1"),
 *                     @OA\Property(property="quantity", type="integer", example="10"),
 *                     @OA\Property(property="cost_per_unit", type="number", format="float", example="15.5")
 *                 )
 *             )
 *         ),
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *          @OA\JsonContent(),
 *      ),
 *
 * )
 */


    public function createPurchaseOrder(PurchaseOrderCreateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('purchase_order_create')) {
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


                $purchase_order =  PurchaseOrder::create($request_data);


                  // Add purchase items (purchase_order_goods)
            foreach ($request_data['purchase_items'] as $item) {
                $purchase_order->goods()->create([
                    'good_id' => $item['good_id'],
                    'quantity' => $item['quantity'],
                    'cost_per_unit' => $item['cost_per_unit'],
                ]);
            }


                return response($purchase_order, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }





    /**
     *
     * @OA\Put(
     *      path="/v1.0/purchase-orders",
     *      operationId="updatePurchaseOrder",
     *      tags={"purchase_orders"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update purchase orders ",
     *      description="This method is to update purchase orders ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="1"),
     * @OA\Property(property="supplier_id", type="string", format="string", example="supplier_id"),
     * @OA\Property(property="order_date", type="string", format="string", example="order_date"),
     * @OA\Property(property="status", type="string", format="string", example="status"),
     * @OA\Property(property="total_amount", type="string", format="string", example="total_amount"),
     * @OA\Property(property="received_date", type="string", format="string", example="received_date"),
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

    public function updatePurchaseOrder(PurchaseOrderUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('purchase_order_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                $purchase_order_query_params = [
                    "id" => $request_data["id"],
                ];

                $purchase_order = PurchaseOrder::where($purchase_order_query_params)->first();

                if ($purchase_order) {
                    $purchase_order->fill(collect($request_data)->only([
                        "supplier_id",
                        "order_date",
                        "status",
                        "total_amount",
                        "received_date",
                        // "is_default",
                        // "is_active",
                        // "business_id",
                        // "created_by"
                    ])->toArray());
                    $purchase_order->save();
                } else {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                foreach ($request_data['items'] as $item) {
                    // You may need to create or update purchase order items here
                    PurchaseOrderItem::updateOrCreate(
                        ['purchase_order_id' => $purchase_order->id, 'product_id' => $item['product_id']],
                        ['quantity' => $item['quantity'], 'price' => $item['price']]
                    );
                }



                return response($purchase_order, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/purchase-orders",
     *      operationId="getPurchaseOrders",
     *      tags={"purchase_orders"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *         @OA\Parameter(
     *         name="start_order_date",
     *         in="query",
     *         description="start_order_date",
     *         required=true,
     *  example="6"
     *      ),
     *         @OA\Parameter(
     *         name="end_order_date",
     *         in="query",
     *         description="end_order_date",
     *         required=true,
     *  example="6"
     *      ),



     *         @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="status",
     *         required=true,
     *  example="6"
     *      ),




     *         @OA\Parameter(
     *         name="start_received_date",
     *         in="query",
     *         description="start_received_date",
     *         required=true,
     *  example="6"
     *      ),
     *         @OA\Parameter(
     *         name="end_received_date",
     *         in="query",
     *         description="end_received_date",
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




     *      summary="This method is to get purchase orders  ",
     *      description="This method is to get purchase orders ",
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

    public function getPurchaseOrders(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('purchase_order_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }



            $purchase_orders = PurchaseOrder::where('purchase_orders.business_id', auth()->user()->business_id)






                ->when(!empty($request->start_order_date), function ($query) use ($request) {
                    return $query->where('purchase_orders.order_date', ">=", $request->start_order_date);
                })
                ->when(!empty($request->end_order_date), function ($query) use ($request) {
                    return $query->where('purchase_orders.order_date', "<=", ($request->end_order_date . ' 23:59:59'));
                })




                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('purchase_orders.status', $request->status);
                })





                ->when(!empty($request->start_received_date), function ($query) use ($request) {
                    return $query->where('purchase_orders.received_date', ">=", $request->start_received_date);
                })
                ->when(!empty($request->end_received_date), function ($query) use ($request) {
                    return $query->where('purchase_orders.received_date', "<=", ($request->end_received_date . ' 23:59:59'));
                })




                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query

                            ->orWhere("purchase_orders.status", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->whereDate('purchase_orders.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->whereDate('purchase_orders.created_at', "<=", ($request->end_date));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("purchase_orders.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("purchase_orders.id", "DESC");
                })
                ->when($request->filled("id"), function ($query) use ($request) {
                    return $query
                        ->where("purchase_orders.id", $request->input("id"))
                        ->first();
                }, function ($query) {
                    return $query->when(!empty(request()->per_page), function ($query) {
                        return $query->paginate(request()->per_page);
                    }, function ($query) {
                        return $query->get();
                    });
                });

            if ($request->filled("id") && empty($purchase_orders)) {
                throw new Exception("No data found", 404);
            }


            return response()->json($purchase_orders, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/purchase-orders/{ids}",
     *      operationId="deletePurchaseOrdersByIds",
     *      tags={"purchase_orders"},
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
     *      summary="This method is to delete purchase order by id",
     *      description="This method is to delete purchase order by id",
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

    public function deletePurchaseOrdersByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('purchase_order_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = PurchaseOrder::whereIn('id', $idsArray)
                ->where('purchase_orders.business_id', auth()->user()->business_id)

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





            PurchaseOrder::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
