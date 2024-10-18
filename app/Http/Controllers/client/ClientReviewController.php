<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Garage;
use App\Models\GuestUser;
use App\Models\Question;
use App\Models\ReviewNew;
use App\Models\ReviewValueNew;
use App\Models\Star;
use Exception;
use Illuminate\Http\Request;

class ClientReviewController extends Controller
{
    use ErrorUtil,UserActivityUtil;
     /**
        *
     * @OA\Get(
     *      path="/client/review-new/get/questions-all",
     *      operationId="getQuestionAllUnauthorized",
     *      tags={"client.review.setting.question"},

     *      summary="This method is to get all question without pagination",
     *      description="This method is to get all question without pagination",
     *
 *         @OA\Parameter(
     *         name="garage_id",
     *         in="query",
     *         description="garage Id",
     *         required=false,
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *           @OA\Response(
     *          response=201,
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
    public function   getQuestionAllUnauthorized(Request $request)
    {
        try{
            $this->storeActivity($request,"");



            $is_dafault = false;

            $garage =    Garage::where(["id" => $request->garage_id])->first();
            if(!$garage){
                return response("No Business Found", 404);
            }
            // if ($garage->enable_question == true) {
            //     $query =  Question::where(["is_default" => 1]);
            // }
            // else {
                $query =  Question::where(["garage_id" => $request->garage_id,"is_default" => 0]);
            // }





        $questions =  $query->get();

    $data =  json_decode(json_encode($questions), true);
    foreach($questions as $key1=>$question){

        foreach($question->question_stars as $key2=>$questionStar){
            $data[$key1]["stars"][$key2]= json_decode(json_encode($questionStar->star), true) ;


            $data[$key1]["stars"][$key2]["tags"] = [];
            foreach($questionStar->star->star_tags as $key3=>$starTag){
if($starTag->question_id == $question->id) {

    array_push($data[$key1]["stars"][$key2]["tags"],json_decode(json_encode($starTag->tag), true));


}



            }

        }

    }
    return response($data, 200);





        }catch(Exception $e) {
      return $this->sendError($e, 500,$request);
        }



    }

/**
        *
     * @OA\Get(
     *      path="/client/review-new/get/questions-all-report",
     *      operationId="getQuestionAllReportUnauthorized",
     *      tags={"client.review.setting.question"},

     *      summary="This method is to get all question report unauthorized",
     *      description="This method is to get all question report unauthorized",
 *         @OA\Parameter(
     *         name="garage_id",
     *         in="query",
     *         description="garage Id",
     *         required=false,
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *           @OA\Response(
     *          response=201,
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

    public function getQuestionAllReportUnauthorized(Request $request) {

        try{
            $this->storeActivity($request,"");
            $garage =    Garage::where(["id" => $request->garage_id])->first();
            if(!$garage){
                return response("No Business Found", 404);
            }

        $query =  Question::where(["garage_id" => $request->garage_id,"is_default" => false]);

        $questions =  $query->get();

        $questionsCount = $query->get()->count();

    $data =  json_decode(json_encode($questions), true);
    foreach($questions as $key1=>$question){

        $tags_rating = [];
       $starCountTotal = 0;
       $starCountTotalTimes = 0;
        foreach($question->question_stars as $key2=>$questionStar){


            $data[$key1]["stars"][$key2]= json_decode(json_encode($questionStar->star), true) ;

            $data[$key1]["stars"][$key2]["stars_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
            ->where([
                "review_news.garage_id" => $garage->id,
                "question_id" => $question->id,
                "star_id" => $questionStar->star->id,
                // "review_news.guest_id" => NULL

                ]
            )
            ->get()
            ->count();

            $starCountTotal += $data[$key1]["stars"][$key2]["stars_count"] * $questionStar->star->value;

            $starCountTotalTimes += $data[$key1]["stars"][$key2]["stars_count"];

            if($starCountTotalTimes > 0) {
                $data[$key1]["rating"] = $starCountTotal / $starCountTotalTimes;
            }




            foreach($questionStar->star->star_tags as $key3=>$starTag){





         if($starTag->question_id == $question->id) {



            $starTag->tag->count =  ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
            ->where([
                "review_news.garage_id" => $garage->id,
                "question_id" => $question->id,
                "tag_id" => $starTag->tag->id,
                // "review_news.guest_id" => NULL
                ]
            )->get()->count();





                if($starTag->tag->count > 0) {
     array_push($tags_rating,json_decode(json_encode($starTag->tag)));
                }











          }



            }

        }


        $data[$key1]["tags_rating"] = array_values(collect($tags_rating)->unique()->toArray());
    }





    $totalCount = 0;
    $ttotalRating = 0;

    foreach(Star::get() as $star) {

    $data2["star_" . $star->value . "_selected_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
    ->where([
        "review_news.garage_id" => $garage->id,
        "star_id" => $star->id,
        // "review_news.guest_id" => NULL
    ])
    ->distinct("review_value_news.review_id","review_value_news.question_id")
    ->count();

    $totalCount += $data2["star_" . $star->value . "_selected_count"] * $star->value;

    $ttotalRating += $data2["star_" . $star->value . "_selected_count"];

    }
    if($totalCount > 0) {
    $data2["total_rating"] = $totalCount / $ttotalRating;

    }
    else {
    $data2["total_rating"] = 0;

    }

    $data2["total_comment"] = ReviewNew::with("user","guest_user")->where([
    "garage_id" => $garage->id,
    // "guest_id" => NULL,
    ])
    ->whereNotNull("comment")
    ->get();

    return response([
        "part1" =>  $data2,
        // "part2" =>  $data
    ], 200);
        }catch(Exception $e) {
      return $this->sendError($e, 500,$request);
        }

}



















































































}
