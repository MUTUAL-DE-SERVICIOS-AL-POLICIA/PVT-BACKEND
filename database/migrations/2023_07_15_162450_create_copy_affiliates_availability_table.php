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
        Schema::connection('db_aux')->create('copy_affiliates_availability', function (Blueprint $table) {
            $table->id();
            $table->string('cedula')->comment('Carnet Identidad');
            $table->string('grado')->nullable()->comment('Grado');
            $table->string('paterno')->nullable()->comment('Apellido paterno');
            $table->string('materno')->nullable()->comment('Apellido materno');
            // $table->string('nombres')->nullable()->commnet('Primero nombre, segundo nombre');
            $table->string('primer_nombre')->nullable()->comment('Primer nombre');
            $table->string('segundo_nombre')->nullable()->comment('Segundo nombre');
            $table->string('situacion_laboral')->nullable()->comment('Estado de disponibilidad');
            $table->string('unidad')->nullable()->comment('Unidad');
            $table->integer('mes')->comment('Mes');
            $table->integer('a_o')->comment('Año');
            $table->enum('state', ['accomplished', 'unrealized'])->default('unrealized')->comment('Estado si fue encontrado o no encontrado');
            $table->unsignedBigInteger('affiliate_id')->nullable()->comment('Id del afiliado titular');
            $table->string('criteria')->nullable()->comment('Criterio de identificación del afiliado');
            $table->string('error_mensaje')->nullable()->comment('Mensaje de error');
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
        Schema::dropIfExists('copy_affiliates_availability');
    }
};
