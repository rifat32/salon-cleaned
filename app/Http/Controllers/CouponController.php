<?php

namespace App\Http\Controllers;

use App\Http\Requests\CouponCreateRequest;
use App\Http\Requests\CouponUpdateRequest;
use App\Http\Requests\GarageOwnerToggleOptionsRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\GarageUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Coupon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    use ErrorUtil,GarageUtil,UserActivityUtil;

    /**
     *
     * @OA\Post(
     *      path="/v1.0/coupons",
     *      operationId="createCoupon",
     *      tags={"coupon_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store coupon",
     *      description="This method is to store coupon",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"garage_id","name","code","discount_type","discount_amount","min_total", "max_total","redemptions","coupon_start_date","coupon_end_date","is_auto_apply","is_active"},
     *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     *    @OA\Property(property="name", type="string", format="string",example="name"),
     *    @OA\Property(property="code", type="string", format="string",example="tttdddsss"),
     * *    @OA\Property(property="discount_type", type="string", format="string",example="percentage"),
     * *    @OA\Property(property="discount_amount", type="number", format="number",example="10"),
     *    * *    @OA\Property(property="min_total", type="number", format="number",example="10"),
     *    * *    @OA\Property(property="max_total", type="number", format="number",example="30"),
     *    * *    @OA\Property(property="redemptions", type="number", format="number",example="10"),
     *    * *    @OA\Property(property="coupon_start_date", type="string", format="string",example="2019-06-29"),
     *    * *    @OA\Property(property="coupon_end_date", type="string", format="string",example="2019-06-29"),
     *    * *    @OA\Property(property="is_auto_apply", type="boolean", format="boolean",example="1"),
     *  *    * *    @OA\Property(property="is_active", type="boolean", format="boolean",example="1"),
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

    public function createCoupon(CouponCreateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('coupon_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $insertableData = $request->validated();
                if (!$this->garageOwnerCheck($insertableData["garage_id"])) {
                    return response()->json([
                        "message" => "you are not the owner of the garage or the requested garage does not exist."
                    ], 401);
                }

                // if(empty($insertableData["code"])) {
                //     $insertableData["code"] =
                // }

                $code_exists = Coupon::where([
                    "garage_id" =>$insertableData["garage_id"],
                    "code" => $insertableData["code"]
                ])->first();

              if ($code_exists) {
                        $error =  [
                            "message" => "The given data was invalid.",
                            "errors" => ["code"=>["This code is already taken"]]
                     ];
                        throw new Exception(json_encode($error),422);

                    }


                $coupon =  Coupon::create($insertableData);


                return response($coupon, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/coupons",
     *      operationId="updateCoupon",
     *      tags={"coupon_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update coupons",
     *      description="This method is to update coupons",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","garage_id","name","code","discount_type","discount_amount","min_total", "max_total","redemptions","coupon_start_date","coupon_end_date","is_auto_apply","is_active"},
     *  *    @OA\Property(property="id", type="number", format="number",example="1"),
     *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     *    @OA\Property(property="name", type="string", format="string",example="name"),
     *    @OA\Property(property="code", type="string", format="string",example="tttdddsss"),
     * *    @OA\Property(property="discount_type", type="string", format="string",example="percentage"),
     * *    @OA\Property(property="discount_amount", type="number", format="number",example="10"),
     *    * *    @OA\Property(property="min_total", type="number", format="number",example="10"),
     *    * *    @OA\Property(property="max_total", type="number", format="number",example="30"),
     *    * *    @OA\Property(property="redemptions", type="number", format="number",example="10"),
     *    * *    @OA\Property(property="coupon_start_date", type="string", format="string",example="2019-06-29"),
     *    * *    @OA\Property(property="coupon_end_date", type="string", format="string",example="2019-06-29"),
     *    * *    @OA\Property(property="is_auto_apply", type="boolean", format="boolean",example="1"),
     *  *    * *    @OA\Property(property="is_active", type="boolean", format="boolean",example="1"),
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

    public function updateCoupon(CouponUpdateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('coupon_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $updatableData = $request->validated();


                if (!$this->garageOwnerCheck($updatableData["garage_id"])) {
                    return response()->json([
                        "message" => "you are not the owner of the garage or the requested garage does not exist."
                    ], 401);
                }
                $code_exists = Coupon::where([
                    "garage_id" =>$updatableData["garage_id"],
                    "code" => $updatableData["code"]
                ])
                ->where('id', '<>',$updatableData["id"])
                ->first();

              if ($code_exists) {
                        $error =  [
                            "message" => "The given data was invalid.",
                            "errors" => ["code"=>["This code is already taken"]]
                     ];
                        throw new Exception(json_encode($error),422);

                    }

                $coupon  =  tap(Coupon::where(["id" => $updatableData["id"]]))->update(
                    collect($updatableData)->only([
                        "garage_id",
                        "name",
                        "code",
                        "discount_type",
                        "discount_amount",
                        "min_total",
                        "max_total",
                        "redemptions",
                        "coupon_start_date",
                        "coupon_end_date",
                        "is_auto_apply",
                        "is_active",
                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();

                    if(!$coupon) {
                        return response()->json([
                            "message" => "no coupon found"
                            ],404);

                }
                return response($coupon, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }

   /**
        *
     * @OA\Put(
     *      path="/v1.0/coupons/toggle-active",
     *      operationId="toggleActiveCoupon",
     *      tags={"garage_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle coupon",
     *      description="This method is to toggle coupon",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *  *           @OA\Property(property="garage_id", type="string", format="number",example="1"),
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

     public function toggleActiveCoupon(GarageOwnerToggleOptionsRequest $request)
     {

         try{
             $this->storeActivity($request,"");
             if(!$request->user()->hasPermissionTo('coupon_update')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }
            $updatableData = $request->validated();

            if (!$this->garageOwnerCheck($updatableData["garage_id"])) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }



            $coupon =  Coupon::where([
                "garage_id"=> $updatableData["garage_id"],
                "id"=> $updatableData["id"]
            ])
            ->first();



            $coupon->update([
                'is_active' => !$coupon->is_active
            ]);

            return response()->json(['message' => 'coupon status updated successfully'], 200);


         } catch(Exception $e){
             error_log($e->getMessage());
         return $this->sendError($e,500,$request);
         }
     }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/coupons/{garage_id}/{perPage}",
     *      operationId="getCoupons",
     *      tags={"coupon_management"},
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
     *              @OA\Parameter(
     *         name="perPage",
     *         in="path",
     *         description="perPage",
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
     *      summary="This method is to get coupons ",
     *      description="This method is to get coupons",
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

    public function getCoupons($garage_id,$perPage, Request $request)
    {
        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('coupon_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            if (!$this->garageOwnerCheck($garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }

            $couponQuery = Coupon::where([
                "garage_id" => $garage_id
            ]);

            if (!empty($request->search_key)) {
                $couponQuery = $couponQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                    $query->orWhere("code", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $couponQuery = $couponQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $couponQuery = $couponQuery->where('created_at', "<=", $request->end_date);
            }

            $couponQuery = $couponQuery->orderByDesc("id");

            if($perPage == '0') {
                $coupons = $couponQuery->get();
            }else {
                $coupons = $couponQuery->paginate($perPage);

            }




            return response()->json($coupons, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }

     /**
     *
     * @OA\Get(
     *      path="/v1.0/coupons/single/{garage_id}/{id}",
     *      operationId="getCouponById",
     *      tags={"coupon_management"},
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
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get coupon by id ",
     *      description="This method is to get coupon by id ",
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

    public function getCouponById($garage_id,$id, Request $request)
    {
        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('coupon_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            if (!$this->garageOwnerCheck($garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }

            $coupon = Coupon::where([
                "garage_id" => $garage_id,
                "id" => $id
            ])
            ->first();

            if(!$coupon) {
                 return response()->json([
                    "message" => "coupon not found"
                 ],404);
            }


            return response()->json($coupon, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/coupons/{garage_id}/{id}",
     *      operationId="deleteCouponById",
     *      tags={"coupon_management"},
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
     *  *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to delete coupon by id",
     *      description="This method is to delete coupon by id",
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

    public function deleteCouponById($garage_id,$id, Request $request)
    {

        try {

            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('coupon_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           if (!$this->garageOwnerCheck($garage_id)) {
            return response()->json([
                "message" => "you are not the owner of the garage or the requested garage does not exist."
            ], 401);
        }



            $coupon = Coupon::where([
                 "garage_id" => $garage_id,
                "id" => $id
            ])
            ->first();
             if(!$coupon){
                return response()->json([
            "message" => "coupon not found"
                ], 404);
            }

            $coupon->delete();




            return response()->json(["ok" => true], 200);

        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }
}
