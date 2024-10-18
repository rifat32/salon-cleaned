<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLogoToAutomobileCategoriesTable extends Migration
{
    public function up()
    {
        Schema::table('automobile_categories', function (Blueprint $table) {
            $table->string('logo')->nullable(); // Adding the nullable 'logo' column
        });
    }

    public function down()
    {
        Schema::table('automobile_categories', function (Blueprint $table) {
            $table->dropColumn('logo'); // Dropping the 'logo' column if rolling back the migration
        });
    }

}
