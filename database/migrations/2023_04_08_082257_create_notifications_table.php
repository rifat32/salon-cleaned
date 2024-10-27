<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("sender_id");
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger("receiver_id");
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger("customer_id");
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger("business_id")->nullable();
            $table->foreign('business_id')->references('id')->on('garages')->onDelete('cascade');

            $table->unsignedBigInteger("garage_id")->nullable();
            $table->foreign('garage_id')->references('id')->on('garages')->onDelete('cascade');

            $table->unsignedBigInteger("bid_id")->nullable();
            $table->foreign('bid_id')->references('id')->on('job_bids')->onDelete('cascade');

            $table->unsignedBigInteger("pre_booking_id")->nullable();
            $table->foreign('pre_booking_id')->references('id')->on('pre_bookings')->onDelete('cascade');

            $table->unsignedBigInteger("booking_id")->nullable();
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');

            $table->unsignedBigInteger("job_id")->nullable();
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');

            $table->string("entity_name")->nullable();
            $table->unsignedBigInteger("entity_id")->nullable();
            $table->json("entity_ids")->nullable();

            $table->string('notification_title')->nullable();
            $table->text('notification_description')->nullable();
            $table->string('notification_link')->nullable();

            $table->boolean("is_system_generated")->default(false);

            $table->unsignedBigInteger("notification_template_id");
            $table->foreign('notification_template_id')->references('id')->on('notification_templates')->onDelete('cascade');

            $table->enum("status", ['read', 'unread'])->default("unread");

            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();

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
        Schema::dropIfExists('notifications');
    }
}
