<?php

namespace App\Http\Controllers;

use App\Http\Requests\MultipleImageUploadRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ShopUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\ShopGallery;
use Exception;
use Illuminate\Http\Request;

class ShopGalleryController extends Controller
{
    use ErrorUtil,ShopUtil,UserActivityUtil;
    /**
      *
   * @OA\Post(
   *      path="/v1.0/shop-galleries/{shop_id}",
   *      operationId="createShopGallery",
   *      tags={"shop_section.shop_gallery_management"},
   *       security={
   *           {"bearerAuth": {}}
   *       },
   *         @OA\Parameter(
   *         name="shop_id",
   *         in="path",
   *         description="shop_id",
   *         required=true,
   *  example="1"
   *      ),
   *      summary="This method is to store shop gallery",
   *      description="This method is to store shop gallery",
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

  public function createShopGallery($shop_id,MultipleImageUploadRequest $request)
  {
      try{
        $this->storeActivity($request,"");
          if(!$request->user()->hasPermissionTo('shop_gallery_create')){
               return response()->json([
                  "message" => "You can not perform this action"
               ],401);
          }

          if (!$this->shopOwnerCheck($shop_id)) {
              return response()->json([
                  "message" => "you are not the owner of the shop or the requested shop does not exist."
              ], 401);
          }
          $insertableData = $request->validated();

          $location =  config("setup-config.shop_gallery_location");
          if(!empty($insertableData["images"])) {
            foreach($insertableData["images"] as $image){
                $new_file_name = time() . '_' . str_replace(' ', '_', $image->getClientOriginalName());
                $image->move(public_path($location), $new_file_name);


                ShopGallery::create([
                    "image" => ("/".$location."/".$new_file_name),
                    "shop_id" => $shop_id
                ]);

            }
          }


          return response()->json(["ok" => true], 201);


      } catch(Exception $e){
          error_log($e->getMessage());
      return $this->sendError($e,500,$request);
      }
  }
/**
      *
   * @OA\Get(
   *      path="/v1.0/shop-galleries/{shop_id}",
   *      operationId="getShopGalleries",
   *      tags={"shop_section.shop_gallery_management"},
  *       security={
   *           {"bearerAuth": {}}
   *       },

   *              @OA\Parameter(
   *         name="shop_id",
   *         in="path",
   *         description="shop_id",
   *         required=true,
   *  example="6"
   *      ),
   *      summary="This method is to get shop galleries",
   *      description="This method is to get shop galleries",
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

  public function getShopGalleries($shop_id,Request $request) {
      try{
        $this->storeActivity($request,"");
          if(!$request->user()->hasPermissionTo('shop_gallery_view')){
              return response()->json([
                 "message" => "You can not perform this action"
              ],401);
         }

         if (!$this->shopOwnerCheck($shop_id)) {
          return response()->json([
              "message" => "you are not the owner of the shop or the requested shop does not exist."
          ], 401);
      }

          $data["shop_galleries"] = ShopGallery::where([
             "shop_id" => $shop_id
          ])->orderByDesc("id")->get();

          $data["image_location_folder"] =  config("setup-config.shop_gallery_location");
          return response()->json($data, 200);
      } catch(Exception $e){

      return $this->sendError($e,500,$request);
      }
  }



     /**
      *
   *     @OA\Delete(
   *      path="/v1.0/shop-galleries/{shop_id}/{id}",
   *      operationId="deleteShopGalleryById",
   *      tags={"shop_section.shop_gallery_management"},
  *       security={
   *           {"bearerAuth": {}}
   *       },
   * *              @OA\Parameter(
   *         name="shop_id",
   *         in="path",
   *         description="shop_id",
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
   *      summary="This method is to delete shop gallery by id",
   *      description="This method is to delete shop gellery by id",
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

  public function deleteShopGalleryById($shop_id,$id,Request $request) {
    $this->storeActivity($request,"");
      try{
          if(!$request->user()->hasPermissionTo('shop_gallery_delete')){
              return response()->json([
                 "message" => "You can not perform this action"
              ],401);
         }
         if (!$this->shopOwnerCheck($shop_id)) {
          return response()->json([
              "message" => "you are not the owner of the shop or the requested shop does not exist."
          ], 401);
      }

      $shop_gallery  = ShopGallery::where([
          "id" => $id,
          "shop_id" => $shop_id
         ])
         ->first();
         if(!$shop_gallery) {
          return response()->json([
              "message" => "gallery not found"
                  ], 404);
         }

      // Define the path of the file you want to delete
$location =  config("setup-config.shop_gallery_location");
$file_path = public_path($location) . '/' . $shop_gallery->image;

// Check if the file exists before trying to delete it
if (file_exists($file_path)) {
  unlink($file_path);
}
      $shop_gallery->delete();




          return response()->json(["ok" => true], 200);
      } catch(Exception $e){

      return $this->sendError($e,500,$request);
      }

  }


}
