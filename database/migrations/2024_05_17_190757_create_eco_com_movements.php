<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eco_com_movements', function (Blueprint $table) {
            $table->id();
            $table->integer('affiliate_id')->nullable(false)->comment('ID del afiliado');
            $table->unsignedBigInteger('movement_id')->nullable(false)->comment('Id del movimiento de dinero');
            $table->string('movement_type')->nullable(false)->comment('Tabla del tipo de movimiento');
            $table->enum('description',['DEUDA','PAGO DIRECTO','PAGO MEDIANTE TRÁMITE'])->nullable(false)->comment('Descripción de movimiento');
            $table->decimal('amount', 10, 2)->nullable(false)->comment('monto de dinero');
            $table->decimal('balance', 10, 2)->nullable(false)->comment('Balance acumulado');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('eco_com_movements');
    }
};
