<?php

namespace Database\Seeders;

use App\Models\AutomobileCategory;
use App\Models\AutomobileFuelType;
use App\Models\AutomobileMake;
use App\Models\AutomobileModel;
use App\Models\AutomobileModelVariant;
use App\Models\Service;
use App\Models\SubService;
use Illuminate\Database\Seeder;
use File;
use Illuminate\Support\Facades\DB;

class AutomobileCarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // AutomobileCategory::truncate();
        // AutomobileMake::truncate();
        // AutomobileModel::truncate();
        // AutomobileModelVariant::truncate();
        // AutomobileFuelType::truncate();
        // Service::truncate();
        // SubService::truncate();
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');



        // @@@@@
        // automobile category  create
        // @@@@@
        $automobile_category_car =  AutomobileCategory::create([
            "name" => "car"
        ]);

        // @@@@@
        // automobile make, model, model variant create
        // @@@@@
        $car_json = File::get("database/data/automobiles_car_data.json");
        $automobiles_car_data = json_decode($car_json);
        foreach ($automobiles_car_data as $dummy_make_key => $values) {

            foreach ($values as $make_key => $make_values) {

                $make =    AutomobileMake::create([
                    "name" => $make_key,
                    "automobile_category_id" =>  $automobile_category_car->id
                ]);

                error_log(json_encode(($make_key . ".............")));

                foreach ($make_values as $dummy_model_keys => $dummy_model_values) {
                    // error_log(json_encode($model_keys));
                    foreach ($dummy_model_values as $model_keys => $model_values) {
                        error_log(json_encode(("*****" . $model_keys)));

                        $model = AutomobileModel::create([
                            "name" => $model_keys,
                            "automobile_make_id" =>  $make->id
                        ]);

                        foreach ($model_values as $dummy_model_variant_keys => $dummy_model_variant_values) {
                            $model_variant = AutomobileModelVariant::create([
                                "name" => $dummy_model_variant_values,
                                "automobile_model_id" =>  $model->id
                            ]);
                            error_log(json_encode(($dummy_model_variant_values . "#######")));
                        }
                    }
                }
            }
        }




        // @@@@@
        // automobile service
        // @@@@@
        $service_json = File::get("database/data/car_service_data.json");
        $car_service_data = json_decode($service_json);


        foreach ($car_service_data as $car_service) {
            error_log(($car_service->service_name . "....."));

            $service = Service::create([
                "name" => $car_service->service_name,
                "automobile_category_id" =>  $automobile_category_car->id
            ]);

            foreach ($car_service->sub_services as $sub_service) {
                SubService::create([
                    "name" => $sub_service,
                    "service_id" =>  $service->id
                ]);
                error_log(("....." . $sub_service));
            }
        }
    }
}
