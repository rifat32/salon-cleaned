<?php

namespace App\Http\Controllers;

use App\Http\Requests\GarageSubServicePriceCreateRequest;
use App\Http\Requests\GarageSubServicePriceUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\GarageUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\AutomobileMake;
use App\Models\GarageAutomobileMake;
use App\Models\GarageSubService;
use App\Models\GarageSubServicePrice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GarageServicePriceController extends Controller
{
    use ErrorUtil,GarageUtil,UserActivityUtil;





    /**
     *
     * @OA\Post(
     *      path="/v1.0/garage-sub-service-prices",
     *      operationId="createGarageSubServicePrice",
     *      tags={"garage_sub_service_price_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store garage sub service price",
     *      description="This method is to store garage sub service price",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"garage_id","garage_sub_service_id","garage_sub_service_prices"},
     *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     *    *    @OA\Property(property="garage_sub_service_id", type="number", format="number",example="1"),
     * *    @OA\Property(property="garage_sub_service_prices", type="string", format="array",example={
     *
     * {"automobile_make_id":1,"price":"10"},
     * * {"automobile_make_id":null,"price":"20"},
     *
     *
     *
     *
     *
     *
     *
     * }),
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

    public function createGarageSubServicePrice(GarageSubServicePriceCreateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('garage_service_price_create')) {
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

        $garage_sub_service = GarageSubService::leftJoin('sub_services', 'garage_sub_services.sub_service_id', '=', 'sub_services.id')
        ->leftJoin('services', 'sub_services.service_id', '=', 'services.id')
        ->where([
            "garage_sub_services.id" => $insertableData["garage_sub_service_id"]
        ])
        ->select(

            "garage_sub_services.id",
            "services.automobile_category_id",

        )
        ->first();


                    foreach($insertableData["garage_sub_service_prices"] as $index=>$price_details){



                        $garage_make = NULL;
                        if(!empty($price_details["automobile_make_id"])){

                            $garage_make =  GarageAutomobileMake::
                            leftJoin('automobile_makes', 'garage_automobile_makes.automobile_make_id', '=', 'automobile_makes.id')
                            ->where([
                              "garage_automobile_makes.garage_id" => $insertableData["garage_id"],
                              "garage_automobile_makes.automobile_make_id" => $price_details["automobile_make_id"],
                              "automobile_makes.automobile_category_id" =>  $garage_sub_service->automobile_category_id,
                              ])
                              ->select(
                                  "garage_automobile_makes.id",
                                  "garage_automobile_makes.automobile_make_id"
                                  )
                              ->first();

                          if(!$garage_make) {

                              $error =  [
                                "message" => "The given data was invalid.",
                                "errors" => [("garage_sub_service_prices[".$index."]".".automobile_make_id")=>["invalid automobile make id"]]
                         ];
                            throw new Exception(json_encode($error),422);

                          }

                        }



GarageSubServicePrice::create([
    "garage_sub_service_id" => $garage_sub_service->id,
    "automobile_make_id" => (!empty($garage_make)?$garage_make->automobile_make_id:NULL),
    "price" => $price_details["price"],
    "business_id" => $insertableData["garage_id"],
    "expert_id" => $insertableData["expert_id"]
]);


                    }





                return response(["ok" => true], 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/garage-service-prices",
     *      operationId="updateGarageSubServicePrice",
     *      tags={"garage_sub_service_price_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update garage sub service price",
     *      description="This method is to update garage sub service price",
     *
    *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"garage_id","garage_sub_service_id","garage_sub_service_prices"},
     *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     *    *    @OA\Property(property="garage_sub_service_id", type="number", format="number",example="1"),
     * *    @OA\Property(property="garage_sub_service_prices", type="string", format="array",example={
     *
     * {"id":1,"automobile_make_id":1,"price":"10"},
     * {"id":2,"automobile_make_id":null,"price":"20"},
     *
     *
     *
     *
     *
     *
     *
     * }),
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

    public function updateGarageSubServicePrice(GarageSubServicePriceUpdateRequest $request)
    {

        try {
            $this->storeActivity($request,"");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('garage_service_price_update')) {
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

                $garage_sub_service = GarageSubService::
                leftJoin('sub_services', 'garage_sub_services.sub_service_id', '=', 'sub_services.id')
                ->leftJoin('services', 'sub_services.service_id', '=', 'services.id')
                ->where([
                    "garage_sub_services.id" => $updatableData["garage_sub_service_id"]
                ])

                ->select(

                    "garage_sub_services.id",
                    "services.automobile_category_id",

                )
                ->first();

                if(!$garage_sub_service){
          return response()->json([
            "message" => "no garage sub service found"
           ],404);
                }


                            foreach($updatableData["garage_sub_service_prices"] as $index=>$price_details){


                                $garage_make = NULL;
                                if(!empty($price_details["automobile_make_id"])){
                                    $garage_make =  GarageAutomobileMake::
                                    leftJoin('automobile_makes', 'garage_automobile_makes.automobile_make_id', '=', 'automobile_makes.id')
                                    ->where([
                                      "garage_automobile_makes.garage_id" => $updatableData["garage_id"],
                                      "garage_automobile_makes.automobile_make_id" => $price_details["automobile_make_id"],
                                      "automobile_makes.automobile_category_id" =>  $garage_sub_service->automobile_category_id,
                                      ])
                                      ->select(
                                          "garage_automobile_makes.id",
                                          "garage_automobile_makes.automobile_make_id"
                                          )
                                      ->first();

                                      if(!$garage_make) {

                                        $error =  [
                                            "message" => "The given data was invalid.",
                                            "errors" => [("garage_sub_service_prices[".$index."]".".automobile_make_id")=>["invalid automobile make id"]]
                                     ];
                                        throw new Exception(json_encode($error),422);

                                    }
                                }

        $updatableCustomisedData = [
            "garage_sub_service_id" => $garage_sub_service->id,
            "automobile_make_id" => (!empty($garage_make)?$garage_make->automobile_make_id:NULL),
            "price" => $price_details["price"],
            "business_id" => $updatableData["garage_id"],
            "expert_id" => $updatableData["expert_id"]
        ];
        if(!empty($price_details["id"])){
            $garage_sub_service_price  =  tap(GarageSubServicePrice::where(["id" => $price_details["id"]]))->update(
                collect($updatableCustomisedData)->only([
                "garage_sub_service_id",
                "automobile_make_id",
                "price",
                "expert_id"
                ])->toArray()
            )
                // ->with("somthing")

                ->first();

                if(!$garage_sub_service_price) {


     $error =  [
        "message" => "The given data was invalid.",
        "errors" => [("garage_sub_service_prices[".$index."]".".automobile_make_id")=>["price id not found"]]
 ];
    throw new Exception(json_encode($error),422);
                }
        }
        else {
            $garage_sub_service_price  =  GarageSubServicePrice::create($updatableCustomisedData);
        }







                            }






                return response(["ok" => true], 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }








    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/garage-service-prices/{id}",
     *      operationId="deleteGarageSubServicePriceById",
     *      tags={"garage_sub_service_price_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *

     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to delete garage sub service price by id",
     *      description="This method is to delete garage sub service price by id",
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



    public function deleteGarageSubServicePriceById($id, Request $request)
    {

        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('garage_service_price_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

        $garage_sub_service_price =  GarageSubServicePrice::
            leftJoin('garage_sub_services', 'garage_sub_service_prices.garage_sub_service_id', '=', 'garage_sub_services.id')
            ->leftJoin('garage_services', 'garage_sub_services.garage_service_id', '=', 'garage_services.id')
            ->where([
                "garage_sub_service_prices.id" => $id
            ])
            ->select(
                "garage_sub_service_prices.id",
                "garage_services.garage_id"
                )
                ->first();
            if (!$this->garageOwnerCheck($garage_sub_service_price->garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }

            $garage_sub_service_price->delete();






            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }


     /**
     *
     *     @OA\Delete(
     *      path="/v1.0/garage-service-prices/by-garage-sub-service/{id}",
     *      operationId="deleteGarageSubServicePriceByGarageSubServiceId",
     *      tags={"garage_sub_service_price_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *

     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to delete garage sub service price by garage sub service id",
     *      description="This method is to delete garage sub service price by garage sub service id",
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

    public function deleteGarageSubServicePriceByGarageSubServiceId($id, Request $request)
    {

        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('garage_service_price_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

        $garage_sub_service =  GarageSubService::

            leftJoin('garage_services', 'garage_sub_services.garage_service_id', '=', 'garage_services.id')
            ->where([
                "garage_sub_services.id" => $id
            ])
            ->select(
                "garage_sub_services.id",
                "garage_services.garage_id"
                )
                ->first();
            if (!$this->garageOwnerCheck($garage_sub_service->garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }

         GarageSubServicePrice::where(
            [
               "garage_sub_service_id" => $garage_sub_service->id
            ]
         )
         ->delete();






            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }
}
