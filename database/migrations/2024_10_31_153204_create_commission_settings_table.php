<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')
            ->constrained('garages')
            ->onDelete('cascade');
            $table->decimal('target_amount', 15, 2)->default(0); // Target amount for commission
            $table->decimal('commission_percentage', 5, 2)->default(0); // Commission percentage
            $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly'); // Frequency of commission
            
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
        Schema::dropIfExists('commission_settings');
    }
}
