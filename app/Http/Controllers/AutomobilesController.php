<?php

namespace App\Http\Controllers;

use App\Http\Requests\AutomobileCategoryCreateRequest;
use App\Http\Requests\AutomobileCategoryUpdateRequest;
use App\Http\Requests\AutomobileFuelTypeCreateRequest;
use App\Http\Requests\AutomobileFuelTypeUpdateRequest;
use App\Http\Requests\AutomobileMakeCreateRequest;
use App\Http\Requests\AutomobileMakeUpdateRequest;
use App\Http\Requests\AutomobileModelCreateRequest;
use App\Http\Requests\AutomobileModelUpdateRequest;
use App\Http\Requests\AutomobileModelVariantCreateRequest;
use App\Http\Requests\AutomobileModelVariantUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\AutomobileCategory;
use App\Models\AutomobileFuelType;
use App\Models\AutomobileMake;
use App\Models\AutomobileModel;
use App\Models\AutomobileModelVariant;
use App\Models\GarageAutomobileMake;
use App\Models\GarageService;
use Exception;
use Illuminate\Http\Request;

class AutomobilesController extends Controller
{
    use ErrorUtil,UserActivityUtil;
   /**
        *
     * @OA\Post(
     *      path="/v1.0/automobile-categories",
     *      operationId="createAutomobileCategory",
     *      tags={"automobile_management.category"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store automobile category",
     *      description="This method is to store automobile category",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name"},
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     **             @OA\Property(property="logo", type="string", format="string",example="logo"),
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

    public function createAutomobileCategory(AutomobileCategoryCreateRequest $request)
    {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_create')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }

            $insertableData = $request->validated();


            $automobile =  AutomobileCategory::create($insertableData);


            return response($automobile, 201);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }

     /**
        *
     * @OA\Put(
     *      path="/v1.0/automobile-categories",
     *      operationId="updateAutomobileCategory",
     *      tags={"automobile_management.category"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update automobile category",
     *      description="This method is to update automobile category",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","name"},
     *             @OA\Property(property="id", type="number", format="number",example="1"),
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     *             @OA\Property(property="logo", type="string", format="string",example="logo")
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

    public function updateAutomobileCategory(AutomobileCategoryUpdateRequest $request)
    {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_update')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            $updatableData = $request->validated();



                $automobile  =  tap(AutomobileCategory::where(["id" => $updatableData["id"]]))->update(collect($updatableData)->only([
                    'name',
                    'logo'
                ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$automobile) {
                        return response()->json([
                            "message" => "no automobile category found"
                            ],404);

                }


            return response($automobile, 200);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }
     /**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-categories/{perPage}",
     *      operationId="getAutomobileCategories",
     *      tags={"automobile_management.category"},
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

     *      summary="This method is to get automobile categories",
     *      description="This method is to get automobile categories",
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

    public function getAutomobileCategories($perPage,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            $automobilesQuery = AutomobileCategory::with("makes");

            if(!empty($request->search_key)) {
                $automobilesQuery = $automobilesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', "<=", $request->end_date);
            }

            $users = $automobilesQuery->orderBy("name",'asc')->paginate($perPage);

            return response()->json($users, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }
      /**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-categories/get/all",
     *      operationId="getAllAutomobileCategories",
     *      tags={"basics"},
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

     *      summary="This method is to get all automobile categories",
     *      description="This method is to get all automobile categories",
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

    public function getAllAutomobileCategories(Request $request) {
        try{
            $this->storeActivity($request,"");
        //     if(!$request->user()->hasPermissionTo('automobile_view') && !$request->user()->hasPermissionTo('service_view')){
        //         return response()->json([
        //            "message" => "You can not perform this action"
        //         ],401);
        //    }


            $automobilesQuery = AutomobileCategory::with("makes");

            if(!empty($request->search_key)) {
                $automobilesQuery = $automobilesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', "<=", $request->end_date);
            }

            $users = $automobilesQuery->orderBy("name",'asc')->get();

            return response()->json($users, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }
  /**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-categories/single/get/{id}",
     *      operationId="getAutomobileCategoryById",
     *      tags={"automobile_management.category"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get automobile category by id",
     *      description="This method is to get automobile category by id",
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

    public function getAutomobileCategoryById($id,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            $automobileCategory = AutomobileCategory::with("makes")
            ->where([
                "id" => $id
            ])
            ->first()
            ;

            return response()->json($automobileCategory, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


/**
        *
     * @OA\Delete(
     *      path="/v1.0/automobile-categories/{id}",
     *      operationId="deleteAutomobileCategoryById",
     *      tags={"automobile_management.category"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to delete automobile category by id",
     *      description="This method is to delete automobile category by id",
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

    public function deleteAutomobileCategoryById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           AutomobileCategory::where([
            "id" => $id
           ])
           ->delete();

            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


/**
        *
     * @OA\Post(
     *      path="/v1.0/automobile-makes",
     *      operationId="createAutomobileMake",
     *      tags={"automobile_management.make"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store automobile make",
     *      description="This method is to store automobile make",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","description","automobile_category_id"},
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     *              @OA\Property(property="description", type="string", format="string",example="car"),
     *  *              @OA\Property(property="automobile_category_id", type="string", format="number",example="1"),
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

    public function createAutomobileMake(AutomobileMakeCreateRequest $request)
    {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_create')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }

            $insertableData = $request->validated();


            $automobile =  AutomobileMake::create($insertableData);


            return response($automobile, 201);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }
 /**
        *
     * @OA\Put(
     *      path="/v1.0/automobile-makes",
     *      operationId="updateAutomobileMake",
     *      tags={"automobile_management.make"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update automobile make",
     *      description="This method is to update automobile make",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","name","description"},
     *             @OA\Property(property="id", type="number", format="number",example="1"),
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     *             @OA\Property(property="description", type="string", format="string",example="description"),
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

    public function updateAutomobileMake(AutomobileMakeUpdateRequest $request)
    {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_update')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            $updatableData = $request->validated();



                $automobile  =  tap(AutomobileMake::where(["id" => $updatableData["id"]]))->update(collect($updatableData)->only([
                    'name',
                    "description"
                ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$automobile) {
                        return response()->json([
                            "message" => "no automobile make found"
                            ],404);

                }

            return response($automobile, 200);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }
 /**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-makes/{categoryId}/{perPage}",
     *      operationId="getAutomobileMakes",
     *      tags={"automobile_management.make"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *         @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         description="categoryId",
     *         required=true,
     *  example="6"
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
     *      summary="This method is to get automobile makes by category id",
     *      description="This method is to get automobile makes by category id",
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

    public function getAutomobileMakes($categoryId,$perPage,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            // $automobilesQuery = AutomobileMake::with("makes");

            $automobilesQuery = AutomobileMake::with("category")
            ->where([
                "automobile_category_id" => $categoryId
            ]);

            if(!empty($request->search_key)) {
                $automobilesQuery = $automobilesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', "<=", $request->end_date);
            }

            $makes = $automobilesQuery->orderBy("name",'asc')->paginate($perPage);
            return response()->json($makes, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }


 /**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-makes-all/{categoryId}",
     *      operationId="getAutomobileMakesAll",
     *      tags={"basics"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *         @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         description="categoryId",
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
     *      summary="This method is to get all automobile makes by category id",
     *      description="This method is to get all automobile makes by category id",
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

    public function getAutomobileMakesAll($categoryId,Request $request) {
        try{
            $this->storeActivity($request,"");

            $automobilesQuery = AutomobileMake::with("models")
            ->where([
                "automobile_category_id" => $categoryId
            ]);

            if(!empty($request->search_key)) {
                $automobilesQuery = $automobilesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', "<=", $request->end_date);
            }

            $makes = $automobilesQuery->orderBy("name",'asc')->get();
            return response()->json($makes, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

    /**
        *
     * @OA\Get(
     *      path="/v2.0/automobile-makes-all/{categoryId}",
     *      operationId="getAutomobileMakesAllV2",
     *      tags={"basics"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *         @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         description="categoryId",
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
     *      summary="This method is to get all automobile makes by category id",
     *      description="This method is to get all automobile makes by category id",
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

    public function getAutomobileMakesAllV2($categoryId,Request $request) {
        try{
            $this->storeActivity($request,"");

            $automobilesQuery = AutomobileMake::where([
                "automobile_category_id" => $categoryId
            ]);

            if(!empty($request->search_key)) {
                $automobilesQuery = $automobilesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', "<=", $request->end_date);
            }

            $makes = $automobilesQuery->orderBy("name",'asc')->get();
            return response()->json($makes, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }














/**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-makes/single/get/{id}",
     *      operationId="getAutomobileMakeById",
     *      tags={"automobile_management.make"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get automobile make by id",
     *      description="This method is to get automobile make by id",
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

    public function getAutomobileMakeById($id,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }


            $automobileCategory = AutomobileMake::with("category")->where([
                "id" => $id
            ])
            ->first()
            ;

            return response()->json($automobileCategory, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

/**
        *
     * @OA\Delete(
     *      path="/v1.0/automobile-makes/{id}",
     *      operationId="deleteAutomobileMakeById",
     *      tags={"automobile_management.make"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to delete automobile make by id",
     *      description="This method is to delete automobile make by id",
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

    public function deleteAutomobileMakeById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           AutomobileMake::where([
            "id" => $id
           ])
           ->delete();

            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

    /**
        *
     * @OA\Post(
     *      path="/v1.0/automobile-models",
     *      operationId="createAutomobileModel",
     *      tags={"automobile_management.model"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store automobile model",
     *      description="This method is to store automobile model",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","description","automobile_make_id"},
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     *              @OA\Property(property="description", type="string", format="string",example="car"),
     *  *              @OA\Property(property="automobile_make_id", type="string", format="number",example="1"),
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

    public function createAutomobileModel(AutomobileModelCreateRequest $request)
    {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_create')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }

            $insertableData = $request->validated();


            $automobile =  AutomobileModel::create($insertableData);


            return response($automobile, 201);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }
 /**
        *
     * @OA\Put(
     *      path="/v1.0/automobile-models",
     *      operationId="updateAutomobileModel",
     *      tags={"automobile_management.model"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update automobile model",
     *      description="This method is to update automobile model",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","name","description"},
     *             @OA\Property(property="id", type="number", format="number",example="1"),
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     *             @OA\Property(property="description", type="string", format="string",example="description"),
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

    public function updateAutomobileModel(AutomobileModelUpdateRequest $request)
    {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_update')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            $updatableData = $request->validated();



                $automobile  =  tap(AutomobileModel::where(["id" => $updatableData["id"]]))->update(collect($updatableData)->only([
                    'name',
                    "description"
                ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$automobile) {
                        return response()->json([
                            "message" => "no automobile model found"
                            ],404);

                }

            return response($automobile, 200);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }
 /**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-models/{makeId}/{perPage}",
     *      operationId="getAutomobileModel",
     *      tags={"automobile_management.model"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *         @OA\Parameter(
     *         name="makeId",
     *         in="path",
     *         description="makeId",
     *         required=true,
     *  example="6"
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
     *      summary="This method is to get automobile models by make id",
     *      description="This method is to get automobile models by make id",
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

    public function getAutomobileModel($makeId,$perPage,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            // $automobilesQuery = AutomobileMake::with("makes");

            $automobilesQuery = AutomobileModel::with("make.category")
            ->where([
                "automobile_make_id" => $makeId
            ]);

            if(!empty($request->search_key)) {
                $automobilesQuery = $automobilesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', "<=", $request->end_date);
            }

            $models = $automobilesQuery->orderBy("name",'asc')->paginate($perPage);
            return response()->json($models, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }
/**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-models/single/get/{id}",
     *      operationId="getAutomobileModelById",
     *      tags={"automobile_management.model"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get automobile model by id",
     *      description="This method is to get automobile model by id",
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

    public function getAutomobileModelById($id,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            $automobileModel = AutomobileModel::with("make.category")->where([
                "id" => $id
            ])
            ->first()
            ;

            return response()->json($automobileModel, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

    /**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-models-all",
     *      operationId="getAutomobileModelsAll",
     *      tags={"basics"},
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
*  @OA\Parameter(
*      name="automobile_make_ids[]",
*      in="query",
*      description="automobile_make_id",
*      required=true,
*      example="1,2"
* ),
     *      summary="This method is to get all automobile models by make ids",
     *      description="This method is to get all automobile models by make ids",
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

    public function getAutomobileModelsAll(Request $request) {
        try{
            $this->storeActivity($request,"");

            $automobilesQuery = new AutomobileModel();

            if(!empty($request->search_key)) {
                $automobilesQuery = $automobilesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', "<=", $request->end_date);
            }
            if(!empty($request->automobile_make_ids)) {
                if(count($request->automobile_make_ids)) {
                    $automobilesQuery = $automobilesQuery->whereIn("automobile_make_id",$request->automobile_make_ids);
                }

            }

            $models = $automobilesQuery->orderBy("name",'asc')->get();
            return response()->json($models, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

/**
        *
     * @OA\Delete(
     *      path="/v1.0/automobile-models/{id}",
     *      operationId="deleteAutomobileModelById",
     *      tags={"automobile_management.model"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to delete automobile model by id",
     *      description="This method is to delete automobile model by id",
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

    public function deleteAutomobileModelById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           AutomobileModel::where([
            "id" => $id
           ])
           ->delete();

            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


    /**
        *
     * @OA\Post(
     *      path="/v1.0/automobile-model-variants",
     *      operationId="createAutomobileModelVariant",
     *      tags={"automobile_management.model_variant"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store automobile model variant",
     *      description="This method is to store automobile model variant",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","description","automobile_model_id"},
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     *              @OA\Property(property="description", type="string", format="string",example="car"),
     *  *              @OA\Property(property="automobile_model_id", type="string", format="number",example="1"),
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

    public function createAutomobileModelVariant(AutomobileModelVariantCreateRequest $request)
    {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_create')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }

            $insertableData = $request->validated();


            $automobile =  AutomobileModelVariant::create($insertableData);


            return response($automobile, 201);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }
 /**
        *
     * @OA\Put(
     *      path="/v1.0/automobile-model-variants",
     *      operationId="updateAutomobileModelVariant",
     *      tags={"automobile_management.model_variant"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update automobile model variant",
     *      description="This method is to update automobile model variant",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","name","description"},
     *             @OA\Property(property="id", type="number", format="number",example="1"),
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     *             @OA\Property(property="description", type="string", format="string",example="description"),
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

    public function updateAutomobileModelVariant(AutomobileModelVariantUpdateRequest $request)
    {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_update')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            $updatableData = $request->validated();



                $automobile  =  tap(AutomobileModelVariant::where(["id" => $updatableData["id"]]))->update(collect($updatableData)->only([
                    'name',
                    "description"
                ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$automobile) {
                        return response()->json([
                            "message" => "no automobile model variant found"
                            ],404);

                }

            return response($automobile, 200);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }
 /**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-model-variants/{modelId}/{perPage}",
     *      operationId="getAutomobileModelVariant",
     *      tags={"automobile_management.model_variant"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *         @OA\Parameter(
     *         name="makeId",
     *         in="path",
     *         description="makeId",
     *         required=true,
     *  example="6"
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
     *      summary="This method is to get automobile model variants by model id",
     *      description="This method is to get automobile model variants by model id",
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

    public function getAutomobileModelVariant($modelId,$perPage,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            // $automobilesQuery = AutomobileMake::with("makes");

            $automobilesQuery = AutomobileModelVariant::with("model.make.category")
            ->where([
                "automobile_model_id" => $modelId
            ]);

            if(!empty($request->search_key)) {
                $automobilesQuery = $automobilesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', "<=", $request->end_date);
            }

            $models = $automobilesQuery->orderBy("name",'asc')->paginate($perPage);
            return response()->json($models, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

/**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-model-variants/single/get/{id}",
     *      operationId="getAutomobileModelVariantById",
     *      tags={"automobile_management.model_variant"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get automobile model variant by id",
     *      description="This method is to get automobile model variant by id",
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

    public function getAutomobileModelVariantById($id,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            $automobileModelVariant = AutomobileModelVariant::with("model.make.category")->where([
                "id" => $id
            ])
            ->first()
            ;

            return response()->json($automobileModelVariant, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

/**
        *
     * @OA\Delete(
     *      path="/v1.0/automobile-model-variants/{id}",
     *      operationId="deleteAutomobileModelVariantById",
     *      tags={"automobile_management.model_variant"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to delete automobile model variant by id",
     *      description="This method is to delete automobile model variant by id",
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

    public function deleteAutomobileModelVariantById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           AutomobileModelVariant::where([
            "id" => $id
           ])
           ->delete();

            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }






























     /**
        *
     * @OA\Post(
     *      path="/v1.0/automobile-fuel-types",
     *      operationId="createAutomobileFuelType",
     *      tags={"automobile_management.fuel_type"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store automobile Fuel Type",
     *      description="This method is to store automobile Fuel Type",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","description","automobile_model_variant_id"},
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     *              @OA\Property(property="description", type="string", format="string",example="car"),
     *  *              @OA\Property(property="automobile_model_variant_id", type="string", format="number",example="1"),
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

    public function createAutomobileFuelType(AutomobileFuelTypeCreateRequest $request)
    {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_create')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }

            $insertableData = $request->validated();

            $automobile =  AutomobileFuelType::create($insertableData);


            return response($automobile, 201);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }
 /**
        *
     * @OA\Put(
     *      path="/v1.0/automobile-fuel-types",
     *      operationId="updateAutomobileFuelType",
     *      tags={"automobile_management.fuel_type"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update automobile Fuel Type",
     *      description="This method is to update automobile Fuel Type",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","name","description"},
     *             @OA\Property(property="id", type="number", format="number",example="1"),
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     *             @OA\Property(property="description", type="string", format="string",example="description"),
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

    public function updateAutomobileFuelType(AutomobileFuelTypeUpdateRequest $request)
    {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_update')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            $updatableData = $request->validated();



                $automobile  =  tap(AutomobileFuelType::where(["id" => $updatableData["id"]]))->update(collect($updatableData)->only([
                    'name',
                    "description"
                ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$automobile) {
                        return response()->json([
                            "message" => "no automobile fuel type found"
                            ],404);

                }

            return response($automobile, 200);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }
 /**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-fuel-types/{modelVariantId}/{perPage}",
     *      operationId="getAutomobileFuelType",
     *      tags={"automobile_management.fuel_type"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *         @OA\Parameter(
     *         name="makeId",
     *         in="path",
     *         description="makeId",
     *         required=true,
     *  example="6"
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
     *      summary="This method is to get automobile Fuel Types by model variant id",
     *      description="This method is to get automobile Fuel Types by model variant id",
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

    public function getAutomobileFuelType($modelId,$perPage,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            // $automobilesQuery = AutomobileMake::with("makes");

            $automobilesQuery = AutomobileFuelType::with("model_variant.model.make.category")
            ->where([
                "automobile_model_variant_id" => $modelId
            ]);

            if(!empty($request->search_key)) {
                $automobilesQuery = $automobilesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $automobilesQuery = $automobilesQuery->where('created_at', "<=", $request->end_date);
            }

            $models = $automobilesQuery->orderBy("name",'asc')->paginate($perPage);
            return response()->json($models, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

/**
        *
     * @OA\Get(
     *      path="/v1.0/automobile-fuel-types/single/get/{id}",
     *      operationId="getAutomobileFuelTypeById",
     *      tags={"automobile_management.fuel_type"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get automobile fuel type by id",
     *      description="This method is to get automobile fuel type by id",
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

    public function getAutomobileFuelTypeById($id,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            $automobileFuelType = AutomobileFuelType::where([
                "id" => $id
            ])
            ->first()
            ;

            return response()->json($automobileFuelType, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

/**
        *
     * @OA\Delete(
     *      path="/v1.0/automobile-fuel-types/{id}",
     *      operationId="deleteAutomobileFuelTypeById",
     *      tags={"automobile_management.fuel_type"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to delete automobile fuel type by id",
     *      description="This method is to delete automobile fuel type by id",
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

    public function deleteAutomobileFuelTypeById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('automobile_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           AutomobileFuelType::where([
            "id" => $id
           ])
           ->delete();

            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


}
