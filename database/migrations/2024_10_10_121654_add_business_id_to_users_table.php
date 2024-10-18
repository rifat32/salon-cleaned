<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessIdToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable()->after('id'); // Add the business_id column
            $table->foreign('business_id')->references('id')->on('garages')->onDelete('set null'); // Foreign key constraint
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_id']); // Drop foreign key
            $table->dropColumn('business_id');    // Remove the column
        });
    }

}
