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

class SendNextVisitReminders extends Command
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
        $experts = User::with("translation")
                ->where("users.is_active", 1)
                ->whereHas('roles', function ($query) {
                    $query->where('roles.name', 'business_experts');
                })
                ->get();

                foreach($experts as $expert){
                    $date = Carbon::yesterday();
                    $dayOfWeek = $date->dayOfWeek;

                    $businessSetting = $this->get_business_setting($expert->business_id);
                    $garageTime = GarageTime::
                    where("garage_id", request()->input($expert->business_id))
                    ->where("day",yesterday())
                    ->first();

                    $total_slots = count($garageTime->time_slots);

                    $expert_rota = ExpertRota::where('expert_rotas.business_id', $expert->business_id)
                    ->whereDate('expert_rotas.date', ">=", $date)
                    ->where('expert_rotas.expert_id', $expert->id)
                    ->orderBy("expert_rotas.id", "DESC")
                    ->first();

                    if(empty($expert_rota)) {
                        $expert_rota = ExpertRota::create([
                        'expert_id' => $expert->id,
                        'date' => $date ,
                        'busy_slots' => [],
                        "is_active" => 1,
                        "business_id" => $expert->business_id,
                        "created_by" => 1
                      ]);
                    }

                    $expert_rota->worked_minutes = ($total_slots - count($expert_rota->busy_slots)) * $businessSetting->slot_duration;
$expert_rota->save();

                }

    }

}
