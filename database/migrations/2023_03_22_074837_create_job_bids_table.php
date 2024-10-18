<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobBidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_bids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("pre_booking_id");
            $table->foreign('pre_booking_id')->references('id')->on('pre_bookings')->onDelete('cascade');
            $table->unsignedBigInteger("garage_id");
            $table->foreign('garage_id')->references('id')->on('garages')->onDelete('cascade');
            $table->double("price");
            $table->text("offer_template");


            $table->date("job_start_date")->nullable();


           $table->time("job_start_time")->nullable();
           $table->time("job_end_time")->nullable();


           $table->enum("status",["pending",'accepted', 'rejected',"canceled_after_booking", "canceled_after_job",'job_completed'])->default("pending");

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
        Schema::dropIfExists('job_bids');
    }
}
