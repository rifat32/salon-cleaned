<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFuelStationOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fuel_station_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("fuel_station_id");
            $table->foreign('fuel_station_id')->references('id')->on('fuel_stations')->onDelete('cascade')->nullable();

            $table->unsignedBigInteger("option_id");
            $table->foreign('option_id')->references('id')->on('fuel_station_services')->onDelete('cascade')->nullable();

            $table->boolean("is_active");
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
        Schema::dropIfExists('fuel_station_options');
    }
}
