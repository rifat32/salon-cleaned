<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRegisterShopRequest;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\MultipleImageUploadRequest;
use App\Http\Requests\ShopUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Shop;
use App\Models\ShopGallery;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ShopsController extends Controller
{
    use ErrorUtil,UserActivityUtil;


    /**
     *
  * @OA\Post(
  *      path="/v1.0/shop-image",
  *      operationId="createShopImage",
  *      tags={"shop_section.shop_management"},
  *       security={
  *           {"bearerAuth": {}}
  *       },
  *      summary="This method is to store shop image ",
  *      description="This method is to store shop image",
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

 public function createShopImage(ImageUploadRequest $request)
 {
     try{
        $this->storeActivity($request,"");
         $insertableData = $request->validated();

         $location =  config("setup-config.shop_gallery_location");

         $new_file_name = time() . '_' . str_replace(' ', '_', $insertableData["image"]->getClientOriginalName());

         $insertableData["image"]->move(public_path($location), $new_file_name);


         return response()->json(["image" => $new_file_name,"location" => $location,"full_location"=>("/".$location."/".$new_file_name)], 200);


     } catch(Exception $e){
         error_log($e->getMessage());
     return $this->sendError($e,500,$request);
     }
 }

 /**
        *
     * @OA\Post(
     *      path="/v1.0/shop-image-multiple",
     *      operationId="createShopImageMultiple",
     *      tags={"shop_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

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

    public function createShopImageMultiple(MultipleImageUploadRequest $request)
    {
        try{

            $this->storeActivity($request,"");
            $insertableData = $request->validated();

            $location =  config("setup-config.shop_gallery_location");

            $images = [];
            if(!empty($insertableData["images"])) {
                foreach($insertableData["images"] as $image){
                    $new_file_name = time() . '_' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->move(public_path($location), $new_file_name);

                    array_push($images,("/".$location."/".$new_file_name));




                    // GarageGallery::create([
                    //     "image" => ("/".$location."/".$new_file_name),
                    //     "garage_id" => $garage_id
                    // ]);




                }
            }


            return response()->json(["images" => $images], 201);


        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }

  /**
     *
  * @OA\Post(
  *      path="/v1.0/auth/register-with-shop",
  *      operationId="registerUserWithShop",
  *      tags={"shop_section.shop_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  *      summary="This method is to store user with shop",
  *      description="This method is to store user with shop",
  *
  *  @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(
  *            required={"user","shop"},
  *             @OA\Property(property="user", type="string", format="array",example={
  * "first_Name":"Rifat",
  * "last_Name":"Al-Ashwad",
  * "email":"rifatalashwad@gmail.com",
  *  "password":"12345678",
  *  "password_confirmation":"12345678",
  *  "phone":"01771034383",
  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
  *
  *  "address_line_1":"Dhaka",
  *  "address_line_2":"Dinajpur",
  * *  "country":"Bangladesh",
  * *  "country":"Bangladesh",
  *  "country":"Bangladesh",
  *  "city":"Dhaka",
  *  "postcode":"Dinajpur",
    *  "lat":"12",
  *  "long":"12",
  *
  * }),
  *
  *  @OA\Property(property="shop", type="string", format="array",example={
  * "name":"ABCD Shop",
  * "about":"Best Shop in Dhaka",
  * "web_page":"https://www.facebook.com/",
  *  "phone":"01771034383",
  *  "email":"rifatalashwad@gmail.com",
  *  "phone":"01771034383",
  *  "additional_information":"No Additional Information",
  *  "address_line_1":"Dhaka",
  *  "address_line_2":"Dinajpur",
  *    * *  "lat":"23.704263332849386",
  *    * *  "long":"90.44707059805279",
  *
  *  "country":"Bangladesh",
  *  "city":"Dhaka",
  *  "postcode":"Dinajpur",
  *  "sku_prefix":"bd shop",
  *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
       *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
  *  "is_mobile_shop":true,
  *  "wifi_available":true,
  *  "labour_rate":500
  *
  * }),
  *
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
 public function registerUserWithShop(AuthRegisterShopRequest $request) {

     try{
        $this->storeActivity($request,"");
  return  DB::transaction(function ()use (&$request) {
     if(!$request->user()->hasPermissionTo('shop_create')){
         return response()->json([
            "message" => "You can not perform this action"
         ],401);
    }
     $insertableData = $request->validated();

// user info starts ##############
 $insertableData['user']['password'] = Hash::make($insertableData['user']['password']);
 $insertableData['user']['remember_token'] = Str::random(10);
 $insertableData['user']['is_active'] = true;
 $insertableData['user']['created_by'] = $request->user()->id;
 $user =  User::create($insertableData['user']);
 $user->assignRole('shop_owner');
// end user info ##############


//  shop info ##############
     $insertableData['shop']['status'] = "pending";
     $insertableData['shop']['owner_id'] = $user->id;
     $insertableData['shop']['created_by'] = $request->user()->id;
     $shop =  Shop::create($insertableData['shop']);
     if(!empty($insertableData["images"])) {
        foreach($insertableData["images"] as $shop_images){
            ShopGallery::create([
                "image" => $shop_images,
                "shop_id" =>$shop->id,
            ]);
        }
     }

// end shop info ##############



     return response([
         "user" => $user,
         "shop" => $shop
     ], 201);
     });
     } catch(Exception $e){

     return $this->sendError($e,500,$request);
     }

 }



  /**
     *
  * @OA\Put(
  *      path="/v1.0/shops",
  *      operationId="updateShop",
  *      tags={"shop_section.shop_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  *      summary="This method is to update user with shop",
  *      description="This method is to update user with shop",
  *
  *  @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(
  *            required={"user","shop"},
  *             @OA\Property(property="user", type="string", format="array",example={
  *  * "id":1,
  * "first_Name":"Rifat",
  * "last_Name":"Al-Ashwad",
  * "email":"rifatalashwad@gmail.com",
  *  "password":"12345678",
  *  "password_confirmation":"12345678",
  *  "phone":"01771034383",
  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
  *
  *  "address_line_1":"Dhaka",
  *  "address_line_2":"Dinajpur",
  *  "country":"Bangladesh",
  *  "city":"Dhaka",
  *  "postcode":"Dinajpur",
  *   *  "lat":"12",
  *  "long":"12",
  * }),
  *
  *  @OA\Property(property="shop", type="string", format="array",example={
  *   *  * "id":1,
  * "name":"ABCD Shop",
  * "about":"Best Shop in Dhaka",
  * "web_page":"https://www.facebook.com/",
  *  "phone":"01771034383",
  *  "email":"rifatalashwad@gmail.com",
  *  "phone":"01771034383",
  *  "additional_information":"No Additional Information",
  *  "address_line_1":"Dhaka",
  *  "address_line_2":"Dinajpur",
  *    * *  "lat":"23.704263332849386",
  *    * *  "long":"90.44707059805279",
  *
  *  "country":"Bangladesh",
  *  "city":"Dhaka",
  *  "postcode":"Dinajpur",
  * "sku_prefix":"bd shop",
  *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
       *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
  *  "is_mobile_shop":true,
  *  "wifi_available":true,
  *  "labour_rate":500
  *
  * }),
  *

  *
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
 public function updateShop(ShopUpdateRequest $request) {

     try{
        $this->storeActivity($request,"");
  return  DB::transaction(function ()use (&$request) {
     if(!$request->user()->hasPermissionTo('shop_update')){
         return response()->json([
            "message" => "You can not perform this action"
         ],401);
    }


    $updatableData = $request->validated();
 //    user email check
    $userPrev = User::where([
     "id" => $updatableData["user"]["id"]
    ]);
    if(!$request->user()->hasRole('superadmin')) {
     $userPrev =    $userPrev->where([
         "created_by" =>$request->user()->id
     ]);
 }
 $userPrev = $userPrev->first();
  if(!$userPrev) {
         return response()->json([
            "message" => "no user found with this id"
         ],404);
  }


if($userPrev->email !== $updatableData['user']['email']) {
     if(User::where(["email" => $updatableData['user']['email']])->exists()) {
           return response()->json([
              "message" => "The given data was invalid.",
              "errors" => ["user.password"=>["email already taken"]]
           ],422);
     }
 }
 // user email check
  // shop email check + authorization check
  $shopPrev = Shop::where([
     "id" => $updatableData["shop"]["id"]
  ]);
  if(!$request->user()->hasRole('superadmin')) {
     $shopPrev =    $shopPrev->where([
         "created_by" =>$request->user()->id
     ]);
 }
 $shopPrev = $shopPrev->first();
 if(!$shopPrev) {
     return response()->json([
        "message" => "no shop found with this id"
     ],404);
   }

if($shopPrev->email !== $updatableData['shop']['email']) {
     if(Shop::where(["email" => $updatableData['shop']['email']])->exists()) {
           return response()->json([
              "message" => "The given data was invalid.",
              "errors" => ["shop.password"=>["email already taken"]]
           ],422);
     }
 }
 // shop email check + authorization check



     if(!empty($updatableData['user']['password'])) {
         $updatableData['user']['password'] = Hash::make($updatableData['user']['password']);
     } else {
         unset($updatableData['user']['password']);
     }
     $updatableData['user']['is_active'] = true;
     $updatableData['user']['remember_token'] = Str::random(10);
     $user  =  tap(User::where([
         "id" => $updatableData['user']["id"]
         ]))->update(collect($updatableData['user'])->only([
         'first_Name',
         'last_Name',
         'phone',
         'image',
         'address_line_1',
         'address_line_2',
         'country',
         'city',
         'postcode',
         'email',
         'password',
         "lat",
         "long",

     ])->toArray()
     )
         // ->with("somthing")

         ->first();
         if(!$user) {
            return response()->json([
                "message" => "no user found"
                ],404);

    }

     $user->syncRoles(["shop_owner"]);



//  shop info ##############
     // $updatableData['shop']['status'] = "pending";

     $shop  =  tap(Shop::where([
         "id" => $updatableData['shop']["id"]
         ]))->update(collect($updatableData['shop'])->only([
             "name",
             "about",
             "web_page",
             "phone",
             "email",
             "additional_information",
             "address_line_1",
             "address_line_2",
             "lat",
             "long",
             "country",
             "city",
             "postcode",
             "logo",
             "image",
             "sku_prefix",
             "status",
             // "is_active",
             "is_mobile_shop",
             "wifi_available",
             "labour_rate",

     ])->toArray()
     )
         // ->with("somthing")

         ->first();

         if(!$shop) {
            return response()->json([
                "massage" => "no shop found"
            ],404);

        }
        if(!empty($updatableData["images"])) {
            foreach($updatableData["images"] as $shop_images){
                ShopGallery::create([
                    "image" => $shop_images,
                    "shop_id" =>$shop->id,
                ]);
            }
        }


// end shop info ##############






     return response([
         "user" => $user,
         "shop" => $shop
     ], 201);
     });
     } catch(Exception $e){

     return $this->sendError($e,500,$request);
     }

 }



 /**
     *
  * @OA\Get(
  *      path="/v1.0/shops/{perPage}",
  *      operationId="getShops",
  *      tags={"shop_section.shop_management"},
  * *  @OA\Parameter(
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
  * *  @OA\Parameter(
* name="address",
* in="query",
* description="address",
* required=true,
* example="address"
* ),
  * *  @OA\Parameter(
* name="country_code",
* in="query",
* description="country_code",
* required=true,
* example="country_code"
* ),
  * *  @OA\Parameter(
* name="city",
* in="query",
* description="city",
* required=true,
* example="city"
* ),
 * *  @OA\Parameter(
* name="start_lat",
* in="query",
* description="start_lat",
* required=true,
* example="3"
* ),
  * *  @OA\Parameter(
* name="end_lat",
* in="query",
* description="end_lat",
* required=true,
* example="2"
* ),
  * *  @OA\Parameter(
* name="start_long",
* in="query",
* description="start_long",
* required=true,
* example="1"
* ),
  * *  @OA\Parameter(
* name="end_long",
* in="query",
* description="end_long",
* required=true,
* example="4"
* ),
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
  *      summary="This method is to get shops",
  *      description="This method is to get shops",
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

 public function getShops($perPage,Request $request) {

     try{
        $this->storeActivity($request,"");
         if(!$request->user()->hasPermissionTo('shop_view')){
             return response()->json([
                "message" => "You can not perform this action"
             ],401);
        }

         $shopsQuery = Shop::with(
             "owner",

         );


         if(!$request->user()->hasRole('superadmin')) {
             $shopsQuery =    $shopsQuery->where([
                 "created_by" =>$request->user()->id
             ]);
         }

         if(!empty($request->search_key)) {
             $shopsQuery = $shopsQuery->where(function($query) use ($request){
                 $term = $request->search_key;
                 $query->where("name", "like", "%" . $term . "%");
                 $query->orWhere("phone", "like", "%" . $term . "%");
                 $query->orWhere("email", "like", "%" . $term . "%");
                 $query->orWhere("city", "like", "%" . $term . "%");
                 $query->orWhere("postcode", "like", "%" . $term . "%");
             });

         }


         if (!empty($request->start_date)) {
             $shopsQuery = $shopsQuery->where('created_at', ">=", $request->start_date);
         }
         if (!empty($request->end_date)) {
             $shopsQuery = $shopsQuery->where('created_at', "<=", $request->end_date);
         }

         if (!empty($request->start_lat)) {
             $shopsQuery = $shopsQuery->where('lat', ">=", $request->start_lat);
         }
         if (!empty($request->end_lat)) {
             $shopsQuery = $shopsQuery->where('lat', "<=", $request->end_lat);
         }
         if (!empty($request->start_long)) {
             $shopsQuery = $shopsQuery->where('long', ">=", $request->start_long);
         }
         if (!empty($request->end_long)) {
             $shopsQuery = $shopsQuery->where('long', "<=", $request->end_long);
         }

         if (!empty($request->address)) {
            $shopsQuery = $shopsQuery->where(function ($query) use ($request) {
                $term = $request->address;
                $query->where("country", "like", "%" . $term . "%");
                $query->orWhere("city", "like", "%" . $term . "%");


            });
        }
         if (!empty($request->country_code)) {
             $shopsQuery =   $shopsQuery->orWhere("country", "like", "%" . $request->country_code . "%");

         }
         if (!empty($request->city)) {
             $shopsQuery =   $shopsQuery->orWhere("city", "like", "%" . $request->city . "%");

         }


         $shops = $shopsQuery->orderByDesc("id")->paginate($perPage);
         return response()->json($shops, 200);
     } catch(Exception $e){

     return $this->sendError($e,500,$request);
     }

 }

  /**
     *
  * @OA\Get(
  *      path="/v1.0/shops/single/{id}",
  *      operationId="getShopById",
  *      tags={"shop_section.shop_management"},
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
  *      summary="This method is to get shop by id",
  *      description="This method is to get shop by id",
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

 public function getShopById($id,Request $request) {

     try{
        $this->storeActivity($request,"");
         if(!$request->user()->hasPermissionTo('shop_view')){
             return response()->json([
                "message" => "You can not perform this action"
             ],401);
        }

         $shopsQuery = Shop::with(
             "owner"
         );


         if(!$request->user()->hasRole('superadmin')) {
             $shopsQuery =    $shopsQuery->where([
                 "created_by" =>$request->user()->id
             ]);
         }

         $data["shop"] = $shopsQuery->where([
             "id" => $id
         ])
         ->first();



     return response()->json($data, 200);
     } catch(Exception $e){

     return $this->sendError($e,500,$request);
     }

 }

/**
     *
  * @OA\Delete(
  *      path="/v1.0/shops/{id}",
  *      operationId="deleteShopById",
  *      tags={"shop_section.shop_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  *              @OA\Parameter(
  *         name="id",
  *         in="path",
  *         description="id",
  *         required=true,
  *  example="6"
  *      ),
  *      summary="This method is to delete shop by id",
  *      description="This method is to delete shop by id",
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

 public function deleteShopById($id,Request $request) {

     try{
        $this->storeActivity($request,"");
         if(!$request->user()->hasPermissionTo('shop_delete')){
             return response()->json([
                "message" => "You can not perform this action"
             ],401);
        }

        $shopsQuery =   Shop::where([
         "id" => $id
        ]);
        if(!$request->user()->hasRole('superadmin')) {
         $shopsQuery =    $shopsQuery->where([
             "created_by" =>$request->user()->id
         ]);
     }

     $shop = $shopsQuery->first();

     $shop->delete();



         return response()->json(["ok" => true], 200);
     } catch(Exception $e){

     return $this->sendError($e,500,$request);
     }



 }




/**
     *
  * @OA\Get(
  *      path="/v1.0/available-countries/for-shop",
  *      operationId="getAvailableCountriesForShop",
  *      tags={"shop_section.shop_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },

  * *  @OA\Parameter(
* name="search_key",
* in="query",
* description="search_key",
* required=true,
* example="search_key"
* ),

  *      summary="This method is to get available country list for shop",
  *      description="This method is to get available country list for shop",
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

 public function getAvailableCountriesForShop(Request $request) {
     try{

        $this->storeActivity($request,"");
         $countryQuery = new Shop();

         if(!empty($request->search_key)) {
             $automobilesQuery = $countryQuery->where(function($query) use ($request){
                 $term = $request->search_key;
                 $query->where("country", "like", "%" . $term . "%");
             });

         }



         $countries = $countryQuery
         ->distinct("country")
         ->orderByDesc("country")
         ->select("id","country")
         ->get();
         return response()->json($countries, 200);
     } catch(Exception $e){

     return $this->sendError($e,500,$request);
     }

 }



 /**
     *
  * @OA\Get(
  *      path="/v1.0/available-cities/for-shop/{country_code}",
  *      operationId="getAvailableCitiesForShop",
  *      tags={"shop_section.shop_management"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  * *  @OA\Parameter(
* name="country_code",
* in="path",
* description="country_code",
* required=true,
* example="country_code"
* ),


  * *  @OA\Parameter(
* name="search_key",
* in="query",
* description="search_key",
* required=true,
* example="search_key"
* ),

  *      summary="This method is to get available city list",
  *      description="This method is to get available city list",
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

 public function getAvailableCitiesForShop($country_code,Request $request) {
     try{

        $this->storeActivity($request,"");
         $countryQuery =  Shop::where("country",$country_code);

         if(!empty($request->search_key)) {
             $automobilesQuery = $countryQuery->where(function($query) use ($request){
                 $term = $request->search_key;
                 $query->where("city", "like", "%" . $term . "%");
             });

         }



         $countries = $countryQuery
         ->distinct("city")
         ->orderByDesc("city")
         ->select("id","city")
         ->get();
         return response()->json($countries, 200);
     } catch(Exception $e){

     return $this->sendError($e,500,$request);
     }

 }


 /**
     *
  * @OA\Get(
  *      path="/v1.0/shops/by-shop-owner/all",
  *      operationId="getAllShopsByShopOwner",
  *      tags={"shop_section.shop_management"},

 *       security={
  *           {"bearerAuth": {}}
  *       },

  *      summary="This method is to get shops",
  *      description="This method is to get shops",
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

 public function getAllShopsByShopOwner(Request $request) {

     try{
        $this->storeActivity($request,"");
         if(!$request->user()->hasRole('shop_owner')){
             return response()->json([
                "message" => "You can not perform this action"
             ],401);
        }

         $shopsQuery = Shop::where([
             "owner_id" => $request->user()->id
         ]);



         $shops = $shopsQuery->orderByDesc("id")->get();
         return response()->json($shops, 200);
     } catch(Exception $e){

     return $this->sendError($e,500,$request);
     }

 }

}
