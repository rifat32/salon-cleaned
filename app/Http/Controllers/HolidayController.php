<?php

namespace App\Http\Controllers;

use App\Http\Requests\HolidayCreateRequest;
use App\Http\Requests\HolidayUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Holiday;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, BasicUtil;




    /**
     *
     * @OA\Post(
     *      path="/v1.0/holidays",
     *      operationId="createHoliday",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store holiday",
     *      description="This method is to store holiday",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *             @OA\Property(property="description", type="string", format="string", example="Updated holiday celebration"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="repeats_annually", type="boolean", format="boolean", example=false),
     *  *     @OA\Property(property="departments", type="string", format="array", example={1,2,3}),
     *  *     @OA\Property(property="users", type="string", format="array", example={1,2,3})
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

    public function createHoliday(HolidayCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('holiday_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["business_id"] = auth()->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;
                $request_data["status"] = (auth()->user()->hasRole("business_owner") ? "approved" : "pending_approval");

                $holiday =  Holiday::create($request_data);


                return response()->json($holiday, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Put(
     *      path="/v1.0/holidays",
     *      operationId="updateHoliday",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update holiday ",
     *      description="This method is to update holiday",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *             @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *             @OA\Property(property="description", type="string", format="string", example="Updated holiday celebration"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="repeats_annually", type="boolean", format="boolean", example=false),
     *  *  *     @OA\Property(property="departments", type="string", format="array", example={1,2,3}),
     *  *     @OA\Property(property="users", type="string", format="array", example={1,2,3})

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

    public function updateHoliday(HolidayUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('holiday_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  auth()->user()->business_id;
                $request_data = $request->validated();



                $holiday_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                $holiday_prev = Holiday::where($holiday_query_params)
                    ->first();
                if (!$holiday_prev) {

                    return response()->json([
                        "message" => "no holiday found"
                    ], 404);
                }

                $holiday  =  tap(Holiday::where($holiday_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'description',
                        'start_date',
                        'end_date',
                        'repeats_annually',
                        // 'business_id',
                        // 'is_active',

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$holiday) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }




                return response($holiday, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/holidays",
     *      operationId="getHolidays",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *    *   *              @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         description="response_type: in pdf,csv,json",
     *         required=true,
     *  example="json"
     *      ),
     *      *   *              @OA\Parameter(
     *         name="file_name",
     *         in="query",
     *         description="file_name",
     *         required=true,
     *  example="employee"
     *      ),

     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *

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
     *     * *  @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     *     *     * *  @OA\Parameter(
     * name="repeat",
     * in="query",
     * description="repeat",
     * required=true,
     * example="repeat"
     * ),
     *   *     *     * *  @OA\Parameter(
     * name="description",
     * in="query",
     * description="description",
     * required=true,
     * example="description"
     * ),
     *
     *     *   *     *     * *  @OA\Parameter(
     * name="department_id",
     * in="query",
     * description="department_id",
     * required=true,
     * example="department_id"
     * ),
     *
     *     *     *   *     *     * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="show_my_data"
     * ),
     *
     *
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *      summary="This method is to get holidays  ",
     *      description="This method is to get holidays ",
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

     public function getHolidays(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");




             if (!$request->user()->hasPermissionTo('holiday_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }


             $business_id =  auth()->user()->business_id;




             $holidays = Holiday::
                 where(
                     [
                         "holidays.business_id" => $business_id
                     ]
                 )




                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                         $query->where("holidays.name", "like", "%" . $term . "%")
                             ->orWhere("holidays.description", "like", "%" . $term . "%");
                     });
                 })
                 //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                 //        return $query->where('product_category_id', $request->product_category_id);
                 //    })
                 ->when(!empty($request->name), function ($query) use ($request) {
                     return $query->where("holidays.name", "like", "%" . $request->name . "%");
                 })

                 ->when(isset($request->repeat), function ($query) use ($request) {
                     return $query->where('holidays.repeats_annually', intval($request->repeat));
                 })
                 ->when(!empty($request->description), function ($query) use ($request) {
                     return $query->where("holidays.description", "like", "%" . $request->description . "%");
                 })

                 ->when(!empty($request->department_id), function ($query) use ($request) {
                     $idsArray = explode(',', $request->department_id);
                     $query->whereHas('departments', function ($query) use ($idsArray) {
                         $query->whereIn("departments.id", $idsArray);
                     });
                 })


                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('holidays.created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('holidays.created_at', "<=", ($request->end_date . ' 23:59:59'));
                 })
                 ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                     return $query->orderBy("holidays.id", $request->order_by);
                 }, function ($query) {
                     return $query->orderBy("holidays.id", "DESC");
                 })
                 ->when(!empty($request->per_page), function ($query) use ($request) {
                     return $query->paginate($request->per_page);
                 }, function ($query) {
                     return $query->get();
                 });





             return response()->json($holidays, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }








    /**
     *
     * @OA\Get(
     *      path="/v1.0/holidays/{id}",
     *      operationId="getHolidayById",
     *      tags={"administrator.holiday"},
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
     *      summary="This method is to get holiday by id",
     *      description="This method is to get holiday by id",
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


    public function getHolidayById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('holiday_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $holiday =  Holiday::where([
                "id" => $id,
                "business_id" => auth()->user()->business_id
            ])

                ->first();
            if (!$holiday) {

                return response()->json([
                    "message" => "no holiday found"
                ], 404);
            }

            return response()->json($holiday, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/holidays/{ids}",
     *      operationId="deleteHolidaysByIds",
     *      tags={"administrator.holiday"},
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
     *      summary="This method is to delete holiday by id",
     *      description="This method is to delete holiday by id",
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

    public function deleteHolidaysByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('holiday_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Holiday::where([
                "business_id" => $business_id
            ])
                ->whereIn('id', $idsArray)
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

            Holiday::destroy($existingIds);



            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

}
