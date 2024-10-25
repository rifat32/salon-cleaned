<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStripeSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->text('STRIPE_KEY')->nullable();
            $table->text('STRIPE_SECRET')->nullable();
            $table->foreignId('business_id')
            ->constrained('garages')
            ->onDelete('cascade');

                // Stripe settings
    $table->boolean('stripe_enabled')->default(false); // Whether Stripe is enabled
    $table->boolean('is_expert_price')->default(1);
    $table->boolean('is_auto_booking_approve')->default(false);

    // Business settings
    $table->boolean('allow_pay_after_service')->default(false); // Allow pay after service
    $table->boolean('allow_expert_booking')->default(false); // Expert can take booking
    $table->boolean('allow_expert_self_busy')->default(false); // Expert can mark themselves as busy
    $table->boolean('allow_expert_booking_cancel')->default(false); // Expert can cancel booking
    $table->boolean('allow_expert_view_revenue')->default(false); // Expert can view their revenue
    $table->boolean('allow_expert_view_customer_details')->default(false); // Expert can view customer details
    $table->boolean('allow_receptionist_add_question')->default(false); // Receptionist can add question

    // Currency and language settings
    $table->string('default_currency', 10)->nullable(); // Default business currency
    $table->string('default_language', 10)->nullable(); // Default business language

    // VAT settings
    $table->boolean('vat_enabled')->default(false); // Whether VAT is enabled
    $table->decimal('vat_percentage', 5, 2)->nullable(); // VAT percentage (e.g., 15.00 for 15%)

            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stripe_settings');
    }
}
