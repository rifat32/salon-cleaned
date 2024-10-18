<?php

namespace App\Http\Controllers;

use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\GarageAutomobileMake;
use Exception;
use Illuminate\Http\Request;

class GarageAutomobilesController extends Controller
{
    use ErrorUtil,UserActivityUtil;
    /**
        *
     * @OA\Get(
     *      path="/v1.0/garage-automobile-makes/all/{garage_id}",
     *      operationId="getGarageAutomobileMakesAll",
     *      tags={"garage_automobile_management.make"},
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
     *      summary="This method is to get automobile makes  by garage  id",
     *      description="This method is to get automobile makes by  garage id",
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

    public function getGarageAutomobileMakesAll($garage_id,Request $request) {

        try{
            $this->storeActivity($request,"");


       $garage_automobile_makes=  GarageAutomobileMake::with("automobileMake")
       ->where(["garage_id"=>$garage_id])->get();
        // $garage_service_ids =   GarageService::where(["garage_id"=>$garage_id])->pluck("service_id");


        return response()->json($garage_automobile_makes, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


}
