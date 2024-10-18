<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGarageSubServicePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('garage_sub_service_prices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger("garage_sub_service_id");
            $table->foreign('garage_sub_service_id')->references('id')->on('garage_sub_services')->onDelete('cascade');

            $table->unsignedBigInteger("automobile_make_id")->nullable();
            $table->foreign('automobile_make_id')->references('id')->on('automobile_makes')->onDelete('cascade');

            $table->double("price")->default(0);

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
        Schema::dropIfExists('garage_sub_service_prices');
    }
}
