<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Job;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientJobController extends Controller
{
    use ErrorUtil,UserActivityUtil;
   /**
    *
     * @OA\Get(
     *      path="/v1.0/client/jobs/{perPage}",
     *      operationId="getJobsClient",
     *      tags={"client.job"},
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
     *      summary="This method is to get  jobs ",
     *      description="This method is to get jobs",
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

    public function getJobsClient($perPage,Request $request) {
        try{
            $this->storeActivity($request,"");
            $jobsQuery = Job::with(
                "garage",
                "customer",
                "job_sub_services.sub_service",
                "job_packages.garage_package",
                "job_payments"
                )
            ->where([
                "customer_id" => $request->user()->id
            ]);

            if(!empty($request->search_key)) {
                $jobsQuery = $jobsQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("car_registration_no", "like", "%" . $term . "%");
                });

            }

            if (!empty($request->start_date)) {
                $jobsQuery = $jobsQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $jobsQuery = $jobsQuery->where('created_at', "<=", $request->end_date);
            }
            if (!empty($request->status)) {
                $status   = $request->status;
                $jobsQuery = $jobsQuery->where('jobs.status', $status);
            }

            $jobs = $jobsQuery
            ->select("jobs.*",
            DB::raw('CASE WHEN(SELECT COUNT(questions.id)
            FROM
            questions

            WHERE jobs.garage_id = questions.garage_id
            AND questions.is_active = 1


            ) = 0 THEN 0  ELSE 1
            END AS is_question_available'),
            )

            ->orderByDesc("id")->paginate($perPage);

            return response()->json($jobs, 200);
        } catch(Exception $e){

            return $this->sendError($e,500,$request);
        }
    }




     /**
        *
     * @OA\Get(
     *      path="/v1.0/client/jobs/single/{id}",
     *      operationId="getJobByIdClient",
     *      tags={"client.job"},
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
     *      summary="This method is to get  job by id ",
     *      description="This method is to get job by id",
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

    public function getJobByIdClient($id,Request $request) {
        try{
            $this->storeActivity($request,"");
            $job = Job::with(
                     "garage",
                "customer",
                "job_sub_services.sub_service",
                "job_packages.garage_package",
                "job_payments"
            )
            ->where([
                "id" => $id,
                "customer_id" => $request->user()->id
            ])
            ->first();

            if(!$job){
                return response()->json([
            "message" => "job not found"
                ], 404);
            }


            return response()->json($job, 200);
        } catch(Exception $e){

            return $this->sendError($e,500,$request);
        }
    }

}
