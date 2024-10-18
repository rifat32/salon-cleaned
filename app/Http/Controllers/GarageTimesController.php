<?php

namespace App\Http\Controllers;

use App\Http\Requests\GarageTimesUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\GarageUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Garage;
use App\Models\GarageTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GarageTimesController extends Controller
{
    use ErrorUtil,GarageUtil,UserActivityUtil;
    /**
     *
     * @OA\Patch(
     *      path="/v1.0/garage-times",
     *      operationId="updateGarageTimes",
     *      tags={"garage_times_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update garage times",
     *      description="This method is to update garage times",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"garage_id","times"},
     *    @OA\Property(property="garage_id", type="number", format="number", example="1"),
     *    @OA\Property(property="times", type="string", format="array",example={
     *
    *{"day":0,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
    *{"day":1,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
    *{"day":2,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":3,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
    *{"day":4,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
    *{"day":5,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
    *{"day":6,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true}
     *
     * }),

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

    public function updateGarageTimes(GarageTimesUpdateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('garage_times_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $updatableData = $request->validated();

                $garage_id = $updatableData["garage_id"];
           if (!$this->garageOwnerCheck($garage_id)) {
            return response()->json([
                "message" => "you are not the owner of the garage or the requested garage does not exist."
            ], 401);
        }


               GarageTime::where([
                "garage_id" => $garage_id
               ])
               ->delete();
               $timesArray = collect($updatableData["times"])->unique("day");
               foreach($timesArray as $garage_time) {
                GarageTime::create([
                    "garage_id" => $garage_id,
                    "day"=> $garage_time["day"],
                    "opening_time"=> $garage_time["opening_time"],
                    "closing_time"=> $garage_time["closing_time"],
                    "is_closed"=> $garage_time["is_closed"],
                ]);
               }


                return response(["message" => "data inserted"], 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }


     /**
        *
     * @OA\Get(
     *      path="/v1.0/garage-times/{garage_id}",
     *      operationId="getGarageTimes",
     *      tags={"garage_times_management"},
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
     *      summary="This method is to get garage times ",
     *      description="This method is to get garage times",
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

    public function getGarageTimes($garage_id,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('garage_times_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           if (!$this->garageOwnerCheck($garage_id)) {
            return response()->json([
                "message" => "you are not the owner of the garage or the requested garage does not exist."
            ], 401);
        }

            $garageTimes = GarageTime::where([
                "garage_id" => $garage_id
            ])->orderByDesc("id")->get();
            return response()->json($garageTimes, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }
}
