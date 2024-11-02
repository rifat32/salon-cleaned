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
   *      tags={"notification_setting"},
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
    *     @OA\Property(property="notify_expert", type="boolean", example=true),
 *     @OA\Property(property="notify_customer", type="boolean", example=true),
 *     @OA\Property(property="notify_receptionist", type="boolean", example=false),
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
           $request_data["business_id"] = auth()->user()->business_id;

          $notificationSetting = NotificationSetting::
          where([
            "business_id" => auth()->user()->business_id
        ])
        ->first();

        if (!$notificationSetting) {
            NotificationSetting::create($request_data);
        } else {
            $notificationSetting->fill(collect($request_data)->only([
                'notify_expert',
                'notify_customer',
                'notify_receptionist',
                'notify_business_owner'
              ])->toArray());
              $notificationSetting->save();
        }





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
   *      tags={"notification_setting"},
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
