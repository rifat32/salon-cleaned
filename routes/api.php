<?php

use App\Http\Controllers\AffiliationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutomobilesController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\client\ClientBasicController;
use App\Http\Controllers\client\ClientBookingController;
use App\Http\Controllers\client\ClientCouponController;
use App\Http\Controllers\client\ClientJobController;
use App\Http\Controllers\client\ClientPreBookingController;
use App\Http\Controllers\client\ClientReviewController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CustomWebhookController;
use App\Http\Controllers\DashboardManagementController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\EmailTemplateWrapperController;
use App\Http\Controllers\ExpertRotaController;
use App\Http\Controllers\FuelStationController;
use App\Http\Controllers\FuelStationGalleryController;
use App\Http\Controllers\FuelStationServiceController;
use App\Http\Controllers\GarageAffiliationController;
use App\Http\Controllers\GarageAutomobilesController;

use App\Http\Controllers\GarageBackgroundImageController;
use App\Http\Controllers\GarageGalleryController;
use App\Http\Controllers\GaragePackageController;
use App\Http\Controllers\GarageRuleController;
use App\Http\Controllers\GaragesController;
use App\Http\Controllers\GarageServiceController;
use App\Http\Controllers\GarageServicePriceController;
use App\Http\Controllers\GarageTimesController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\JobBidController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServicePriceController;
use App\Http\Controllers\ShopGalleryController;
use App\Http\Controllers\ShopsController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SubServicePriceController;
use App\Http\Controllers\UserManagementController;
use App\Models\GaragePackage;
use App\Models\JobBid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/v1.0/expert-users', [UserManagementController::class, "getExpertUsers"]);


Route::post('/create-payment-intent', [BookingController::class, 'createPaymentIntent']);
Route::post('/create-refund', [BookingController::class, 'createRefund']);

Route::post("/activate-user", [AuthController::class, 'activateUser']);

Route::post('/v1.0/register', [AuthController::class, "register"]);



Route::post('/v1.0/login', [AuthController::class, "login"]);
Route::post('/v1.0/token-regenerate', [AuthController::class, "regenerateToken"]);

Route::post('/forgetpassword', [AuthController::class, "storeToken"]);
Route::post('/resend-email-verify-mail', [AuthController::class, "resendEmailVerifyToken"]);

Route::patch('/forgetpassword/reset/{token}', [AuthController::class, "changePasswordByToken"]);


Route::post('/auth/check/email', [AuthController::class, "checkEmail"]);
Route::post('/auth/check/business-email', [AuthController::class, "checkBusinessEmail"]);




Route::post('/v1.0/auth/user-register-with-garage', [AuthController::class, "registerUserWithGarageClient"]);


Route::get('/v1.0/automobile-categories/get/all', [AutomobilesController::class, "getAllAutomobileCategories"]);


Route::get('/v1.0/automobile-makes-all/{categoryId}', [AutomobilesController::class, "getAutomobileMakesAll"]);
Route::get('/v2.0/automobile-makes-all/{categoryId}', [AutomobilesController::class, "getAutomobileMakesAllV2"]);
Route::get('/v1.0/automobile-models-all', [AutomobilesController::class, "getAutomobileModelsAll"]);



Route::get('/v1.0/services-all/{categoryId}', [ServiceController::class, "getAllServicesByCategoryId"]);
Route::get('/v2.0/services-all/{categoryId}', [ServiceController::class, "getAllServicesByCategoryIdV2"]);
Route::get('/v1.0/sub-services-all', [ServiceController::class, "getSubServicesAll"]);
Route::get('/v1.0/garage-sub-services-all/{garage_id}', [GarageServiceController::class, "getGarageSubServicesAll"]);



Route::get('/v1.0/service-make-model-combined', [ServiceController::class, "getServiceMakeModelCombined"]);






Route::get('/v1.0/available-countries', [GaragesController::class, "getAvailableCountries"]);

Route::get('/v1.0/available-countries/for-shop', [ShopsController::class, "getAvailableCountriesForShop"]);

Route::get('/v1.0/available-cities/{country_code}', [GaragesController::class, "getAvailableCities"]);

Route::get('/v1.0/available-cities/for-shop/{country_code}', [ShopsController::class, "getAvailableCitiesForShop"]);






Route::post('/v1.0/user-image', [UserManagementController::class, "createUserImage"]);
Route::post('/v2.0/user-image', [UserManagementController::class, "createUserImageV2"]);

Route::post('/v1.0/garage-image', [GaragesController::class, "createGarageImage"]);
Route::post('/v2.0/garage-image', [GaragesController::class, "createGarageImageV2"]);

Route::post('/v1.0/garage-image-multiple', [GaragesController::class, "createGarageImageMultiple"]);
Route::post('/v1.0/shop-image', [ShopsController::class, "createShopImage"]);
Route::post('/v1.0/shop-image-multiple', [ShopsController::class, "createShopImage"]);

// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^
// Protected Routes
// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^
Route::middleware(['auth:api','verified'])->group(function () {



    Route::get('/v2.0/expert-users', [UserManagementController::class, "getExpertUsersV2"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// expert rotas management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/expert-rotas', [ExpertRotaController::class, "createExpertRota"]);
Route::put('/v1.0/expert-rotas', [ExpertRotaController::class, "updateExpertRota"]);
Route::put('/v1.0/expert-rotas/toggle-active', [ExpertRotaController::class, "toggleActiveExpertRota"]);
Route::get('/v1.0/expert-rotas', [ExpertRotaController::class, "getExpertRotas"]);
Route::delete('/v1.0/expert-rotas/{ids}', [ExpertRotaController::class, "deleteExpertRotasByIds"]);



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end expert rotas management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// service prices management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/service-prices', [ServicePriceController::class, "createServicePrice"]);
Route::post('/v1.0/service-prices/multiple', [ServicePriceController::class, "createMultipleServicePrices"]);

Route::put('/v1.0/service-prices', [ServicePriceController::class, "updateServicePrice"]);
Route::put('/v1.0/service-prices/bulk-update', [ServicePriceController::class, "bulkUpdateServicePrices"]);



Route::put('/v1.0/service-prices/toggle-active', [ServicePriceController::class, "toggleActiveServicePrice"]);
Route::get('/v1.0/service-prices', [ServicePriceController::class, "getServicePrices"]);
Route::delete('/v1.0/service-prices/{ids}', [ServicePriceController::class, "deleteServicePricesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end service prices management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@





// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// sub service prices management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/sub-service-prices', [SubServicePriceController::class, "createSubServicePrice"]);
Route::post('/v1.0/sub-service-prices/bulk', [SubServicePriceController::class, "bulkCreateSubServicePrice"]);

Route::put('/v1.0/sub-service-prices', [SubServicePriceController::class, "updateSubServicePrice"]);
Route::put('/v1.0/sub-service-prices/bulk', [SubServicePriceController::class, "bulkUpdateSubServicePrices"]);

Route::get('/v1.0/sub-service-prices', [SubServicePriceController::class, "getSubServicePrices"]);
Route::delete('/v1.0/sub-service-prices/{ids}', [SubServicePriceController::class, "deleteSubServicePricesByIds"]);



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end sub service prices management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@














    Route::get('/v1.0/user', [AuthController::class, "getUser"]);
    Route::patch('/auth/changepassword', [AuthController::class, "changePassword"]);

    Route::put('/v1.0/update-user-info', [AuthController::class, "updateUserInfo"]);



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// notification management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::get('/v2.0/notifications', [NotificationController::class, "getNotificationsV2"]);

    Route::get('/v1.0/notifications/{perPage}', [NotificationController::class, "getNotifications"]);

    Route::get('/v1.0/notifications/{garage_id}/{perPage}', [NotificationController::class, "getNotificationsByGarageId"]);

    Route::put('/v1.0/notifications/change-status', [NotificationController::class, "updateNotificationStatus"]);

    Route::delete('/v1.0/notifications/{id}', [NotificationController::class, "deleteNotificationById"]);
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// notification management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// user management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// ********************************************
// user management section --user
// ********************************************



Route::post('/v1.0/walk-in-customers', [UserManagementController::class, "registerWalkInCustomer"]);


Route::post('/v1.0/users', [UserManagementController::class, "createUser"]);


  // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // business  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::put('/v1.0/business-settingss', [SettingController::class, "updateBusinessSettings"]);
    Route::get('/v1.0/business-settingss', [SettingController::class, "getBusinessSettings"]);


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end business management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

     // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // notification setting  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::put('/v1.0/notification-settings', [NotificationSettingController::class, "updateNotificationSetting"]);
    Route::get('/v1.0/notification-settings', [NotificationSettingController::class, "getNotificationSetting"]);


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end notification setting  section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



Route::put('/v1.0/customer-users', [UserManagementController::class, "createOrUpdateCustomerUser"]);
Route::get('/v1.0/customer-users/get-by-phone/{phone}', [UserManagementController::class, "getCustomerUserByPhone"]);
Route::post('/v2.0/customer-users/get-by-phone', [UserManagementController::class, "getCustomerUserByPhoneV2"]);











Route::get('/v1.0/users/get-by-id/{id}', [UserManagementController::class, "getUserById"]);

Route::put('/v1.0/users', [UserManagementController::class, "updateUser"]);
Route::put('/v1.0/users/profile', [UserManagementController::class, "updateUserProfile"]);


Route::put('/v1.0/users/toggle-active', [UserManagementController::class, "toggleActiveUser"]);



Route::get('/v1.0/users/{perPage}', [UserManagementController::class, "getUsers"]);
Route::get('/v1.0/users', [UserManagementController::class, "getUsersV2"]);


Route::delete('/v1.0/users/{id}', [UserManagementController::class, "deleteUserById"]);

// ********************************************
// user management section --role
// ********************************************
Route::get('/v1.0/initial-role-permissions', [RolesController::class, "getInitialRolePermissions"]);
Route::post('/v1.0/roles', [RolesController::class, "createRole"]);
Route::put('/v1.0/roles', [RolesController::class, "updateRole"]);
Route::get('/v1.0/roles/{perPage}', [RolesController::class, "getRoles"]);
Route::get('/v1.0/roles/get/all', [RolesController::class, "getRolesAll"]);
Route::get('/v1.0/roles/get-by-id/{id}', [RolesController::class, "getRoleById"]);
Route::delete('/v1.0/roles/{id}', [RolesController::class, "deleteRoleById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end user management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// garage management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



Route::post('/v1.0/auth/register-with-garage', [GaragesController::class, "registerUserWithGarage"]);

Route::post('/v1.0/garages', [GaragesController::class, "createGarage"]);




Route::put('/v1.0/garages/toggle-active', [GaragesController::class, "toggleActiveGarage"]);



Route::put('/v1.0/garages', [GaragesController::class, "updateGarage"]);
Route::put('/v1.0/garages/separate', [GaragesController::class, "updateGarageSeparate"]);
Route::put('/v1.0/garages/update-time-format', [GaragesController::class, "updateGarageTimeFormat"]);



Route::get('/v1.0/garages/{perPage}', [GaragesController::class, "getGarages"]);
Route::get('/v2.0/garages/{perPage}', [GaragesController::class, "getGaragesV2"]);

Route::get('/v1.0/garages/single/{id}', [GaragesController::class, "getGarageById"]);

Route::get('/v2.0/garages/single/{id}', [GaragesController::class, "getGarageByIdV2"]);

Route::delete('/v1.0/garages/{id}', [GaragesController::class, "deleteGarageById"]);

Route::get('/v1.0/garages/by-garage-owner/all', [GaragesController::class, "getAllGaragesByGarageOwner"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end garage management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%







// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// automobile management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// ********************************************
// automobile management section --category
// ********************************************
Route::post('/v1.0/automobile-categories', [AutomobilesController::class, "createAutomobileCategory"]);
Route::put('/v1.0/automobile-categories', [AutomobilesController::class, "updateAutomobileCategory"]);
Route::get('/v1.0/automobile-categories/{perPage}', [AutomobilesController::class, "getAutomobileCategories"]);

Route::get('/v1.0/automobile-categories/single/get/{id}', [AutomobilesController::class, "getAutomobileCategoryById"]);
Route::delete('/v1.0/automobile-categories/{id}', [AutomobilesController::class, "deleteAutomobileCategoryById"]);


// ********************************************
// automobile management section --make
// ********************************************
Route::post('/v1.0/automobile-makes', [AutomobilesController::class, "createAutomobileMake"]);
Route::put('/v1.0/automobile-makes', [AutomobilesController::class, "updateAutomobileMake"]);
Route::get('/v1.0/automobile-makes/{categoryId}/{perPage}', [AutomobilesController::class, "getAutomobileMakes"]);
Route::get('/v1.0/automobile-makes/single/get/{id}', [AutomobilesController::class, "getAutomobileMakeById"]);
Route::delete('/v1.0/automobile-makes/{id}', [AutomobilesController::class, "deleteAutomobileMakeById"]);





// ********************************************
// automobile management section --model
// ********************************************
Route::post('/v1.0/automobile-models', [AutomobilesController::class, "createAutomobileModel"]);
Route::put('/v1.0/automobile-models', [AutomobilesController::class, "updateAutomobileModel"]);
Route::get('/v1.0/automobile-models/{makeId}/{perPage}', [AutomobilesController::class, "getAutomobileModel"]);
Route::get('/v1.0/automobile-models/single/get/{id}', [AutomobilesController::class, "getAutomobileModelById"]);
Route::delete('/v1.0/automobile-models/{id}', [AutomobilesController::class, "deleteAutomobileModelById"]);




// ********************************************
// automobile management section --model variant
// ********************************************
Route::post('/v1.0/automobile-model-variants', [AutomobilesController::class, "createAutomobileModelVariant"]);
Route::put('/v1.0/automobile-model-variants', [AutomobilesController::class, "updateAutomobileModelVariant"]);
Route::get('/v1.0/automobile-model-variants/{modelId}/{perPage}', [AutomobilesController::class, "getAutomobileModelVariant"]);
Route::get('/v1.0/automobile-model-variants/single/get/{id}', [AutomobilesController::class, "getAutomobileModelVariantById"]);
Route::delete('/v1.0/automobile-model-variants/{id}', [AutomobilesController::class, "deleteAutomobileModelVariantById"]);


// ********************************************
// automobile management section --fuel types
// ********************************************
Route::post('/v1.0/automobile-fuel-types', [AutomobilesController::class, "createAutomobileFuelType"]);
Route::put('/v1.0/automobile-fuel-types', [AutomobilesController::class, "updateAutomobileFuelType"]);
Route::get('/v1.0/automobile-fuel-types/{modelVariantId}/{perPage}', [AutomobilesController::class, "getAutomobileFuelType"]);
Route::get('/v1.0/automobile-fuel-types/single/get/{id}', [AutomobilesController::class, "getAutomobileFuelTypeById"]);
Route::delete('/v1.0/automobile-fuel-types/{id}', [AutomobilesController::class, "deleteAutomobileFuelTypeById"]);



// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end automobile management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// garage automobile management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::get('/v1.0/garage-automobile-makes/all/{garage_id}', [GarageAutomobilesController::class, "getGarageAutomobileMakesAll"]);


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end garage automobile management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// service management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// ********************************************
// service management section --service
// ********************************************
Route::post('/v1.0/services', [ServiceController::class, "createService"]);
Route::put('/v1.0/services', [ServiceController::class, "updateService"]);
Route::get('/v1.0/services/{perPage}', [ServiceController::class, "getServices"]);
Route::delete('/v1.0/services/{id}', [ServiceController::class, "deleteServiceById"]);
Route::get('/v1.0/services/single/get/{id}', [ServiceController::class, "getServiceById"]);

// ********************************************
// service management section --sub service
// ********************************************
Route::post('/v1.0/sub-services', [ServiceController::class, "createSubService"]);
Route::put('/v1.0/sub-services', [ServiceController::class, "updateSubService"]);
Route::get('/v1.0/sub-services/{serviceId}/{perPage}', [ServiceController::class, "getSubServicesByServiceId"]);
Route::get('/v1.0/sub-services-all/{serviceId}', [ServiceController::class, "getAllSubServicesByServiceId"]);
Route::delete('/v1.0/sub-services/{id}', [ServiceController::class, "deleteSubServiceById"]);


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end service management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// garage service management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::get('/v1.0/garage-services/{garage_id}/{perPage}', [GarageServiceController::class, "getGarageServices"]);


Route::get('/v1.0/garage-sub-services/{garage_id}/{garage_service_id}/{perPage}', [GarageServiceController::class, "getGarageSubServices"]);



// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end garage service management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// fuel station services management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/fuel-station-services', [FuelStationServiceController::class, "createFuelStationService"]);

Route::put('/v1.0/fuel-station-services', [FuelStationServiceController::class, "updateFuelStationService"]);

Route::get('/v1.0/fuel-station-services/{perPage}', [FuelStationServiceController::class, "getFuelStationServices"]);

Route::get('/v1.0/fuel-station-services/get/all', [FuelStationServiceController::class, "getFuelStationServicesAll"]);

Route::delete('/v1.0/fuel-station-services/{id}', [FuelStationServiceController::class, "deleteFuelStationServiceById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// fuel station services management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// fuel station management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/fuel-station', [FuelStationController::class, "createFuelStation"]);
Route::put('/v1.0/fuel-station', [FuelStationController::class, "updateFuelStation"]);
Route::post('/v1.0/fuel-station-image-multiple', [FuelStationController::class, "createFuelStationImageMultiple"]);
Route::put('/v1.0/fuel-station/toggle-active', [FuelStationController::class, "toggleActiveFuelStation"]);
Route::get('/v1.0/fuel-station/{perPage}', [FuelStationController::class, "getFuelStations"]);
Route::get('/v2.0/fuel-station/single/{id}', [FuelStationController::class, "getFuelStationByIdV2"]);
Route::delete('/v1.0/fuel-station/{id}', [FuelStationController::class, "deleteFuelStationById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// fuel station management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// review management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



Route::post('/review-new/create/questions', [ReviewController::class, "storeQuestion"]);
Route::put('/review-new/update/questions', [ReviewController::class, "updateQuestion"]);
Route::put('/review-new/update/active_state/questions', [ReviewController::class, "updateQuestionActiveState"]);

Route::get('/review-new/get/questions', [ReviewController::class, "getQuestion"]);
Route::get('/review-new/get/questions-all', [ReviewController::class, "getQuestionAll"]);



Route::get('/review-new/get/questions-all-report', [ReviewController::class, "getQuestionAllReport"]);
Route::get('/review-new/get/questions-all-report-by-user/{perPage}', [ReviewController::class, "getQuestionAllReportByUser"]);

Route::get('/review-new/get/questions/{id}', [ReviewController::class, "getQuestionById"]);



Route::delete('/review-new/delete/questions/{id}', [ReviewController::class, "deleteQuestionById"]);









Route::get('/review-new/get/questions-all-report/quantum', [ReviewController::class, "getQuestionAllReportQuantum"]);





Route::post('/review-new/create/tags', [ReviewController::class, "storeTag"]);
Route::post('/review-new/create/tags/multiple/by/admin', [ReviewController::class, "storeTagMultipleByAdmin"]);
Route::post('/review-new/create/tags/multiple/{garage_id}', [ReviewController::class, "storeTagMultiple"]);

Route::put('/review-new/update/tags', [ReviewController::class, "updateTag"]);




Route::get('/review-new/get/tags', [ReviewController::class, "getTag"]);
Route::get('/review-new/get/tags/{id}', [ReviewController::class, "getTagById"]);

Route::delete('/review-new/delete/tags/{id}', [ReviewController::class, "deleteTagById"]);

Route::post('/review-new/owner/create/questions', [ReviewController::class, "storeOwnerQuestion"]);

Route::patch('/review-new/owner/update/questions', [ReviewController::class, "updateOwnerQuestion"]);



Route::get('/review-new/getavg/review/{garageId}/{start}/{end}', [ReviewController::class, "getAverage"]);

Route::get('/review-new/getreview/{garageId}/{rate}/{start}/{end}', [ReviewController::class, "filterReview"]);

Route::get('/review-new/getcustomerreview/{garageId}/{start}/{end}', [ReviewController::class, "getCustommerReview"]);

Route::post('/review-new/{bookingId}', [ReviewController::class, "storeReview"]);

Route::post('/review-action/{reviewId}', [ReviewController::class, "reviewAction"]);




// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// fuel station management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%










// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// template management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// ********************************************
// template management section --wrapper
// ********************************************
Route::put('/v1.0/email-template-wrappers', [EmailTemplateWrapperController::class, "updateEmailTemplateWrapper"]);
Route::get('/v1.0/email-template-wrappers/{perPage}', [EmailTemplateWrapperController::class, "getEmailTemplateWrappers"]);
Route::get('/v1.0/email-template-wrappers/single/{id}', [EmailTemplateWrapperController::class, "getEmailTemplateWrapperById"]);




// ********************************************
// template management section
// ********************************************
Route::post('/v1.0/email-templates', [EmailTemplateController::class, "createEmailTemplate"]);
Route::put('/v1.0/email-templates', [EmailTemplateController::class, "updateEmailTemplate"]);
Route::get('/v1.0/email-templates/{perPage}', [EmailTemplateController::class, "getEmailTemplates"]);
Route::get('/v1.0/email-templates/single/{id}', [EmailTemplateController::class, "getEmailTemplateById"]);
Route::get('/v1.0/email-template-types', [EmailTemplateController::class, "getEmailTemplateTypes"]);
 Route::delete('/v1.0/email-templates/{id}', [EmailTemplateController::class, "deleteEmailTemplateById"]);

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// template management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%



// ********************************************
// notification template management section
// ********************************************

Route::put('/v1.0/notification-templates', [NotificationTemplateController::class, "updateNotificationTemplate"]);
Route::get('/v1.0/notification-templates/{perPage}', [NotificationTemplateController::class, "getNotificationTemplates"]);
Route::get('/v1.0/notification-templates/single/{id}', [NotificationTemplateController::class, "getEmailTemplateById"]);
Route::get('/v1.0/notification-template-types', [NotificationTemplateController::class, "getNotificationTemplateTypes"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// notification template management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%






// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// Garage Time Management
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::patch('/v1.0/garage-times', [GarageTimesController::class, "updateGarageTimes"]);
Route::get('/v1.0/garage-times/{garage_id}', [GarageTimesController::class, "getGarageTimes"]);




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// Garage Background Image Management
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/garage-background-image', [GarageBackgroundImageController::class, "updateGarageBackgroundImage"]);
Route::post('/v1.0/garage-background-image/by-user', [GarageBackgroundImageController::class, "updateGarageBackgroundImageByUser"]);
Route::get('/v1.0/garage-background-image', [GarageBackgroundImageController::class, "getGarageBackgroundImage"]);


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end Garage Time Management
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// Garage Rule Management
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::patch('/v1.0/garage-rules', [GarageRuleController::class, "updateGarageRules"]);
Route::get('/v1.0/garage-rules/{garage_id}', [GarageRuleController::class, "getGarageRules"]);

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end Garage Rule Management
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// garage gallery management section

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/garage-galleries/{garage_id}', [GarageGalleryController::class, "createGarageGallery"]);

Route::post('/v1.0/garage-galleries-by-url/{garage_id}', [GarageGalleryController::class, "createGarageGalleryByUrl"]);

Route::get('/v1.0/garage-galleries/{garage_id}', [GarageGalleryController::class, "getGarageGalleries"]);

Route::delete('/v1.0/garage-galleries/{garage_id}/{id}', [GarageGalleryController::class, "deleteGarageGalleryById"]);



// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end garage gallery management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// shop gallery management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/shop-galleries/{shop_id}', [ShopGalleryController::class, "createShopGallery"]);
Route::get('/v1.0/shop-galleries/{shop_id}', [ShopGalleryController::class, "getShopGalleries"]);
Route::delete('/v1.0/shop-galleries/{shop_id}/{id}', [ShopGalleryController::class, "deleteShopGalleryById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// shop garage gallery management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// fuel station gallery management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/fuel-stations-galleries/{fuel_station_id}', [FuelStationGalleryController::class, "createFuelStationGallery"]);
Route::post('/v1.0/fuel-stations-galleries-by-url/{garage_id}', [FuelStationGalleryController::class, "createFuelStationGalleryByUrl"]);
Route::get('/v1.0/fuel-stations-galleries/{fuel_station_id}', [FuelStationGalleryController::class, "getFuelStationGalleries"]);
Route::delete('/v1.0/fuel-stations-galleries/{fuel_station_id}/{id}', [FuelStationGalleryController::class, "deleteFuelStationGalleryById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end fuel station gallery management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// payment type management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/payment-types', [PaymentTypeController::class, "createPaymentType"]);
Route::put('/v1.0/payment-types', [PaymentTypeController::class, "updatePaymentType"]);
Route::get('/v1.0/payment-types/{perPage}', [PaymentTypeController::class, "getPaymentTypes"]);
Route::delete('/v1.0/payment-types/{id}', [PaymentTypeController::class, "deletePaymentTypeById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// payment type management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%






// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// booking management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/bookings', [BookingController::class, "createBooking"]);
Route::put('/v1.0/bookings', [BookingController::class, "updateBooking"]);
Route::put('/v1.0/bookings/confirm', [BookingController::class, "confirmBooking"]);

Route::put('/v1.0/bookings/change-status', [BookingController::class, "changeBookingStatus"]);
Route::put('/v1.0/bookings/change-statuses', [BookingController::class, "changeMultipleBookingStatuses"]);


Route::get('/v1.0/bookings/{garage_id}/{perPage}', [BookingController::class, "getBookings"]);

Route::get('/v1.0/customers', [BookingController::class, "getCustomers"]);

Route::get('/v2.0/customers', [BookingController::class, "getCustomersV2"]);

Route::get('/v1.0/upcoming-bookings', [BookingController::class, "getUpcomingBookings"]);


Route::get('/v1.0/bookings/single/{garage_id}/{id}', [BookingController::class, "getBookingById"]);

Route::delete('/v1.0/bookings/{garage_id}/{id}', [BookingController::class, "deleteBookingById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// booking management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// job bid management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::get('/v1.0/pre-bookings/{garage_id}/{perPage}', [JobBidController::class, "getPreBookings"]);

Route::get('/v2.0/pre-bookings/{garage_id}/{perPage}', [JobBidController::class, "getPreBookingsV2"]);

Route::get('/v1.0/pre-bookings/single/{garage_id}/{id}', [JobBidController::class, "getPreBookingById"]);

Route::post('/v1.0/job-bids', [JobBidController::class, "createJobBid"]);
Route::put('/v1.0/job-bids', [JobBidController::class, "updateJobBid"]);

Route::get('/v1.0/job-bids/{garage_id}/{perPage}', [JobBidController::class, "getJobBids"]);
Route::get('/v1.0/job-bids/single/{garage_id}/{id}', [JobBidController::class, "getJobBidById"]);

Route::delete('/v1.0/job-bids/{garage_id}/{id}', [JobBidController::class, "deleteJobBidById"]);


Route::delete('/v1.0/bookings/{garage_id}/{id}', [BookingController::class, "deleteBookingById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// job bid management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// job management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::patch('/v1.0/jobs/booking-to-job', [JobController::class, "bookingToJob"]);
Route::put('/v1.0/jobs', [JobController::class, "updateJob"]);
Route::put('/v1.0/jobs/change-status', [JobController::class, "changeJobStatus"]);
Route::get('/v1.0/jobs/{garage_id}/{perPage}', [JobController::class, "getJobs"]);
Route::get('/v1.0/jobs/single/{garage_id}/{id}', [JobController::class, "getJobById"]);
Route::delete('/v1.0/jobs/{garage_id}/{id}', [JobController::class, "deleteJobById"]);
Route::patch('/v1.0/jobs/payment', [JobController::class, "addPayment"]);
Route::delete('/v1.0/jobs/payment/{garage_id}/{id}', [JobController::class, "deletePaymentById"]);
Route::get('/v1.0/jobs-payments/{garage_id}', [JobController::class, "getJobPayments"]);
Route::get('/v1.0/jobs-payments-sum/{garage_id}', [JobController::class, "getJobPaymentsSum"]);

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// job management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%





// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// coupon management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/coupons', [CouponController::class, "createCoupon"]);
Route::put('/v1.0/coupons', [CouponController::class, "updateCoupon"]);

Route::put('/v1.0/coupons/toggle-active', [CouponController::class, "toggleActiveCoupon"]);

Route::get('/v1.0/coupons/{garage_id}/{perPage}', [CouponController::class, "getCoupons"]);
Route::get('/v1.0/coupons/single/{garage_id}/{id}', [CouponController::class, "getCouponById"]);
Route::delete('/v1.0/coupons/{garage_id}/{id}', [CouponController::class, "deleteCouponById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// coupon management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// affiliation management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/affiliations-logo', [AffiliationController::class, "createAffiliationLogo"]);


Route::post('/v1.0/affiliations', [AffiliationController::class, "createAffiliation"]);
Route::put('/v1.0/affiliations', [AffiliationController::class, "updateAffiliation"]);
Route::get('/v1.0/affiliations/{perPage}', [AffiliationController::class, "getAffiliations"]);
Route::delete('/v1.0/affiliations/{id}', [AffiliationController::class, "deleteAffiliationById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// affiliation management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// affiliation management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/garage-affiliations', [GarageAffiliationController::class, "createGarageAffiliation"]);
Route::put('/v1.0/garage-affiliations', [GarageAffiliationController::class, "updateGarageAffiliation"]);
Route::get('/v1.0/garage-affiliations/{perPage}', [GarageAffiliationController::class, "getGarageAffiliations"]);
Route::get('/v1.0/garage-affiliations/{garage_id}/{perPage}', [GarageAffiliationController::class, "getGarageAffiliationsByGarageId"]);


Route::get('/v1.0/garage-affiliations/get/all/{garage_id}', [GarageAffiliationController::class, "getGarageAffiliationsAllByGarageId"]);



Route::delete('/v1.0/garage-affiliations/{id}', [GarageAffiliationController::class, "deleteGarageAffiliationById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// affiliation management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// price management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/garage-sub-service-prices', [GarageServicePriceController::class, "createGarageSubServicePrice"]);

Route::put('/v1.0/garage-service-prices', [GarageServicePriceController::class, "updateGarageSubServicePrice"]);



Route::delete('/v1.0/garage-service-prices/{id}', [GarageServicePriceController::class, "deleteGarageSubServicePriceById"]);

Route::delete('/v1.0/garage-service-prices/by-garage-sub-service/{id}', [GarageServicePriceController::class, "deleteGarageSubServicePriceByGarageSubServiceId"]);


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// price management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// package management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/garage-packages', [GaragePackageController::class, "createGaragePackage"]);

Route::put('/v1.0/garage-packages', [GaragePackageController::class, "updateGaragePackage"]);




Route::put('/v1.0/garage-packages/toggle-active', [GaragePackageController::class, "toggleActiveGaragePackage"]);











Route::get('/v1.0/garage-packages/{garage_id}/{perPage}', [GaragePackageController::class, "getGaragePackages"]);



Route::get('/v1.0/garage-packages/single/{garage_id}/{id}', [GaragePackageController::class, "getGaragePackageById"]);

Route::delete('/v1.0/garage-packages/single/{garage_id}/{id}', [GaragePackageController::class, "deleteGaragePackageById"]);


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// package management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// dashboard section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



Route::get('/v1.0/garage-owner-dashboard/jobs-in-area/{garage_id}', [DashboardManagementController::class, "getGarageOwnerDashboardDataJobList"]);

Route::get('/v1.0/garage-owner-dashboard/jobs-application/{garage_id}', [DashboardManagementController::class, "getGarageOwnerDashboardDataJobApplications"]);


Route::get('/v1.0/garage-owner-dashboard/winned-jobs-application/{garage_id}', [DashboardManagementController::class, "getGarageOwnerDashboardDataWinnedJobApplications"]);

Route::get('/v1.0/garage-owner-dashboard/completed-bookings/{garage_id}', [DashboardManagementController::class, "getGarageOwnerDashboardDataCompletedBookings"]);


Route::get('/v1.0/garage-owner-dashboard/upcoming-jobs/{garage_id}/{duration}', [DashboardManagementController::class, "getGarageOwnerDashboardDataUpcomingJobs"]);

Route::get('/v1.0/garage-owner-dashboard/expiring-affiliations/{garage_id}/{duration}', [DashboardManagementController::class, "getGarageOwnerDashboardDataExpiringAffiliations"]);


Route::get('/v1.0/garage-owner-dashboard/{garage_id}', [DashboardManagementController::class, "getGarageOwnerDashboardData"]);

Route::get('/v1.0/superadmin-dashboard', [DashboardManagementController::class, "getSuperAdminDashboardData"]);
Route::get('/v1.0/data-collector-dashboard', [DashboardManagementController::class, "getDataCollectorDashboardData"]);



Route::get('/v1.0/business-owner-dashboard', [DashboardManagementController::class, "getBusinessOwnerDashboardData"]);

Route::get("/v1.0/expert-report",[DashboardManagementController::class, "getExpertReport"]);

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end dashboard section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%














// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// shop section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// shop management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



Route::post('/v1.0/auth/register-with-shop', [ShopsController::class, "registerUserWithShop"]);
Route::put('/v1.0/shops', [ShopsController::class, "updateShop"]);
Route::get('/v1.0/shops/{perPage}', [ShopsController::class, "getShops"]);
Route::get('/v1.0/shops/single/{id}', [ShopsController::class, "getShopById"]);
Route::delete('/v1.0/shops/{id}', [ShopsController::class, "deleteShopById"]);

Route::get('/v1.0/shops/by-shop-owner/all', [ShopsController::class, "getAllShopsByShopOwner"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end shop management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// product category management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/product-categories', [ProductCategoryController::class, "createProductCategory"]);
Route::put('/v1.0/product-categories', [ProductCategoryController::class, "updateProductCategory"]);
Route::get('/v1.0/product-categories/{perPage}', [ProductCategoryController::class, "getProductCategories"]);
Route::delete('/v1.0/product-categories/{id}', [ProductCategoryController::class, "deleteProductCategoryById"]);
Route::get('/v1.0/product-categories/single/get/{id}', [ProductCategoryController::class, "getProductCategoryById"]);

Route::get('/v1.0/product-categories/get/all', [ProductCategoryController::class, "getAllProductCategory"]);


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end product category management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// product  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/products', [ProductController::class, "createProduct"]);
Route::put('/v1.0/products', [ProductController::class, "updateProduct"]);
Route::patch('/v1.0/products/link-product-to-shop', [ProductController::class, "linkProductToShop"]);

Route::get('/v1.0/products/{perPage}', [ProductController::class, "getProducts"]);
Route::get('/v1.0/products/single/get/{id}', [ProductController::class, "getProductById"]);
Route::delete('/v1.0/products/{id}', [ProductController::class, "deleteProductById"]);




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end product  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@











});

// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^
// end admin routes
// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^




























































// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^
// client routes
// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^

Route::get('/v1.0/client/business-settings', [SettingController::class, "getBusinessSettingsClient"]);

Route::get('/v1.0/garage-packages/get/all/{garage_id}', [GaragePackageController::class, "getGaragePackagesAll"]);
Route::get('/v1.0/client/garage-packages/single/{garage_id}/{id}', [GaragePackageController::class, "getGaragePackageByIdClient"]);


Route::get('/v1.0/client/expert-rotas', [ExpertRotaController::class, "getExpertRotasClient"]);
Route::get('/v1.0/client/garage-services/get/all/{garage_id}', [GarageServiceController::class, "getGarageServicesAll"]);


Route::get('/v1.0/client/fuel-station/{perPage}', [FuelStationController::class, "getFuelStationsClient"]);


Route::get('/v3.0/client/fuel-station/{perPage}', [FuelStationController::class, "getFuelStationsClientV3"]);



Route::get('/v2.0/client/fuel-station', [FuelStationController::class, "getFuelStationsClient2"]);



Route::get('/v1.0/client/fuel-station/get/single/{id}', [FuelStationController::class, "getFuelStationByIdClient"]);


Route::get('/v1.0/client/fuel-station-services/get/all', [FuelStationServiceController::class, "getFuelStationServicesAllClient"]);


Route::get('/v1.0/client/garage-galleries/{garage_id}', [GarageGalleryController::class, "getGarageGalleriesClient"]);
Route::get('/v1.0/client/fuel-stations-galleries/{fuel_station_id}', [FuelStationGalleryController::class, "getFuelStationGalleriesClient"]);


Route::get('/v1.0/client/garages/{perPage}', [ClientBasicController::class, "getGaragesClient"]);
Route::get('/v2.0/client/garages/{perPage}', [ClientBasicController::class, "getGaragesClient2"]);
Route::get('/v3.0/client/garages', [ClientBasicController::class, "getGaragesClient3"]);

Route::get('/v1.0/client/garages/single/{id}', [ClientBasicController::class, "getGarageByIdClient"]);
Route::get('/v2.0/client/garages/single/{id}', [ClientBasicController::class, "getGarageByIdClient2"]);

Route::get('/v1.0/client/garages/service-model-details/{garage_id}', [ClientBasicController::class, "getGarageServiceModelDetailsByIdClient"]);

Route::get('/v1.0/client/garages/garage-automobile-models/{garage_id}/{automobile_make_id}', [ClientBasicController::class, "getGarageAutomobileModelsByAutomobileMakeId"]);

Route::get('/v1.0/client/garage-affiliations/get/all/{garage_id}', [ClientBasicController::class, "getGarageAffiliationsAllByGarageIdClient"]);



Route::get('/client/review-new/get/questions-all', [ClientReviewController::class, "getQuestionAllUnauthorized"]);

Route::get('/client/review-new/get/questions-all-report', [ClientReviewController::class, "getQuestionAllReportUnauthorized"]);

Route::get('/review-new/getreviewAll/{garageId}', [ReviewController::class, "getReviewByGarageIdAll"]);
Route::get('/review-new/getreviewAll/{garageId}/{perPage}', [ReviewController::class, "getReviewByGarageId"]);



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// coupon management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::get('/v1.0/client/coupons/by-garage-id/{garage_id}/{perPage}', [ClientCouponController::class, "getCouponsByGarageIdClient"]);
Route::get('/v1.0/client/coupons/all/{perPage}', [ClientCouponController::class, "getCouponsClient"]);
Route::get('/v1.0/client/coupons/single/{id}', [ClientCouponController::class, "getCouponByIdClient"]);
Route::get('/v1.0/client/coupons/get-discount/{garage_id}/{code}/{amount}', [ClientCouponController::class, "getCouponDiscountClient"]);
Route::get('/v1.0/client/coupons/all-auto-applied-coupons/{garage_id}', [ClientCouponController::class, "getAutoAppliedCouponsByGarageIdClient"]);





// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// coupon management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%






Route::post('/v1.0/client/pre-bookings-image-multiple', [ClientPreBookingController::class, "createPreBookingImageMultipleClient"]);
Route::post('/v1.0/client/pre-bookings-video', [ClientPreBookingController::class, "createPreBookingVideoClient"]);

// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^
// client protected routes
// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^

Route::middleware(['auth:api'])->group(function () {


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // holiday  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    Route::post('/v1.0/holidays', [HolidayController::class, "createHoliday"]);

    Route::put('/v1.0/holidays', [HolidayController::class, "updateHoliday"]);



    Route::get('/v1.0/holidays', [HolidayController::class, "getHolidays"]);


    Route::get('/v1.0/holidays/{id}', [HolidayController::class, "getHolidayById"]);

    Route::delete('/v1.0/holidays/{ids}', [HolidayController::class, "deleteHolidaysByIds"]);




    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end holiday  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::get('/v1.0/client/favourite-sub-services/{perPage}', [ClientBasicController::class, "getFavouriteSubServices"]);


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// booking management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::get('/v1.0/client/blocked-slots/{expert_id}', [ClientBookingController::class, "getBlockedSlotsClient"]);
Route::get('/v1.0/client/blocked-dates', [ClientBookingController::class, "getBlockedDatesClient"]);
Route::post('/v1.0/hold-slot', [ClientBookingController::class, 'holdSlot']);



Route::get('/v1.0/client/available-experts', [ClientBookingController::class, "getAvailableExpertsClient"]);


Route::post('/v1.0/client/bookings', [ClientBookingController::class, "createBookingClient"]);

Route::put('/v1.0/client/bookings', [ClientBookingController::class, "updateBookingClient"]);
Route::patch('/v1.0/client/bookings/change-status', [ClientBookingController::class, "changeBookingStatusClient"]);
Route::get('/v1.0/client/bookings/{perPage}', [ClientBookingController::class, "getBookingsClient"]);
Route::get('/v1.0/client/bookings/single/{id}', [ClientBookingController::class, "getBookingByIdClient"]);
Route::delete('/v1.0/client/bookings/{id}', [ClientBookingController::class, "deleteBookingByIdClient"]);
Route::delete('/v1.0/client/bookings-stripe-error/{id}', [ClientBookingController::class, "deleteBookingStripeErrorByIdClient"]);


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// booking management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// booking management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



Route::get('/v1.0/client/jobs/{perPage}', [ClientJobController::class, "getJobsClient"]);
Route::get('/v1.0/client/jobs/single/{id}', [ClientJobController::class, "getJobByIdClient"]);

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// booking management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// client pre booking management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



Route::post('/v1.0/client/pre-bookings', [ClientPreBookingController::class, "createPreBookingClient"]);
Route::put('/v1.0/client/pre-bookings', [ClientPreBookingController::class, "updatePreBookingClient"]);



Route::get('/v1.0/client/pre-bookings/{perPage}', [ClientPreBookingController::class, "getPreBookingsClient"]);

Route::get('/v1.0/client/pre-bookings/single/{id}', [ClientPreBookingController::class, "getPreBookingByIdClient"]);

Route::post('/v1.0/client/pre-bookings/confirm', [ClientPreBookingController::class, "confirmPreBookingClient"]);




Route::delete('/v1.0/client/pre-bookings/{id}', [ClientPreBookingController::class, "deletePreBookingByIdClient"]);


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
//  client pre booking management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%






});


Route::post('webhooks/stripe', [CustomWebhookController::class, "handleStripeWebhook"])->name("stripe.webhook");




Route::get('/whatsapp', function(Request $request) {
    $response = Http::withHeaders([
        'Authorization' => "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJhMWUxNzdlYS0yODc4LTQ3YTMtOGUxYi04N2E0Y2MxZTczM2YiLCJ1bmlxdWVfbmFtZSI6InJpZmF0YmlsYWxwaGlsaXBzQGdtYWlsLmNvbSIsIm5hbWVpZCI6InJpZmF0YmlsYWxwaGlsaXBzQGdtYWlsLmNvbSIsImVtYWlsIjoicmlmYXRiaWxhbHBoaWxpcHNAZ21haWwuY29tIiwiYXV0aF90aW1lIjoiMTAvMjUvMjAyNCAxMzowNToyNCIsInRlbmFudF9pZCI6IjM2MzMxNCIsImRiX25hbWUiOiJtdC1wcm9kLVRlbmFudHMiLCJodHRwOi8vc2NoZW1hcy5taWNyb3NvZnQuY29tL3dzLzIwMDgvMDYvaWRlbnRpdHkvY2xhaW1zL3JvbGUiOiJBRE1JTklTVFJBVE9SIiwiZXhwIjoyNTM0MDIzMDA4MDAsImlzcyI6IkNsYXJlX0FJIiwiYXVkIjoiQ2xhcmVfQUkifQ.qPCV8OoqfFQrK9XGeFSx2Qa3Lb2tDG2l6tQvoA4hyb0",
        'Content-Type' => 'application/json',
    ])->post("https://live-mt-server.wati.io/363314", [
        'to' => "+8801771034383",
        'message' => "Hi!!",
    ]);
    if ($response->failed()) {
        Log::error('Error response:', [$response->json()]);
        return response()->json(['error' => 'Failed to send message', 'details' => $response->json()], 400);
    }
    return response()->json($response->json());
} );
