<?php





namespace App\Http\Controllers;

use App\Http\Requests\GoodCreateRequest;
use App\Http\Requests\GoodUpdateRequest;
use App\Http\Requests\GetIdRequest;

use App\Http\Requests\LinkSubServicesToGoodRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Good;
use App\Models\ServiceGood;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodController extends Controller
{

    use ErrorUtil, UserActivityUtil, BusinessUtil;


    /**
     *
     * @OA\Post(
     *      path="/v1.0/goods",
     *      operationId="createGood",
     *      tags={"goods"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store goods",
     *      description="This method is to store goods",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="name", type="string", format="string", example="name"),
     * @OA\Property(property="sku", type="string", format="string", example="sku"),
     * @OA\Property(property="product_category_id", type="string", format="string", example="product_category_id"),
     * @OA\Property(property="preferred_supplier_id", type="string", format="string", example="preferred_supplier_id"),
     * @OA\Property(property="cost_price", type="string", format="string", example="cost_price"),
     * @OA\Property(property="retail_price", type="string", format="string", example="retail_price"),
     * @OA\Property(property="barcode", type="string", format="string", example="barcode"),
     * @OA\Property(property="current_stock", type="string", format="string", example="current_stock"),
     * @OA\Property(property="min_stock_level", type="string", format="string", example="min_stock_level"),
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

    public function createGood(GoodCreateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('good_create')) {
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

                $good =  Good::create($request_data);

                return response($good, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
 *
 * @OA\Post(
 *      path="/v1.0/goods/sub-services",
 *      operationId="linkSubServicesToGood",
 *      tags={"goods"},
 *      security={
 *          {"bearerAuth": {}}
 *      },
 *      summary="This method links products to sub-services",
 *      description="This method links all sub-services to a product and removes previous links.",
 *
 *  @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="good_id", type="integer", example=1, description="ID of the product (good) to link sub-services to"),
 *             @OA\Property(property="sub_service_ids", type="array", items={
 *                 @OA\Property(type="integer", example=1)
 *             }, description="Array of sub-service IDs to link with the product")
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
public function linkSubServicesToGood(LinkSubServicesToGoodRequest $request)
{
    try {
        // Start transaction
        DB::beginTransaction();

        // Check permission
        if (!auth()->user()->hasPermissionTo('link_sub_services')) {
            return response()->json([
                "message" => "You do not have permission to perform this action."
            ], 401);
        }

        // Get the good_id and validate the good exists
        $good_id = $request->good_id;
        $good = Good::find($good_id);

        if (!$good) {
            return response()->json([
                "message" => "Good not found."
            ], 404);
        }

        // Detach previous sub-service links
        $good->subServices()->detach();

        // Link new sub-services
        $good->subServices()->attach($request->sub_service_ids);

        // Commit transaction
        DB::commit();

        return response()->json([
            "message" => "Sub-services linked successfully."
        ], 200);
    } catch (Exception $e) {
        // Rollback transaction in case of error
        DB::rollBack();

        return $this->sendError($e, 500, $request);
    }
}

    /**
     *
     * @OA\Put(
     *      path="/v1.0/goods",
     *      operationId="updateGood",
     *      tags={"goods"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update goods ",
     *      description="This method is to update goods ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="1"),
     * @OA\Property(property="name", type="string", format="string", example="name"),
     * @OA\Property(property="sku", type="string", format="string", example="sku"),
     * @OA\Property(property="product_category_id", type="string", format="string", example="product_category_id"),
     * @OA\Property(property="preferred_supplier_id", type="string", format="string", example="preferred_supplier_id"),
     * @OA\Property(property="cost_price", type="string", format="string", example="cost_price"),
     * @OA\Property(property="retail_price", type="string", format="string", example="retail_price"),
     * @OA\Property(property="barcode", type="string", format="string", example="barcode"),
     * @OA\Property(property="current_stock", type="string", format="string", example="current_stock"),
     * @OA\Property(property="min_stock_level", type="string", format="string", example="min_stock_level"),
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

    public function updateGood(GoodUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!auth()->user()->hasPermissionTo('good_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                $good_query_params = [
                    "id" => $request_data["id"],
                ];

                $good = Good::where($good_query_params)->first();

                if ($good) {
                    $good->fill(collect($request_data)->only([

                        "name",
                        "sku",
                        "product_category_id",
                        "preferred_supplier_id",
                        "cost_price",
                        "retail_price",
                        "barcode",
                        "current_stock",
                        "min_stock_level",
                        // "is_default",
                        // "is_active",
                        // "business_id",
                        // "created_by"
                    ])->toArray());
                    $good->save();
                } else {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }




                return response($good, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/goods/toggle-active",
     *      operationId="toggleActiveGood",
     *      tags={"goods"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle goods",
     *      description="This method is to toggle goods",
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

    public function toggleActiveGood(GetIdRequest $request)
    {

        try {

            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('good_activate')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $good =  Good::where([
                "id" => $request_data["id"],
            ])
                ->first();
            if (!$good) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            $good->update([
                'is_active' => !$good->is_active
            ]);




            return response()->json(['message' => 'good status updated successfully'], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/goods",
     *      operationId="getGoods",
     *      tags={"goods"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *         @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="name",
     *         required=true,
     *  example="6"
     *      ),



     *         @OA\Parameter(
     *         name="sku",
     *         in="query",
     *         description="sku",
     *         required=true,
     *  example="6"
     *      ),







     *         @OA\Parameter(
     *         name="barcode",
     *         in="query",
     *         description="barcode",
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




     *      summary="This method is to get goods  ",
     *      description="This method is to get goods ",
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

    public function getGoods(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('good_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }



            $goods = Good::where('goods.business_id', auth()->user()->business_id)





                ->when(!empty($request->name), function ($query) use ($request) {
                    return $query->where('goods.name', $request->name);
                })




                ->when(!empty($request->sku), function ($query) use ($request) {
                    return $query->where('goods.sku', $request->sku);
                })








                ->when(!empty($request->barcode), function ($query) use ($request) {
                    return $query->where('goods.barcode', $request->barcode);
                })






                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query

                            ->orWhere("goods.name", "like", "%" . $term . "%")
                            ->where("goods.sku", "like", "%" . $term . "%")
                            ->orWhere("goods.barcode", "like", "%" . $term . "%")
                        ;
                    });
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->whereDate('goods.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->whereDate('goods.created_at', "<=", ($request->end_date));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("goods.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("goods.id", "DESC");
                })
                ->when($request->filled("id"), function ($query) use ($request) {
                    return $query
                        ->where("goods.id", $request->input("id"))
                        ->first();
                }, function ($query) {
                    return $query->when(!empty(request()->per_page), function ($query) {
                        return $query->paginate(request()->per_page);
                    }, function ($query) {
                        return $query->get();
                    });
                });

            if ($request->filled("id") && empty($goods)) {
                throw new Exception("No data found", 404);
            }


            return response()->json($goods, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/goods/{ids}",
     *      operationId="deleteGoodsByIds",
     *      tags={"goods"},
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
     *      summary="This method is to delete good by id",
     *      description="This method is to delete good by id",
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

    public function deleteGoodsByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('good_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = Good::whereIn('id', $idsArray)
                ->where('goods.business_id', auth()->user()->business_id)

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





            Good::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
 *
 * @OA\Get(
 *      path="/v1.0/linked-items/good-sub-services",
 *      operationId="getLinkedGoodSubServices",
 *      tags={"goods", "sub-services"},
 *      security={
 *          {"bearerAuth": {}}
 *      },
 *      summary="This method retrieves linked goods or sub-services based on the provided query parameter",
 *      description="This method returns the linked sub-services for a good or the linked goods for a sub-service.",
 *
 *  @OA\Parameter(
 *      name="good_id",
 *      in="query",
 *      description="ID of the good to retrieve linked sub-services",
 *      required=false,
 *      @OA\Schema(type="integer")
 *  ),
 *  @OA\Parameter(
 *      name="sub_service_id",
 *      in="query",
 *      description="ID of the sub-service to retrieve linked goods",
 *      required=false,
 *      @OA\Schema(type="integer")
 *  ),
 *  @OA\Response(
 *          response=200,
 *          description="Successful operation",
 *          @OA\JsonContent(type="array", @OA\Items(type="object")),
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
 *          response=404,
 *          description="Not Found",
 *          @OA\JsonContent()
 *      )
 * )
 */
public function getLinkedGoodSubServices(Request $request)
{
    try {
        // Start building the query for ServiceGood
        $linked_items = ServiceGood::with('good', 'subService')
        ->when(request()->has('good_id'), function ($query) {
            return $query->where('good_id', request()->input("good_id"));
        })
        ->when(request()->has('sub_service_id'), function ($query)  {
            return $query->where('sub_service_id', request()->input("sub_service_id"));
        })
        ->get();



        // Return the linked items as a JSON response
        return response()->json($linked_items, 200);

    } catch (Exception $e) {
        return $this->sendError($e, 500, $request);
    }
}
}
