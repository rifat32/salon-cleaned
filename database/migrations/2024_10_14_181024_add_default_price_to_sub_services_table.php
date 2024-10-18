<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultPriceToSubServicesTable extends Migration
{
    public function up()
    {
        Schema::table('sub_services', function (Blueprint $table) {
            $table->double('default_price')->nullable();
            $table->double('price')->nullable();
        });
    }

    public function down()
    {
        Schema::table('sub_services', function (Blueprint $table) {
            $table->dropColumn('default_price');
            $table->dropColumn('price');
        });
    }
}
