<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpertIdAndBusinessIdToGarageSubServicePricesTable extends Migration
{
    public function up()
    {
        Schema::table('garage_sub_service_prices', function (Blueprint $table) {
            $table->foreignId('expert_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('business_id')
                ->constrained('garages')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('garage_sub_service_prices', function (Blueprint $table) {
            $table->dropForeign(['expert_id']);
            $table->dropColumn('expert_id');

            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });
    }
}
