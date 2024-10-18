<?php

namespace App\Http\Controllers;

use App\Http\Requests\GarageOwnerToggleOptionsRequest;
use App\Http\Requests\GaragePackageCreateRequest;
use App\Http\Requests\GaragePackageRequest;
use App\Http\Requests\GaragePackageUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\GarageUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\GaragePackage;
use App\Models\GaragePackageSubService;
use App\Models\GarageSubService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GaragePackageController extends Controller
{
     use ErrorUtil,GarageUtil,UserActivityUtil;



  /**
     *
     * @OA\Post(
     *      path="/v1.0/garage-packages",
     *      operationId="createGaragePackage",
     *      tags={"garage_package_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store garage packages",
     *      description="This method is to store garage packages",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"garage_id","name","description","price"},
     *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     * *    @OA\Property(property="name", type="string", format="string",example="name"),
     * * *    @OA\Property(property="description", type="string", format="string",example="description"),
     *   * * *    @OA\Property(property="price", type="number", format="number",example="10.99"),
    *  * *    @OA\Property(property="sub_service_ids", type="string", format="array",example={1,2,3,4}),
     *
     *  *   * * *    @OA\Property(property="is_active", type="number", format="number",example="1"),
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

    public function createGaragePackage(GaragePackageCreateRequest $request)
    {


        try {
            $this->storeActivity($request,"");

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('garage_package_create')) {
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


                    $garage_package = GaragePackage::create($insertableData);


                    foreach ($insertableData["sub_service_ids"] as $index=>$sub_service_id) {
                        $garage_sub_service =  GarageSubService::leftJoin('garage_services', 'garage_sub_services.garage_service_id', '=', 'garage_services.id')

                            ->where([
                                "garage_services.garage_id" => $insertableData["garage_id"],
                                "garage_sub_services.sub_service_id" => $sub_service_id,

                            ])

                            ->select(
                                "garage_sub_services.id",
                                "garage_sub_services.sub_service_id",
                                "garage_sub_services.garage_service_id"
                            )
                            ->first();

                        if (!$garage_sub_service) {
                            $error =  [
                                "message" => "The given data was invalid.",
                                "errors" => [("sub_service_ids[".$index."]")=>["invalid service"]]
                         ];
                            throw new Exception(json_encode($error),422);
                        }

                        GaragePackageSubService::create([
                            "sub_service_id" => $garage_sub_service->sub_service_id,
                            "garage_package_id" => $garage_package->id,
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
     *      path="/v1.0/garage-packages",
     *      operationId="updateGaragePackage",
     *      tags={"garage_package_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update garage package",
     *      description="This method is to update garage package",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","garage_id","name","description","price"},
     *     *    @OA\Property(property="id", type="number", format="number",example="1"),
     *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     * *    @OA\Property(property="name", type="string", format="string",example="name"),
     * * *    @OA\Property(property="description", type="string", format="string",example="description"),
     *   * * *    @OA\Property(property="price", type="number", format="number",example="10.99"),
    *  * *    @OA\Property(property="sub_service_ids", type="string", format="array",example={1,2,3,4}),
     *
     *  *   * * *    @OA\Property(property="is_active", type="number", format="number",example="1"),
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






    public function updateGaragePackage(GaragePackageUpdateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return  DB::transaction(function () use ($request) {


                if (!$request->user()->hasPermissionTo('garage_package_update')) {
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






                $garage_package  =  tap(GaragePackage::where(["id" => $updatableData["id"]]))->update(
                    collect($updatableData)->only([
                        "name",
                        "description",
                        "price",
                        "garage_id",
                        "is_active"
                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();

                if (!$garage_package) {
                    return response()->json([
                        "message" => "garage package not found"
                    ], 404);
                }
                GaragePackageSubService::where([
                    "garage_package_id" => $garage_package->id
                ])
                ->delete();


                foreach ($updatableData["sub_service_ids"] as $sub_service_id) {
                    $garage_sub_service =  GarageSubService::where([
                            "garage_sub_services.sub_service_id" => $sub_service_id
                        ])
                        ->select(
                            "garage_sub_services.id",
                            "garage_sub_services.sub_service_id",
                            "garage_sub_services.garage_service_id"
                        )
                        ->first();

                    if (!$garage_sub_service) {
                        throw new Exception("invalid service");
                    }


                    GaragePackageSubService::create([

                        "sub_service_id" => $garage_sub_service->sub_service_id,
                        "garage_package_id" => $garage_package->id,

                    ]);

                }


                return response($garage_package, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }


   /**
        *
     * @OA\Put(
     *      path="/v1.0/garage-packages/toggle-active",
     *      operationId="toggleActiveGaragePackage",
     *      tags={"garage_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle Garage Package",
     *      description="This method is to toggle Garage Package",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *  *           @OA\Property(property="garage_id", type="string", format="number",example="1"),
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

     public function toggleActiveGaragePackage(GarageOwnerToggleOptionsRequest $request)
     {

         try{
             $this->storeActivity($request,"");
             if(!$request->user()->hasPermissionTo('garage_package_update')){
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



            $garage_package =  GaragePackage::where([
                "garage_id"=> $updatableData["garage_id"],
                "id"=> $updatableData["id"]
            ])
            ->first();



            $garage_package->update([
                'is_active' => !$garage_package->is_active
            ]);




            return response()->json(['message' => 'garage package status updated successfully'], 200);




         } catch(Exception $e){
             error_log($e->getMessage());
         return $this->sendError($e,500,$request);
         }
     }




 /**
        *
     * @OA\Get(
     *      path="/v1.0/garage-packages/{garage_id}/{perPage}",
     *      operationId="getGaragePackages",
     *      tags={"garage_package_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="6"
     *      ),
     *  *              @OA\Parameter(
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
     *      summary="This method is to get  garage packages ",
     *      description="This method is to get garage packages",
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

    public function getGaragePackages($garage_id,$perPage,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('garage_package_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            if (!$this->garageOwnerCheck($garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }


            $garagePackageQuery = GaragePackage::with("garage_package_sub_services.sub_service")
            ->where([
                "garage_id" => $garage_id
            ]);

            if(!empty($request->search_key)) {
                $garagePackageQuery = $garagePackageQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $garagePackageQuery = $garagePackageQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $garagePackageQuery = $garagePackageQuery->where('created_at', "<=", $request->end_date);
            }
            $garages = $garagePackageQuery->orderByDesc("id")->paginate($perPage);
            return response()->json($garages, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }
 /**
        *
     * @OA\Get(
     *      path="/v1.0/garage-packages/get/all/{garage_id}",
     *      operationId="getGaragePackagesAll",
     *      tags={"basics"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
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
     *      summary="This method is to get  garage packages all ",
     *      description="This method is to get garage packages all",
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

    public function getGaragePackagesAll($garage_id,Request $request) {
        try{
            $this->storeActivity($request,"");
        //     if(!$request->user()->hasPermissionTo('garage_package_view')){
        //         return response()->json([
        //            "message" => "You can not perform this action"
        //         ],401);
        //    }
        //     if (!$this->garageOwnerCheck($garage_id)) {
        //         return response()->json([
        //             "message" => "you are not the owner of the garage or the requested garage does not exist."
        //         ], 401);
        //     }


            $garagePackageQuery = GaragePackage::with("garage_package_sub_services.sub_service")
            ->where([
                "garage_id" => $garage_id
            ])
            ->where('is_active', true);

            if(!empty($request->search_key)) {
                $garagePackageQuery = $garagePackageQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $garagePackageQuery = $garagePackageQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $garagePackageQuery = $garagePackageQuery->where('created_at', "<=", $request->end_date);
            }
            $garages = $garagePackageQuery->orderByDesc("id")->get();
            return response()->json($garages, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }


     /**
        *
     * @OA\Get(
     *      path="/v1.0/garage-packages/single/{garage_id}/{id}",
     *      operationId="getGaragePackageById",
     *      tags={"garage_package_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
 *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="6"
     *      ),
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to  get garage package by id",
     *      description="This method is to get garage package by id",
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

    public function getGaragePackageById($garage_id,$id,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('garage_package_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            if (!$this->garageOwnerCheck($garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }


            $garage_package = GaragePackage::with("garage_package_sub_services.sub_service")
            ->where([
                "garage_id" => $garage_id,
                "id" => $id
            ])
            ->first();
             if(!$garage_package){
                return response()->json([
            "message" => "package not found"
                ], 404);
            }


            return response()->json($garage_package, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }

      /**
        *
     * @OA\Get(
     *      path="/v1.0/client/garage-packages/single/{garage_id}/{id}",
     *      operationId="getGaragePackageByIdClient",
     *      tags={"client.package"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
 *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="6"
     *      ),
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to  get garage package by id",
     *      description="This method is to get garage package by id",
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

     public function getGaragePackageByIdClient($garage_id,$id,Request $request) {
        try{
            $this->storeActivity($request,"");



            $garage_package = GaragePackage::with("garage_package_sub_services.sub_service")
            ->where([
                "garage_id" => $garage_id,
                "id" => $id
            ])
            ->where('is_active', true)
            ->first();
             if(!$garage_package){
                return response()->json([
            "message" => "package not found"
                ], 404);
            }


            return response()->json($garage_package, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }



 /**
        *
     * @OA\Delete(
     *      path="/v1.0/garage-packages/single/{garage_id}/{id}",
     *      operationId="deleteGaragePackageById",
     *      tags={"garage_package_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
 *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
     *         required=true,
     *  example="6"
     *      ),
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to  delete garage package by id",
     *      description="This method is to delete garage package by id",
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

    public function deleteGaragePackageById($garage_id,$id,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('garage_package_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            if (!$this->garageOwnerCheck($garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }


            $garage_package = GaragePackage::where([


                "garage_id" => $garage_id,
                "id" => $id


            ])
            ->first();


             if(!$garage_package){
                return response()->json([
            "message" => "garage package not found"
                ], 404);
            }
            $garage_package->delete();


            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }





}
