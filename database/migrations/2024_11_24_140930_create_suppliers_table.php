<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();



            $table->string('name');





            $table->string('contact_info')->nullable();





            $table->string('address')->nullable();





            $table->longText('payment_terms')->nullable();






                            $table->boolean('is_active')->default(false);



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
        Schema::dropIfExists('suppliers');
    }
}



