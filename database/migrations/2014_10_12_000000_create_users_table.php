<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_Name');
            $table->string('last_Name');
            $table->string('phone')->nullable();
            $table->string('image')->nullable();

            $table->string("address_line_1")->nullable();
            $table->string("address_line_2")->nullable();
            $table->string("country")->nullable();
            $table->string("city")->nullable();
            $table->string("postcode")->nullable();
            $table->string("lat")->nullable();
            $table->string("long")->nullable();
            $table->string('email')->nullable();
            $table->string('email_verify_token')->nullable();
            $table->string('email_verify_token_expires')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('resetPasswordToken')->nullable();
            $table->string('resetPasswordExpires')->nullable();

            $table->string('site_redirect_token')->nullable();

            $table->string('is_active')->default(false);
            $table->unsignedBigInteger("created_by")->nullable();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->integer('login_attempts')->default(0);
            $table->dateTime('last_failed_login_attempt_at')->nullable();


            $table->string("background_image")->nullable();



            $table->softDeletes();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
