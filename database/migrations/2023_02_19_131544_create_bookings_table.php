<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

                // Add the expert_id foreign key referencing users table
                   $table->unsignedBigInteger('expert_id');
                   $table->foreign('expert_id')->references('id')->on('users')->onDelete('cascade');

                   // Add the booked_slots JSON column
                   $table->json('booked_slots');

                   $table->enum("booking_type", ["self_booking", "admin_panel_booking","walk_in_customer_booking"]);


            $table->unsignedBigInteger("pre_booking_id")->nullable();
            $table->foreign('pre_booking_id')->references('id')->on('pre_bookings')->onDelete('restrict');


            $table->unsignedBigInteger("garage_id");
            $table->foreign('garage_id')->references('id')->on('garages')->onDelete('cascade');
            $table->unsignedBigInteger("customer_id");
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');

            $table->text("additional_information")->nullable();
            $table->text("reason")->nullable();

            $table->enum("coupon_discount_type",['fixed', 'percentage'])->default("fixed")->nullable();
            $table->double("coupon_discount_amount")->nullable()->default(0);


            $table->enum("discount_type",['fixed', 'percentage'])->default("fixed")->nullable();
            $table->double("discount_amount")->nullable()->default(0);

            $table->enum("tip_type",['fixed', 'percentage'])->default("fixed")->nullable();
            $table->double("tip_amount")->nullable()->default(0);



            $table->double("price")->default(0);

            $table->double("final_price")->default(0);


            $table->string("coupon_code")->nullable();
            $table->string("payment_intent_id")->nullable();


            $table->date("job_start_date")->nullable();

            $table->enum("status",["pending", "confirmed", "check_in", "rejected_by_client", "rejected_by_garage_owner", "arrived", "converted_to_job"]);


            $table->unsignedBigInteger("created_by");
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->enum("created_from",["customer_side","garage_owner_side"]);

            $table->timestamps();
            $table->softDeletes();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}
