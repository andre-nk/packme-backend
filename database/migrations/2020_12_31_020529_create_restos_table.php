<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restos', function (Blueprint $table) {
            $table->id();
            $table->string('restoName');
            $table->longText('address');
            $table->json('cashier1')->nullable();
            $table->json('cashier1_quantity')->nullable();
            $table->json('cashier2')->nullable();
            $table->json('cashier2_quantity')->nullable();
            $table->json('cashier3')->nullable();
            $table->json('cashier3_quantity')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('restos');
    }
}
