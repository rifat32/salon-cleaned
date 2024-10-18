<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("garage_id");
            $table->foreign('garage_id')->references('id')->on('garages')->onDelete('cascade');
            $table->string("name");
            $table->string("code");


            $table->enum("discount_type",['fixed', 'percentage'])->default("fixed")->nullable();
            $table->double("discount_amount");



            $table->double("min_total")->nullable();
            $table->double("max_total")->nullable();



            $table->double("redemptions")->nullable();
            $table->double("customer_redemptions")->default(0);





            $table->dateTime("coupon_start_date");
            $table->dateTime("coupon_end_date");


            $table->boolean("is_auto_apply")->default(0);


            $table->boolean("is_active")->default(0);
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
        Schema::dropIfExists('coupons');
    }
}
