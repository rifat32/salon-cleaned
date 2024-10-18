<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGaragesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text("about")->nullable();
            $table->string("web_page")->nullable();
            $table->string("phone")->nullable();
            $table->string("email")->unique();
            $table->text("additional_information")->nullable();
            $table->string("address_line_1")->nullable();
            $table->string("address_line_2")->nullable();
            $table->string("lat")->nullable();
            $table->string("long")->nullable();
            $table->string("country")->nullable();
            $table->string("city")->nullable();
            $table->string("postcode")->nullable();
            $table->string("currency")->nullable();


            $table->string("logo")->nullable();
            $table->string("image")->nullable();
            $table->string("background_image")->nullable();

            $table->string('status')->default("pending");
            // $table->enum('status', ['status1', 'status2',  'status3'])->default("status1");
            $table->boolean("is_active")->default(false);
            $table->boolean("is_mobile_garage")->default(false);
            $table->boolean("wifi_available")->default(false);
            $table->unsignedBigInteger("labour_rate")->nullable();



            $table->enum('time_format', ['12-hour', '24-hour'])->nullable()->default('12-hour');


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
        Schema::dropIfExists('garages');
    }
}
