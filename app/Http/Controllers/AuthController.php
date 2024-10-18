<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRegenerateTokenRequest;

use App\Http\Requests\AuthRegisterGarageRequestClient;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\EmailVerifyTokenRequest;
use App\Http\Requests\ForgetPasswordRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\UserInfoUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\GarageUtil;
use App\Http\Utils\UserActivityUtil;
use App\Mail\ForgetPasswordMail;
use App\Mail\VerifyMail;
use App\Models\AutomobileCategory;
use App\Models\AutomobileMake;
use App\Models\AutomobileModel;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateWrapper;
use App\Models\Garage;
use App\Models\GarageAutomobileMake;
use App\Models\GarageAutomobileModel;
use App\Models\GarageGallery;
use App\Models\GarageService;
use App\Models\GarageSubService;
use App\Models\GarageTime;
use App\Models\Service;
use App\Models\SubService;
use App\Models\User;
use App\Models\UserTranslation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    use ErrorUtil, GarageUtil, UserActivityUtil;

        /**
     *
     * @OA\Post(
     *      path="/activate-user",
     *      operationId="activateUser",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user",
     *      description="This method is to store user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"first_Name"},
     *             @OA\Property(property="token", type="string", format="string",example="token")
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

     public function activateUser(Request $request)
     {
         // Validate the request
         $request->validate([
             'token' => 'required',
         ]);

         $token = $request->input('token');

         // Find the user with the provided token and check if the token is still valid
         $user = User::where('email_verify_token', $token)
                     ->where('email_verify_token_expires', '>', now())
                     ->first();

         if (!$user) {
             return response()->json([
                 'message' => 'Invalid Token or Token Expired',
             ], 400);
         }

         // Mark the email as verified
         $user->email_verified_at = now();
         $user->save();

         // Retrieve the welcome email template
         $email_content = EmailTemplate::where([
             'type' => 'welcome_message',
             'is_active' => 1
         ])->first();

         if (!$email_content) {
             return response()->json([
                 'message' => 'Email template not found',
             ], 404);
         }

         // Replace placeholders with actual user data
         $html_content = json_decode($email_content->template);
         $html_content = str_replace('[FirstName]', $user->first_name, $html_content);
         $html_content = str_replace('[LastName]', $user->last_name, $html_content);
         $html_content = str_replace('[FullName]', $user->first_name . ' ' . $user->last_name, $html_content);
         $html_content = str_replace('[AccountVerificationLink]', env('APP_URL').'/activate/'.$user->email_verify_token, $html_content);
         $html_content = str_replace('[ForgotPasswordLink]', env('FRONT_END_URL').'/forget-password/'.$user->resetPasswordToken, $html_content);

         // Get the email wrapper and wrap the content
         $email_template_wrapper = EmailTemplateWrapper::where('id', $email_content->wrapper_id)->first();

         if ($email_template_wrapper) {
             $html_final = json_decode($email_template_wrapper->template);
             $html_final = str_replace('[content]', $html_content, $html_final);
         } else {
             $html_final = $html_content;
         }

         // Return the final dynamic message as a view or response
         return view('dynamic-welcome-message', ['html_content' => $html_final]);
     }



    /**
     *
     * @OA\Post(
     *      path="/v1.0/register",
     *      operationId="register",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user",
     *      description="This method is to store user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode"},
     *             @OA\Property(property="first_Name", type="string", format="string",example="Rifat"),
     *            @OA\Property(property="last_Name", type="string", format="string",example="Al"),
     *            @OA\Property(property="email", type="string", format="string",example="rifat@g.c"),

     * *  @OA\Property(property="password", type="string", format="string",example="12345678"),
     *  * *  @OA\Property(property="password_confirmation", type="string", format="string",example="12345678"),
     *  * *  @OA\Property(property="phone", type="string", format="string",example="01771034383"),
     *  * *  @OA\Property(property="address_line_1", type="string", format="string",example="dhaka"),
     *  * *  @OA\Property(property="address_line_2", type="string", format="string",example="dinajpur"),
     *  * *  @OA\Property(property="country", type="string", format="string",example="bangladesh"),
     *  * *  @OA\Property(property="city", type="string", format="string",example="dhaka"),
     *  * *  @OA\Property(property="postcode", type="string", format="string",example="1207"),
     *      *  * *  @OA\Property(property="lat", type="string", format="string",example="1207"),
     *      *  * *  @OA\Property(property="long", type="string", format="string",example="1207"),
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

    public function register(AuthRegisterRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            $insertableData = $request->validated();

            $insertableData['password'] = Hash::make($request['password']);
            $insertableData['remember_token'] = Str::random(10);
            $insertableData['is_active'] = true;
            $user =  User::create($insertableData);

              // verify email starts
              $email_token = Str::random(30);
              $otp = random_int(100000, 999999);
              $user->email_verify_token = $otp;
              $user->email_verify_token_expires = Carbon::now()->subDays(-1);
              $user->save();


             $user->assignRole("customer");

            $user->token = $user->createToken('Laravel Password Grant Client')->accessToken;
            $user->permissions = $user->getAllPermissions()->pluck('name');
            $user->roles = $user->roles->pluck('name');


            if(env("SEND_EMAIL") == true) {
                Mail::to($user->email)->send(new VerifyMail($user));
            }

// verify email ends

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

$user->translation = $user->translation;



            return response($user, 201);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }




    /**
     *
     * @OA\Post(
     *      path="/v1.0/login",
     *      operationId="login",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to login user",
     *      description="This method is to login user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email","password"},
     *            @OA\Property(property="email", type="string", format="string",example="admin@gmail.com"),

     * *  @OA\Property(property="password", type="string", format="string",example="12345678"),
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
    public function login(Request $request)
    {


        try {
            $this->storeActivity($request,"");
            $loginData = $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);
            $user = User::where('email', $loginData['email'])->first();

            if ($user && $user->login_attempts >= 5) {
                $now = Carbon::now();
                $lastFailedAttempt = Carbon::parse($user->last_failed_login_attempt_at);
                $diffInMinutes = $now->diffInMinutes($lastFailedAttempt);

                if ($diffInMinutes < 15) {
                    return response(['message' => 'You have 5 failed attempts. Reset your password or wait for 15 minutes to access your account.'], 403);
                } else {
                    $user->login_attempts = 0;
                    $user->last_failed_login_attempt_at = null;
                    $user->save();
                }
            }


            if (!auth()->attempt($loginData)) {
                if ($user) {
                    $user->login_attempts++;
                    $user->last_failed_login_attempt_at = Carbon::now();
                    $user->save();

                    if ($user->login_attempts >= 5) {
                        $now = Carbon::now();
                        $lastFailedAttempt = Carbon::parse($user->last_failed_login_attempt_at);
                        $diffInMinutes = $now->diffInMinutes($lastFailedAttempt);

                        if ($diffInMinutes < 15) {
                            return response(['message' => 'You have 5 failed attempts. Reset your password or wait for 15 minutes to access your account.'], 403);
                        } else {
                            $user->login_attempts = 0;
                            $user->last_failed_login_attempt_at = null;
                            $user->save();
                        }
                    }
                }

                return response(['message' => 'Invalid Credentials'], 401);
            }

            $user = auth()->user();

            if(!$user->is_active) {
                return response(['message' => 'User not active'], 403);

            }
            $now = time(); // or your date as well
$user_created_date = strtotime($user->created_at);
$datediff = $now - $user_created_date;

            if(!$user->email_verified_at && (($datediff / (60 * 60 * 24))>1)){
                return response(['message' => 'please activate your email first'], 409);
            }


            $user->login_attempts = 0;
            $user->last_failed_login_attempt_at = null;


            $site_redirect_token = Str::random(30);
            $site_redirect_token_data["created_at"] = $now;
            $site_redirect_token_data["token"] = $site_redirect_token;
            $user->site_redirect_token = json_encode($site_redirect_token_data);
            $user->save();

            $user->redirect_token = $site_redirect_token;

            $user->token = auth()->user()->createToken('authToken')->accessToken;
            $user->permissions = $user->getAllPermissions()->pluck('name');
            $user->roles = $user->roles->pluck('name');


            $user->translation = $user->translation;


            return response()->json(['data' => $user,   "ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }

  /**
     *
     * @OA\Post(
     *      path="/v1.0/token-regenerate",
     *      operationId="regenerateToken",
     *      tags={"auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to regenerate Token",
     *      description="This method is to regenerate Token",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user_id","site_redirect_token"},
     *            @OA\Property(property="user_id", type="number", format="number",example="1"),

     * *  @OA\Property(property="site_redirect_token", type="string", format="string",example="12345678"),
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
    public function regenerateToken(AuthRegenerateTokenRequest $request)
    {

        try {
            $this->storeActivity($request,"");
            $insertableData = $request->validated();
            $user = User::where([
                "id" => $insertableData["user_id"],
            ])
            ->first();



            $site_redirect_token_db = (json_decode($user->site_redirect_token,true));

            if($site_redirect_token_db["token"] !== $insertableData["site_redirect_token"]) {
               return response()
               ->json([
                  "message" => "invalid token"
               ],409);
            }

            $now = time(); // or your date as well

            $timediff = $now - $site_redirect_token_db["created_at"];

            if ($timediff > 20){
                return response(['message' => 'token expired'], 409);
            }



            $user->tokens()->delete();
            $user->token = $user->createToken('authToken')->accessToken;
            $user->permissions = $user->getAllPermissions()->pluck('name');
            $user->roles = $user->roles->pluck('name');
            $user->a = ($timediff);




            return response()->json(['data' => $user,   "ok" => true], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }
   /**
        *
     * @OA\Post(
     *      path="/forgetpassword",
     *      operationId="storeToken",
     *      tags={"auth"},

     *      summary="This method is to store token",
     *      description="This method is to store token",

     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="string",* example="test@g.c"),
     *    *             @OA\Property(property="client_site", type="string", format="string",* example="client"),
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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */

    public function storeToken(ForgetPasswordRequest $request) {

        try {
            $this->storeActivity($request,"");
            return DB::transaction(function () use (&$request) {
                $insertableData = $request->validated();

            $user = User::where(["email" => $insertableData["email"]])->first();
            if (!$user) {
                return response()->json(["message" => "no user found"], 404);
            }

            $token = Str::random(30);

            $user->resetPasswordToken = $token;
            $user->resetPasswordExpires = Carbon::now()->subDays(-1);
            $user->save();

            Mail::to($insertableData["email"])->send(new ForgetPasswordMail($user,$insertableData["client_site"]));


            return response()->json([
                "message" => "please check email"
            ]);
            });




        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }

    }

      /**
        *
     * @OA\Post(
     *      path="/resend-email-verify-mail",
     *      operationId="resendEmailVerifyToken",
     *      tags={"auth"},

     *      summary="This method is to resend email verify mail",
     *      description="This method is to resend email verify mail",

     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},

     *             @OA\Property(property="email", type="string", format="string",* example="test@g.c"),
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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */

    public function resendEmailVerifyToken(EmailVerifyTokenRequest $request) {

        try {
            $this->storeActivity($request,"");
            return DB::transaction(function () use (&$request) {
                $insertableData = $request->validated();

            $user = User::
            where(["email" => $insertableData["email"]])->first();
            if (!$user) {
                return response()->json(["message" => "no user found"], 404);
            }



            $otp = random_int(100000, 999999);
            $user->email_verify_token = $otp;
            $user->email_verify_token_expires = Carbon::now()->subDays(-1);
            if(env("SEND_EMAIL") == true) {
                Mail::to($user->email)->send(new VerifyMail($user));
            }

            $user->save();


            return response()->json([
                "message" => "please check email"
            ]);
            });




        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }

    }


