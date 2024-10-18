<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->string("icon")->nullable();
            $table->text("description")->nullable();
            $table->text("image")->nullable();
            $table->unsignedBigInteger("automobile_category_id");
            $table->foreign('automobile_category_id')->references('id')->on('automobile_categories')->onDelete('cascade');
            $table->boolean("is_active")->default(1);
            $table->unsignedBigInteger('business_id')->nullable(); // Add nullable business_id column
            $table->foreign('business_id')->references('id')->on('garages')->onDelete('cascade'); // Foreign key for business_id

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
        Schema::dropIfExists('services');
    }
}
