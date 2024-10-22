<?php

namespace App\Http\Controllers;

use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Booking;
use App\Models\Garage;
use App\Models\GuestUser;

use App\Models\Question;
use App\Models\QusetionStar;
use App\Models\ReviewNew;
use App\Models\ReviewValueNew;
use App\Models\Star;
use App\Models\StarTag;
use App\Models\Tag;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    use ErrorUtil, UserActivityUtil;






    /**
     *
     * @OA\Post(
     *      path="/review-new/create/questions",
     *      operationId="storeQuestion",
     *      tags={"review.setting.question"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store question",
     *      description="This method is to store question",
     *
     *  @OA\RequestBody(
     *  * description="supported value is of type is 'star','emoji','numbers','heart'",
     *         required=true,
     *         @OA\JsonContent(
     *            required={"question","is_active"},
     *            @OA\Property(property="question", type="string", format="string",example="How was this?"),
     *  @OA\Property(property="garage_id", type="number", format="number",example="1"),
     * *  @OA\Property(property="is_active", type="boolean", format="boolean",example="1"),
     * * *  @OA\Property(property="type", type="string", format="string",example="star"),
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


    public function storeQuestion(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $question = [
                'garage_id' => $request->garage_id,
                'question' => $request->question,
                'is_active' => $request->is_active,
                'type' => !empty($request->type) ? $request->type : "star",
                "is_default" => false
            ];
            if ($request->user()->hasRole("superadmin")) {
                $question["is_default"] = true;
                $question["garage_id"] = NULL;
            } else {

                $garage =    Garage::where(["id" => $request->garage_id, "owner_id" => $request->user()->id])->first();

                if (!$garage) {
                    return response()->json(["message" => "No garage Found"], 400);
                }
                if ($garage->enable_question == true) {
                    return response()->json(["message" => "question is enabled"], 400);
                }
            }







            $createdQuestion =    Question::create($question);
            $createdQuestion->info = "supported value is of type is 'star','emoji','numbers','heart'";

            return response($createdQuestion, 201);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/review-new/update/questions",
     *      operationId="updateQuestion",
     *      tags={"review.setting.question"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update question",
     *      description="This method is to update question",
     *
     *  @OA\RequestBody(
     * description="supported value is of type is 'star','emoji','numbers','heart'",
     *         required=true,
     *         @OA\JsonContent(
     *            required={"question","is_active","id"},
     *  @OA\Property(property="id", type="number", format="number",example="1"),
     *            @OA\Property(property="question", type="string", format="string",example="was it good?"),
     *  *            @OA\Property(property="type", type="string", format="string",example="star"),
     *

     *   @OA\Property(property="is_active", type="boolean", format="boolean",example="1"),
     *
     *
     *         ),
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

    public function updateQuestion(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $question = [
                'type' => $request->type,
                'question' => $request->question,
                "is_active" => $request->is_active,
            ];


            $checkQuestion =    Question::where(["id" => $request->id])->first();
            if ($checkQuestion->is_default == true && !$request->user()->hasRole("superadmin")) {
                return response()->json(["message" => "you can not update the question. you are not a super admin"]);
            }
            $updatedQuestion =    tap(Question::where(["id" => $request->id]))->update(
                $question
            )
                // ->with("somthing")

                ->first();
            $updatedQuestion->info = "supported value is of type is 'star','emoji','numbers','heart'";
            return response($updatedQuestion, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/review-new/update/active_state/questions",
     *      operationId="updateQuestionActiveState",
     *      tags={"review.setting.question"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update question's active state",
     *      description="This method is to update question's active state",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"is_active","id"},
     *  @OA\Property(property="id", type="number", format="number",example="1"),
     *   @OA\Property(property="is_active", type="boolean", format="boolean",example="1"),
     *
     *
     *         ),
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
    public function updateQuestionActiveState(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $question = [
                "is_active" => $request->is_active,
            ];
            $checkQuestion =    Question::where(["id" => $request->id])->first();
            if ($checkQuestion->is_default == true && !$request->user()->hasRole("superadmin")) {
                return response()->json(["message" => "you can not update the question. you are not a super admin"]);
            }
            $updatedQuestion =    tap(Question::where(["id" => $request->id]))->update(
                $question
            )
                // ->with("somthing")

                ->first();
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/review-new/get/questions",
     *      operationId="getQuestion",
     *      tags={"review.setting.question"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to get question",
     *      description="This method is to get question",
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
    public function   getQuestion(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $is_dafault = false;
            $garageId = !empty($request->garage_id) ? $request->garage_id : NULL;
            if ($request->user()->hasRole("superadmin")) {

                $is_dafault = true;
                $garageId = NULL;
            } else {
                $garage =    Garage::where(["id" => $request->garage_id])->first();
                if (!$garage && !$request->user()->hasRole("superadmin")) {
                    return response("no garage found", 404);
                }
                // if ($garage->enable_question == true) {
                //     $is_dafault = true;

                // }
            }


            $query =  Question::where(["garage_id" => $garageId, "is_default" => $is_dafault]);


            $questions =  $query->get();


            return response($questions, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }

















    /**
     *
     * @OA\Get(
     *      path="/review-new/get/questions-all",
     *      operationId="getQuestionAll",
     *      tags={"review.setting.question"},

     *      summary="This method is to get all question without pagination",
     *      description="This method is to get all question without pagination",
     *       security={
     *           {"bearerAuth": {}}
     *       },
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
    public function   getQuestionAll(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $is_dafault = false;
            if ($request->user()->hasRole("superadmin")) {

                $is_dafault = true;
            } else {
                $garage =    Garage::where(["id" => $request->garage_id])->first();
                if (!$garage && !$request->user()->hasRole("superadmin")) {
                    return response("No Business Found", 404);
                }
                // if ($garage->enable_question == true) {
                //     $is_dafault = true;

                // }
            }


            $query =  Question::where(["garage_id" => $request->garage_id, "is_default" => $is_dafault]);


            $questions =  $query->get();

            $data =  json_decode(json_encode($questions), true);
            foreach ($questions as $key1 => $question) {

                foreach ($question->question_stars as $key2 => $questionStar) {
                    $data[$key1]["stars"][$key2] = json_decode(json_encode($questionStar->star), true);


                    $data[$key1]["stars"][$key2]["tags"] = [];
                    foreach ($questionStar->star->star_tags as $key3 => $starTag) {
                        if ($starTag->question_id == $question->id) {

                            array_push($data[$key1]["stars"][$key2]["tags"], json_decode(json_encode($starTag->tag), true));
                        }
                    }
                }
            }
            return response($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/review-new/get/questions-all-report",
     *      operationId="getQuestionAllReport",
     *      tags={"review.setting.question"},

     *      summary="This method is to get all question report",
     *      description="This method is to get all question report",
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *         @OA\Parameter(
     *         name="garage_id",
     *         in="query",
     *         description="garage Id",
     *         required=false,
     *      ),
     *    @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="start_date",
     *         required=false,
     * * example="2023-06-29"
     *      ),
     *    @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="end_date",
     *         required=false,
     * * example="2023-06-29"
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

    public function getQuestionAllReport(Request $request)
    {

        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $garage =    Garage::where(["id" => $request->garage_id])->first();
            if (!$garage) {
                return response("no business found", 404);
            }

            $query =  Question::where(["garage_id" => $request->garage_id, "is_default" => false]);

            $questions =  $query->get();

            $questionsCount = $query->get()->count();

            $data =  json_decode(json_encode($questions), true);
            foreach ($questions as $key1 => $question) {

                $tags_rating = [];
                $starCountTotal = 0;
                $starCountTotalTimes = 0;
                foreach ($question->question_stars as $key2 => $questionStar) {


                    $data[$key1]["stars"][$key2] = json_decode(json_encode($questionStar->star), true);

                    $data[$key1]["stars"][$key2]["stars_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                        ->where(
                            [
                                "review_news.garage_id" => $garage->id,
                                "question_id" => $question->id,
                                "star_id" => $questionStar->star->id,
                                // "review_news.guest_id" => NULL

                            ]
                        );
                    if (!empty($request->start_date) && !empty($request->end_date)) {

                        $data[$key1]["stars"][$key2]["stars_count"] = $data[$key1]["stars"][$key2]["stars_count"]->whereBetween('review_news.created_at', [
                            $request->start_date,
                            $request->end_date
                        ]);
                    }
                    $data[$key1]["stars"][$key2]["stars_count"] = $data[$key1]["stars"][$key2]["stars_count"]->get()
                        ->count();

                    $starCountTotal += $data[$key1]["stars"][$key2]["stars_count"] * $questionStar->star->value;

                    $starCountTotalTimes += $data[$key1]["stars"][$key2]["stars_count"];
                    $data[$key1]["stars"][$key2]["tag_ratings"] = [];
                    if ($starCountTotalTimes > 0) {
                        $data[$key1]["rating"] = $starCountTotal / $starCountTotalTimes;
                    }


                    foreach ($questionStar->star->star_tags as $key3 => $starTag) {


                        if ($starTag->question_id == $question->id) {

                            $starTag->tag->count =  ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                                ->where(
                                    [
                                        "review_news.garage_id" => $garage->id,
                                        "question_id" => $question->id,
                                        "tag_id" => $starTag->tag->id,
                                        // "review_news.guest_id" => NULL
                                    ]
                                );
                            if (!empty($request->start_date) && !empty($request->end_date)) {

                                $starTag->tag->count = $starTag->tag->count->whereBetween('review_news.created_at', [
                                    $request->start_date,
                                    $request->end_date
                                ]);
                            }

                            $starTag->tag->count = $starTag->tag->count->get()->count();
                            if ($starTag->tag->count > 0) {
                                array_push($tags_rating, json_decode(json_encode($starTag->tag)));
                            }


                            $starTag->tag->total =  ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                                ->where(
                                    [
                                        "review_news.garage_id" => $garage->id,
                                        "question_id" => $question->id,
                                        "star_id" => $questionStar->star->id,
                                        "tag_id" => $starTag->tag->id,
                                        // "review_news.guest_id" => NULL
                                    ]
                                );
                            if (!empty($request->start_date) && !empty($request->end_date)) {

                                $starTag->tag->total = $starTag->tag->total->whereBetween('review_news.created_at', [
                                    $request->start_date,
                                    $request->end_date
                                ]);
                            }
                            $starTag->tag->total = $starTag->tag->total->get()->count();

                            if ($starTag->tag->total > 0) {
                                unset($starTag->tag->count);
                                array_push($data[$key1]["stars"][$key2]["tag_ratings"], json_decode(json_encode($starTag->tag)));
                            }
                        }
                    }
                }


                $data[$key1]["tags_rating"] = array_values(collect($tags_rating)->unique()->toArray());
            }





            $totalCount = 0;
            $ttotalRating = 0;

            foreach (Star::get() as $star) {

                $data2["star_" . $star->value . "_selected_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                    ->where([
                        "review_news.garage_id" => $garage->id,
                        "star_id" => $star->id,
                        // "review_news.guest_id" => NULL
                    ])
                    ->distinct("review_value_news.review_id", "review_value_news.question_id");
                if (!empty($request->start_date) && !empty($request->end_date)) {

                    $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->whereBetween('review_news.created_at', [
                        $request->start_date,
                        $request->end_date
                    ]);
                }
                $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->count();

                $totalCount += $data2["star_" . $star->value . "_selected_count"] * $star->value;

                $ttotalRating += $data2["star_" . $star->value . "_selected_count"];
            }
            if ($totalCount > 0) {
                $data2["total_rating"] = $totalCount / $ttotalRating;
            } else {
                $data2["total_rating"] = 0;
            }

            $data2["total_comment"] = ReviewNew::with("user")->where([
                "garage_id" => $garage->id,
                // "guest_id" => NULL,
            ])
                ->whereNotNull("comment");
            if (!empty($request->start_date) && !empty($request->end_date)) {

                $data2["total_comment"] = $data2["total_comment"]->whereBetween('review_news.created_at', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
            $data2["total_comment"] = $data2["total_comment"]->get();

            return response([
                "part1" =>  $data2,
                "part2" =>  $data
            ], 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/review-new/get/questions-all-report-by-user/{perPage}",
     *      operationId="getQuestionAllReportByUser",
     *      tags={"review"},

     *      summary="This method is to get all question report by user",
     *      description="This method is to get all question report by user",
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *  *         @OA\Parameter(
     *         name="perPage",
     *         in="path",
     *         description="perPage Id",
     *         required=true,
     *      ),
     *         @OA\Parameter(
     *         name="garage_id",
     *         in="query",
     *         description="garage Id",
     *         required=false,
     *      ),
     *    @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="start_date",
     *         required=false,
     * * example="2023-06-29"
     *      ),
     *    @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="end_date",
     *         required=false,
     * * example="2023-06-29"
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

    public function getQuestionAllReportByUser($perPage, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $garage =    Garage::where(["id" => $request->garage_id])->first();
            if (!$garage) {
                return response("No Business Found", 404);
            }
            $usersQuery = User::leftjoin('review_news', 'users.id', '=', 'review_news.user_id')
                ->leftjoin('review_value_news', 'review_news.id', '=', 'review_value_news.review_id')
                ->leftjoin('questions', 'review_value_news.question_id', '=', 'questions.id')

                ->where([
                    "review_news.garage_id" => $garage->id
                ])
                ->havingRaw('COUNT(review_news.id) > 0')
                ->havingRaw('COUNT(review_value_news.question_id) > 0')
                ->havingRaw('COUNT(questions.id) > 0')
                ->groupBy("users.id")
                ->select("users.*", "review_news.created_at as review_created_at");
            if (!empty($request->start_date) && !empty($request->end_date)) {

                $usersQuery = $usersQuery->whereBetween('review_news.created_at', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
            $users = $usersQuery->paginate($perPage);

            for ($i = 0; $i < count($users->items()); $i++) {
                $query =  Question::leftjoin('review_value_news', 'questions.id', '=', 'review_value_news.question_id')
                    ->leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                    ->where([
                        "questions.garage_id" => $request->garage_id,
                        "questions.is_default" => false,
                        "review_news.user_id" => $users->items()[$i]->id
                    ])
                    ->groupBy("questions.id")
                    ->select("questions.*");

                $questions =  $query->get();



                $data =  json_decode(json_encode($questions), true);
                foreach ($questions as $key1 => $question) {

                    $tags_rating = [];
                    $starCountTotal = 0;
                    $starCountTotalTimes = 0;
                    foreach ($question->question_stars as $key2 => $questionStar) {


                        $data[$key1]["stars"][$key2] = json_decode(json_encode($questionStar->star), true);

                        $data[$key1]["stars"][$key2]["stars_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                            ->where(
                                [
                                    "review_news.garage_id" => $garage->id,
                                    "question_id" => $question->id,
                                    "star_id" => $questionStar->star->id,
                                    // "review_news.guest_id" => NULL,
                                    "review_news.user_id" => $users->items()[$i]->id
                                ]
                            );
                        if (!empty($request->start_date) && !empty($request->end_date)) {

                            $data[$key1]["stars"][$key2]["stars_count"] = $data[$key1]["stars"][$key2]["stars_count"]->whereBetween('review_news.created_at', [
                                $request->start_date,
                                $request->end_date
                            ]);
                        }
                        $data[$key1]["stars"][$key2]["stars_count"] = $data[$key1]["stars"][$key2]["stars_count"]->get()
                            ->count();

                        $starCountTotal += $data[$key1]["stars"][$key2]["stars_count"] * $questionStar->star->value;

                        $starCountTotalTimes += $data[$key1]["stars"][$key2]["stars_count"];
                        $data[$key1]["stars"][$key2]["tag_ratings"] = [];
                        if ($starCountTotalTimes > 0) {
                            $data[$key1]["rating"] = $starCountTotal / $starCountTotalTimes;
                        }


                        foreach ($questionStar->star->star_tags as $key3 => $starTag) {


                            if ($starTag->question_id == $question->id) {

                                $starTag->tag->count =  ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                                    ->where(
                                        [
                                            "review_news.garage_id" => $garage->id,
                                            "question_id" => $question->id,
                                            "tag_id" => $starTag->tag->id,
                                            // "review_news.guest_id" => NULL,
                                            "review_news.user_id" => $users->items()[$i]->id
                                        ]
                                    );
                                if (!empty($request->start_date) && !empty($request->end_date)) {

                                    $starTag->tag->count = $starTag->tag->count->whereBetween('review_news.created_at', [
                                        $request->start_date,
                                        $request->end_date
                                    ]);
                                }

                                $starTag->tag->count = $starTag->tag->count->get()->count();
                                if ($starTag->tag->count > 0) {
                                    array_push($tags_rating, json_decode(json_encode($starTag->tag)));
                                }


                                $starTag->tag->total =  ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                                    ->where(
                                        [
                                            "review_news.garage_id" => $garage->id,
                                            "question_id" => $question->id,
                                            "star_id" => $questionStar->star->id,
                                            "tag_id" => $starTag->tag->id,
                                            // "review_news.guest_id" => NULL,
                                            "review_news.user_id" => $users->items()[$i]->id
                                        ]
                                    );
                                if (!empty($request->start_date) && !empty($request->end_date)) {

                                    $starTag->tag->total = $starTag->tag->total->whereBetween('review_news.created_at', [
                                        $request->start_date,
                                        $request->end_date
                                    ]);
                                }
                                $starTag->tag->total = $starTag->tag->total->get()->count();

                                if ($starTag->tag->total > 0) {
                                    unset($starTag->tag->count);
                                    array_push($data[$key1]["stars"][$key2]["tag_ratings"], json_decode(json_encode($starTag->tag)));
                                }
                            }
                        }
                    }


                    $data[$key1]["tags_rating"] = array_values(collect($tags_rating)->unique()->toArray());
                }





                $totalCount = 0;
                $ttotalRating = 0;

                foreach (Star::get() as $star) {

                    $data2["star_" . $star->value . "_selected_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                        ->where([
                            "review_news.garage_id" => $garage->id,
                            "star_id" => $star->id,
                            // "review_news.guest_id" => NULL,
                            "review_news.user_id" => $users->items()[$i]->id
                        ])
                        ->distinct("review_value_news.review_id", "review_value_news.question_id");
                    if (!empty($request->start_date) && !empty($request->end_date)) {

                        $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->whereBetween('review_news.created_at', [
                            $request->start_date,
                            $request->end_date
                        ]);
                    }
                    $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->count();

                    $totalCount += $data2["star_" . $star->value . "_selected_count"] * $star->value;

                    $ttotalRating += $data2["star_" . $star->value . "_selected_count"];
                }
                if ($totalCount > 0) {
                    $data2["total_rating"] = $totalCount / $ttotalRating;
                } else {
                    $data2["total_rating"] = 0;
                }

                $data2["total_comment"] = ReviewNew::with("user")->where([
                    "garage_id" => $garage->id,
                    // "guest_id" => NULL,
                    "review_news.user_id" => $users->items()[$i]->id
                ])
                    ->whereNotNull("comment");
                if (!empty($request->start_date) && !empty($request->end_date)) {

                    $data2["total_comment"] = $data2["total_comment"]->whereBetween('review_news.created_at', [
                        $request->start_date,
                        $request->end_date
                    ]);
                }
                $data2["total_comment"] = $data2["total_comment"]->get();

                $users->items()[$i]["review_info"] = [
                    "part1" =>  $data2,
                    "part2" =>  $data
                ];
            }
            return response()->json($users, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/review-new/get/questions/{id}",
     *      operationId="getQuestionById",
     *      tags={"review.setting.question"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to get question by id",
     *      description="This method is to get question by id",
     *
     *         @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="question Id",
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

    public function   getQuestionById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $questions =    Question::where(["id" => $id])
                ->first();


            if (!$questions) {
                return response([
                    "message" => "No question found"
                ], 404);
            }
            $data =  json_decode(json_encode($questions), true);

            foreach ($questions->question_stars as $key2 => $questionStar) {
                $data["stars"][$key2] = json_decode(json_encode($questionStar->star), true);


                $data["stars"][$key2]["tags"] = [];
                foreach ($questionStar->star->star_tags as $key3 => $starTag) {

                    if ($starTag->question_id == $questions->id) {

                        array_push($data["stars"][$key2]["tags"], json_decode(json_encode($starTag->tag), true));
                    }
                }
            }
            return response($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }











    /**
     *
     * @OA\Delete(
     *      path="/review-new/delete/questions/{id}",
     *      operationId="deleteQuestionById",
     *      tags={"review.setting.question"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to delete question by id",
     *      description="This method is to delete question by id",
     *        @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="question Id",
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
    public function   deleteQuestionById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $questions =    Question::where(["id" => $id])
                ->delete();

            return response(["message" => "ok"], 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }





    /**
     *
     * @OA\Get(
     *      path="/review-new/get/questions-all-report/quantum",
     *      operationId="getQuestionAllReportQuantum",
     *      tags={"review.setting.question"},
     *   *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to get all question report.  and only garage owner of the garage can view this ",
     *      description="This method is to get all question report. and only garage owner of the garage can view this ",

     *         @OA\Parameter(
     *         name="garage_id",
     *         in="query",
     *         description="garage Id",
     *         required=false,
     *      ),
     *  *         @OA\Parameter(
     *         name="quantum",
     *         in="query",
     *         description="quantum",
     *         required=false,
     *      ),
     *  *         @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="period",
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

    public function getQuestionAllReportQuantum(Request $request)
    {

        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $garage =    Garage::where([
                "id" => $request->garage_id,
                "owner_id" => $request->user()->id
            ])->first();
            if (!$garage) {
                return response("no garage found", 404);
            }
            $data = [];

            $period = 0;
            for ($i = 0; $i < $request->quantum; $i++) {
                $totalCount = 0;
                $ttotalRating = 0;

                foreach (Star::get() as $star) {

                    $data2["star_" . $star->value . "_selected_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                        ->where([
                            "review_news.garage_id" => $garage->id,
                            "star_id" => $star->id,

                        ])
                        ->whereBetween(
                            'review_news.created_at',
                            [now()->subDays(($request->period + $period))->startOfDay(), now()->subDays($period)->endOfDay()]
                        )
                        ->distinct("review_value_news.review_id", "review_value_news.question_id")
                        ->count();

                    $totalCount += $data2["star_" . $star->value . "_selected_count"] * $star->value;

                    $ttotalRating += $data2["star_" . $star->value . "_selected_count"];
                }
                if ($totalCount > 0) {
                    $data2["total_rating"] = $totalCount / $ttotalRating;
                } else {
                    $data2["total_rating"] = 0;
                }

                // $data2["total_comment"] = ReviewNew::where([
                //     "garage_id" => $garage->id,
                //     "user_id" => NULL,
                // ])
                // ->whereNotNull("comment")
                // ->count();
                array_push($data, $data2);
                $period +=  $request->period + $period;
            }







            return response([
                "data" =>  $data,

            ], 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Post(
     *      path="/review-new/create/tags",
     *      operationId="storeTag",
     *      tags={"review.setting.tag"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store tag",
     *      description="This method is to store tag",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"tag","garage_id"},
     *            @OA\Property(property="tag", type="string", format="string",example="How was this?"),
     *  @OA\Property(property="garage_id", type="number", format="number",example="1"),

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
    public function storeTag(Request $request)
    {
        $this->storeActivity($request, "");
        if (!$request->user()->hasPermissionTo('questions_create')) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }
        $question = [
            'tag' => $request->tag,
            'garage_id' => $request->garage_id
        ];
        if ($request->user()->hasRole("superadmin")) {
            $question["is_default"] = true;
        } else {
            $garage =    Garage::where(["id" => $request->garage_id])->first();
            if (!$garage) {
                return response()->json(["message" => "No Business Found"]);
            }
            if ($garage->enable_question == true) {
                return response()->json(["message" => "question is enabled"]);
            }
        }



        $createdQuestion =    Tag::create($question);


        return response($createdQuestion, 201);
    }


    /**
     *
     * @OA\Post(
     *      path="/review-new/create/tags/multiple/by/admin",
     *      operationId="storeTagMultipleByAdmin",
     *      tags={"review.setting.tag"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store tag",
     *      description="This method is to store tag",

     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"tags"},
     *  @OA\Property(property="tags", type="string", format="array",example={
     * "tag1","tag2"
     * }
     *
     * ),
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
    public function storeTagMultipleByAdmin(Request $request)
    {



        try {
            $this->storeActivity($request, "");



            if (!$request->user()->hasPermissionTo('questions_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $dataArray = [];
            $duplicate_indexes_array = [];

            $uniqueTags = collect($request->tags)->unique()->values()->all();





            foreach ($uniqueTags as $index => $tag) {
                $question = [
                    'tag' => $tag,
                    'garage_id' => NULL
                ];


                if ($request->user()->hasRole("superadmin")) {


                    $tag_found =  Tag::where([
                        "garage_id" => NULL,
                        "tag" => $question["tag"],
                        "is_default" => 1
                    ])
                        ->first();

                    if ($tag_found) {

                        array_push($duplicate_indexes_array, $index);
                    }
                }
            }



            if (count($duplicate_indexes_array)) {

                return response([
                    "message" => "duplicate data",
                    "duplicate_indexes_array" => $duplicate_indexes_array
                ], 409);
            } else {

                foreach ($uniqueTags as $index => $tag) {
                    $question = [
                        'tag' => $tag,
                        'garage_id' => NULL
                    ];


                    if ($request->user()->hasRole("superadmin")) {
                        $question["is_default"] = true;
                        $garage_id = NULL;
                        $question["garage_id"] = NULL;
                    }

                    if (!count($duplicate_indexes_array)) {
                        $finalTag =  Tag::create($question);
                        array_push($dataArray, $finalTag);
                    } else {
                        return response()->json($duplicate_indexes_array, 200);
                    }
                }
            }





            return response(["message" => "data inserted", "data" => $dataArray], 201);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/review-new/create/tags/multiple/{garage_id}",
     *      operationId="storeTagMultiple",
     *      tags={"review.setting.tag"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store tag",
     *      description="This method is to store tag",
     *  @OA\Parameter(
     * name="garage_id",
     * in="path",
     * description="garage_id",
     * required=true,
     * example="1"
     * ),
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"tags"},
     *  @OA\Property(property="tags", type="string", format="array",example={
     * "tag1","tag2"
     * }
     *
     * ),
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
    public function storeTagMultiple($garage_id, Request $request)
    {



        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $dataArray = [];
            $duplicate_indexes_array = [];

            $uniqueTags = collect($request->tags)->unique()->values()->all();





            foreach ($uniqueTags as $index => $tag) {
                $question = [
                    'tag' => $tag,
                    'garage_id' => $garage_id
                ];


                if ($request->user()->hasRole("superadmin")) {


                    $tag_found =    Tag::where([
                        "garage_id" => NULL,
                        "tag" => $question["tag"],
                        "is_default" => 1
                    ])
                        ->first();

                    if ($tag_found) {

                        array_push($duplicate_indexes_array, $index);
                    }
                } else {
                    $tag_found =    Tag::where(["garage_id" => $garage_id, "is_default" => 0, "tag" => $question["tag"]])

                        ->first();

                    if ($tag_found) {

                        array_push($duplicate_indexes_array, $index);
                    } else {
                        $tag_found =    Tag::where(["garage_id" => NULL, "is_default" => 1, "tag" => $question["tag"]])
                            ->first();
                        if ($tag_found) {

                            array_push($duplicate_indexes_array, $index);
                        }
                    }
                }
            }



            if (count($duplicate_indexes_array)) {

                return response([
                    "message" => "duplicate data",
                    "duplicate_indexes_array" => $duplicate_indexes_array
                ], 409);
            } else {

                foreach ($uniqueTags as $index => $tag) {
                    $question = [
                        'tag' => $tag,
                        'garage_id' => $garage_id
                    ];


                    if ($request->user()->hasRole("superadmin")) {
                        $question["is_default"] = true;
                        $garage_id = NULL;
                        $question["garage_id"] = NULL;
                    } else {

                        $question["is_default"] = false;

                        $garage =    Garage::where(["id" => $garage_id])->first();
                        if (!$garage) {
                            return response()->json(["message" => "No Business Found"]);
                        }
                    }

                    if (!count($duplicate_indexes_array)) {
                        $finalTag =  Tag::create($question);
                        array_push($dataArray, $finalTag);
                    } else {
                        return response()->json($duplicate_indexes_array, 200);
                    }
                }
            }





            return response(["message" => "data inserted", "data" => $dataArray], 201);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Put(
     *      path="/review-new/update/tags",
     *      operationId="updateTag",
     *      tags={"review.setting.tag"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update tag",
     *      description="This method is to update tag",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"tag","id"},
     *            @OA\Property(property="tag", type="string", format="string",example="How was this?"),
     *  @OA\Property(property="id", type="number", format="number",example="1"),

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
    public function updateTag(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $tag = [
                'tag' => $request->tag
            ];

            // Check if the question exists
            $checkQuestion = Tag::where('id', $request->id)->first();

            if (!$checkQuestion) {
                return response()->json(['message' => 'tag not found.'], 404);
            }

            // Check if the user is allowed to update
            if ($checkQuestion->is_default == true && !$request->user()->hasRole('superadmin')) {
                return response()->json(['message' => 'You can not update the tag. You are not a super admin.'], 403);
            }

            // Update the question
            $updatedQuestion = tap($checkQuestion)->update($tag);

            // Return the updated question
            return response()->json($updatedQuestion);



            return response($updatedQuestion, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }





    /**
     *
     * @OA\Get(
     *      path="/review-new/get/tags",
     *      operationId="getTag",
     *      tags={"review.setting.tag"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to get tag",
     *      description="This method is to get tag",
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
    public function   getTag(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $is_dafault = false;
            $garageId = $request->garage_id;

            if ($request->user()->hasRole("superadmin")) {
                $is_dafault = true;
                $garageId = NULL;
                $query =  Tag::where(["garage_id" => NULL, "is_default" => true]);
            } else {
                $garage =    Garage::where(["id" => $request->garage_id])->first();
                if (!$garage && !$request->user()->hasRole("superadmin")) {
                    return response("No Business Found", 404);
                }
                // if ($garage->enable_question == true) {
                //     $is_dafault = true;
                // }
                $query =  Tag::where(["garage_id" => $garageId, "is_default" => 0])
                    ->orWhere(["garage_id" => NULL, "is_default" => 1]);
            }



            $questions =  $query->get();


            return response($questions, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/review-new/get/tags/{id}",
     *      operationId="getTagById",
     *      tags={"review.setting.tag"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to get tag  by id",
     *      description="This method is to get tag  by id",
     *         @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="tag Id",
     *         required=false,
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
    public function   getTagById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $questions =    Tag::where(["id" => $id])
                ->first();
            if (!$questions) {
                return response([
                    "message" => "No Tag Found"
                ], 404);
            }
            return response($questions, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Delete(
     *      path="/review-new/delete/tags/{id}",
     *      operationId="deleteTagById",
     *      tags={"review.setting.tag"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to delete tag  by id",
     *      description="This method is to delete tag  by id",
     *         @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="tag Id",
     *         required=false,
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
    public function   deleteTagById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $tag =    Tag::where(["id" => $id])
                ->first();
            $tagId = $tag->id;

            if ($request->user()->hasRole("superadmin") &&  $tag->is_default == 1) {
                StarTag::where(["tag_id" => $tagId])->delete();
                $tag->delete();
                ReviewValueNew::where([
                    'tag_id' => $tagId
                ])
                    ->delete();
            } else  if (!$request->user()->hasRole("superadmin") &&  $tag->is_default == 0) {
                StarTag::where(["tag_id" => $tagId])->delete();
                $tag->delete();
                ReviewValueNew::where([
                    'tag_id' => $tagId
                ])
                    ->delete();
            }
            return response(["message" => "ok"], 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/review-new/owner/create/questions",
     *      operationId="storeOwnerQuestion",
     *      tags={"review.setting.link"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store question",
     *      description="This method is to store question.",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"question_id","stars"},

     *  @OA\Property(property="question_id", type="number", format="number",example="1"),
     *  @OA\Property(property="stars", type="string", format="array",example={
     *
     * { "star_id":"2",
     *
     * "tags":{
     * {"tag_id":"2"},
     * {"tag_id":"2"}
     * }
     *
     * }
     *
     *
     * }
     *
     * ),
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

    public function storeOwnerQuestion(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            return DB::transaction(function () use ($request) {
                $question_id = $request->question_id;
                foreach ($request->stars as $requestStar) {


                    QusetionStar::create([
                        "question_id" => $question_id,
                        "star_id" => $requestStar["star_id"]
                    ]);


                    foreach ($requestStar["tags"] as $tag) {


                        StarTag::create([
                            "question_id" => $question_id,
                            "tag_id" => $tag["tag_id"],
                            "star_id" => $requestStar["star_id"]
                        ]);
                    }
                }

                return response(["message" => "ok"], 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/review-new/owner/update/questions",
     *      operationId="updateOwnerQuestion",
     *      tags={"review.setting.link"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update question",
     *      description="This method is to update question",
     *             @OA\Parameter(
     *         name="_method",
     *         in="query",
     *         description="method",
     *         required=false,
     * example="PATCH"
     *      ),
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"question_id","stars"},

     *  @OA\Property(property="question_id", type="number", format="number",example="1"),
     *  @OA\Property(property="stars", type="string", format="array",example={
     *  {* "star_id":"2",
     * "tags":{
     * {"tag_id":"2"},
     * {"tag_id":"2"}
     *
     * }
     *
     * }
     * }
     *
     * ),
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

    public function updateOwnerQuestion(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('questions_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            return DB::transaction(function () use ($request) {
                $question_id = $request->question_id;

                $starIds = collect($request->stars)->pluck('star_id')->toArray();


                QusetionStar::where([
                    'question_id' => $question_id,
                ])
                    ->whereNotIn('star_id', $starIds)
                    ->delete();



                foreach ($request->stars as $requestStar) {

                    if (!(QusetionStar::where([
                        "question_id" => $question_id,
                        "star_id" => $requestStar["star_id"]
                    ])->exists())) {
                        QusetionStar::create([
                            "question_id" => $question_id,
                            "star_id" => $requestStar["star_id"]
                        ]);
                    }

                    $starTagIds = collect($requestStar["tags"])->pluck('tag_id')->toArray();

                    StarTag::where([
                        "question_id"  => $question_id,
                        "star_id" => $requestStar["star_id"]
                    ])
                        ->whereNotIn('tag_id', $starTagIds)
                        ->delete();

                    foreach ($requestStar["tags"] as $tag) {

                        if (!(StarTag::where([
                            "question_id" => $question_id,
                            "tag_id" => $tag["tag_id"],
                            "star_id" => $requestStar["star_id"]
                        ])->exists())) {
                            StarTag::create([
                                "question_id" => $question_id,
                                "tag_id" => $tag["tag_id"],
                                "star_id" => $requestStar["star_id"]
                            ]);
                        }
                    }
                }

                return response(["message" => "ok"], 201);
            });
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }







    /**
     *
     * @OA\Get(
     *      path="/review-new/getavg/review/{garageId}/{start}/{end}",
     *      operationId="getAverage",
     *      tags={"review"},
     *   *       security={
     *           {"bearerAuth": {}}
     *       },
     *  @OA\Parameter(
     * name="garageId",
     * in="path",
     * description="garageId",
     * required=true,
     * example="1"
     * ),
     *  @OA\Parameter(
     * name="start",
     * in="path",
     * description="from date",
     * required=true,
     * example="2019-06-29"
     * ),
     *  @OA\Parameter(
     * name="end",
     * in="path",
     * description="to date",
     * required=true,
     * example="2026-06-29"
     * ),
     *      summary="This method is to get average",
     *      description="This method is to get average",
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
    public function  getAverage($garage_id, $start, $end, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('review_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            // with
            $reviews = ReviewNew::where([
                "garage_id" => $garage_id
            ])
                ->whereBetween('created_at', [$start, $end])
                ->with("question")
                ->get();

            $data["total"]   = $reviews->count();
            $data["one"]   = 0;
            $data["two"]   = 0;
            $data["three"] = 0;
            $data["four"]  = 0;
            $data["five"]  = 0;
            foreach ($reviews as $review) {
                switch ($review->rate) {
                    case 1:
                        $data[$review->question->name]["one"] += 1;
                        break;
                    case 2:
                        $data["two"] += 1;
                        break;
                    case 3:
                        $data["three"] += 1;
                        break;
                    case 4:
                        $data["four"] += 1;
                        break;
                    case 5:
                        $data[$review->question->question]["five"] += 1;
                        break;
                }
            }


            return response($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/review-new/getreview/{garageId}/{rate}/{start}/{end}",
     *      operationId="filterReview",
     *      tags={"review"},
     *        security={
     *           {"bearerAuth": {}}
     *       },
     *  @OA\Parameter(
     * name="garageId",
     * in="path",
     * description="garageId",
     * required=true,
     * example="1"
     * ),
     *  @OA\Parameter(
     * name="rate",
     * in="path",
     * description="rate",
     * required=true,
     * example="1"
     * ),
     *  @OA\Parameter(
     * name="start",
     * in="path",
     * description="from date",
     * required=true,
     * example="2019-06-29"
     * ),
     *  @OA\Parameter(
     * name="end",
     * in="path",
     * description="to date",
     * required=true,
     * example="2026-06-29"
     * ),
     *      summary="This method is to filter   Review",
     *      description="This method is to filter   Review",
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


    public function  filterReview($garage_id, $rate, $start, $end, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('review_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            // with
            $reviewValues = ReviewNew::where([
                "garage_id" => $garage_id,
                "rate" => $rate
            ])
                ->with("garage", "value")
                ->whereBetween('created_at', [$start, $end])
                ->get();


            return response($reviewValues, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/review-new/getreviewAll/{garageId}",
     *      operationId="getReviewByGarageIdAll",
     *      tags={"review"},
     *        security={
     *           {"bearerAuth": {}}
     *       },
     *  @OA\Parameter(
     * name="garageId",
     * in="path",
     * description="garageId",
     * required=true,
     * example="1"
     * ),

     *      summary="This method is to get review by garage id",
     *      description="This method is to get review by garage id",
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

    public function  getReviewByGarageIdAll($garage_id, Request $request)
    {
        try {
            $this->storeActivity($request, "");

            // with
            $reviewValue = ReviewNew::with(
                "value.star",
                "value.tag",
                "value.question",
            )->where([
                "garage_id" => $garage_id,
            ])
                ->get();

            $info = [];
            $totalCount = 0;
            $totalRating = 0;

            foreach (Star::get() as $star) {

                $data2["star_" . $star->value . "_selected_count"] = ReviewValueNew::leftjoin('review_news', 'review_value_news.review_id', '=', 'review_news.id')
                    ->where([
                        "review_news.garage_id" => $garage_id,
                        "star_id" => $star->id,
                        // "review_news.guest_id" => NULL
                    ])
                    ->distinct("review_value_news.review_id", "review_value_news.question_id");
                if (!empty($request->start_date) && !empty($request->end_date)) {

                    $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->whereBetween('review_news.created_at', [
                        $request->start_date,
                        $request->end_date
                    ]);
                }
                $data2["star_" . $star->value . "_selected_count"] = $data2["star_" . $star->value . "_selected_count"]->count();

                $totalCount += $data2["star_" . $star->value . "_selected_count"] * $star->value;

                $totalRating += $data2["star_" . $star->value . "_selected_count"];
            }
            if ($totalCount > 0) {
                $data2["average_rating"] = $totalCount / $totalRating;
            } else {
                $data2["average_rating"] = 0;
            }

            $info["average_rating"]  = $data2["average_rating"];
            $info["total_rating_count"] = $totalCount;



            return response(["review" => $reviewValue, "info" => $info], 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/review-new/getreviewAll/{garageId}/{perPage}",
     *      operationId="getReviewByGarageId",
     *      tags={"review"},
     *        security={
     *           {"bearerAuth": {}}
     *       },
     *  @OA\Parameter(
     * name="garageId",
     * in="path",
     * description="garageId",
     * required=true,
     * example="1"
     * ),
     *  @OA\Parameter(
     * name="perPage",
     * in="path",
     * description="perPage",
     * required=true,
     * example="1"
     * ),
     *      summary="This method is to get review by garage id",
     *      description="This method is to get review by garage id",
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

    public function  getReviewByGarageId($garage_id, $perPage, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            //  if (!$request->user()->hasPermissionTo('review_view')) {
            //      return response()->json([
            //          "message" => "You can not perform this action"
            //      ], 401);
            //  }
            // with
            $reviewValue = ReviewNew::with(
                "value.star",
                "value.tag",
                "value.question",
            )->where([
                "garage_id" => $garage_id,
            ])
                ->paginate($perPage);


            return response($reviewValue, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/review-new/getcustomerreview/{garageId}/{start}/{end}",
     *      operationId="getCustommerReview",
     *      tags={"review"},
     *        security={
     *           {"bearerAuth": {}}
     *       },
     *  @OA\Parameter(
     * name="garageId",
     * in="path",
     * description="garageId",
     * required=true,
     * example="1"
     * ),

     *  @OA\Parameter(
     * name="start",
     * in="path",
     * description="from date",
     * required=true,
     * example="2019-06-29"
     * ),
     *  @OA\Parameter(
     * name="end",
     * in="path",
     * description="to date",
     * required=true,
     * example="2026-06-29"
     * ),
     *      summary="This method is to get customer review",
     *      description="This method is to get customer review",
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





    public function  getCustommerReview($garage_id, $start, $end, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('review_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            // with
            $data["reviews"] = ReviewNew::where([
                "garage_id" => $garage_id,
            ])
                ->whereBetween('created_at', [$start, $end])
                ->get();
            $data["total"]   = $data["reviews"]->count();
            $data["one"]   = 0;
            $data["two"]   = 0;
            $data["three"] = 0;
            $data["four"]  = 0;
            $data["five"]  = 0;
            foreach ($data["reviews"]  as $reviewValue) {
                switch ($reviewValue->rate) {
                    case 1:
                        $data["one"] += 1;
                        break;
                    case 2:
                        $data["two"] += 1;
                        break;
                    case 3:
                        $data["three"] += 1;
                        break;
                    case 4:
                        $data["four"] += 1;
                        break;
                    case 5:
                        $data["five"] += 1;
                        break;
                }
            }

            return response($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Post(
     *      path="/review-new/{bookingId}",
     *      operationId="storeReview",
     *      tags={"review"},
     *    *  @OA\Parameter(
     * name="bookingId",
     * in="path",
     * description="bookingId",
     * required=true,
     * example="1"
     * ),
     *
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store review",
     *      description="This method is to store review",
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"description","rate","comment","values"},
     *
     *             @OA\Property(property="description", type="string", format="string",example="test"),
     *            @OA\Property(property="rate", type="string", format="string",example="2.5"),
     *              @OA\Property(property="comment", type="string", format="string",example="not good"),
     *
     *
     *    *  @OA\Property(property="values", type="string", format="array",example={

     *  {"question_id":1,"tag_id":2,"star_id":1},
     *  {"question_id":2,"tag_id":1,"star_id":4},

     * }
     *
     * ),
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

    public function storeReview($bookingId,  Request $request)
    {
        $this->storeActivity($request, "");
        try {
            if (!$request->user()->hasPermissionTo('review_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $booking =   Booking::where([
                "id" => $bookingId,
                "customer_id" => $request->user()->id,
            ])
                ->first();
            if (empty($booking)) {
                return response()->json([
                    "message" => "Booking does not exist."
                ], 404);
            }

            if ($booking->status != "converted_to_job" || $booking->payment_status != "complete") {
                return response()->json([
                    "message" => "Booking is not completed"
                ], 404);
            }


            // Check if the review already exists
            $existingReview = ReviewNew::where('booking_id', $booking->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'message' => 'You have already reviewed this booking.',
                ], 400); // Bad Request status
            }

            // Prepare the review data
            $review = [
                'description' => $request["description"],
                'booking_id' => $booking->id,
                'garage_id' => $booking->garage_id,
                'rate' => $request["rate"],
                'user_id' => $request->user()->id,
                'comment' => $request["comment"],
                // 'question_id' => $singleReview["question_id"],
                // 'tag_id' => $request->tag_id,
                // 'star_id' => $request->star_id,
            ];

            // Create the new review
            $createdReview = ReviewNew::create($review);


            $rate = 0;
            $questionCount = 0;
            $previousQuestionId = NULL;
            foreach ($request["values"] as $value) {
                if (!$previousQuestionId) {
                    $previousQuestionId = $value["question_id"];
                    $rate += $value["star_id"];
                } else {

                    if ($value["question_id"] != $previousQuestionId) {
                        $rate += $value["star_id"];
                        $previousQuestionId = $value["question_id"];
                        $questionCount += 1;
                    }
                }

                $createdReview->rate =  $rate;
                $createdReview->save();
                $value["review_id"] = $createdReview->id;
                // $value["question_id"] = $createdReview->question_id;
                // $value["tag_id"] = $createdReview->tag_id;
                // $value["star_id"] = $createdReview->star_id;
                ReviewValueNew::create($value);
            }


            return response(["message" => "created successfully"], 201);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }
}
