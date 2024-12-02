<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AutomobileCategory;
use App\Models\AutomobileFuelType;


use App\Models\ErrorLog;
use App\Models\Service;
use App\Models\ServiceTranslation;
use App\Models\SubService;
use App\Models\SubServiceTranslation;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SetUpController extends Controller
{

    public function setupRoles()
    {
  // ###############################
        // permissions
        // ###############################
        $permissions =  config("setup-config.permissions");
        // setup permissions
        foreach ($permissions as $permission) {
            if(!Permission::where([
            'name' => $permission,
            'guard_name' => 'api'
            ])
            ->exists()){
                Permission::create(['guard_name' => 'api', 'name' => $permission]);
            }

        }

        // setup roles
        $roles = config("setup-config.roles");
        foreach ($roles as $role) {
            if (!Role::where([
                'name' => $role,
                'guard_name' => 'api',
                "is_system_default" => 1,
                "business_id" => NULL,
                "is_default" => 1,
            ])
                ->exists()) {
                Role::create([
                    'guard_name' => 'api',
                    'name' => $role,
                    "is_system_default" => 1,
                    "business_id" => NULL,
                    "is_default" => 1,
                    "is_default_for_business" => (in_array($role, [
                        "business_experts",
                        "business_receptionist",
                    ]) ? 1 : 0)


                ]);
            }
        }

        // setup roles and permissions
        $role_permissions = config("setup-config.roles_permission");
        foreach ($role_permissions as $role_permission) {
            $role = Role::where(["name" => $role_permission["role"]])->first();
            // error_log($role_permission["role"]);
            $permissions = $role_permission["permissions"];
            $role->syncPermissions($permissions);
            // foreach ($permissions as $permission) {
            //     if(!$role->hasPermissionTo($permission)){
            //         $role->givePermissionTo($permission);
            //     }


            // }
        }
    }
    public function getErrorLogs() {
        $error_logs = ErrorLog::orderbyDesc("id")->paginate(10);
        return view("error-log",compact("error_logs"));
    }
    public function getActivityLogs() {
        $activity_logs = ActivityLog::orderbyDesc("id")->paginate(10);
        return view("user-activity-log",compact("activity_logs"));
    }


    public function automobileRefresh() {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        AutomobileCategory::truncate();
        Service::truncate();
        SubService::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        Artisan::call('db:seed --class AutomobileCarSeeder');

        return "automobile refreshed";

    }
    public function swaggerRefresh() {
        Artisan::call('optimize:clear');
Artisan::call('l5-swagger:generate');
return "swagger generated ...............";
    }


    public function setUp(Request $request)
    {
        // @@@@@@@@@@@@@@@@@@@
        // clear everything
        // @@@@@@@@@@@@@@@@@@@
        Artisan::call('optimize:clear');
        Artisan::call('migrate:fresh');
        Artisan::call('migrate:fresh', [
            '--path' => 'database/activity_migrations',
            '--database' => 'logs'
        ]);
        Artisan::call('migrate', ['--path' => 'vendor/laravel/passport/database/migrations']);
        Artisan::call('passport:install');

        Artisan::call('l5-swagger:generate');



        // ##########################################
        // user
        // #########################################
      $admin =  User::create([
        'first_Name' => "super",
        'last_Name'=> "admin",
        'phone'=> "01771034383",
        'address_line_1',
        'address_line_2',
        'country'=> "Bangladesh",
        'city'=> "Dhaka",
        'postcode'=> "1207",
        'email'=> "asjadtariq@gmail.com",
        'password'=>Hash::make("12345678@We"),
        "email_verified_at"=>now(),
        'is_active' => 1
        ]);
        $admin->email_verified_at = now();
        $admin->save();
        // ###############################
        // permissions
        // ###############################

        $this->setupRoles();

        $admin->assignRole("superadmin");

        return "You are done with setup";
    }

    public function migrate(Request $request) {

        Artisan::call('check:migrate');
        return "migrated";
            }
    public function setUp2(Request $request)
    {
        // @@@@@@@@@@@@@@@@@@@
        // clear everything
        // @@@@@@@@@@@@@@@@@@@
        Artisan::call('optimize:clear');
        Artisan::call('migrate:fresh');
        Artisan::call('migrate', ['--path' => 'vendor/laravel/passport/database/migrations']);
        Artisan::call('passport:install');

        Artisan::call('l5-swagger:generate');



        // ##########################################
        // user
        // #########################################
      $admin =  User::create([
        'first_Name' => "super",
        'last_Name'=> "admin",
        'phone'=> "01771034383",
        'address_line_1',
        'address_line_2',
        'country'=> "Bangladesh",
        'city'=> "Dhaka",
        'postcode'=> "1207",
        'email'=> "asjadtariq@gmail.com",
        'password'=>Hash::make("12345678"),
        'is_active' => 1
        ]);

        // ###############################
        // permissions
        // ###############################
        $permissions =  config("setup-config.permissions");
        // setup permissions
        foreach ($permissions as $permission) {
            if(!Permission::where([
            'name' => $permission
            ])
            ->exists()){
                Permission::create(['guard_name' => 'api', 'name' => $permission]);
            }

        }
        // setup roles
        $roles = config("setup-config.roles");
        foreach ($roles as $role) {
            if(!Role::where([
            'name' => $role
            ])
            ->exists()){
             Role::create(['guard_name' => 'api', 'name' => $role]);
            }

        }

        // setup roles and permissions
        $role_permissions = config("setup-config.roles_permission");
        foreach ($role_permissions as $role_permission) {
            $role = Role::where(["name" => $role_permission["role"]])->first();
            error_log($role_permission["role"]);
            $permissions = $role_permission["permissions"];
            foreach ($permissions as $permission) {
                if(!$role->hasPermissionTo($permission)){
                    $role->givePermissionTo($permission);
                }


            }
        }
        $admin->assignRole("superadmin");

        return "You are done with setup";
    }


    public function loadService($business_id) {
        $services = [
            [
                'name' => 'Massage Therapy',
                'description' => 'This category focuses on muscle relaxation, tension relief, and overall wellness. Massage therapy also improves blood circulation and eases stress.',
                'sub_services' => [
                    ['name' => 'Swedish Massage', 'description' => 'A relaxing, full-body massage that uses light to medium pressure, promoting relaxation and stress relief.'],
                    ['name' => 'Deep Tissue Massage', 'description' => 'Targets deeper muscle layers, perfect for chronic pain and muscle tension by applying more intense pressure.'],
                    ['name' => 'Hot Stone Massage', 'description' => 'Heated stones are placed on key points of the body, enhancing relaxation and easing muscle stiffness.'],
                    ['name' => 'Aromatherapy Massage', 'description' => 'Combines massage with the use of essential oils to boost physical and emotional well-being.'],
                    ['name' => 'Prenatal Massage', 'description' => 'Specifically designed for pregnant women, focusing on relieving pregnancy-related discomfort and improving circulation.'],
                ]
            ],
            [
                'name' => 'Facial Treatments',
                'description' => 'Facial services target skincare needs, focusing on rejuvenating the skin, treating conditions like acne, and improving overall complexion.',
                'sub_services' => [
                    ['name' => 'Classic European Facial', 'description' => 'A deep-cleansing facial that exfoliates, extracts impurities, and nourishes the skin, leaving it smooth and refreshed.'],
                    ['name' => 'Anti-Aging Facial', 'description' => 'Uses specialized products and techniques, like collagen masks, to reduce signs of aging such as fine lines and wrinkles.'],
                    ['name' => 'Hydrating Facial', 'description' => 'Designed to restore moisture and elasticity in the skin, ideal for dry or dehydrated skin.'],
                    ['name' => 'Acne Facial', 'description' => 'Targets oily, acne-prone skin using products that help reduce oil production and minimize breakouts.'],
                    ['name' => 'Microdermabrasion', 'description' => 'A non-invasive treatment that exfoliates the skin to reduce fine lines, sun damage, and mild acne scars.'],
                ]
            ],
            [
                'name' => 'Body Treatments',
                'description' => 'These services aim to detoxify, exfoliate, and hydrate the entire body, improving skin texture and appearance while providing relaxation.',
                'sub_services' => [
                    ['name' => 'Body Scrub', 'description' => 'A full-body exfoliation using ingredients like sea salt or sugar to remove dead skin cells, leaving the skin smooth and radiant.'],
                    ['name' => 'Body Wrap', 'description' => 'Involves applying detoxifying creams or masks, followed by wrapping the body in warm towels or sheets to encourage sweating and detoxification.'],
                    ['name' => 'Mud/Clay Wrap', 'description' => 'Uses mineral-rich mud or clay to draw out impurities, improve circulation, and tighten the skin.'],
                    ['name' => 'Cellulite Treatment', 'description' => 'Targets areas prone to cellulite, often using specific massage techniques and products to reduce its appearance.'],
                    ['name' => 'Hydrotherapy', 'description' => 'Uses water in various forms (jets, baths, or showers) to relieve stress, promote relaxation, and detoxify the body.'],
                ]
            ],
            [
                'name' => 'Hand and Foot Care',
                'description' => 'This category includes services that focus on the well-being and appearance of the hands and feet, typically involving grooming and relaxation.',
                'sub_services' => [
                    ['name' => 'Manicure', 'description' => 'Grooming and beautification of the hands and nails, including filing, cuticle care, massage, and polish application.'],
                    ['name' => 'Pedicure', 'description' => 'Similar to a manicure but focuses on the feet, with added benefits of exfoliating and massaging the feet and lower legs.'],
                    ['name' => 'Paraffin Hand/Foot Treatment', 'description' => 'Involves dipping hands or feet into warm paraffin wax to moisturize, soften, and soothe tired skin.'],
                    ['name' => 'Foot Reflexology', 'description' => 'Targets specific pressure points on the feet that correspond to various body organs, promoting overall health and relaxation.'],
                    ['name' => 'Gel or Acrylic Nails', 'description' => 'Cosmetic nail services that extend or strengthen nails using gel or acrylic products.'],
                ]
            ],
            [
                'name' => 'Wellness and Holistic Therapies',
                'description' => 'These services focus on balancing the mind, body, and spirit, often combining ancient techniques with modern wellness practices.',
                'sub_services' => [
                    ['name' => 'Reiki', 'description' => 'A Japanese technique that channels energy through touch to reduce stress, promote relaxation, and encourage healing.'],
                    ['name' => 'Acupuncture', 'description' => 'Involves the insertion of fine needles at specific points in the body to improve energy flow, relieve pain, and treat various health issues.'],
                    ['name' => 'Yoga or Meditation Classes', 'description' => 'Mind-body wellness practices that focus on breathing, stretching, and mental clarity, often offered as part of spa retreats.'],
                    ['name' => 'Ayurvedic Treatments', 'description' => 'Ancient Indian holistic treatments that use natural oils, herbs, and massage techniques to restore balance to the body’s energies.'],
                    ['name' => 'Sound Therapy', 'description' => 'Utilizes vibrational sounds, like gongs or singing bowls, to promote deep relaxation and healing.'],
                ]
            ],
        ];



        foreach ($services as $serviceData) {
            // Translate the service name
            $service_name_query = Http::get('https://api.mymemory.translated.net/get', [
                'q' => $serviceData['name'],
                'langpair' => 'en|ar'  // Specify the source language as 'en' (English) and target as 'ar' (Arabic)
            ]);

            if ($service_name_query['responseStatus'] !== 200) {
                throw new Exception('Translation failed for service name');
            }
            $service_name_translation = $service_name_query['responseData']['translatedText'];

            // Translate the service description
            $service_description_translation = "";
            if (!empty($serviceData['description'])) {
                $service_description_query = Http::get('https://api.mymemory.translated.net/get', [
                    'q' => $serviceData['description'],
                    'langpair' => 'en|ar'
                ]);

                if ($service_description_query['responseStatus'] !== 200) {
                    throw new Exception('Translation failed for service description');
                }
                $service_description_translation = $service_description_query['responseData']['translatedText'];
            }

            // Create the service record
            $service = Service::create([
                'name' => $serviceData['name'],
                'icon' => "",
                'description' => $serviceData['description'],
                'image' => "",
                'automobile_category_id' => 1,
                'business_id' => $business_id,
            ]);

            // Save the translated service details
            ServiceTranslation::create([
                'service_id' => $service->id,
                'language' => 'ar', // For Arabic translation
                'name_translation' => $service_name_translation,
                'description_translation' => $service_description_translation,
            ]);

            // Handle SubServices
            foreach ($serviceData['sub_services'] as $subServiceData) {
                // Translate the sub-service name
                $sub_service_name_query = Http::get('https://api.mymemory.translated.net/get', [
                    'q' => $subServiceData['name'],
                    'langpair' => 'en|ar'
                ]);

                if ($sub_service_name_query['responseStatus'] !== 200) {
                    throw new Exception('Translation failed for sub-service name');
                }
                $sub_service_name_translation = $sub_service_name_query['responseData']['translatedText'];

                // Translate the sub-service description
                $sub_service_description_translation = "";
                if (!empty($subServiceData['description'])) {
                    $sub_service_description_query = Http::get('https://api.mymemory.translated.net/get', [
                        'q' => $subServiceData['description'],
                        'langpair' => 'en|ar'
                    ]);

                    if ($sub_service_description_query['responseStatus'] !== 200) {
                        throw new Exception('Translation failed for sub-service description');
                    }
                    $sub_service_description_translation = $sub_service_description_query['responseData']['translatedText'];
                }

                // Create the sub-service record
                $subService = SubService::create([
                    'name' => $subServiceData['name'],
                    'description' => $subServiceData['description'],
                    'business_id' => $business_id,
                    'service_id' => $service->id,
                    'default_price' => 10,
                    'discounted_price' => 0,


                    'is_fixed_price' => 1,
                    'number_of_slots' => 2,
                ]);

                // Save the translated sub-service details
                SubServiceTranslation::create([
                    'sub_service_id' => $subService->id,
                    'language' => 'ar', // For Arabic translation
                    'name_translation' => $sub_service_name_translation,
                    'description_translation' => $sub_service_description_translation,
                ]);
            }
        }




    }



    public function roleRefreshFunc()
    {


        // ###############################
        // permissions
        // ###############################
        $permissions =  config("setup-config.permissions");

        // setup permissions
        foreach ($permissions as $permission) {
            if (!Permission::where([
                'name' => $permission,
                'guard_name' => 'api'
            ])
                ->exists()) {
                Permission::create(['guard_name' => 'api', 'name' => $permission]);
            }
        }
        // setup roles
        $roles = config("setup-config.roles");
        foreach ($roles as $role) {
            if (!Role::where([
                'name' => $role,
                'guard_name' => 'api',
                "is_system_default" => 1,
                "business_id" => NULL,
                "is_default" => 1,
            ])
                ->exists()) {
                Role::create([
                    'guard_name' => 'api',
                    'name' => $role,
                    "is_system_default" => 1,
                    "business_id" => NULL,
                    "is_default" => 1,
                    "is_default_for_business" => (in_array($role, [
                        "business_experts",
                        "business_receptionist"
                    ]) ? 1 : 0)

                ]);
            }
        }


        // setup roles and permissions
        $role_permissions = config("setup-config.roles_permission");
        foreach ($role_permissions as $role_permission) {
            $role = Role::where(["name" => $role_permission["role"]])->first();

            $permissions = $role_permission["permissions"];


            // Get current permissions associated with the role
            $currentPermissions = $role->permissions()->pluck('name')->toArray();

            // Determine permissions to remove
            $permissionsToRemove = array_diff($currentPermissions, $permissions);

            // Deassign permissions not included in the configuration
            if (!empty($permissionsToRemove)) {
                foreach ($permissionsToRemove as $permission) {
                    $role->revokePermissionTo($permission);
                }
            }

            // Assign permissions from the configuration
            $role->syncPermissions($permissions);
        }


        // $business_ids = Business::get()->pluck("id");

        // foreach ($role_permissions as $role_permission) {

        //     if($role_permission["role"] == "business_employee"){
        //         foreach($business_ids as $business_id){

        //             $role = Role::where(["name" => $role_permission["role"] . "#" . $business_id])->first();

        //            if(empty($role)){

        //             continue;
        //            }

        //                 $permissions = $role_permission["permissions"];

        //                 // Assign permissions from the configuration
        //     $role->syncPermissions($permissions);



        //         }

        //     }

        //     if($role_permission["role"] == "business_manager"){
        //         foreach($business_ids as $business_id){

        //             $role = Role::where(["name" => $role_permission["role"] . "#" . $business_id])->first();

        //            if(empty($role)){

        //             continue;
        //            }

        //                 $permissions = $role_permission["permissions"];

        //                 // Assign permissions from the configuration
        //     $role->syncPermissions($permissions);



        //         }

        //     }



        // }
    }

    public function roleRefresh(Request $request)
    {

        $this->roleRefreshFunc();
        return "You are done with setup";


    }


    public function backup() {
        foreach(DB::connection('backup_database')->table('users')->get() as $backup_data){

        $data_exists = DB::connection('mysql')->table('users')->where([
            "id" => $backup_data->id
           ])->first();
           if(!$data_exists) {
            DB::connection('mysql')->table('users')->insert(get_object_vars($backup_data));
           }
        }


        // foreach(DB::connection('backup_database')->table('automobile_categories')->get() as $backup_data){
        //     $data_exists = DB::connection('mysql')->table('automobile_categories')->where([
        //         "id" => $backup_data->id
        //        ])->first();
        //        if(!$data_exists) {
        //         DB::connection('mysql')->table('automobile_categories')->insert(get_object_vars($backup_data));
        //        }
        //     }



        //         foreach(DB::connection('backup_database')->table('automobile_models')->get() as $backup_data){
        //             $data_exists = DB::connection('mysql')->table('automobile_models')->where([
        //                 "id" => $backup_data->id
        //                ])->first();
        //                if(!$data_exists) {
        //                 DB::connection('mysql')->table('automobile_models')->insert(get_object_vars($backup_data));
        //                }
        //             }

        //             foreach(DB::connection('backup_database')->table('services')->get() as $backup_data){
        //                 $data_exists = DB::connection('mysql')->table('services')->where([
        //                     "id" => $backup_data->id
        //                    ])->first();
        //                    if(!$data_exists) {
        //                     DB::connection('mysql')->table('services')->insert(get_object_vars($backup_data));
        //                    }
        //                 }


        //                 foreach(DB::connection('backup_database')->table('sub_services')->get() as $backup_data){
        //                     $data_exists = DB::connection('mysql')->table('sub_services')->where([
        //                         "id" => $backup_data->id
        //                        ])->first();
        //                        if(!$data_exists) {
        //                         DB::connection('mysql')->table('sub_services')->insert(get_object_vars($backup_data));
        //                        }
        //                     }



                            foreach(DB::connection('backup_database')->table('garages')->get() as $backup_data){
                                $data_exists = DB::connection('mysql')->table('garages')->where([
                                    "id" => $backup_data->id
                                   ])->first();
                                   if(!$data_exists) {
                                    DB::connection('mysql')->table('garages')->insert(get_object_vars($backup_data));
                                   }
                                }



                                    foreach(DB::connection('backup_database')->table('garage_automobile_models')->get() as $backup_data){
                                        $data_exists = DB::connection('mysql')->table('garage_automobile_models')->where([
                                            "id" => $backup_data->id
                                           ])->first();
                                           if(!$data_exists) {
                                            DB::connection('mysql')->table('garage_automobile_models')->insert(get_object_vars($backup_data));
                                           }
                                        }

                                        foreach(DB::connection('backup_database')->table('garage_services')->get() as $backup_data){
                                            $data_exists = DB::connection('mysql')->table('garage_services')->where([
                                                "id" => $backup_data->id
                                               ])->first();
                                               if(!$data_exists) {
                                                DB::connection('mysql')->table('garage_services')->insert(get_object_vars($backup_data));
                                               }
                                            }

                                            foreach(DB::connection('backup_database')->table('garage_sub_services')->get() as $backup_data){
                                                $data_exists = DB::connection('mysql')->table('garage_sub_services')->where([
                                                    "id" => $backup_data->id
                                                   ])->first();
                                                   if(!$data_exists) {
                                                    DB::connection('mysql')->table('garage_sub_services')->insert(get_object_vars($backup_data));
                                                   }
                                                }


                                                return response()->json("done",200);
    }
  
}
