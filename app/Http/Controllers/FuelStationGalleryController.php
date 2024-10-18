<?php

namespace App\Http\Controllers;

use App\Http\Requests\FuelStationGalleryCreateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\FuelStation;
use App\Models\FuelStationGallery;
use Exception;
use Illuminate\Http\Request;

class FuelStationGalleryController extends Controller
{
    use ErrorUtil,UserActivityUtil;

    /**
        *
     * @OA\Post(
     *      path="/v1.0/fuel-stations-galleries/{fuel_station_id}",
     *      operationId="createFuelStationGallery",
     *      tags={"fuel_station_gallery_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *         @OA\Parameter(
     *         name="fuel_station_id",
     *         in="path",
     *         description="fuel_station_id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to store fuel station gallery",
     *      description="This method is to store fuel station gallery",
     *
   *  @OA\RequestBody(
        *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"images[]"},
*         @OA\Property(
*             description="array of images to upload",
*             property="images[]",
*             type="array",
*             @OA\Items(
*                 type="file"
*             ),
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

    public function createFuelStationGallery($fuel_station_id,FuelStationGalleryCreateRequest $request)
    {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('fuel_station_gallery_create')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }

            $fuelStationQuery =   FuelStation::where([
                "id" => $fuel_station_id
               ]);
               if(!$request->user()->hasRole('superadmin')) {
                $fuelStationQuery =    $fuelStationQuery->where([
                    "created_by" =>$request->user()->id
                ]);
            }
            $fuelStation = $fuelStationQuery->first();
            if(!$fuelStation){
                return response()->json([
                    "message" => "fuel station does not exists or you did not created the fuel station"
                ],404);
            }


            $insertableData = $request->validated();

            $location =  config("setup-config.fuel_station_gallery_location");
            if(!empty($insertableData["images"])) {
                foreach($insertableData["images"] as $image){
                    $new_file_name = time() . '_' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->move(public_path($location), $new_file_name);


                    FuelStationGallery::create([
                        "image" => ("/".$location."/".$new_file_name),
                        "fuel_station_id" => $fuel_station_id
                    ]);

                }
            }


            return response()->json(["ok" => true], 201);


        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }

    public function createFuelStationGalleryByUrl($fuel_station_id, Request $request)
{
    try {
        $this->storeActivity($request,"");
        if(!$request->user()->hasPermissionTo('fuel_station_gallery_create')){
             return response()->json([
                "message" => "You can not perform this action"
             ],401);
        }

        $fuelStationQuery =   FuelStation::where([
            "id" => $fuel_station_id
           ]);
           if(!$request->user()->hasRole('superadmin')) {
            $fuelStationQuery =    $fuelStationQuery->where([
                "created_by" =>$request->user()->id
            ]);
        }
        $fuelStation = $fuelStationQuery->first();
        if(!$fuelStation){
            return response()->json([
                "message" => "fuel station does not exists or you did not created the fuel station"
            ],404);
        }

        $validatedData = $request->validate([
            'urls' => 'required|array',
            'urls.*' => 'string'
        ]);



        foreach ($validatedData['urls'] as $url) {
            FuelStationGallery::create([
                "image" => $url,
                "fuel_station_id" => $fuel_station_id
            ]);
        }

        return response()->json(["ok" => true], 201);

    } catch (Exception $e) {
        error_log($e->getMessage());
        return $this->sendError($e, 500, $request);
    }
}

    /**
        *
     * @OA\Get(
     *      path="/v1.0/fuel-stations-galleries/{fuel_station_id}",
     *      operationId="getFuelStationGalleries",
     *      tags={"fuel_station_gallery_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="fuel_station_id",
     *         in="path",
     *         description="fuel_station_id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get fuel station galleries",
     *      description="This method is to get fuel station galleries",
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

    public function getFuelStationGalleries($fuel_station_id,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('fuel_station_gallery_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

           $fuelStationQuery =   FuelStation::where([
            "id" => $fuel_station_id
           ]);
           if(!$request->user()->hasRole('superadmin')) {
            $fuelStationQuery =    $fuelStationQuery->where([
                "created_by" =>$request->user()->id
            ]);
        }
        $fuelStation = $fuelStationQuery->first();
        if(!$fuelStation){
            return response()->json([
                "message" => "fuel station does not exists or you did not created the fuel station"
            ],404);
        }


            $data["fuel_station_galleries"] = FuelStationGallery::where([
               "fuel_station_id" => $fuel_station_id
            ])->orderByDesc("id")->get();


            return response()->json($data, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }
     /**
        *
     * @OA\Get(
     *      path="/v1.0/client/fuel-stations-galleries/{fuel_station_id}",
     *      operationId="getFuelStationGalleriesClient",
     *      tags={"client.fuel_station_gallery_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="fuel_station_id",
     *         in="path",
     *         description="fuel_station_id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get fuel station galleries client",
     *      description="This method is to get fuel station galleries client",
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

    public function getFuelStationGalleriesClient($fuel_station_id,Request $request) {
        try{

            $this->storeActivity($request,"");

            $data["fuel_station_galleries"] = FuelStationGallery::where([
               "fuel_station_id" => $fuel_station_id
            ])->orderByDesc("id")->get();


            return response()->json($data, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }



       /**
        *
     *     @OA\Delete(
     *      path="/v1.0/fuel-stations-galleries/{fuel_station_id}/{id}",
     *      operationId="deleteFuelStationGalleryById",
     *      tags={"fuel_station_gallery_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     * *              @OA\Parameter(
     *         name="fuel_station_id",
     *         in="path",
     *         description="fuel_station_id",
     *         required=true,
     *  example="1"
     *      ),
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to delete fuel station gallery by id",
     *      description="This method is to delete fuel station gellery by id",
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

    public function deleteFuelStationGalleryById($fuel_station_id,$id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('fuel_station_gallery_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

           $fuelStationQuery =   FuelStation::where([
            "id" => $fuel_station_id
           ]);
           if(!$request->user()->hasRole('superadmin')) {
            $fuelStationQuery =    $fuelStationQuery->where([
                "created_by" =>$request->user()->id
            ]);
        }
        $fuelStation = $fuelStationQuery->first();
        if(!$fuelStation){
            return response()->json([
                "message" => "fuel station does not exists or you did not created the fuel station"
            ],404);
        }


        $fuel_station_gallery  = FuelStationGallery::where([
            "id" => $id,
            "fuel_station_id" => $fuel_station_id
           ])
           ->first();
           if(!$fuel_station_gallery) {
            return response()->json([
                "message" => "gallery not found"
                    ], 404);
           }

        // Define the path of the file you want to delete
$location =  config("setup-config.fuel_station_gallery_location");
$file_path = public_path($location) . '/' . $fuel_station_gallery->image;

// Check if the file exists before trying to delete it
if (file_exists($file_path)) {
    unlink($file_path);
}
        $fuel_station_gallery->delete();




            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


}
