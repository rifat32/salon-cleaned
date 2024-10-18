<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationStatusUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\GarageUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Notification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use ErrorUtil, GarageUtil,UserActivityUtil;

    /**
     *
     * @OA\Get(
     *      path="/v1.0/notifications/{perPage}",
     *      operationId="getNotifications",
     *      tags={"notification_management"},
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

     *      summary="This method is to get notification",
     *      description="This method is to get notification",
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

    public function getNotifications($perPage, Request $request)
    {
        try {

            $this->storeActivity($request,"");

            $notificationsQuery = Notification::where([
                "receiver_id" => $request->user()->id
            ]);



            $notifications = $notificationsQuery->orderByDesc("id")->paginate($perPage);




            $total_data = count($notifications->items());
            for ($i = 0; $i < $total_data; $i++) {

                 $notifications->items()[$i]["template_string"] = json_decode($notifications->items()[$i]->template->template);

                 error_log($notifications->items()[$i]["template_string"]);



                if (!empty($notifications->items()[$i]->customer_id)) {
                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[customer_name]",

                        ($notifications->items()[$i]->customer->first_Name . " " . $notifications->items()[$i]->customer->last_Name),

                        $notifications->items()[$i]["template_string"]
                    );
                }

                if (!empty($notifications->items()[$i]->garage_id)) {
                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[garage_owner_name]",

                        ($notifications->items()[$i]->garage->owner->first_Name . " " . $notifications->items()[$i]->garage->owner->last_Name),

                        $notifications->items()[$i]["template_string"]
                    );

                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[garage_name]",

                        ($notifications->items()[$i]->garage->name),

                        $notifications->items()[$i]["template_string"]
                    );
                }

                if(in_array($notifications->items()[$i]->template->type,["booking_created_by_client","booking_accepted_by_client"]) ) {

                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[Date]",
                        ($notifications->items()[$i]->booking->job_start_date),

                        $notifications->items()[$i]["template_string"]
                    );
                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[Time]",
                        ($notifications->items()[$i]->booking->job_start_time),

                        $notifications->items()[$i]["template_string"]
                    );

                }











                // link section
                $notifications->items()[$i]["link"] = json_decode($notifications->items()[$i]->template->link);



                $notifications->items()[$i]["link"] =  str_replace(
                    "[customer_id]",
                    $notifications->items()[$i]->customer_id,
                    $notifications->items()[$i]["link"]
                );

                $notifications->items()[$i]["link"] =  str_replace(
                    "[pre_booking_id]",
                    $notifications->items()[$i]->pre_booking_id,
                    $notifications->items()[$i]["link"]
                );

                $notifications->items()[$i]["link"] =  str_replace(
                    "[garage_id]",
                    $notifications->items()[$i]->garage_id,
                    $notifications->items()[$i]["link"]
                );

                $notifications->items()[$i]["link"] =  str_replace(
                    "[bid_id]",
                    $notifications->items()[$i]->bid_id,
                    $notifications->items()[$i]["link"]
                );
            }

            $data = json_decode(json_encode($notifications),true);

            $data["total_unread_messages"] = Notification::where('receiver_id', $request->user()->id)->where([
                "status" => "unread"
            ])->count();
            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }


     /**
     *
     * @OA\Get(
     *      path="/v1.0/notifications/{garage_id}/{perPage}",
     *      operationId="getNotificationsByGarageId",
     *      tags={"notification_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   *              @OA\Parameter(
     *         name="garage_id",
     *         in="path",
     *         description="garage_id",
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

     *      summary="This method is to get notification by garage id",
     *      description="This method is to get notification by garage id",
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

    public function getNotificationsByGarageId($garage_id,$perPage, Request $request)
    {
        try {
     $this->storeActivity($request,"");
            if (!$this->garageOwnerCheck($garage_id)) {
                return response()->json([
                    "message" => "you are not the owner of the garage or the requested garage does not exist."
                ], 401);
            }

            $notificationsQuery = Notification::where([
                "receiver_id" => $request->user()->id,
                "garage_id" => $garage_id
            ]);



            $notifications = $notificationsQuery->orderByDesc("id")->paginate($perPage);


            $total_data = count($notifications->items());
            for ($i = 0; $i < $total_data; $i++) {

                 $notifications->items()[$i]["template_string"] = json_decode($notifications->items()[$i]->template->template);

                 error_log($notifications->items()[$i]["template_string"]);


                if (!empty($notifications->items()[$i]->customer_id)) {
                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[customer_name]",

                        ($notifications->items()[$i]->customer->first_Name . " " . $notifications->items()[$i]->customer->last_Name),

                        $notifications->items()[$i]["template_string"]
                    );
                }

                if (!empty($notifications->items()[$i]->garage_id)) {
                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[garage_owner_name]",

                        ($notifications->items()[$i]->garage->owner->first_Name . " " . $notifications->items()[$i]->garage->owner->last_Name),

                        $notifications->items()[$i]["template_string"]
                    );

                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[garage_name]",

                        ($notifications->items()[$i]->garage->name),

                        $notifications->items()[$i]["template_string"]
                    );
                }

                if(in_array($notifications->items()[$i]->template->type,["booking_created_by_client","booking_accepted_by_client"]) ) {

                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[Date]",
                        ($notifications->items()[$i]->booking->job_start_date),

                        $notifications->items()[$i]["template_string"]
                    );
                    $notifications->items()[$i]["template_string"] =  str_replace(
                        "[Time]",
                        ($notifications->items()[$i]->booking->job_start_time),

                        $notifications->items()[$i]["template_string"]
                    );


                }



                $notifications->items()[$i]["link"] = json_decode($notifications->items()[$i]->template->link);



                $notifications->items()[$i]["link"] =  str_replace(
                    "[customer_id]",
                    $notifications->items()[$i]->customer_id,
                    $notifications->items()[$i]["link"]
                );

                $notifications->items()[$i]["link"] =  str_replace(
                    "[pre_booking_id]",
                    $notifications->items()[$i]->pre_booking_id,
                    $notifications->items()[$i]["link"]
                );
                $notifications->items()[$i]["link"] =  str_replace(
                    "[booking_id]",
                    $notifications->items()[$i]->booking_id,
                    $notifications->items()[$i]["link"]
                );
                $notifications->items()[$i]["link"] =  str_replace(
                    "[job_id]",
                    $notifications->items()[$i]->job_id,
                    $notifications->items()[$i]["link"]
                );

                $notifications->items()[$i]["link"] =  str_replace(
                    "[garage_id]",
                    $notifications->items()[$i]->garage_id,
                    $notifications->items()[$i]["link"]
                );

                $notifications->items()[$i]["link"] =  str_replace(
                    "[bid_id]",
                    $notifications->items()[$i]->bid_id,
                    $notifications->items()[$i]["link"]
                );
            }

            $data = json_decode(json_encode($notifications),true);

            $data["total_unread_messages"] = Notification::where('receiver_id', $request->user()->id)->where([
                "status" => "unread"
            ])->count();
            return response()->json($data, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }






     /**
     *
     * @OA\Put(
     *      path="/v1.0/notifications/change-status",
     *      operationId="updateNotificationStatus",
     *      tags={"notification_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update notification status",
     *      description="This method is to update notification status",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"notification_ids"},
     *    @OA\Property(property="notification_ids", type="string", format="array", example={1,2,3,4,5,6}),

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

    public function updateNotificationStatus(NotificationStatusUpdateRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return    DB::transaction(function () use (&$request) {

                $updatableData = $request->validated();


     Notification::whereIn('id', $updatableData["notification_ids"])
    ->where('receiver_id', $request->user()->id)
    ->update([
        "status" => "read"
    ]);



                return response(["ok" => true], 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500,$request);
        }
    }


/**
        *
     * @OA\Delete(
     *      path="/v1.0/notifications/{id}",
     *      operationId="deleteNotificationById",
     *      tags={"notification_management"},
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
     *      summary="This method is to delete notification by id",
     *      description="This method is to delete notification by id",
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

    public function deleteNotificationById($id,Request $request) {

        try{
            $this->storeActivity($request,"");

            $notification = Notification::where([
                "id" => $id,
                'receiver_id' => $request->user()->id
            ])->first();

            if(!$notification) {
                return response(["message" => "Notification not found"], 404);
            }

            $notification->delete();
            return response(["message" => "Notification deleted"], 200);



        } catch(Exception $e){

        return $this->sendError($e,500,$request);


        }

    }
}
