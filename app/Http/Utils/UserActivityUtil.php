<?php

namespace App\Http\Utils;

use App\Models\ActivityLog;
use App\Models\ErrorLog;
use Exception;
use Illuminate\Http\Request;

trait UserActivityUtil
{
    // this function do all the task and returns transaction id or -1
    public function storeActivity(Request $request,$activity="")
    {

 $user = auth()->user();
$activityLog = [
    "api_url" => $request->fullUrl(),
    "user"=> !empty($user)?(json_encode($user)):"",
    "user_id"=> !empty($user)?$user->id:"",
    "activity"=> $activity,
    "ip_address" =>  $request->header('X-Forwarded-For'),
    "request_method"=>$request->method()
];
         ActivityLog::create($activityLog);
        error_log(json_encode($activityLog));

return true;

    }
}
