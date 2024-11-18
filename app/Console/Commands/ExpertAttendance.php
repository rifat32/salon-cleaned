<?php

namespace App\Console\Commands;

use App\Http\Utils\BasicUtil;
use App\Mail\NextVisitReminderMail;
use App\Models\Booking;
use App\Models\ExpertRota;
use App\Models\GarageTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ExpertAttendance extends Command
{
    use BasicUtil;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
           $this->attendanceCommand();
    }

}
