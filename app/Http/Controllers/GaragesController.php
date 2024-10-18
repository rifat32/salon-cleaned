<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRegisterGarageRequest;
use App\Http\Requests\GarageCreateRequest;
use App\Http\Requests\GarageTimeFormatUpdateRequest;
use App\Http\Requests\GarageUpdateRequest;
use App\Http\Requests\GarageUpdateSeparateRequest;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\MultipleImageUploadRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\GarageUtil;
use App\Http\Utils\UserActivityUtil;
use App\Mail\SendPassword;
use App\Mail\VerifyMail;
use App\Models\Garage;
use App\Models\GarageAutomobileMake;
use App\Models\GarageAutomobileModel;
use App\Models\GarageGallery;
use App\Models\GarageService;
use App\Models\GarageSubService;
use App\Models\GarageTime;
use App\Models\User;
use App\Models\UserTranslation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class GaragesController extends Controller
{
    use ErrorUtil,GarageUtil,UserActivityUtil;


       /**
        *
     * @OA\Post(
     *      path="/v1.0/garage-image",
     *      operationId="createGarageImage",
     *      tags={"garage_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store garage image ",
     *      description="This method is to store garage image",
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

    public function createGarageImage(ImageUploadRequest $request)
    {
        try{
            $this->storeActivity($request,"");
            // if(!$request->user()->hasPermissionTo('garage_create')){
            //      return response()->json([
            //         "message" => "You can not perform this action"
            //      ],401);
            // }

            $insertableData = $request->validated();

            $location =  config("setup-config.garage_gallery_location");

            $new_file_name = time() . '_' . str_replace(' ', '_', $insertableData["image"]->getClientOriginalName());

            $insertableData["image"]->move(public_path($location), $new_file_name);


            return response()->json(["image" => $new_file_name,"location" => $location,"full_location"=>("/".$location."/".$new_file_name)], 200);


        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }



public function createGarageImageV2(ImageUploadRequestInBase64 $request)
    {
        try {
            $this->storeActivity($request,"");

            // Decode the base64 image
            $base64Image = $request->validated()['image'];

            // Extract base64 string by removing metadata (if present)
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]); // Get the image extension (e.g., jpg, png, gif)
            } else {
                throw new \Exception('Invalid image format');
            }

            // Decode base64 to binary
            $base64Image = base64_decode($base64Image);
            if ($base64Image === false) {
                throw new \Exception('Base64 decode failed');
            }

            // Generate new file name
            $new_file_name = time() . '_' . uniqid() . '.' . $type;

            // Define the location to save the image
            $location = config("setup-config.garage_gallery_location");

            // Save the image to the public path
            $file_path = public_path($location) . '/' . $new_file_name;
            file_put_contents($file_path, $base64Image);

            return response()->json([
                "image" => $new_file_name,
                "location" => $location,
                "full_location" => "/" . $location . "/" . $new_file_name
            ], 200);

        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

  /**
        *
     * @OA\Post(
     *      path="/v1.0/garage-image-multiple",
     *      operationId="createGarageImageMultiple",
     *      tags={"garage_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to store garage gallery",
     *      description="This method is to store garage gallery",
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

    public function createGarageImageMultiple(MultipleImageUploadRequest $request)
    {
        try{
            $this->storeActivity($request,"");

            $insertableData = $request->validated();

            $location =  config("setup-config.garage_gallery_location");

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
     *      path="/v1.0/garages",
     *      operationId="createGarage",
     *      tags={"garage_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store garage",
     *      description="This method is to store  garage",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","garage","service"},

     *
     *  @OA\Property(property="garage", type="string", format="array",example={
     *  "owner_id":"1",
     * "name":"ABCD Garage",
     * "about":"Best Garage in Dhaka",
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
     *  * "currency":"BDT",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",

     *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     *  "is_mobile_garage":true,
     *  "wifi_available":true,
     *  "labour_rate":500,
     *  "time_format":"12-hour"
     *
     * }),
     *
     *
     *   *      *    @OA\Property(property="times", type="string", format="array",example={
     *
    *{"day":0,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
    *{"day":1,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
    *{"day":2,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
     *{"day":3,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
    *{"day":4,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
    *{"day":5,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true},
    *{"day":6,"opening_time":"10:10:00","closing_time":"10:15:00","is_closed":true}
     *
     * }),
     *
     *   *  @OA\Property(property="service", type="string", format="array",example={
     *{

     *"automobile_category_id":1,
     *"services":{
     *{
     *"id":1,
     *"checked":true,
     *  "sub_services":{{"id":1,"checked":true},{"id":2,"checked":false}}
     * }
     *},
     *"automobile_makes":{
     *{
     *"id":1,
     *"checked":true,
     *  "models":{{"id":1,"checked":true},{"id":2,"checked":false}}
     * }
     *}
     *

     *}

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
    public function createGarage(GarageCreateRequest $request) {

        try{
            $this->storeActivity($request,"");
     return  DB::transaction(function ()use (&$request) {

        if(!$request->user()->hasPermissionTo('garage_create')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }
        $insertableData = $request->validated();



$user = User::where([
    "id" =>  $insertableData['garage']['owner_id']
])
->first();

if(!$user) {
    $error =  [
        "message" => "The given data was invalid.",
        "errors" => ["owner_id"=>["No User Found"]]
 ];
    throw new Exception(json_encode($error),422);
}

if(!$user->hasRole('garage_owner')) {
    $error =  [
        "message" => "The given data was invalid.",
        "errors" => ["owner_id"=>["The user is not a Garage Owner"]]
 ];
    throw new Exception(json_encode($error),422);
}



        $insertableData['garage']['status'] = "pending";

        $insertableData['garage']['created_by'] = $request->user()->id;
        $insertableData['garage']['is_active'] = true;
        $garage =  Garage::create($insertableData['garage']);


        GarageTime::where([
            "garage_id" => $garage->id
           ])
           ->delete();
           $timesArray = collect($insertableData["times"])->unique("day");
           foreach($timesArray as $garage_time) {
            GarageTime::create([
                "garage_id" => $garage->id,
                "day"=> $garage_time["day"],
                "opening_time"=> $garage_time["opening_time"],
                "closing_time"=> $garage_time["closing_time"],
                "is_closed"=> $garage_time["is_closed"],
            ]);
           }




        if(!empty($insertableData["images"])) {
            foreach($insertableData["images"] as $garage_images){
                GarageGallery::create([
                    "image" => $garage_images,
                    "garage_id" =>$garage->id,
                ]);
            }
        }


  // end garage info ##############

  // create services
     $serviceUpdate = $this->createGarageServices($insertableData['service'],$garage->id);

     if(!$serviceUpdate["success"]){
        $error =  [
            "message" => "The given data was invalid.",
            "errors" => [("service".$serviceUpdate["type"])=>[$serviceUpdate["message"]]]
     ];
        throw new Exception(json_encode($error),422);

     }

     $this->storeQuestion($garage->id);


     UserTranslation::where([
        "user_id" => $user->id
    ])
    ->delete();

    $first_name_query = Http::get('https://api.mymemory.translated.net/get', [
        'q' => $user->first_Name,
        'langpair' => 'en|ar'  // Set the correct source and target language
    ]);

                 // Check for translation errors or unexpected responses
    if ($first_name_query['responseStatus'] !== 200) {
    throw new Exception('Translation failed');
    }

    $first_name_translation = $first_name_query['responseData']['translatedText'];

    $last_name_query = Http::get('https://api.mymemory.translated.net/get', [
        'q' => $user->last_Name,
        'langpair' => 'en|ar'  // Set the correct source and target language
    ]);

                 // Check for translation errors or unexpected responses
    if ($last_name_query['responseStatus'] !== 200) {
    throw new Exception('Translation failed');
    }
    $last_name_translation = $last_name_query['responseData']['translatedText'];


    UserTranslation::create([
        "user_id" => $user->id,
        "language" => "ar",
        "first_Name" => $first_name_translation,
        "last_Name" => $last_name_translation
    ]);





        return response([

            "garage" => $garage
        ], 201);
        });
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


     /**
        *
     * @OA\Post(
     *      path="/v1.0/auth/register-with-garage",
     *      operationId="registerUserWithGarage",
     *      tags={"garage_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user with garage",
     *      description="This method is to store user with garage",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","garage","service"},
     *             @OA\Property(property="user", type="string", format="array",example={
     * "first_Name":"Rifat",
     * "last_Name":"Al-Ashwad",
     * "email":"rifatalashwad@gmail.com",
     *  "password":"12345678",
     *  "password_confirmation":"12345678",
     *  "phone":"01771034383",
     *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     * "send_password":1
     *
     *
     * }),
     *
     *  @OA\Property(property="garage", type="string", format="array",example={
     * "name":"ABCD Garage",
     * "about":"Best Garage in Dhaka",
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
     *  * "currency":"BDT",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",

     *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     *  "is_mobile_garage":true,
     *  "wifi_available":true,
     *  "labour_rate":500,
     *  "time_format":"12-hour"
     *
     * }),
     *
     *   *  @OA\Property(property="service", type="string", format="array",example={
     *{

     *"automobile_category_id":1,
     *"services":{
     *{
     *"id":1,
     *"checked":true,
     *  "sub_services":{{"id":1,"checked":true},{"id":2,"checked":false}}
     * }
     *},
     *"automobile_makes":{
     *{
     *"id":1,
     *"checked":true,
     *  "models":{{"id":1,"checked":true},{"id":2,"checked":false}}
     * }
     *}
     *

     *}

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
    public function registerUserWithGarage(AuthRegisterGarageRequest $request) {

        try{
            $this->storeActivity($request,"");
     return  DB::transaction(function ()use (&$request) {

        if(!$request->user()->hasPermissionTo('garage_create')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }
        $insertableData = $request->validated();

   // user info starts ##############

   $password = $insertableData['user']['password'];
   $insertableData['user']['password'] = Hash::make($password);
   if(!$request->user()->hasRole('superadmin') || empty($insertableData['user']['password'])) {
    $password = Str::random(10);
    $insertableData['user']['password'] = Hash::make($password);
    }




    $insertableData['user']['remember_token'] = Str::random(10);
    $insertableData['user']['is_active'] = true;
    $insertableData['user']['created_by'] = $request->user()->id;

    $insertableData['user']['address_line_1'] = $insertableData['garage']['address_line_1'];
    $insertableData['user']['address_line_2'] = (!empty($insertableData['garage']['address_line_2'])?$insertableData['garage']['address_line_2']:"") ;
    $insertableData['user']['country'] = $insertableData['garage']['country'];
    $insertableData['user']['city'] = $insertableData['garage']['city'];
    $insertableData['user']['postcode'] = $insertableData['garage']['postcode'];
    $insertableData['user']['lat'] = $insertableData['garage']['lat'];
    $insertableData['user']['long'] = $insertableData['garage']['long'];

    $user =  User::create($insertableData['user']);

   // end user info ##############


  //  garage info ##############


        $insertableData['garage']['status'] = "pending";
        $insertableData['garage']['owner_id'] = $user->id;
        $insertableData['garage']['created_by'] = $request->user()->id;
        $insertableData['garage']['is_active'] = true;
        $garage =  Garage::create($insertableData['garage']);

        if(!empty($insertableData["images"])) {
            foreach($insertableData["images"] as $garage_images){
                GarageGallery::create([
                    "image" => $garage_images,
                    "garage_id" =>$garage->id,
                ]);
            }
        }

        GarageTime::where([
            "garage_id" => $garage->id
           ])
           ->delete();
           $timesArray = collect($insertableData["times"])->unique("day");
           foreach($timesArray as $garage_time) {
            GarageTime::create([
                "garage_id" => $garage->id,
                "day"=> $garage_time["day"],
                "opening_time"=> $garage_time["opening_time"],
                "closing_time"=> $garage_time["closing_time"],
                "is_closed"=> $garage_time["is_closed"],
            ]);
           }



  // end garage info ##############

  // create services
     $serviceUpdate = $this->createGarageServices($insertableData['service'],$garage->id);

     if(!$serviceUpdate["success"]){
        $error =  [
            "message" => "The given data was invalid.",
            "errors" => [("service".$serviceUpdate["type"])=>[$serviceUpdate["message"]]]
     ];
        throw new Exception(json_encode($error),422);

     }

     $user->email_verified_at = now();
     $user->business_id = $garage->id;
     $user->save();

     $user->assignRole('garage_owner');

     $this->storeQuestion($garage->id);

     if($insertableData['user']['send_password']) {
        if(env("SEND_EMAIL") == true) {
            Mail::to($insertableData['user']['email'])->send(new SendPassword($user,$password));
        }
    }


    UserTranslation::where([
        "user_id" => $user->id
    ])
    ->delete();

    $first_name_query = Http::get('https://api.mymemory.translated.net/get', [
        'q' => $user->first_Name,
        'langpair' => 'en|ar'  // Set the correct source and target language
    ]);

                 // Check for translation errors or unexpected responses
    if ($first_name_query['responseStatus'] !== 200) {
    throw new Exception('Translation failed');
    }

    $first_name_translation = $first_name_query['responseData']['translatedText'];

    $last_name_query = Http::get('https://api.mymemory.translated.net/get', [
        'q' => $user->last_Name,
        'langpair' => 'en|ar'  // Set the correct source and target language
    ]);

                 // Check for translation errors or unexpected responses
    if ($last_name_query['responseStatus'] !== 200) {
    throw new Exception('Translation failed');
    }
    $last_name_translation = $last_name_query['responseData']['translatedText'];


    UserTranslation::create([
        "user_id" => $user->id,
        "language" => "ar",
        "first_Name" => $first_name_translation,
        "last_Name" => $last_name_translation
    ]);




        return response([
            "user" => $user,
            "garage" => $garage
        ], 201);
        });
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }



     /**
        *
     * @OA\Put(
     *      path="/v1.0/garages",
     *      operationId="updateGarage",
     *      tags={"garage_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user with garage",
     *      description="This method is to update user with garage",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","garage","service"},
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
     *
     * }),
     *
     *  @OA\Property(property="garage", type="string", format="array",example={
     *   *  * "id":1,
     * "name":"ABCD Garage",
     * "about":"Best Garage in Dhaka",
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
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *      *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     *  "is_mobile_garage":true,
     *  "wifi_available":true,
     *  "labour_rate":500,
     *  "currency":"BDT",
     *  "time_format":"12-hour"
     *
     * }),
     *
     *   *  @OA\Property(property="service", type="string", format="array",example={
     *{

     *"automobile_category_id":1,
     *"services":{
     *{
     *"id":1,
     *"checked":true,
     *  "sub_services":{{"id":1,"checked":true},{"id":2,"checked":false}}
     * }
     *},
     *"automobile_makes":{
     *{
     *"id":1,
     *"checked":true,
     *  "models":{{"id":1,"checked":true},{"id":2,"checked":false}}
     * }
     *}
     *

     *}

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
    public function updateGarage(GarageUpdateRequest $request) {

        try{
            $this->storeActivity($request,"");
     return  DB::transaction(function ()use (&$request) {
        if(!$request->user()->hasPermissionTo('garage_update')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }
       if (!$this->garageOwnerCheck($request["garage"]["id"])) {
        return response()->json([
            "message" => "you are not the owner of the garage or the requested garage does not exist."
        ], 401);
    }

       $updatableData = $request->validated();
    //    user email check
       $userPrev = User::where([
        "id" => $updatableData["user"]["id"]
       ]);
       if(!$request->user()->hasRole('superadmin')) {
        $userPrev  = $userPrev->where(function ($query) {
            $query->where('created_by', auth()->user()->id)
                  ->orWhere('id', auth()->user()->id);
        });
    }
    $userPrev = $userPrev->first();
     if(!$userPrev) {
            return response()->json([
               "message" => "no user found with this id"
            ],404);
     }




    //  $garagePrev = Garage::where([
    //     "id" => $updatableData["garage"]["id"]
    //  ]);

    // $garagePrev = $garagePrev->first();
    // if(!$garagePrev) {
    //     return response()->json([
    //        "message" => "no garage found with this id"
    //     ],404);
    //   }

        if(!empty($updatableData['user']['password'])) {
            $updatableData['user']['password'] = Hash::make($updatableData['user']['password']);
        } else {
            unset($updatableData['user']['password']);
        }
        $updatableData['user']['is_active'] = true;
        $updatableData['user']['remember_token'] = Str::random(10);
        $updatableData['user']['address_line_1'] = $updatableData['garage']['address_line_1'];
    $updatableData['user']['address_line_2'] = $updatableData['garage']['address_line_2'];
    $updatableData['user']['country'] = $updatableData['garage']['country'];
    $updatableData['user']['city'] = $updatableData['garage']['city'];
    $updatableData['user']['postcode'] = $updatableData['garage']['postcode'];
    $updatableData['user']['lat'] = $updatableData['garage']['lat'];
    $updatableData['user']['long'] = $updatableData['garage']['long'];
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

        $user->syncRoles(["garage_owner"]);



  //  garage info ##############
        // $updatableData['garage']['status'] = "pending";

        $garage  =  tap(Garage::where([
            "id" => $updatableData['garage']["id"]
            ]))->update(collect($updatableData['garage'])->only([
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
                "status",
                // "is_active",
                "is_mobile_garage",
                "wifi_available",
                "labour_rate",
                "time_format",
                "currency",

        ])->toArray()
        )
            // ->with("somthing")

            ->first();
            if(!$garage) {
                return response()->json([
                    "massage" => "no garage found"
                ],404);

            }
            if(!empty($updatableData["images"])) {
                foreach($updatableData["images"] as $garage_images){
                    GarageGallery::create([
                        "image" => $garage_images,
                        "garage_id" =>$garage->id,
                    ]);
                }
            }


  // end garage info ##############

  GarageService::where([
    "garage_id" => $garage->id
  ])
  ->delete();
  GarageAutomobileMake::where([
    "garage_id" => $garage->id
    ])
    ->delete();

  // create services
  $this->createGarageServices($updatableData['service'],$garage->id);

  UserTranslation::where([
    "user_id" => $user->id
])
->delete();

$first_name_query = Http::get('https://api.mymemory.translated.net/get', [
    'q' => $user->first_Name,
    'langpair' => 'en|ar'  // Set the correct source and target language
]);

             // Check for translation errors or unexpected responses
if ($first_name_query['responseStatus'] !== 200) {
throw new Exception('Translation failed');
}

$first_name_translation = $first_name_query['responseData']['translatedText'];

$last_name_query = Http::get('https://api.mymemory.translated.net/get', [
    'q' => $user->last_Name,
    'langpair' => 'en|ar'  // Set the correct source and target language
]);

             // Check for translation errors or unexpected responses
if ($last_name_query['responseStatus'] !== 200) {
throw new Exception('Translation failed');
}
$last_name_translation = $last_name_query['responseData']['translatedText'];


UserTranslation::create([
    "user_id" => $user->id,
    "language" => "ar",
    "first_Name" => $first_name_translation,
    "last_Name" => $last_name_translation
]);




        return response([
            "user" => $user,
            "garage" => $garage
        ], 201);
        });
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }



     /**
        *
     * @OA\Put(
     *      path="/v1.0/garages/toggle-active",
     *      operationId="toggleActiveGarage",
     *      tags={"garage_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle garage",
     *      description="This method is to toggle garage",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
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

     public function toggleActiveGarage(GetIdRequest $request)
     {

         try{
             $this->storeActivity($request,"");
             if(!$request->user()->hasPermissionTo('garage_update')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }
            $updatableData = $request->validated();

            $garageQuery  = Garage::where(["id" => $updatableData["id"]]);
            if(!auth()->user()->hasRole('superadmin')) {
                $garageQuery = $garageQuery->where(function ($query) {
                    $query->where('created_by', auth()->user()->id);
                });
            }

            $garage =  $garageQuery->first();


            if (!$garage) {
                return response()->json([
                    "message" => "no garage found"
                ], 404);
            }


            $garage->update([
                'is_active' => !$garage->is_active
            ]);

            return response()->json(['message' => 'garage status updated successfully'], 200);


         } catch(Exception $e){
             error_log($e->getMessage());
         return $this->sendError($e,500,$request);
         }
     }





      /**
        *
     * @OA\Put(
     *      path="/v1.0/garages/separate",
     *      operationId="updateGarageSeparate",
     *      tags={"garage_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update garage",
     *      description="This method is to update garage",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"garage","service"},

     *
     *  @OA\Property(property="garage", type="string", format="array",example={
     *   *  * "id":1,
     * "name":"ABCD Garage",
     * "about":"Best Garage in Dhaka",
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
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *      *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     *  "is_mobile_garage":true,
     *  "wifi_available":true,
     *  "labour_rate":500,
     *  "time_format":"12-hour",
     * *  "currency":"BDT"
     *
     * }),
     *
     *   *  @OA\Property(property="service", type="string", format="array",example={
     *{

     *"automobile_category_id":1,
     *"services":{
     *{
     *"id":1,
     *"checked":true,
     *  "sub_services":{{"id":1,"checked":true},{"id":2,"checked":false}}
     * }
     *},
     *"automobile_makes":{
     *{
     *"id":1,
     *"checked":true,
     *  "models":{{"id":1,"checked":true},{"id":2,"checked":false}}
     * }
     *}
     *

     *}

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
    public function updateGarageSeparate(GarageUpdateSeparateRequest $request) {

        try{
            $this->storeActivity($request,"");
     return  DB::transaction(function ()use (&$request) {
        if(!$request->user()->hasPermissionTo('garage_update')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }
       if (!$this->garageOwnerCheck($request["garage"]["id"])) {
        return response()->json([
            "message" => "you are not the owner of the garage or the requested garage does not exist."
        ], 401);
    }

       $updatableData = $request->validated();


  //  garage info ##############
        // $updatableData['garage']['status'] = "pending";

        $garage  =  tap(Garage::where([
            "id" => $updatableData['garage']["id"]
            ]))->update(collect($updatableData['garage'])->only([
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
                "status",
                // "is_active",
                "is_mobile_garage",
                "wifi_available",
                "labour_rate",
                "time_format",
             "currency",

        ])->toArray()
        )
            // ->with("somthing")

            ->first();
            if(!$garage) {
                return response()->json([
                    "massage" => "no garage found"
                ],404);

            }
            if(!empty($updatableData["images"])) {
                foreach($updatableData["images"] as $garage_images){
                    GarageGallery::create([
                        "image" => $garage_images,
                        "garage_id" =>$garage->id,
                    ]);
                }
            }


  // end garage info ##############

  GarageService::where([
    "garage_id" => $garage->id
  ])
  ->delete();
  GarageAutomobileMake::where([
    "garage_id" => $garage->id
    ])
    ->delete();

  // create services
  $this->createGarageServices($updatableData['service'],$garage->id);


        return response([
            "garage" => $garage
        ], 201);
        });
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

      /**
        *
     * @OA\Put(
     *      path="/v1.0/garages/update-time-format",
     *      operationId="updateGarageTimeFormat",
     *      tags={"garage_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update garage time format",
     *      description="This method is to update garage time format",
     *
        *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"garage_id","standard_lead_time",*   "booking_accept_start_time","booking_accept_end_time", "block_out_days"},
     *    @OA\Property(property="garage_id", type="number", format="number", example="1"),
     *  *    @OA\Property(property="time_format", type="number", format="number", example="12-hour")

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
    public function updateGarageTimeFormat(GarageTimeFormatUpdateRequest $request) {

        try{
            $this->storeActivity($request,"");
     return  DB::transaction(function ()use (&$request) {
        if(!$request->user()->hasPermissionTo('garage_update')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }
       if (!$this->garageOwnerCheck($request["garage_id"])) {
        return response()->json([
            "message" => "you are not the owner of the garage or the requested garage does not exist."
        ], 401);
    }

       $updatableData = $request->validated();


  //  garage info ##############
        // $updatableData['garage']['status'] = "pending";

        $garage  =  tap(Garage::where([
            "id" => $updatableData['garage_id']
            ]))->update(collect($updatableData['garage'])->only([
                "time_format",
        ])->toArray()
        )
            // ->with("somthing")

            ->first();
            if(!$garage) {
                throw new Exception("Something went wrong.");
            }




        return response([
            "garage" => $garage
        ], 201);
        });
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }



    /**
        *
     * @OA\Get(
     *      path="/v1.0/garages/{perPage}",
     *      operationId="getGarages",
     *      tags={"garage_management"},
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
* name="country_code",
* in="query",
* description="country_code",
* required=true,
* example="country_code"
* ),
    * *  @OA\Parameter(
* name="address",
* in="query",
* description="address",
* required=true,
* example="address"
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
     *      summary="This method is to get garages",
     *      description="This method is to get garages",
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

    public function getGarages($perPage,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('garage_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            $garagesQuery = Garage::with(
                "owner",
                "garageAutomobileMakes.garageAutomobileModels",
                "garageServices.garageSubServices.garage_sub_service_prices"
            );


            if(!$request->user()->hasRole('superadmin')) {
                $garagesQuery = $garagesQuery->where(function ($query) use ($request) {
                    $query->where('created_by', $request->user()->id)
                          ->orWhere('owner_id', $request->user()->id);
                });
            }

            if(!empty($request->search_key)) {
                $garagesQuery = $garagesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                    $query->orWhere("phone", "like", "%" . $term . "%");
                    $query->orWhere("email", "like", "%" . $term . "%");
                    $query->orWhere("city", "like", "%" . $term . "%");
                    $query->orWhere("postcode", "like", "%" . $term . "%");
                });

            }


            if (!empty($request->start_date)) {
                $garagesQuery = $garagesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $garagesQuery = $garagesQuery->where('created_at', "<=", $request->end_date);
            }

            if (!empty($request->start_lat)) {
                $garagesQuery = $garagesQuery->where('lat', ">=", $request->start_lat);
            }
            if (!empty($request->end_lat)) {
                $garagesQuery = $garagesQuery->where('lat', "<=", $request->end_lat);
            }
            if (!empty($request->start_long)) {
                $garagesQuery = $garagesQuery->where('long', ">=", $request->start_long);
            }
            if (!empty($request->end_long)) {
                $garagesQuery = $garagesQuery->where('long', "<=", $request->end_long);
            }

            if (!empty($request->address)) {
                $garagesQuery = $garagesQuery->where(function ($query) use ($request) {
                    $term = $request->address;
                    $query->where("country", "like", "%" . $term . "%");
                    $query->orWhere("city", "like", "%" . $term . "%");


                });
            }
            if (!empty($request->country_code)) {
                $garagesQuery =   $garagesQuery->orWhere("country", "like", "%" . $request->country_code . "%");

            }
            if (!empty($request->city)) {
                $garagesQuery =   $garagesQuery->orWhere("city", "like", "%" . $request->city . "%");

            }


            $garages = $garagesQuery->orderByDesc("id")->paginate($perPage);
            return response()->json($garages, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


   /**
        *
     * @OA\Get(
     *      path="/v2.0/garages/{perPage}",
     *      operationId="getGaragesV2",
     *      tags={"garage_management"},
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
* name="country_code",
* in="query",
* description="country_code",
* required=true,
* example="country_code"
* ),
    * *  @OA\Parameter(
* name="address",
* in="query",
* description="address",
* required=true,
* example="address"
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
     *      summary="This method is to get garages",
     *      description="This method is to get garages",
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

     public function getGaragesV2($perPage,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('garage_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            $garagesQuery = Garage::with([
                "owner"=> function($query) {
    $query->select("users.id","users.first_Name","users.last_Name","users.email");
                },

            ]);


            if(!$request->user()->hasRole('superadmin')) {
                $garagesQuery = $garagesQuery->where(function ($query) use ($request) {
                    $query->where('created_by', $request->user()->id)
                          ->orWhere('owner_id', $request->user()->id);
                });
            }

            if(!empty($request->search_key)) {
                $garagesQuery = $garagesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                    $query->orWhere("phone", "like", "%" . $term . "%");
                    $query->orWhere("email", "like", "%" . $term . "%");
                    $query->orWhere("city", "like", "%" . $term . "%");
                    $query->orWhere("postcode", "like", "%" . $term . "%");
                });

            }


            if (!empty($request->start_date)) {
                $garagesQuery = $garagesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $garagesQuery = $garagesQuery->where('created_at', "<=", $request->end_date);
            }

            if (!empty($request->start_lat)) {
                $garagesQuery = $garagesQuery->where('lat', ">=", $request->start_lat);
            }
            if (!empty($request->end_lat)) {
                $garagesQuery = $garagesQuery->where('lat', "<=", $request->end_lat);
            }
            if (!empty($request->start_long)) {
                $garagesQuery = $garagesQuery->where('long', ">=", $request->start_long);
            }
            if (!empty($request->end_long)) {
                $garagesQuery = $garagesQuery->where('long', "<=", $request->end_long);
            }

            if (!empty($request->address)) {
                $garagesQuery = $garagesQuery->where(function ($query) use ($request) {
                    $term = $request->address;
                    $query->where("country", "like", "%" . $term . "%");
                    $query->orWhere("city", "like", "%" . $term . "%");


                });
            }
            if (!empty($request->country_code)) {
                $garagesQuery =   $garagesQuery->orWhere("country", "like", "%" . $request->country_code . "%");

            }
            if (!empty($request->city)) {
                $garagesQuery =   $garagesQuery->orWhere("city", "like", "%" . $request->city . "%");

            }


            $garages = $garagesQuery->orderByDesc("id")
            ->select("garages.id","garages.name","garages.email","garages.owner_id")

            ->paginate($perPage);
            return response()->json($garages, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }



     /**
        *
     * @OA\Get(
     *      path="/v1.0/garages/single/{id}",
     *      operationId="getGarageById",
     *      tags={"garage_management"},
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
     *      summary="This method is to get garage by id",
     *      description="This method is to get garage by id",
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

     public function getGarageById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('garage_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           if (!$this->garageOwnerCheck($id)) {
            return response()->json([
                "message" => "you are not the owner of the garage or the requested garage does not exist."
            ], 401);
        }

            $garage = Garage::with(
                "owner",
                "garageAutomobileMakes.automobileMake",
                "garageAutomobileMakes.garageAutomobileModels.automobileModel",
                "garageServices.service",
                "garageServices.garageSubServices.garage_sub_service_prices",
                "garageServices.garageSubServices.subService",
                "garage_times",
                "garageGalleries",
                "garage_packages",
                "garage_affiliations.affiliation"
            )->where([
                "id" => $id
            ])
            ->first();
            $data["garage_automobile_make_ids"] =  GarageAutomobileMake::where(["garage_id"=>$garage->id])->pluck("automobile_make_id");
            $data["garage_service_ids"] =  GarageService::where(["garage_id"=>$garage->id])->pluck("service_id");

            $data["garage_automobile_models_ids"] =  GarageAutomobileModel::
            whereHas("garageAutomobileMake", function($query) use ($garage) {
                  $query->where("garage_automobile_makes.garage_id",$garage->id);
            })
            ->pluck("automobile_model_id");


            $data["garage_sub_service_ids"] =  GarageSubService::
            whereHas("garageService", function($query) use ($garage) {
                  $query->where("garage_services.garage_id",$garage->id);
            })
            ->pluck("sub_service_id");

        $data["garage"] = $garage;


        return response()->json($data, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }
     /**
        *
     * @OA\Get(
     *      path="/v2.0/garages/single/{id}",
     *      operationId="getGarageByIdV2",
     *      tags={"garage_management"},
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
     *      summary="This method is to get garage by id",
     *      description="This method is to get garage by id",
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

    public function getGarageByIdV2($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('garage_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           if (!$this->garageOwnerCheck($id)) {
            return response()->json([
                "message" => "you are not the owner of the garage or the requested garage does not exist."
            ], 401);
        }

            $garage = Garage::with(
                [
                "owner",
                "automobile_makes",
                "automobile_makes.automobile_models" => function($query) use($id) {
                    $query->whereHas("garage_automobile_models.garageAutomobileMake", function($query) use($id) {
                       $query->where("garage_automobile_makes.garage_id",$id);
                    });
                },

                "services",
                "services.automobile_sub_services" => function($query) use($id) {
                    $query->whereHas("garage_automobile_sub_services.garageService", function($query) use($id) {
                       $query->where("garage_services.garage_id",$id);
                    });
                },



                "garage_times",
                "garageGalleries",
                "garage_packages",
                "garage_affiliations.affiliation"
                ]
            )->where([
                "id" => $id
            ])
            ->first();
       $garage_automobile_make_ids =  GarageAutomobileMake::where(["garage_id"=>$garage->id])->pluck("automobile_make_id");
        $garage_service_ids =   GarageService::where(["garage_id"=>$garage->id])->pluck("service_id");

        $data["garage"] = $garage;
        $data["garage_automobile_make_ids"] = $garage_automobile_make_ids;
        $data["garage_service_ids"] = $garage_service_ids;
        return response()->json($data, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

/**
        *
     * @OA\Delete(
     *      path="/v1.0/garages/{id}",
     *      operationId="deleteGarageById",
     *      tags={"garage_management"},
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
     *      summary="This method is to delete garage by id",
     *      description="This method is to delete garage by id",
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

    public function deleteGarageById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('garage_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

           $garagesQuery =   Garage::where([
            "id" => $id
           ]);
           if(!$request->user()->hasRole('superadmin')) {
            $garagesQuery =    $garagesQuery->where([
                "created_by" =>$request->user()->id
            ]);
        }

        $garage = $garagesQuery->first();

        $garage->delete();



            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }



    }




   /**
        *
     * @OA\Get(
     *      path="/v1.0/available-countries",
     *      operationId="getAvailableCountries",
     *      tags={"basics"},
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

     *      summary="This method is to get available country list",
     *      description="This method is to get available country list",
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

    public function getAvailableCountries(Request $request) {
        try{
            $this->storeActivity($request,"");

            $countryQuery = new Garage();

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
     *      path="/v1.0/available-cities/{country_code}",
     *      operationId="getAvailableCities",
     *      tags={"basics"},
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

    public function getAvailableCities($country_code,Request $request) {
        try{

            $this->storeActivity($request,"");
            $countryQuery =  Garage::where("country",$country_code);

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
     *      path="/v1.0/garages/by-garage-owner/all",
     *      operationId="getAllGaragesByGarageOwner",
     *      tags={"garage_management"},

    *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to get garages",
     *      description="This method is to get garages",
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

    public function getAllGaragesByGarageOwner(Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasRole('garage_owner')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            $garagesQuery = Garage::where([
                "owner_id" => $request->user()->id
            ]);



            $garages = $garagesQuery->orderByDesc("id")->get();
            return response()->json($garages, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


}
