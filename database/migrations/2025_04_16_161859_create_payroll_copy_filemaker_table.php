<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('db_aux')->create('payroll_copy_filemaker', function (Blueprint $table) {
            $table->id();
            $table->integer('mes')->comment('Mes');
            $table->integer('a_o')->comment('Año');
            $table->string('carnet')->comment('Cédula de Identidad');
            $table->string('matricula')->nullable()->comment('Matricula');
            $table->string('pat')->nullable()->comment('Apellido paterno');
            $table->string('mat')->nullable()->comment('Apellido materno');
            $table->string('nom')->comment('Primer nombre');
            $table->string('nom2')->nullable()->comment('Segundo nombre');
            $table->string('ap_casada')->nullable()->comment('Apellido casada');
            $table->string('grado')->nullable()->comment('Grado');
            $table->decimal('desc_mens')->default(0)->comment('Descuento mensual');
            $table->string('class_rent')->nullable()->comment('Clase de renta');
            $table->string('tipo')->nullable()->comment('Tipo de aportante');  
            $table->string('error_messaje')->nullable()->comment('Mensaje del error');
            $table->unsignedBigInteger('affiliate_id')->nullable()->comment('Id del afiliado titular');
            $table->enum('state', ['accomplished','unrealized','validated'])->default('unrealized')->comment('Estado si fue encontrado o no encontrado');
            $table->string('criteria')->nullable()->comment('critetio de identificacion del afiliado');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('db_aux')->dropIfExists('payroll_copy_filemaker');
    }
};
