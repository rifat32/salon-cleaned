<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingSubServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_sub_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("booking_id");
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');

            $table->unsignedBigInteger("sub_service_id");
            $table->foreign('sub_service_id')->references('id')->on('sub_services')->onDelete('restrict');

            $table->decimal("price",10,2)->default(0);

            $table->decimal('discount_percentage',10,2)->nullable();
            $table->decimal('discounted_price_to_show',10,2)->nullable();

            // $table->string("coupon_discount_type")->nullable();
            // $table->decimal("coupon_discount")->nullable();





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
        Schema::dropIfExists('booking_sub_services');
    }
}
