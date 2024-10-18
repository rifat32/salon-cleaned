<?php

namespace App\Http\Controllers;

use App\Http\Requests\AffiliationCreateRequest;
use App\Http\Requests\AffiliationUpdateRequest;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Affiliation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AffiliationController extends Controller
{
    use ErrorUtil,UserActivityUtil;


       /**
        *
     * @OA\Post(
     *      path="/v1.0/affiliations-logo",
     *      operationId="createAffiliationLogo",
     *      tags={"affiliation_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store affiliation logo",
     *      description="This method is to store affiliation logo",
     *
   *  @OA\RequestBody(
        *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"image"},
*         @OA\Property(
*             description="image to upload",
*             property="image",
*             type="file",
*             collectionFormat="multi",
*         )
*     )
* )



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

    public function createAffiliationLogo(ImageUploadRequest $request)
    {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('affiliation_create')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }

            $insertableData = $request->validated();

            $location =  config("setup-config.affiliation_logo");

            $new_file_name = time() . '_' . str_replace(' ', '_', $insertableData["image"]->getClientOriginalName());


            $insertableData["image"]->move(public_path($location), $new_file_name);


            return response()->json(["image" => $new_file_name,"location" => $location,"full_location"=>("/".$location."/".$new_file_name)], 200);


        } catch(Exception $e){
            error_log($e->getMessage());
            return $this->sendError($e,500,$request);
        }
    }













    /**
     *
     * @OA\Post(
     *      path="/v1.0/affiliations",
     *      operationId="createAffiliation",
     *      tags={"affiliation_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store affiliation",
     *      description="This method is to store affiliation",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","description","logo"},
     *    @OA\Property(property="name", type="string", format="string",example="car"),
     * *    @OA\Property(property="description", type="string", format="number",example="description"),
     *  * *    @OA\Property(property="logo", type="string", format="number",example="https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80"),
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

    public function createAffiliation(AffiliationCreateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('affiliation_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $insertableData = $request->validated();
                $insertableData["created_by"] = $request->user()->id;
                $affiliation =  Affiliation::create($insertableData);


                return response($affiliation, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/affiliations",
     *      operationId="updateAffiliation",
     *      tags={"affiliation_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update affiliation",
     *      description="This method is to update affiliation",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","name","description","logo"},
     *    @OA\Property(property="id", type="number", format="number", example="1"),
     *    @OA\Property(property="name", type="string", format="string",example="car"),
     * *    @OA\Property(property="description", type="string", format="number",example="description"),
     *    *  * *    @OA\Property(property="logo", type="string", format="number",example="https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80"),
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

    public function updateAffiliation(AffiliationUpdateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('affiliation_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $updatableData = $request->validated();

                $affiliationPrev = Affiliation::where([
                    "id" => $updatableData["id"]
                   ]);

                   if(!$request->user()->hasRole('superadmin')) {
                    $affiliationPrev =    $affiliationPrev->where([
                        "created_by" =>$request->user()->id
                    ]);
                }
                $affiliationPrev = $affiliationPrev->first();
                 if(!$affiliationPrev) {
                        return response()->json([
                           "message" => "you did not create this affiliation."
                        ],404);
                 }




                $affiliation  =  tap(Affiliation::where(["id" => $updatableData["id"]]))->update(
                    collect($updatableData)->only([
                        "name",
                        "description",
                        "logo"
                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$affiliation) {
                            return response()->json([
                                "message" => "no certification found"
                                ],404);

                    }

                return response($affiliation, 200);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/affiliations/{perPage}",
     *      operationId="getAffiliations",
     *      tags={"affiliation_management"},
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
     *      summary="This method is to get affiliations ",
     *      description="This method is to get affiliations",
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

    public function getAffiliations($perPage, Request $request)
    {
        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('affiliation_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // $automobilesQuery = AutomobileMake::with("makes");

            $affiliationQuery = new Affiliation();

            if (!empty($request->search_key)) {
                $affiliationQuery = $affiliationQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $affiliationQuery = $affiliationQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $affiliationQuery = $affiliationQuery->where('created_at', "<=", $request->end_date);
            }

            $affiliations = $affiliationQuery->orderByDesc("id")->paginate($perPage);

            return response()->json($affiliations, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/affiliations/{id}",
     *      operationId="deleteAffiliationById",
     *      tags={"affiliation_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to delete affiliation by id",
     *      description="This method is to delete affiliation by id",
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

    public function deleteAffiliationById($id, Request $request)
    {

        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('affiliation_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


                $affiliationQuery =   Affiliation::where([
                    "id" => $id
                   ]);
                   if(!$request->user()->hasRole('superadmin')) {
                    $affiliationQuery =    $affiliationQuery->where([
                        "created_by" =>$request->user()->id
                    ]);
                }

                $affiliation = $affiliationQuery->first();

                $affiliation->delete();

            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }
}
