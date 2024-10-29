<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNotificationSettingRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;

use App\Models\NotificationSetting;
use Exception;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    use ErrorUtil,UserActivityUtil;

    /**
   *
   * @OA\Put(
   *      path="/v1.0/notification-settings",
   *      operationId="updateNotificationSetting",
   *      tags={"setting"},
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
   * *         @OA\Property(property="STRIPE_KEY", type="string", format="string",example="STRIPE_KEY"),
   *           @OA\Property(property="STRIPE_SECRET", type="string", format="string",example="STRIPE_SECRET"),
   *  *   @OA\Property(property="stripe_enabled", type="boolean", example=true),
   *      @OA\Property(property="is_expert_price", type="boolean", example=true),
 *   @OA\Property(property="allow_pay_after_service", type="boolean", example=false),
 *   @OA\Property(property="allow_expert_booking", type="boolean", example=true),
 *   @OA\Property(property="allow_expert_self_busy", type="boolean", example=true),
 *   @OA\Property(property="allow_expert_booking_cancel", type="boolean", example=false),
 *   @OA\Property(property="allow_expert_view_revenue", type="boolean", example=true),
 *   @OA\Property(property="allow_expert_view_customer_details", type="boolean", example=false),
 *   @OA\Property(property="allow_receptionist_add_question", type="boolean", example=true),
 *   @OA\Property(property="default_currency", type="string", format="string", example="USD"),
 *   @OA\Property(property="default_language", type="string", format="string", example="en"),
 *   @OA\Property(property="vat_enabled", type="boolean", example=true),
 *   @OA\Property(property="vat_percentage", type="number", format="float", example=15.00)
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

   public function updateNotificationSetting(UpdateNotificationSettingRequest $request)
   {

       try {
           $this->storeActivity($request, "DUMMY activity","DUMMY description");
           if (!$request->user()->hasPermissionTo('business_setting_update')) {
               return response()->json([
                   "message" => "You can not perform this action"
               ], 401);
           }
           $request_data = $request->validated();


          $notificationSetting = NotificationSetting::
          where([
            "business_id" => auth()->user()->business_id
        ])
        ->first();

          if (!$notificationSetting) {
              return response()->json([
                  "message" => "no business setting found"
              ], 404);
          }

              $notificationSetting->fill(collect($request_data)->only([
                'notify_expert',
                'notify_customer',
                'notify_receptionist',
                'notify_business_owner'
              ])->toArray());
              $notificationSetting->save();


           return response()->json($notificationSetting, 200);
       } catch (Exception $e) {
           error_log($e->getMessage());
           return $this->sendError($e, 500, $request);
       }
   }

/**
   *
   * @OA\Get(
   *      path="/v1.0/notification-settings",
   *      operationId="getNotificationSetting",
   *      tags={"setting"},
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

   public function getNotificationSetting(Request $request)
   {
       try {
           $this->storeActivity($request, "DUMMY activity","DUMMY description");
           if (!$request->user()->hasPermissionTo('business_setting_view')) {
               return response()->json([
                   "message" => "You can not perform this action"
               ], 401);
           }


           $notificationSetting = NotificationSetting::
           where([
               "business_id" => auth()->user()->business_id
           ])
           ->first();






           return response()->json($notificationSetting, 200);
       } catch (Exception $e) {

           return $this->sendError($e, 500, $request);
       }
   }






}
