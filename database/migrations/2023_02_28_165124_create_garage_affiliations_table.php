<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGarageAffiliationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('garage_affiliations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger("garage_id");
            $table->foreign('garage_id')->references('id')->on('garages')->onDelete('cascade');

            $table->unsignedBigInteger("affiliation_id");
            $table->foreign('affiliation_id')->references('id')->on('affiliations')->onDelete('cascade');


            $table->date("start_date");
            $table->date("end_date")->nullable();



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
        Schema::dropIfExists('garage_affiliations');
    }
}
