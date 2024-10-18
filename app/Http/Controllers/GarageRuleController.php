<?php

namespace App\Http\Controllers;

use App\Http\Requests\GarageRulesUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\GarageUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\GarageRule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GarageRuleController extends Controller
{
    use ErrorUtil,GarageUtil,UserActivityUtil;
    /**
     *
     * @OA\Patch(
     *      path="/v1.0/garage-rules",
     *      operationId="updateGarageRules",
     *      tags={"garage_rules_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update garage rules",
     *      description="This method is to update garage rules",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"garage_id","standard_lead_time",*   "booking_accept_start_time","booking_accept_end_time", "block_out_days"},
     *    @OA\Property(property="garage_id", type="number", format="number", example="1"),
     *  *    @OA\Property(property="standard_lead_time", type="number", format="number", example="1"),
     * *  *    @OA\Property(property="booking_accept_start_time", type="string", format="string", example="10:10"),
     *
     *    * *  *    @OA\Property(property="booking_accept_end_time", type="string", format="string", example="10:10"),
     *
     *
     *    * *  *    @OA\Property(property="block_out_days", type="string", format="array", example={"2019-06-29","2019-07-29"}),
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

    public function updateGarageRules(GarageRulesUpdateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return  DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('garage_rules_update')) {
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


               GarageRule::where([
                "garage_id" => $garage_id
               ])
               ->delete();
               $block_out_days_array = collect($updatableData["block_out_days"])->unique();

                GarageRule::create([
                    "garage_id"=> $garage_id,
                    "standard_lead_time"=>$updatableData["standard_lead_time"],

                    "booking_accept_start_time"=>$updatableData["booking_accept_start_time"],

                    "booking_accept_end_time"=>$updatableData["booking_accept_end_time"],

                    "block_out_days"=>json_encode($block_out_days_array)


                ]);

                GarageRule::upsert([
                    [
                        "garage_id"=> $garage_id,
                        "standard_lead_time"=>$updatableData["standard_lead_time"],

                        "booking_accept_start_time"=>$updatableData["booking_accept_start_time"],

                        "booking_accept_end_time"=>$updatableData["booking_accept_end_time"],

                        "block_out_days"=>json_encode($block_out_days_array)


                    ]
                ], ['garage_id'], ['standard_lead_time',"booking_accept_start_time","booking_accept_end_time","block_out_days"]);

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
     *      path="/v1.0/garage-rules/{garage_id}",
     *      operationId="getGarageRules",
     *      tags={"garage_rules_management"},
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
     *      summary="This method is to get garage rules ",
     *      description="This method is to get garage rules",
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

    public function getGarageRules($garage_id,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('garage_rules_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           if (!$this->garageOwnerCheck($garage_id)) {
            return response()->json([
                "message" => "you are not the owner of the garage or the requested garage does not exist."
            ], 401);
        }

            $garage_rules = GarageRule::where([
                "id" => $garage_id
            ])->orderByDesc("id")->first();

            $garage_rules->block_out_days  = json_decode($garage_rules->block_out_days);

            return response()->json($garage_rules, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }
}
