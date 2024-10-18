<?php

namespace App\Http\Controllers;

use App\Http\Requests\GarageAffiliationCreateRequest;
use App\Http\Requests\GarageAffiliationUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\GarageUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\GarageAffiliation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GarageAffiliationController extends Controller
{
    use ErrorUtil,GarageUtil,UserActivityUtil;





    /**
     *
     * @OA\Post(
     *      path="/v1.0/garage-affiliations",
     *      operationId="createGarageAffiliation",
     *      tags={"garage_affiliation_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store garage affiliation",
     *      description="This method is to store garage affiliation",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"garage_id","affiliation_id","start_date","end_date"},
     *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     * *    @OA\Property(property="affiliation_id", type="number", format="number",example="1"),
     *
     *
     *  *     *  * @OA\Property(property="start_date", type="string", format="string",example="2019-06-29"),
     * *     *  * @OA\Property(property="end_date", type="string", format="string",example="2019-06-29"),
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

    public function createGarageAffiliation(GarageAffiliationCreateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('garage_affiliation_create')) {
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






                $garage_affiliation =  GarageAffiliation::create($insertableData);


                return response($garage_affiliation, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/garage-affiliations",
     *      operationId="updateGarageAffiliation",
     *      tags={"z.unused"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update garage affiliation",
     *      description="This method is to update garage affiliation",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","garage_id","affiliation_id","start_date","end_date"},
     *    @OA\Property(property="id", type="number", format="number", example="1"),
    *    @OA\Property(property="garage_id", type="number", format="number",example="1"),
     * *    @OA\Property(property="affiliation_id", type="number", format="number",example="1"),
     *
     *  *  *     *  * @OA\Property(property="start_date", type="string", format="string",example="2019-06-29"),
     * *     *  * @OA\Property(property="end_date", type="string", format="string",example="2019-06-29"),
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

    public function updateGarageAffiliation(GarageAffiliationUpdateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('garage_affiliation_update')) {
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


                $garage_affiliation  =  tap(GarageAffiliation::where(["id" => $updatableData["id"]]))->update(
                    collect($updatableData)->only([
                        "start_date",
                        "end_date",
                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$garage_affiliation) {
                        return response()->json([
                            "message" => "no garage affiliation found"
                            ],404);

                }

                return response($garage_affiliation, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/garage-affiliations/{perPage}",
     *      operationId="getGarageAffiliations",
     *      tags={"garage_affiliation_management"},
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
     *      summary="This method is to get garage affiliations ",
     *      description="This method is to get garage affiliations",
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

    public function getGarageAffiliations($perPage, Request $request)
    {
        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('garage_affiliation_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // $automobilesQuery = AutomobileMake::with("makes");

            $affiliationQuery =  GarageAffiliation::with("affiliation","garage")
            ->leftJoin('affiliations', 'affiliations.id', '=', 'garage_affiliations.affiliation_id');

            if (!empty($request->search_key)) {
                $affiliationQuery = $affiliationQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("affiliations.name", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $affiliationQuery = $affiliationQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $affiliationQuery = $affiliationQuery->where('created_at', "<=", $request->end_date);
            }

            $affiliations = $affiliationQuery->orderByDesc("garage_affiliations.id")->paginate($perPage);
            return response()->json($affiliations, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }



 /**
     *
     * @OA\Get(
     *      path="/v1.0/garage-affiliations/{garage_id}/{perPage}",
     *      operationId="getGarageAffiliationsByGarageId",
     *      tags={"garage_affiliation_management"},
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
     *      summary="This method is to get garage affiliations ",
     *      description="This method is to get garage affiliations",
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

    public function getGarageAffiliationsByGarageId($garage_id,$perPage, Request $request)
    {
        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('garage_affiliation_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


        if (!$this->garageOwnerCheck($garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
             ], 401);
        }








            // $automobilesQuery = AutomobileMake::with("makes");

            $affiliationQuery =  GarageAffiliation::with("affiliation","garage")
            ->leftJoin('affiliations', 'affiliations.id', '=', 'garage_affiliations.affiliation_id')
            ->where([
                "garage_id" => $garage_id
            ]);

            if (!empty($request->search_key)) {
                $affiliationQuery = $affiliationQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("affiliations.name", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $affiliationQuery = $affiliationQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $affiliationQuery = $affiliationQuery->where('created_at', "<=", $request->end_date);
            }

            $affiliations = $affiliationQuery->orderByDesc("garage_affiliations.id")->paginate($perPage);
            return response()->json($affiliations, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }


     /**
     *
     * @OA\Get(
     *      path="/v1.0/garage-affiliations/get/all/{garage_id}",
     *      operationId="getGarageAffiliationsAllByGarageId",
     *      tags={"garage_affiliation_management"},
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
     *      summary="This method is to get garage affiliations ",
     *      description="This method is to get garage affiliations",
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

    public function getGarageAffiliationsAllByGarageId($garage_id, Request $request)
    {
        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('garage_affiliation_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


        if (!$this->garageOwnerCheck($garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
             ], 401);
        }








            // $automobilesQuery = AutomobileMake::with("makes");

            $affiliationQuery =  GarageAffiliation::with("affiliation","garage")
            ->leftJoin('affiliations', 'affiliations.id', '=', 'garage_affiliations.affiliation_id')
            ->where([
                "garage_id" => $garage_id
            ]);

            if (!empty($request->search_key)) {
                $affiliationQuery = $affiliationQuery->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("affiliations.name", "like", "%" . $term . "%");
                });
            }

            if (!empty($request->start_date)) {
                $affiliationQuery = $affiliationQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $affiliationQuery = $affiliationQuery->where('created_at', "<=", $request->end_date);
            }

            $affiliations = $affiliationQuery->orderByDesc("garage_affiliations.id")->get();
            return response()->json($affiliations, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }










    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/garage-affiliations/{id}",
     *      operationId="deleteGarageAffiliationById",
     *      tags={"garage_affiliation_management"},
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
     *      summary="This method is to delete garage affiliation by id",
     *      description="This method is to delete garage affiliation by id",
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

    public function deleteGarageAffiliationById($id, Request $request)
    {

        try {
            $this->storeActivity($request,"");
            if (!$request->user()->hasPermissionTo('garage_affiliation_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $garage_affiliation = GarageAffiliation::where([
                "id" => $id
            ])
            ->first();

            if (!$this->garageOwnerCheck($garage_affiliation->garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }

            $garage_affiliation->delete();


            return response()->json(["ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }
}
