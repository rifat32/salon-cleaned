<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFuelStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fuel_stations', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text("address")->nullable();
            $table->text("description")->nullable();
            $table->time("opening_time");
            $table->time("closing_time");

            $table->string("lat");
            $table->string("long");

            $table->string("country");
            $table->string("city");
            $table->string("postcode")->nullable();
            $table->text("additional_information")->nullable();
            $table->string("address_line_1")->nullable();
            $table->string("address_line_2")->nullable();
            $table->unsignedBigInteger("created_by")->nullable(true);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->boolean("is_active")->default(1);
            $table->softDeletes();
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
        Schema::dropIfExists('fuel_stations');
    }
}
