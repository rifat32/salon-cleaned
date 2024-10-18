<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text("about")->nullable();
            $table->string("web_page")->nullable();
            $table->string("phone")->nullable();
            $table->string("email")->unique();
            $table->text("additional_information")->nullable();
            $table->string("address_line_1")->nullable();
            $table->string("address_line_2")->nullable();
            $table->string("lat");
            $table->string("long");
            $table->string("country");
            $table->string("city");
            $table->string("postcode")->nullable();
            $table->string("logo")->nullable();
            $table->string("image")->nullable();
            $table->string("sku_prefix")->nullable();
            $table->string('status')->default("pending");
            // $table->enum('status', ['status1', 'status2',  'status3'])->default("status1");
            $table->boolean("is_active")->default(false);
            $table->boolean("is_mobile_shop")->default(false);
            $table->boolean("wifi_available")->default(false);
            $table->unsignedBigInteger("labour_rate")->nullable();
            $table->unsignedBigInteger("owner_id");
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger("created_by")->nullable(true);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('shops');
    }
}
