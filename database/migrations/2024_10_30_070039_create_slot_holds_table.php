<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlotHoldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slot_holds', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('expert_id');
            $table->foreign('expert_id')->references('id')->on('users')->onDelete('cascade');

            $table->json('held_slots');

            $table->timestamp('held_until')->nullable();
            $table->timestamps();

            // Foreign key constraints (adjust table names as needed)


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('slot_holds');
    }
}