/**
        *
     * @OA\Patch(
     *      path="/forgetpassword/reset/{token}",
     *      operationId="changePasswordByToken",
     *      tags={"auth"},
     *  @OA\Parameter(
* name="token",
* in="path",
* description="token",
* required=true,
* example="1"
* ),
     *      summary="This method is to change password",
     *      description="This method is to change password",

     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"password"},
     *
     *     @OA\Property(property="password", type="string", format="string",* example="aaaaaaaa"),

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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */





    public function changePasswordByToken($token, ChangePasswordRequest $request)
    {
        try {
            $this->storeActivity($request,"");
            return DB::transaction(function () use (&$request,&$token) {
                $insertableData = $request->validated();
                $user = User::where([
                    "resetPasswordToken" => $token,
                ])
                    ->where("resetPasswordExpires", ">", now())
                    ->first();
                if (!$user) {
                    return response()->json([
                        "message" => "Invalid Token Or Token Expired"
                    ], 400);
                }

                $password = Hash::make($insertableData["password"]);
                $user->password = $password;

                $user->login_attempts = 0;
                $user->last_failed_login_attempt_at = null;


                $user->save();


                return response()->json([
                    "message" => "password changed"
                ], 200);
            });




        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }

    }



    /**
     *
     * @OA\Post(
     *      path="/v1.0/auth/user-register-with-garage",
     *      operationId="registerUserWithGarageClient",
     *      tags={"auth"},
     *      summary="This method is to store user with garage client side",
     *      description="This method is to store user with garage client side",
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
     *
     *
     * }),
     *
     *
     *
     *   *  @OA\Property(property="garage", type="string", format="array",example={
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
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     * "currency":"BDT",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     *  "is_mobile_garage":true,
     *  "wifi_available":true,
     *  "labour_rate":500,
     * "time_format":"12-hour"
     *
     * }),
     *
     *      *    @OA\Property(property="times", type="string", format="array",example={
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
    public function registerUserWithGarageClient(AuthRegisterGarageRequestClient $request)
    {

        try {
            $this->storeActivity($request,"");
            return DB::transaction(function () use (&$request) {
                $insertableData = $request->validated();
                // user info starts ##############
                $insertableData['user']['password'] = Hash::make($insertableData['user']['password']);
                $insertableData['user']['remember_token'] = Str::random(10);
                $insertableData['user']['is_active'] = true;
                $insertableData['user']['address_line_1'] = $insertableData['garage']['address_line_1'];
                $insertableData['user']['address_line_2'] = $insertableData['garage']['address_line_2'];
                $insertableData['user']['country'] = $insertableData['garage']['country'];
                $insertableData['user']['city'] = $insertableData['garage']['city'];
                $insertableData['user']['postcode'] = $insertableData['garage']['postcode'];
                $insertableData['user']['lat'] = $insertableData['garage']['lat'];
                $insertableData['user']['long'] = $insertableData['garage']['long'];


                $user =  User::create($insertableData['user']);
                $user->assignRole('garage_owner');
                // end user info ##############



                //  garage info ##############
                $insertableData['garage']['status'] = "pending";
                $insertableData['garage']['owner_id'] = $user->id;

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
             $serviceUpdate =  $this->createGarageServices($insertableData['service'],$garage->id,true);

                    if(!$serviceUpdate["success"]){
                        $error =  [
                            "message" => "The given data was invalid.",
                            "errors" => [("service".$serviceUpdate["type"])=>[$serviceUpdate["message"]]]
                     ];
                        throw new Exception(json_encode($error),422);

                     }




               $user->business_id = $garage->id;

               $otp = random_int(100000, 999999);
               $user->email_verify_token = $otp;
               $user->email_verify_token_expires = Carbon::now()->subDays(-1);
               $user->save();

                if(env("SEND_EMAIL") == true) {
                    Mail::to($user->email)->send(new VerifyMail($user));
                }

// verify email ends



$user->token = $user->createToken('Laravel Password Grant Client')->accessToken;
$user->permissions = $user->getAllPermissions()->pluck('name');
$user->roles = $user->roles->pluck('name');

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
                     "user" => $user,
                     "garage" => $garage,
                     "success" => true
                ], 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500,$request);
        }
    }




 /**
        *
     * @OA\Get(
     *      path="/v1.0/user",
     *      operationId="getUser",
     *      tags={"auth"},
    *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="This method is to get  user ",
     *      description="This method is to get user",
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


public function getUser (Request $request) {
    try{
        $this->storeActivity($request,"");
        $user = $request->user();
        $user->token = auth()->user()->createToken('authToken')->accessToken;
        $user->permissions = $user->getAllPermissions()->pluck('name');
        $user->roles = $user->roles->pluck('name');

        $user->default_background_image = ("/".  config("setup-config.garage_background_image_location_full"));

        return response()->json(
            $user,
            200
        );
    }catch(Exception $e) {
        return $this->sendError($e, 500,$request);
    }

}

  /**
        *
     * @OA\Post(
     *      path="/auth/check/email",
     *      operationId="checkEmail",
     *      tags={"auth"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to check user",
     *      description="This method is to check user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},
     *  *             @OA\Property(property="id", type="string", format="string",example="1"),
     *             @OA\Property(property="email", type="string", format="string",example="test@g.c"),
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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */


     public function checkEmail(Request $request) {
        try{
            $this->storeActivity($request,"");
            $user = User::
            when(request()->filled("id"), function($query) {
                $query->whereNotIn("id",[request()->input("id")]);
            })

            ->where([
                "email" => $request->email
               ])->first();
               if($user) {
       return response()->json(["data" => true],200);
               }
               return response()->json(["data" => false],200);
        }catch(Exception $e) {
            return $this->sendError($e, 500,$request);
        }

 }

  /**
        *
     * @OA\Post(
     *      path="/auth/check/business-email",
     *      operationId="checkBusinessEmail",
     *      tags={"auth"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to check business email",
     *      description="This method is to check business email",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},
     *  *             @OA\Property(property="id", type="string", format="string",example="1"),
     *             @OA\Property(property="email", type="string", format="string",example="test@g.c"),
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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */


    public function checkBusinessEmail(Request $request) {
        try{
            $this->storeActivity($request,"");
            $user = Garage::
            when(request()->filled("id"), function($query) {
                $query->whereNotIn("id",[request()->input("id")]);
            })

            ->where([
                "email" => $request->email
               ])->first();
               if($user) {
       return response()->json(["data" => true],200);
               }
               return response()->json(["data" => false],200);
        }catch(Exception $e) {
            return $this->sendError($e, 500,$request);
        }

 }






  /**
        *
     * @OA\Patch(
     *      path="/auth/changepassword",
     *      operationId="changePassword",
     *      tags={"auth"},
 *
     *      summary="This method is to change password",
     *      description="This method is to change password",
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"password","cpassword"},
     *
     *     @OA\Property(property="password", type="string", format="string",* example="aaaaaaaa"),
    *  * *  @OA\Property(property="password_confirmation", type="string", format="string",example="aaaaaaaa"),
     *     @OA\Property(property="current_password", type="string", format="string",* example="aaaaaaaa"),
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
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */





    public function changePassword(PasswordChangeRequest $request)
    {
try{
    $this->storeActivity($request,"");
    $client_request = $request->validated();

    $user = $request->user();



    if (!Hash::check($client_request["current_password"],$user->password)) {
        return response()->json([
            "message" => "Invalid password"
        ], 400);
    }

    $password = Hash::make($client_request["password"]);
    $user->password = $password;



    $user->login_attempts = 0;
    $user->last_failed_login_attempt_at = null;
    $user->save();



    return response()->json([
        "message" => "password changed"
    ], 200);
}catch(Exception $e) {
    return $this->sendError($e,500,$request);
}

    }






 /**
        *
     * @OA\Put(
     *      path="/v1.0/update-user-info",
     *      operationId="updateUserInfo",
     *      tags={"auth"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user by user",
     *      description="This method is to update user by user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode"},
     *             @OA\Property(property="first_Name", type="string", format="string",example="tsa"),
     *            @OA\Property(property="last_Name", type="string", format="string",example="ts"),
     *            @OA\Property(property="email", type="string", format="string",example="admin@gmail.com"),

     * *  @OA\Property(property="password", type="boolean", format="boolean",example="12345678"),
     *  * *  @OA\Property(property="password_confirmation", type="string", format="string",example="12345678"),
     *  * *  @OA\Property(property="phone", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="address_line_1", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="address_line_2", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="country", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="city", type="string", format="string",example="1"),
     *  * *  @OA\Property(property="postcode", type="string", format="string",example="1"),
     *  *  * *  @OA\Property(property="lat", type="string", format="string",example="1"),
     *  *  * *  @OA\Property(property="long", type="string", format="string",example="1"),

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

    public function updateUserInfo(UserInfoUpdateRequest $request)
    {

        try{
            $this->storeActivity($request,"");
            $updatableData = $request->validated();


            if(!empty($updatableData['password'])) {
                $updatableData['password'] = Hash::make($updatableData['password']);
            } else {
                unset($updatableData['password']);
            }
            $updatableData['is_active'] = true;
            $updatableData['remember_token'] = Str::random(10);
            $user  =  tap(User::where(["id" => $request->user()->id]))->update(collect($updatableData)->only([
                'first_Name' ,
                'last_Name',
                'password',
                'phone',
                'address_line_1',
                'address_line_2',
                'country',
                'city',
                'postcode',
                "lat",
                "long",
            ])->toArray()
            )
                // ->with("somthing")

                ->first();
                if(!$user) {
                    return response()->json([
                        "message" => "no user found"
                        ]);

            }


            $user->roles = $user->roles->pluck('name');


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





            return response($user, 200);
        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }










}
