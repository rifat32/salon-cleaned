<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCommissionSettingRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;

use App\Models\CommissionSetting;
use Exception;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    use ErrorUtil,UserActivityUtil;

    /**
   *
   * @OA\Put(
   *      path="/v1.0/commission-settings",
   *      operationId="updateCommissionSetting",
   *      tags={"commission_setting"},
   *       security={
   *           {"bearerAuth": {}}
   *       },
   *      summary="This method is to update busuness setting",
   *      description="This method is to update busuness setting",
   *
   *  @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *
 *     @OA\Property(property="target_amount", type="number", format="float"),
 *     @OA\Property(property="commission_percentage", type="number", format="float"),
 *     @OA\Property(property="notify_business_owner", type="boolean", example=true)
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

   public function updateCommissionSetting(UpdateCommissionSettingRequest $request)
   {

       try {
           $this->storeActivity($request, "DUMMY activity","DUMMY description");
           if (!$request->user()->hasPermissionTo('business_setting_update')) {
               return response()->json([
                   "message" => "You can not perform this action"
               ], 401);
           }
           $request_data = $request->validated();
           $request_data["frequency"] = "monthly";
           $request_data["business_id"] = auth()->user()->business_id;

          $commissionSetting = CommissionSetting::
          where([
            "business_id" => auth()->user()->business_id
        ])
        ->first();

        if (!$commissionSetting) {
            commissionSetting::create($request_data);
        } else {
            $commissionSetting->fill(collect($request_data)->only([
                'target_amount',
                'commission_percentage',
                'frequency',
              ])->toArray());
              $commissionSetting->save();
        }




           return response()->json($commissionSetting, 200);
       } catch (Exception $e) {
           error_log($e->getMessage());
           return $this->sendError($e, 500, $request);
       }
   }

/**
   *
   * @OA\Get(
   *      path="/v1.0/commission-settings",
   *      operationId="getCommissionSetting",
   *      tags={"commission_setting"},
   *       security={
   *           {"bearerAuth": {}}
   *       },
   *      summary="This method is to get busuness _setting",
   *      description="This method is to get busuness setting",
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

   public function getCommissionSetting(Request $request)
   {
       try {
           $this->storeActivity($request, "DUMMY activity","DUMMY description");
           if (!$request->user()->hasPermissionTo('business_setting_view')) {
               return response()->json([
                   "message" => "You can not perform this action"
               ], 401);
           }


           $commissionSetting = CommissionSetting::
           where([
               "business_id" => auth()->user()->business_id
           ])
           ->first();






           return response()->json($commissionSetting, 200);
       } catch (Exception $e) {

           return $this->sendError($e, 500, $request);
       }
   }






}
