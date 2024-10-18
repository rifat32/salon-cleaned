<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGarageSubServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('garage_sub_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("garage_service_id");
            $table->foreign('garage_service_id')->references('id')->on('garage_services')->onDelete('cascade');
            $table->unsignedBigInteger("sub_service_id");
            $table->foreign('sub_service_id')->references('id')->on('sub_services')->onDelete('cascade');

       

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
        Schema::dropIfExists('garage_sub_services');
    }
}
