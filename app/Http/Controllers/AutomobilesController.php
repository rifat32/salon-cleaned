<?php

namespace App\Http\Controllers;

use App\Http\Requests\AutomobileCategoryCreateRequest;
use App\Http\Requests\AutomobileCategoryUpdateRequest;



use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\AutomobileCategory;
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

            $request_data = $request->validated();


            $automobile =  AutomobileCategory::create($request_data);


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
            $request_data = $request->validated();



                $automobile  =  tap(AutomobileCategory::where(["id" => $request_data["id"]]))->update(collect($request_data)->only([
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











}
