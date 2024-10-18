<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubServicePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create('sub_service_prices', function (Blueprint $table) {
            $table->id();



            $table->foreignId('sub_service_id')

            ->constrained('sub_services')
            ->onDelete('cascade');

            $table->double('price');

            $table->foreignId('expert_id')

            ->constrained('users')
            ->onDelete('cascade');

            $table->string('description')->nullable();

            $table->foreignId('business_id')
            ->constrained('garages')
            ->onDelete('cascade');

            $table->unsignedBigInteger("created_by");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        Schema::dropIfExists('sub_service_prices');
    }
}



