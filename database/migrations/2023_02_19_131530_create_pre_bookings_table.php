<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pre_bookings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger("customer_id");
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');


            $table->unsignedBigInteger("automobile_make_id");
            $table->foreign('automobile_make_id')->references('id')->on('automobile_makes')->onDelete('restrict');
            $table->unsignedBigInteger("automobile_model_id");
            $table->foreign('automobile_model_id')->references('id')->on('automobile_models')->onDelete('restrict');



            $table->string("car_registration_no");
            $table->date("car_registration_year")->nullable();
            $table->string("additional_information")->nullable();

            $table->string("fuel")->nullable();
            $table->string("transmission")->nullable();

            $table->date("job_start_date")->nullable();
             $table->date("job_end_date")->nullable();

            $table->time("job_start_time")->nullable();
            $table->time("job_end_time")->nullable();

            $table->text("images")->nullable();
            $table->text("videos")->nullable();
            $table->text("file_links")->nullable();

            $table->enum("status",["pending","booked","converted_to_job","job_completed"])->nullable()->default("pending");
            $table->unsignedBigInteger("selected_bid_id")->nullable();



            // $table->string("address")->nullable();
            // $table->string("country");
            // $table->string("city");
            // $table->string("postcode")->nullable();
            // $table->string("lat")->nullable();
            // $table->string("long")->nullable();


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
        Schema::dropIfExists('pre_bookings');
    }
}
