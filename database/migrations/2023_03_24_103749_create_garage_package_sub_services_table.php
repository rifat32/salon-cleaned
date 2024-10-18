<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGaragePackageSubServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('garage_package_sub_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("sub_service_id");
            $table->foreign('sub_service_id')->references('id')->on('sub_services')->onDelete('cascade');
            $table->unsignedBigInteger("garage_package_id");
            $table->foreign('garage_package_id')->references('id')->on('garage_packages')->onDelete('cascade');



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
        Schema::dropIfExists('garage_package_sub_services');
    }
}
