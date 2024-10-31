<?php

namespace App\Mail;

use App\Http\Utils\BasicEmailUtil;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NextVisitReminderMail extends Mailable
{
    use Queueable, SerializesModels, BasicEmailUtil;

    private $booking;

    /**
     * Create a new message instance.
     *
     * @param $booking
     */
    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $salon = $this->booking->garage; // Assuming this relationship exists
        $business_id = $salon->id ?? null;
        $is_default = empty($business_id) ? 1 : 0;

        $email_content = EmailTemplate::where([
            "type" => "next_visit_reminder",
            "is_active" => 1,
            "business_id" => $business_id,
            "is_default" => $is_default,
        ])->first();

        if (empty($email_content)) {
            $email_content = $this->storeEmailTemplateIfNotExists("next_visit_reminder", $business_id, $is_default, true);
        }

        $html_content = $email_content->template;

        // Replace placeholders
        $client_name = $this->booking->customer->first_name . ' ' . $this->booking->customer->last_name;
        $html_content = str_replace("[CLIENT_NAME]", $client_name, $html_content);
        $html_content = str_replace("[SALON_NAME]", $salon->name, $html_content);
        $html_content = str_replace("[SALON_ADDRESS]", $salon->address_line_1, $html_content);
        $html_content = str_replace("[CONTACT_EMAIL]", $salon->email, $html_content);
        $html_content = str_replace("[CONTACT_PHONE]", $salon->phone, $html_content);
        $html_content = str_replace("[NEXT_VISIT_DATE]", \Carbon\Carbon::parse($this->booking->next_visit_date)->format('F j, Y'), $html_content);

        // Generate services list HTML
        $services_html = "";
        foreach ($this->booking->booking_sub_services as $service) {
            $services_html .= "<li>{$service->sub_service->name} - {$service->price}</li>";
        }
        $html_content = str_replace("[SERVICES_LIST]", $services_html, $html_content);

        $subject = "Reminder: Upcoming Visit at " . ($salon ? ($salon->name) : env("APP_NAME"));

        return $this->subject($subject)->view('emails.dynamic_mail', ["html_content" => $html_content]);
    }
}
