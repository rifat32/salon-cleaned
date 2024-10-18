<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFuelStationTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fuel_station_times', function (Blueprint $table) {
            $table->id();
            $table->integer("day");


            $table->time("opening_time");


            $table->time("closing_time");


            $table->boolean("is_closed");





            $table->unsignedBigInteger("fuel_station_id");
            $table->foreign('fuel_station_id')->references('id')->on('fuel_stations')->onDelete('cascade');


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
        Schema::dropIfExists('fuel_station_times');
    }
}
