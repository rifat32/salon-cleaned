<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ServicePlan;
use App\Models\BusinessSubscription;
use App\Models\JobPayment;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Stripe\Event;

class CustomWebhookController extends WebhookController
{
    /**
     * Handle a Stripe webhook call.
     *
     * @param  Event  $event
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleStripeWebhook(Request $request)
    {
        // Retrieve the event data from the request body
        $payload = $request->all();

        // Log the entire payload for debugging purposes
        Log::info('Webhook Payload: ' . json_encode($payload));

        // Extract the event type
        $eventType = $payload['type'] ?? null;

        // Log the event type
        Log::info('Event Type: ' . $eventType);

        // Handle the event based on its type
        if ($eventType === 'checkout.session.completed') {
            // return response()->json($payload['data']['object'],409);
            $this->handleChargeSucceeded($payload['data']['object']);

        }
         // Check if it's a refund event
    if ($eventType == 'charge.refunded') {
        $event = $request->getContent();
    $eventJson = json_decode($event);
        $charge = $eventJson->data->object;

        // Update your booking or order status based on the refund
        $booking = Booking::where('payment_intent', $charge->payment_intent)->first();
        if ($booking) {
            $booking->payment_status = 'refunded';
            $booking->save();

            JobPayment::where([
                "booking_id" => $booking->id,

            ])
            ->delete();
        }

        return response()->json(['status' => 'Refund handled'], 200);
    }

        // Return a response to Stripe to acknowledge receipt of the webhook
        return response()->json(['message' => 'Webhook received']);
    }

    /**
     * Handle payment succeeded webhook from Stripe.
     *
     * @param  array  $paymentCharge
     * @return void
     */
    protected function handleChargeSucceeded($data)
    {



        // Extract required data from payment charge
        $amount = $data['amount_total'] ? ($data['amount_total'] / 100) :  0;
        $customerID = $data['customer'] ?? null;
        $metadata = $data["metadata"] ?? [];
        // Add more fields as needed

        if(!empty($metadata["our_url"]) && $metadata["our_url"] != route('stripe.webhook')){
               return;
        }




        JobPayment::create([
            "booking_id" => $metadata["booking_id"],
            "amount" => $amount,
        ]);


            Booking::where([
                "id" => $metadata["booking_id"]
            ])
                ->update([
                    "payment_status" => "complete",
                    "payment_method" => "stripe"
                ]);


    //     $subscription = BusinessBusinessBusinessSubscription::where('business_id', $user->business->id)
    //     ->where("service_plan_id",  $user->business->service_plan_id)
    //     ->where('start_date', '<=', now()) // Start date is in the past or now
    //     ->where('end_date', '>=', now())   // End date is in the future or now
    //     ->first();

    // if ($subscription) {
    //     // If a current subscription exists, update it
    //     $subscription->amount = $paymentCharge['amount'];
    //     $subscription->paid_at = now();
    //     $subscription->save();
    // } else {
    //     // If a current subscription does not exist, create a new one
    //     $service_plan = ServicePlan::find($user->business->service_plan_id);

    //     // Create a new subscription with appropriate date conditions
    //     BusinessBusinessBusinessSubscription::create([
    //         'business_id' => $user->business->id,
    //         'service_plan_id' => $user->business->service_plan_id,
    //         'start_date' => now(),  // Assuming the subscription starts immediately upon payment
    //         'end_date' => Carbon::now()->addDays($service_plan->duration),  // Set end date based on plan duration
    //         'amount' => $paymentCharge['amount'],
    //         'paid_at' => now(),
    //         // Add other necessary fields here
    //     ]);
    // }


        // $userID = $user->id ?? null;

        // // Log the extracted data
        // Log::info('Amount: ' . $amount);
        // Log::info('Currency: ' . $currency);
        // Log::info('Customer ID: ' . $customerID);
        // Log::info('User ID: ' . $userID);

        // Process the payment charge data as needed
        // For example, you can update the user's payment information in the database
    }
}
