<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStripeSettingRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\StripeSetting;
use Exception;
use Illuminate\Http\Request;

class StripeSettingController extends Controller
{
    use ErrorUtil,UserActivityUtil;
    /**
   *
   * @OA\Put(
   *      path="/v1.0/stripe-settings",
   *      operationId="updateStripeSetting",
   *      tags={"stripe_setting"},
   *       security={
   *           {"bearerAuth": {}}
   *       },
   *      summary="This method is to update stripe setting",
   *      description="This method is to update stripe setting",
   *
   *  @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *
   * *         @OA\Property(property="STRIPE_KEY", type="string", format="string",example="STRIPE_KEY"),
   *           @OA\Property(property="STRIPE_SECRET", type="string", format="string",example="STRIPE_SECRET"),
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

   public function updateStripeSetting(UpdateStripeSettingRequest $request)
   {

       try {
           $this->storeActivity($request, "DUMMY activity","DUMMY description");
           if (!$request->user()->hasPermissionTo('stripe_setting_update')) {
               return response()->json([
                   "message" => "You can not perform this action"
               ], 401);
           }
           $request_data = $request->validated();


          $stripeSetting = StripeSetting::
          where([
            "business_id" => auth()->user()->business_id
        ])
        ->first();

          if (!$stripeSetting) {
              return response()->json([
                  "message" => "no system setting found"
              ], 404);
          }

              $stripeSetting->fill(collect($request_data)->only([
                  'business_id',
                  'STRIPE_KEY',
                  "STRIPE_SECRET",
              ])->toArray());
              $stripeSetting->save();

              $stripeSettingArray = $stripeSetting->toArray();

              $stripeSettingArray["STRIPE_KEY"] = $stripeSetting->STRIPE_KEY;
              $stripeSettingArray["STRIPE_SECRET"] = $stripeSetting->STRIPE_SECRET;

           return response()->json($stripeSettingArray, 200);
       } catch (Exception $e) {
           error_log($e->getMessage());
           return $this->sendError($e, 500, $request);
       }
   }

/**
   *
   * @OA\Get(
   *      path="/v1.0/system-settings",
   *      operationId="getStripeSetting",
   *      tags={"stripe_setting"},
   *       security={
   *           {"bearerAuth": {}}
   *       },
   *      summary="This method is to get stripe _setting",
   *      description="This method is to get stripe setting",
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

   public function getStripeSetting(Request $request)
   {
       try {
           $this->storeActivity($request, "DUMMY activity","DUMMY description");
           if (!$request->user()->hasPermissionTo('system_setting_view')) {
               return response()->json([
                   "message" => "You can not perform this action"
               ], 401);
           }


           $stripeSetting = StripeSetting::
           where([
               "business_id" => auth()->user()->business_id
           ])
           ->first();

           $stripeSettingArray = $stripeSetting->toArray();

           $stripeSettingArray["STRIPE_KEY"] = $stripeSetting->STRIPE_KEY;
           $stripeSettingArray["STRIPE_SECRET"] = $stripeSetting->STRIPE_SECRET;


           return response()->json($stripeSettingArray, 200);
       } catch (Exception $e) {

           return $this->sendError($e, 500, $request);
       }
   }

/**
   *
   * @OA\Get(
   *      path="/v1.0/client/system-settings",
   *      operationId="getSystemSettingSettingClient",
   *      tags={"system_setting"},
   *       security={
   *           {"bearerAuth": {}}
   *       },

   *              @OA\Parameter(
   *         name="per_page",
   *         in="query",
   *         description="per_page",
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
   *    * *  @OA\Parameter(
   * name="business_tier_id",
   * in="query",
   * description="business_tier_id",
   * required=true,
   * example="1"
   * ),
   * *  @OA\Parameter(
   * name="order_by",
   * in="query",
   * description="order_by",
   * required=true,
   * example="ASC"
   * ),

   *      summary="This method is to get system_setting",
   *      description="This method is to get system_setting",
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

   public function getSystemSettingSettingClient(Request $request)
   {
       try {
           $this->storeActivity($request, "DUMMY activity","DUMMY description");

           $stripeSetting = StripeSetting::first();

           return response()->json($stripeSetting, 200);
       } catch (Exception $e) {

           return $this->sendError($e, 500, $request);
       }
   }








}
