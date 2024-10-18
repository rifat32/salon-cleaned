<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobSubServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_sub_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("job_id");
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');

            $table->unsignedBigInteger("sub_service_id");
            $table->foreign('sub_service_id')->references('id')->on('sub_services')->onDelete('restrict');


            $table->double("price")->default(0);

            // $table->string("coupon_discount_type")->nullable();
            // $table->double("coupon_discount_amount")->nullable();


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
        Schema::dropIfExists('job_sub_services');
    }
}
