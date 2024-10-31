<?php

namespace App\Mail;

use App\Http\Utils\BasicEmailUtil;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingCreateMail extends Mailable
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
        $salon = $this->booking->garage;
        $services = $this->booking->booking_sub_services;

        $business_id = $salon->id ?? null;
        $is_default = empty($business_id) ? 1 : 0;

        $email_content = EmailTemplate::where([
            "type" => "booking_create",
            "is_active" => 1,
            "business_id" => $business_id,
            "is_default" => $is_default,
        ])->first();

        if (empty($email_content)) {
            $email_content = $this->storeEmailTemplateIfNotExists("booking_create", $business_id, $is_default, true);
        }

        $html_content = $email_content->template;

        // Replace placeholders
        $client_name = $this->booking->customer->first_name . ' ' . $this->booking->customer->last_name;
        $html_content = str_replace("[CLIENT_NAME]", $client_name, $html_content);

        $html_content = str_replace("[SALON_NAME]", $salon->name, $html_content);
        $html_content = str_replace("[SALON_ADDRESS]", $salon->address_line_1, $html_content);
        $html_content = str_replace("[CONTACT_EMAIL]", $salon->email, $html_content);
        $html_content = str_replace("[CONTACT_PHONE]", $salon->phone, $html_content);

        // Generate services list HTML
        $services_html = "";
        foreach ($services as $service) {
            $service_html = "
                <li>
                    <strong>Service:</strong> {$service->sub_service->name}<br>
                    <strong>Price:</strong> {$service->price}
                </li>";
            $services_html .= $service_html;
        }
        $html_content = str_replace("[SERVICES_LIST]", $services_html, $html_content);

        $subject = "Booking Confirmation at " . ($salon ? ($salon->name) : env("APP_NAME"));
        return $this->subject($subject)->view('emails.dynamic_mail', ["html_content" => $html_content]);
    }
}
