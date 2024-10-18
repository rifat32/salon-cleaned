<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateJobPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_payments', function (Blueprint $table) {
            // Make job_id nullable
            $table->unsignedBigInteger('job_id')->nullable()->change();
            $table->unsignedBigInteger('payment_type_id')->nullable()->change();
            // Add foreign key for booking_id
            $table->unsignedBigInteger('booking_id')->nullable()->after('job_id');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_payments', function (Blueprint $table) {
            // Revert job_id back to non-nullable
            $table->unsignedBigInteger('job_id')->nullable(false)->change();

            // Remove booking_id and its foreign key
            $table->dropForeign(['booking_id']);
            $table->dropColumn('booking_id');
        });
    }
}
