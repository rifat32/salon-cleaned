<?php

namespace App\Console\Commands;

use App\Mail\NextVisitReminderMail;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendNextVisitReminders extends Command
{
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
        $today = Carbon::now();
        $reminderPeriod = 7; // Set reminder for 7 days in advance

        // Fetch bookings with next visit dates within the reminder period
        $bookings = Booking::whereHas('customer', function($query) {
                $query->where('users.is_walk_in_customer', 0);
            })
            ->where('send_notification', 1)
            ->whereDate('next_visit_date', $today->addDays($reminderPeriod))
            ->get();

        foreach ($bookings as $booking) {
            // Send email notification to the customer
            Mail::to($booking->customer->email)->send(new NextVisitReminderMail($booking));

            $this->info('Reminder email sent for booking ID: ' . $booking->id);
        }

        if ($bookings->isEmpty()) {
            $this->info('No upcoming visit reminders to send.');
        }
    }

}
