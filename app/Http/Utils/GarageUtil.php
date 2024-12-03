<?php

namespace App\Http\Utils;

use App\Models\AutomobileCategory;
use App\Models\Garage;
use App\Models\GarageService;
use App\Models\Question;
use App\Models\QusetionStar;
use App\Models\Service;
use App\Models\StarTag;
use App\Models\SubService;
use Exception;

trait GarageUtil
{
    // this function do all the task and returns transaction id or -1



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
