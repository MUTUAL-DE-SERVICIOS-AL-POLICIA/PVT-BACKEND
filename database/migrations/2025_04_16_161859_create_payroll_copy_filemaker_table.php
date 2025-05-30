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
            $table->integer('a_o')->comment('Año');
            $table->integer('mes')->comment('Mes');
            $table->string('carnet')->comment('Cédula de Identidad');
            $table->string('matricula')->nullable()->comment('Matricula');
            $table->string('pat')->nullable()->comment('Apellido paterno');
            $table->string('mat')->nullable()->comment('Apellido materno');
            $table->string('nom')->comment('Primer nombre');
            $table->string('nom2')->nullable()->comment('Segundo nombre');
            $table->string('ap_casada')->nullable()->comment('Apellido casada');
            $table->string('grado')->nullable()->comment('Grado');
            $table->integer('cor_afi')->comment('Correlativo del afiliado');
            $table->date('fecha_pago')->nullable()->comment('Fecha de pago');
            $table->string('recibo')->nullable()->comment('Recibo de pago');
            $table->decimal('monto')->default(0)->comment('Descuento mensual');
            $table->string('observacion')->nullable()->comment('Observación');
            $table->unsignedBigInteger('affiliate_id_frcam')->nullable()->comment('Id del afiliado titular segun FRCAM');
            $table->enum('affiliate_state', ['activo','pasivo'])->nullable()->comment('Estado afiliado'); 
            $table->string('tipo_aportante')->nullable()->comment('Tipo de aportante');  
            $table->string('error_message')->nullable()->comment('Mensaje del error');
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
