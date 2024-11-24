<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        Schema::create('goods', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('sku');

            $table->foreignId('product_category_id')

            ->constrained('product_categories')
            ->onDelete('cascade');

            $table->foreignId('preferred_supplier_id')
            ->constrained('supplier')
            ->onDelete('cascade');

            $table->decimal('cost_price',10,2);

            $table->decimal('retail_price',10,2);

            $table->string('barcode');


            $table->integer('current_stock');

            $table->integer('min_stock_level');

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
        Schema::dropIfExists('goods');
    }
}



