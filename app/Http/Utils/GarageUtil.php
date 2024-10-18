<?php

namespace App\Http\Utils;

use App\Models\AutomobileCategory;
use App\Models\AutomobileMake;
use App\Models\AutomobileModel;
use App\Models\Garage;
use App\Models\GarageAutomobileMake;
use App\Models\GarageAutomobileModel;
use App\Models\GarageService;
use App\Models\GarageSubService;
use App\Models\Question;
use App\Models\QusetionStar;
use App\Models\Service;
use App\Models\StarTag;
use App\Models\SubService;
use Exception;

trait GarageUtil
{
    // this function do all the task and returns transaction id or -1
    public function createGarageServices($service_data, $garage_id,$auto_model=false)
    {
        foreach ($service_data as $services) {
            $automobile_category_db = AutomobileCategory::where([
                "id" => $services["automobile_category_id"]
            ])
                ->first();
            if (!$automobile_category_db) {
                return [
                    "type" => "services",
                    "success" => false,
                    "message" => "please provile valid automobile category id"
                ];

            }
            // @@@@@@@@@@@@@@@@@@@@@@@@@@@ services starts @@@@@@@@@@@@@@@@@@@@@@@@@@@@@
            foreach ($services["services"] as $service) {


                if ($service["checked"]) {
                    $service_db = Service::where([
                        "id" => $service["id"],
                        "automobile_category_id" => $automobile_category_db->id

                    ])
                        ->first();

                    if (!$service_db) {

                        return [
                            "type" => "services",
                            "success" => false,
                            "message" => "please provile valid service id"
                        ];
                    }
                    $garage_service =  GarageService::create([
                        "garage_id" => $garage_id,
                        "service_id" => $service_db->id,
                    ]);
                    foreach ($service["sub_services"] as $sub_service) {
                        if ($sub_service["checked"]) {
                            $sub_service_db = SubService::where([
                                "id" => $sub_service["id"],
                                "service_id" => $service_db->id
                            ])
                                ->first();
                            if (!$sub_service_db) {

                                return [
                                    "type" => "services",
                                    "success" => false,
                                    "message" => "please provile valid sub service id"
                                ];
                            }
                            $garage_sub_service =  GarageSubService::create([
                                "garage_service_id" => $garage_service->id,
                                "sub_service_id" => $sub_service_db->id,
                            ]);
                        }
                    }
                }
            }
            // @@@@@@@@@@@@@@@@@@@@@@@@@@@@ services ends @@@@@@@@@@@@@@@@@@@@@@@@@@@@
            // error_log(json_encode($service));

            // @@@@@@@@@@@@@@@@@@@@@@@@@@@ makes starts @@@@@@@@@@@@@@@@@@@@@@@@@@@@@
            foreach ($services["automobile_makes"] as $automobile_make) {


                if ($automobile_make["checked"]) {
                    $automobile_make_db = AutomobileMake::where([
                        "id" => $automobile_make["id"],
                        "automobile_category_id" => $automobile_category_db->id

                    ])
                        ->first();
                    if (!$automobile_make_db) {

                        return [
                            "type" => "automobile_makes",
                            "success" => false,
                            "message" => "please provile valid automobile make id: " . $automobile_make["id"]
                        ];
                    }
                    $garage_automobile_make =  GarageAutomobileMake::create([
                        "garage_id" => $garage_id,
                        "automobile_make_id" => $automobile_make_db->id,
                    ]);

                    if($auto_model){
                        foreach (AutomobileModel::where([
                          "automobile_make_id" => $automobile_make_db->id
                        ])->get()
                         as
                        $model) {


                                $garage_model =  GarageAutomobileModel::create([
                                    "garage_automobile_make_id" => $garage_automobile_make->id,
                                    "automobile_model_id" => $model->id,
                                ]);

                        }
                    }     else {
                        foreach ($automobile_make["models"] as $model) {
                            if ($model["checked"]) {
                                $automobile_model_db = AutomobileModel::where([
                                    "id" => $model["id"],
                                    "automobile_make_id" => $automobile_make_db->id
                                ])
                                    ->first();
                                if (!$automobile_model_db) {

                                    return [
                                        "type" => "automobile_makes",
                                        "success" => false,
                                        "message" => "please provile valid automobile model id"
                                    ];
                                }
                                $garage_model =  GarageAutomobileModel::create([
                                    "garage_automobile_make_id" => $garage_automobile_make->id,
                                    "automobile_model_id" => $automobile_model_db->id,
                                ]);
                            }
                        }
                    }

                }
            }
            // @@@@@@@@@@@@@@@@@@@@@@@@@@@@ makes ends @@@@@@@@@@@@@@@@@@@@@@@@@@@@


        }

        return [
            "success" => true
        ];
    }


    public function garageOwnerCheck($garage_id) {


        $garageQuery  = Garage::where(["id" => $garage_id])
        ->orWhere("id",auth()->user()->business_id);
        if(!auth()->user()->hasRole('superadmin')) {
            $garageQuery = $garageQuery->where(function ($query) {
                $query->where('created_by', auth()->user()->id)
                      ->orWhere('owner_id', auth()->user()->id);
            });
        }

        $garage =  $garageQuery->first();
        if (!$garage) {
            return false;
        }
        return $garage;
    }



    public function storeQuestion($garage_id) {
        $defaultQuestions = Question::where([
            "garage_id" => NULL,
            "is_default" => 1
          ])->get();

          foreach($defaultQuestions as $defaultQuestion) {
              $questionData = [
                  'question' => $defaultQuestion->question,
                  'garage_id' => $garage_id,
                  'is_active' => 0
              ];
           $question  = Question::create($questionData);




    //   $defaultQusetionStars =  QusetionStar::where([
    //         "question_id"=>$defaultQuestion->id,
    //              ])->get();

    //              foreach($defaultQusetionStars as $defaultQusetionStar) {
    //                 $questionStarData = [
    //                     "question_id"=>$question->id,
    //                     "star_id" => $defaultQusetionStar->star_id
    //                 ];
    //              $questionStar  = QusetionStar::create($questionStarData);


    //              $defaultStarTags =  StarTag::where([
    //                 "question_id"=>$defaultQuestion->id,
    //                 "star_id" => $defaultQusetionStar->star_id

    //                      ])->get();

    //                      foreach($defaultStarTags as $defaultStarTag) {
    //                         $starTagData = [
    //                             "question_id"=>$question->id,
    //                             "star_id" => $questionStar->star_id,
    //                             "tag_id"=>$defaultStarTag->tag_id,
    //                         ];
    //                      $starTag  = StarTag::create($starTagData);








    //                     }






    //             }








          }
    }

}
