<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGarageAutomobileModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('garage_automobile_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("garage_automobile_make_id");
            $table->foreign('garage_automobile_make_id')->references('id')->on('garage_automobile_makes')->onDelete('cascade');
            $table->unsignedBigInteger("automobile_model_id");
            $table->foreign('automobile_model_id')->references('id')->on('automobile_models')->onDelete('cascade');
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
        Schema::dropIfExists('garage_automobile_models');
    }
}
