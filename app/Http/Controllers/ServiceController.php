<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceCreateRequest;
use App\Http\Requests\ServiceFuelTypeUpdateRequest;
use App\Http\Requests\ServiceUpdateRequest;
use App\Http\Requests\SubServiceCreateRequest;
use App\Http\Requests\SubServiceUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\AutomobileCategory;
use App\Models\AutomobileMake;
use App\Models\AutomobileModel;
use App\Models\BookingSubService;
use App\Models\FuelStationService;
use App\Models\Service;
use App\Models\SubService;
use App\Models\PaymentType;
use App\Models\ServiceTranslation;
use App\Models\SubServiceTranslation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;



class ServiceController extends Controller
{
    use ErrorUtil,UserActivityUtil;
       /**
        *
     * @OA\Post(
     *      path="/v1.0/services",
     *      operationId="createService",
     *      tags={"service_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store service",
     *      description="This method is to store service",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","icon","description","automobile_category_id"},
     *    @OA\Property(property="name", type="string", format="string",example="car"),
     *  *    @OA\Property(property="icon", type="string", format="string",example="fa fa tui halua kha"),
     *    @OA\Property(property="description", type="string", format="string",example="car"),
     *    @OA\Property(property="automobile_category_id", type="string", format="number",example="1"),

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

    public function createService(ServiceCreateRequest $request)
    {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('service_create')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }

            $insertableData = $request->validated();

            $insertableData["business_id"] = auth()->user()->business_id;

            $service =  Service::create($insertableData);

         // Translate the service name using MyMemory
$service_name_query = Http::get('https://api.mymemory.translated.net/get', [
    'q' => $service->name,
    'langpair' => 'en|ar'  // Specify the source language as 'en' (English) and target as 'ar' (Arabic)
]);


// Check for translation errors or unexpected responses
if ( $service_name_query['responseStatus'] !== 200) {
    throw new Exception('Translation failed');
}
// Extract the translated text from the response
$service_name_translation = $service_name_query['responseData']['translatedText'];


$service_description_translation = "";
if(!empty($service->description)) {
    $service_description_query = Http::get('https://api.mymemory.translated.net/get', [
        'q' => $service->description,
        'langpair' => 'en|ar'
    ]);

    // Check for translation errors or unexpected responses
    if ($service_description_query['responseStatus'] !== 200) {
        throw new Exception('Translation failed');
    }
    $service_description_translation = $service_description_query['responseData']['translatedText'];
}




            ServiceTranslation::create([
                "service_id" => $service->id,
                "language" => "ar",
                "name_translation" => $service_name_translation,
                "description_translation" => $service_description_translation
            ]);


            return response($service, 201);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }
 /**
        *
     * @OA\Put(
     *      path="/v1.0/services",
     *      operationId="updateService",
     *      tags={"service_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update service",
     *      description="This method is to update service",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","name","icon","description"},
     *             @OA\Property(property="id", type="number", format="number",example="1"),
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     *   *  *    @OA\Property(property="icon", type="string", format="string",example="fa fa-- tui halua kha"),
     *             @OA\Property(property="description", type="string", format="string",example="description"),

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

    public function updateService(ServiceUpdateRequest $request)
    {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('service_update')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            $updatableData = $request->validated();



                $service  =  tap(Service::where(["id" => $updatableData["id"]])->where("business_id",auth()->user()->business_id))->update(collect($updatableData)->only([
                    'name',
                    'image',
                    'icon',
                    "description",
                    // "automobile_category_id"
                ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$service) {
                        return response()->json([
                            "message" => "no  service found"
                        ],404);
                    }

                    ServiceTranslation::where([
                        "service_id" => $service->id
                    ])
                    ->delete();
// Translate the service name using MyMemory
$service_name_query = Http::get('https://api.mymemory.translated.net/get', [
    'q' => $service->name,
    'langpair' => 'en|ar'  // Specify the source language as 'en' (English) and target as 'ar' (Arabic)
]);



// Check for translation errors or unexpected responses
if ( $service_name_query['responseStatus'] !== 200) {
    throw new Exception('Translation failed');
}

// Extract the translated text from the response
$service_name_translation = $service_name_query['responseData']['translatedText'];

$service_description_translation = "";
if(!empty($service->description)) {
    $service_description_query = Http::get('https://api.mymemory.translated.net/get', [
        'q' => $service->description,
        'langpair' => 'en|ar'
    ]);

    // Check for translation errors or unexpected responses
    if ($service_description_query['responseStatus'] !== 200) {
        throw new Exception('Translation failed');
    }
    $service_description_translation = $service_description_query['responseData']['translatedText'];
}

                    ServiceTranslation::create([
                        "service_id" => $service->id,
                        "language" => "ar",
                        "name_translation" => $service_name_translation,
                        "description_translation" => $service_description_translation
                    ]);
            return response($service, 201);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }
 /**
        *
     * @OA\Get(
     *      path="/v1.0/services/{perPage}",
     *      operationId="getServices",
     *      tags={"service_management"},
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


     *      summary="This method is to get automobile Services ",
     *      description="This method is to get automobile Services",
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

    public function getServices($perPage,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('service_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            // $automobilesQuery = AutomobileMake::with("makes");

            $servicesQuery = Service::
            where("business_id",auth()->user()->business_id)
            ->with("category", "translation");

            if(!empty($request->search_key)) {
                $servicesQuery = $servicesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $servicesQuery = $servicesQuery->where('created_at', ">=", $request->start_date);
            }

            if (!empty($request->end_date)) {
                $servicesQuery = $servicesQuery->where('created_at', "<=", $request->end_date);
            }








            $services = $servicesQuery->orderBy("name",'asc')->paginate($perPage);
            return response()->json($services, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }
     /**
        *
     * @OA\Get(
     *      path="/v1.0/services/single/get/{id}",
     *      operationId="getServiceById",
     *      tags={"automobile_management.category"},
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
     *      summary="This method is to get service by id",
     *      description="This method is to get service by id",
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


    public function getServiceById($id,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('service_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            $service = Service::with("subServices","category",'translation')
            ->where("business_id",auth()->user()->business_id)
            ->where([
                "id" => $id
            ])
            ->first()
            ;
            if(!$service) {
                return response()->json([
                    "message" => "no  service found"
                ],404);
            }
            return response()->json($service, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }


    /**
        *
     * @OA\Get(
     *      path="/v1.0/services-all/{categoryId}",
     *      operationId="getAllServicesByCategoryId",
     *      tags={"basics"},
    *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         description="categoryId",
     *         required=true,
     *  example="1"
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
     *      summary="This method is to get all automobile Services by category id ",
     *      description="This method is to get all automobile Services by category id",
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

    public function getAllServicesByCategoryId($categoryId,Request $request) {
        try{
            $this->storeActivity($request,"");
        //     if(!$request->user()->hasPermissionTo('service_view')){
        //         return response()->json([
        //            "message" => "You can not perform this action"
        //         ],401);
        //    }

            // $automobilesQuery = AutomobileMake::with("makes");

            $servicesQuery = Service::with("category","subServices",'translation')->where([
                "automobile_category_id" => $categoryId
            ])
            ->when(request()->filled("business_id"), function($query) {
                $query->where("business_id",request()->input("business_id"));
            });

            if(!empty($request->search_key)) {
                $servicesQuery = $servicesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $servicesQuery = $servicesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $servicesQuery = $servicesQuery->where('created_at', "<=", $request->end_date);
            }



            $services = $servicesQuery->orderBy("name",'asc')->get();
            return response()->json($services, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

      /**
        *
     * @OA\Get(
     *      path="/v2.0/services-all/{categoryId}",
     *      operationId="getAllServicesByCategoryIdV2",
     *      tags={"basics"},
    *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         description="categoryId",
     *         required=true,
     *  example="1"
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
     *      summary="This method is to get all automobile Services by category id ",
     *      description="This method is to get all automobile Services by category id",
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

    public function getAllServicesByCategoryIdV2($categoryId,Request $request) {
        try{

            $this->storeActivity($request,"");
            $servicesQuery = Service::with('translation')->where([
                "automobile_category_id" => $categoryId
            ])
            ->when(request()->filled("business_id"), function($query) {
                $query->where("business_id",request()->input("business_id"));
            })

            ;

            if(!empty($request->search_key)) {
                $servicesQuery = $servicesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $servicesQuery = $servicesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $servicesQuery = $servicesQuery->where('created_at', "<=", $request->end_date);
            }

            $services = $servicesQuery->orderBy("name",'asc')->get();
            return response()->json($services, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

  /**
        *
     * @OA\Get(
     *      path="/v1.0/sub-services-all",
     *      operationId="getSubServicesAll",
     *      tags={"basics"},
    *       security={
     *           {"bearerAuth": {}}
     *       },


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
*  @OA\Parameter(
*      name="service_ids[]",
*      in="query",
*      description="service_id",
*      required=true,
*      example="1,2"
* ),
     * *  @OA\Parameter(
* name="is_fixed_price",
* in="query",
* description="is_fixed_price 0 or 1 as it is string sending in request true will be catch in string like 'true'",
* required=true,
* example="0"
* ),

     *      summary="This method is to get all sub services by service ids",
     *      description="This method is to get all sub services by service ids",
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

    public function getSubServicesAll(Request $request) {
        try{
            $this->storeActivity($request,"");

            $subServiceQuery = new SubService();

            if(!empty($request->search_key)) {
                $subServiceQuery = $subServiceQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $subServiceQuery = $subServiceQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $subServiceQuery = $subServiceQuery->where('created_at', "<=", $request->end_date);
            }

            if (!empty($request->is_fixed_price)) {
                $is_fixed_price = (int)$request->is_fixed_price;
                $subServiceQuery = $subServiceQuery->where('is_fixed_price',  $is_fixed_price);
            }

            if(!empty($request->service_ids)) {
                if(count($request->service_ids)) {
                    $subServiceQuery = $subServiceQuery->whereIn("service_id",$request->service_ids);
                }

            }

            $sub_services = $subServiceQuery->orderBy("name",'asc')->get();
            return response()->json($sub_services, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }



      /**
        *
     * @OA\Get(
     *      path="/v1.0/service-make-model-combined",
     *      operationId="getServiceMakeModelCombined",
     *      tags={"basics"},
    *       security={
     *           {"bearerAuth": {}}
     *       },



     *      summary="This method is to get all service make models",
     *      description="This method is to get all service-make-models",
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

     public function getServiceMakeModelCombined(Request $request) {
        try{
            $this->storeActivity($request,"");

            // CHECK IF REQUEST HAVE AUTOMOBILE CATEGORY ID
            $services =  Service::
            with('translation','subServices.translation')->
            when(request()->filled("automobile_category_id"), function($query) {
                $query->where("automobile_category_id",request()->input("automobile_category_id"));
            },

            // IF THERE IS NO CATEGORY ID USE ID 1
            function($query) {
                $query->where("automobile_category_id",1);

            })
            ->when(request()->filled("business_id"), function($query) {
                $query->where("business_id",request()->input("business_id"));
            })

            ->orderBy("name",'asc')
             ->get();

            // GETTING SUB SERVICES
            $sub_services =  SubService::
            with([
                'translation'
                ])
            ->whereIn("service_id",$services->pluck("id"))
            ->when(request()->filled("business_id"), function($query) {
                $query->where("business_id",request()->input("business_id"));
            })
            ->orderBy("name",'asc')
            ->get();

            // GETTING AUTOMOBILE CATEGORIES
            $automobile_categories =  AutomobileCategory::orderBy("name",'asc')
            ->select(
                "id",
                "name",
            )->get();


            // GETTING ROLES
            $roles = Role::with('permissions:name,id')->select("name", "id")->get();

            // GETTING ROLES
            $payment_types = PaymentType::orderBy("name",'asc')->select("name", "id")->get();


            $response_data = [
                "services" => $services,
                "sub_services" => $sub_services,

                "automobile_categories" => $automobile_categories,
                "roles" => $roles,
                "payment_types" => $payment_types,
            ];




            return response()->json($response_data, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }



/**
        *
     *     @OA\Delete(
     *      path="/v1.0/services/{id}",
     *      operationId="deleteServiceById",
     *      tags={"service_management"},
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
     *      summary="This method is to delete service by id",
     *      description="This method is to delete service by id",
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

    public function deleteServiceById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('service_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

           $sub_service_ids = SubService::where([
              "service_id" => $id
           ])
           ->pluck("id");

           $conflicts = [];
           $conflictingBookingExists = BookingSubService::whereIn("sub_service_id", $sub_service_ids)->exists();
           if ($conflictingBookingExists) {
               $conflicts[] = "Booking";
           }

           // Return combined error message if conflicts exist
           if (!empty($conflicts)) {
               $conflictList = implode(', ', $conflicts);
               return response()->json([
                   "message" => "There are bookings associated with this service, preventing deletion.",
               ], 409);
           }





           Service::where([
            "id" => $id
           ])
           ->where("business_id",auth()->user()->business_id)
           ->forceDelete();

            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }





  /**
        *
     * @OA\Post(
     *      path="/v1.0/sub-services",
     *      operationId="createSubService",
     *      tags={"service_management.sub"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store sub service",
     *      description="This method is to store sub service",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","description","service_id"},
     *    @OA\Property(property="name", type="string", format="string",example="car"),
     *    @OA\Property(property="description", type="string", format="string",example="car"),
     *    @OA\Property(property="service_id", type="string", format="number",example="1"),
     *    @OA\Property(property="is_fixed_price", type="number", format="number",example="1"),
     *    *    @OA\Property(property="default_price", type="number", format="number",example="1"),
     *    @OA\Property(property="service_time_in_minute", type="number", format="number",example="1")
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

    public function createSubService(SubServiceCreateRequest $request)
    {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('service_create')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }

            $insertableData = $request->validated();
            $insertableData["is_fixed_price"] = 1;
            $insertableData["business_id"] = auth()->user()->business_id;
            $sub_service =  SubService::create($insertableData);


            $service_name_query = Http::get('https://api.mymemory.translated.net/get', [
                'q' => $sub_service->name,
                'langpair' => 'en|ar'  // Set the correct source and target language
            ]);

                        // Check for translation errors or unexpected responses
if ( $service_name_query['responseStatus'] !== 200) {
    throw new Exception('Translation failed');
}
$service_name_translation = $service_name_query['responseData']['translatedText'];

$service_description_translation = "";
if(!empty($sub_service->description)) {
    $service_description_query = Http::get('https://api.mymemory.translated.net/get', [
        'q' => $sub_service->description,
        'langpair' => 'en|ar'
    ]);

    // Check for translation errors or unexpected responses
    if ($service_description_query['responseStatus'] !== 200) {
        throw new Exception('Translation failed');
    }
    $service_description_translation = $service_description_query['responseData']['translatedText'];
}






            SubServiceTranslation::create([
                "sub_service_id" => $sub_service->id,
                "language" => "ar",
                "name_translation" => $service_name_translation,
                "description_translation" => $service_description_translation
            ]);


            return response($sub_service, 201);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }


/**
        *
     * @OA\Put(
     *      path="/v1.0/sub-services",
     *      operationId="updateSubService",
     *      tags={"service_management.sub"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update sub service",
     *      description="This method is to update sub service",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","name","description"},
     *             @OA\Property(property="id", type="number", format="number",example="1"),
     *             @OA\Property(property="name", type="string", format="string",example="car"),
     *             @OA\Property(property="description", type="string", format="string",example="description"),
     *             @OA\Property(property="is_fixed_price", type="number", format="number",example="1"),
     *              @OA\Property(property="default_price", type="number", format="number",example="1"),
     *
     *            @OA\Property(property="service_time_in_minute", type="number", format="number",example="1")
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

    public function updateSubService(SubServiceUpdateRequest $request)
    {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('service_update')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            $updatableData = $request->validated();



                $sub_service  =  tap(SubService::where([
                    "id" => $updatableData["id"]
                ])
                ->where("business_id",auth()->user()->business_id)

                )->update(collect($updatableData)->only([
                    'name',
                    "description",
                    "service_id",
                    "is_fixed_price",
                    "default_price",
                    "service_time_in_minute"
                    // "automobile_category_id"
                ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$sub_service) {
                        return response()->json([
                            "message" => "no sub service found"
                        ],404);
                    }

                    SubServiceTranslation::where([
                        "sub_service_id" => $sub_service->id
                    ])
                    ->delete();

                    $service_name_query = Http::get('https://api.mymemory.translated.net/get', [
                        'q' => $sub_service->name,
                        'langpair' => 'en|ar'  // Set the correct source and target language
                    ]);

                                 // Check for translation errors or unexpected responses
    if ($service_name_query['responseStatus'] !== 200) {
        throw new Exception('Translation failed');
    }

                    $service_name_translation = $service_name_query['responseData']['translatedText'];

$service_description_translation = "";
if(!empty($sub_service->description)) {
    $service_description_query = Http::get('https://api.mymemory.translated.net/get', [
        'q' => $sub_service->description,
        'langpair' => 'en|ar'
    ]);

    // Check for translation errors or unexpected responses
    if ($service_description_query['responseStatus'] !== 200) {
        throw new Exception('Translation failed');
    }
    $service_description_translation = $service_description_query['responseData']['translatedText'];
}


                    SubServiceTranslation::create([
                        "sub_service_id" => $sub_service->id,
                        "language" => "ar",
                        "name_translation" => $service_name_translation,
                        "description_translation" => $service_description_translation
                    ]);
            return response($sub_service, 201);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }


     /**
        *
     * @OA\Get(
     *      path="/v1.0/sub-services/{serviceId}/{perPage}",
     *      operationId="getSubServicesByServiceId",
     *      tags={"service_management.sub"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
 *              @OA\Parameter(
     *         name="serviceId",
     *         in="path",
     *         description="serviceId",
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
*
     * *  @OA\Parameter(
* name="is_fixed_price",
* in="query",
* description="is_fixed_price 0 or 1 as it is string sending in request true will be catch in string like 'true'",
* required=true,
* example="0"
* ),
     *      summary="This method is to get automobile sub Services by service id",
     *      description="This method is to get automobile sub Services by service id",
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

    public function getSubServicesByServiceId($serviceId,$perPage,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('service_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
            // $automobilesQuery = AutomobileMake::with("makes");
            $servicesQuery = SubService::with("service.category", 'translation')
            ->where("business_id",auth()->user()->business_id)
            ->where("service_id" , $serviceId);
            if(!empty($request->search_key)) {
                $servicesQuery = $servicesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }
            if (!empty($request->start_date)) {
                $servicesQuery = $servicesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $servicesQuery = $servicesQuery->where('created_at', "<=", $request->end_date);
            }
            if (!empty($request->is_fixed_price)) {
                $is_fixed_price = (int)$request->is_fixed_price;
                $servicesQuery = $servicesQuery->where('is_fixed_price',  $is_fixed_price);
            }
            $services = $servicesQuery->orderBy("name",'asc')->paginate($perPage);
            return response()->json($services, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }
    }

/**
        *
     * @OA\Get(
     *      path="/v1.0/sub-services-all/{serviceId}",
     *      operationId="getAllSubServicesByServiceId",
     *      tags={"service_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="serviceId",
     *         in="path",
     *         description="serviceId",
     *         required=true,
     *  example="1"
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
     * *  @OA\Parameter(
* name="is_fixed_price",
* in="query",
* description="is_fixed_price 0 or 1 as it is string sending in request true will be catch in string like 'true'",
* required=true,
* example="0"
* ),
     *      summary="This method is to get all automobile sub Services by service id ",
     *      description="This method is to get all automobile sub Services by service id",
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

    public function getAllSubServicesByServiceId($serviceId,Request $request) {
        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('service_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            // $automobilesQuery = AutomobileMake::with("makes");

            $servicesQuery = SubService::with("service", 'translation')->where([
                "service_id" => $serviceId
            ])
            ->where("business_id",auth()->user()->business_id)

            ;

            if(!empty($request->search_key)) {
                $servicesQuery = $servicesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $servicesQuery = $servicesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $servicesQuery = $servicesQuery->where('created_at', "<=", $request->end_date);
            }
            if (!empty($request->is_fixed_price)) {
                $is_fixed_price = (int)$request->is_fixed_price;
                $servicesQuery = $servicesQuery->where('is_fixed_price',  $is_fixed_price);
            }


            $services = $servicesQuery->orderBy("name",'asc')->get();
            return response()->json($services, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }





/**
        *
     *     @OA\Delete(
     *      path="/v1.0/sub-services/{id}",
     *      operationId="deleteSubServiceById",
     *      tags={"service_management.sub"},
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
     *      summary="This method is to delete sub service by id",
     *      description="This method is to delete sub service by id",
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

    public function deleteSubServiceById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('service_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

           $conflicts = [];
           $conflictingBookingExists = BookingSubService::whereIn("sub_service_id", [$id])->exists();
           if ($conflictingBookingExists) {
               $conflicts[] = "Booking";
           }

           // Return combined error message if conflicts exist
           if (!empty($conflicts)) {
               $conflictList = implode(', ', $conflicts);
               return response()->json([
                   "message" => "There are bookings associated with this service, preventing deletion.",
               ], 409);
           }



           SubService::where([
            "id" => $id
           ])
           ->where("business_id",auth()->user()->business_id)
           ->forceDelete();

            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


}
