<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGarageRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('garage_rules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger("garage_id");
            $table->foreign('garage_id')->references('id')->on('garages')->onDelete('cascade')->nullable();


            $table->integer("standard_lead_time")->nullable();

            $table->time("booking_accept_start_time")->nullable();

            $table->time("booking_accept_end_time")->nullable();


            $table->text("block_out_days")->nullable();


            $table->date("start_date");
            $table->date("end_date");






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
        Schema::dropIfExists('garage_rules');
    }
}
