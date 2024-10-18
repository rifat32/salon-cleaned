<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_services', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text("description")->nullable();
            $table->unsignedBigInteger("service_id");
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->boolean("is_fixed_price")->default(1);
            $table->unsignedBigInteger('business_id')->nullable(); // Add nullable business_id column
            $table->foreign('business_id')->references('id')->on('garages')->onDelete('cascade'); // Foreign key for business_id
            $table->integer('service_time_in_minute')->nullable(); // Add nullable service_time_in_minute column
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
        Schema::dropIfExists('sub_services');
    }
}
