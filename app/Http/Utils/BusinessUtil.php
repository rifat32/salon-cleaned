<?php

namespace App\Http\Utils;

use App\Mail\BusinessWelcomeMail;
use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\BusinessTime;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Designation;
use App\Models\EmailTemplate;
use App\Models\EmploymentStatus;
use App\Models\JobPlatform;
use App\Models\Project;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Models\ServicePlanModule;
use App\Models\SettingAttendance;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\SettingPaymentDate;
use App\Models\SettingPayrun;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PharIo\Manifest\Email;

trait BusinessUtil
{
    use BasicUtil, DiscountUtil;
    // this function do all the task and returns transaction id or -1



}
