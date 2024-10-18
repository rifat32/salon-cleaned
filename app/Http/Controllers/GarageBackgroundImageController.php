<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageUploadRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Garage;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class GarageBackgroundImageController extends Controller
{
    use ErrorUtil,UserActivityUtil;
       /**
        *
     * @OA\Post(
     *      path="/v1.0/garage-background-image",
     *      operationId="updateGarageBackgroundImage",
     *      tags={"garage_background_image"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store garage bakground image ",
     *      description="This method is to garage bakground user image",
     *
   *  @OA\RequestBody(
        *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"image"},
*         @OA\Property(
*             description="image to upload",
*             property="image",
*             type="file",
*             collectionFormat="multi",
*         )
*     )
* )



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

     public function updateGarageBackgroundImage(ImageUploadRequest $request)
     {
         try{
             $this->storeActivity($request,"");
             if (!$request->user()->hasPermissionTo('global_garage_background_image_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

             $insertableData = $request->validated();

             $location =  config("setup-config.garage_background_image_location");

             $new_file_name = time() . "_" ."garage_background_image.jpeg";

             $insertableData["image"]->move(public_path($location), $new_file_name);


// Update the desired key in the configuration
config(["setup-config.garage_background_image_location_full" => $location . "/" . $new_file_name]);

// Save the updated configuration to the file
File::put(config_path('setup-config.php'), '<?php return ' . var_export(config('setup-config'), true) . ';');









             return response()->json(["image" => $new_file_name,"location" => $location,"full_location"=>("/".$location."/".$new_file_name)], 200);


         } catch(Exception $e){
             error_log($e->getMessage());
         return $this->sendError($e,500,$request);
         }
     }


          /**
        *
     * @OA\Post(
     *      path="/v1.0/garage-background-image/by-user",
     *      operationId="updateGarageBackgroundImageByUser",
     *      tags={"garage_background_image"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to store garage bakground image by owner ",
     *      description="This method is to garage bakground user image by owner",
     *
   *  @OA\RequestBody(
        *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"image"},
*         @OA\Property(
*             description="image to upload",
*             property="image",
*             type="file",
*             collectionFormat="multi",
*         )
*     )
* )



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

     public function updateGarageBackgroundImageByUser(ImageUploadRequest $request)
     {
         try{
             $this->storeActivity($request,"");



             $insertableData = $request->validated();

             $location =  config("setup-config.garage_background_image_location");


             $new_file_name = time() . '_' . str_replace(' ', '_', $insertableData["image"]->getClientOriginalName());
             $insertableData["image"]->move(public_path($location), $new_file_name);


             User::where([
                "id" => $request->user()->id
             ])
             ->update([
                "background_image" => ("/".$location."/".$new_file_name)
             ]);










             return response()->json(["image" => $new_file_name,"location" => $location,"full_location"=>("/".$location."/".$new_file_name)], 200);


         } catch(Exception $e){
             error_log($e->getMessage());
         return $this->sendError($e,500,$request);
         }
     }



      /**
        *
     * @OA\Get(
     *      path="/v1.0/garage-background-image",
     *      operationId="getGarageBackgroundImage",
     *      tags={"garage_background_image"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to get garage background image",
     *      description="This method is to get garage background image",
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

    public function getGarageBackgroundImage(Request $request) {

        try{
            $this->storeActivity($request,"");

            if (!$request->user()->hasPermissionTo('global_garage_background_image_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $image = ("/".  config("setup-config.garage_background_image_location_full"));




            return response()->json($image, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


}
