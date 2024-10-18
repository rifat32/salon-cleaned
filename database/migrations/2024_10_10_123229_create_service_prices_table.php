<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();



            $table->foreignId('service_id')
                ->constrained('services')
                ->onDelete('cascade');
            $table->double('price');
            $table->foreignId('expert_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('business_id')
                ->constrained('garages')
                ->onDelete('cascade');
                
            $table->boolean('is_active')->default(false);
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
        Schema::dropIfExists('service_prices');
    }
}
