<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollCopySenasirsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('db_aux')->create('payroll_copy_senasirs', function (Blueprint $table) {
            //$table->id();
            $table->integer('a_o')->comment('año');
            $table->integer('mes')->comment('Mes');
            $table->unsignedBigInteger('id_person_titular')->comment('id persona de titular senasir');
            $table->string('matricula_titular')->comment('Matricula titular');
            $table->string('mat_dh')->nullable()->comment('Matricula derechohabiente');
            $table->string('departamento')->comment('Departamento');
            $table->string('regional')->comment('Regional');
            $table->string('renta')->comment('Renta');
            $table->string('tipo_renta')->comment('Tipo de renta');
            $table->string('carnet')->nullable()->comment('Carnet numero');
            $table->string('num_com')->nullable()->comment('complemento del numero de carnet');
            $table->string('paterno')->nullable()->comment('Apellido paterno');
            $table->string('materno')->nullable()->comment('Apellido materno');
            $table->string('p_nombre')->comment('Primer nombre');
            $table->string('s_nombre')->nullable()->comment('Segundo nombre');
            $table->string('ap_casada')->nullable()->comment('Apellido de casada');
            $table->date('fecha_nacimiento')->comment('Fecha de nacimiento');
            $table->string('clase_renta')->comment('Clase de renta');
            $table->decimal('total_ganado', 13, 2)->comment('Total ganado');
            $table->decimal('total_descuentos', 13, 2)->comment('Total descuentos');
            $table->decimal('liquido_pagable', 13, 2)->comment('Liquido Pagable');
            $table->decimal('rentegro_r_basica', 13, 2)->comment('reintegro');
            $table->decimal('renta_dignidad', 13, 2)->comment('Renta Dignidad');
            $table->decimal('reintegro_renta_dignidad', 13, 2)->comment('Reintegro renta dignidad');
            $table->decimal('reintegro_aguinaldo', 13, 2)->comment('Reintegro aguinaldo');
            $table->decimal('reintegro_importe_adicional', 13, 2)->comment('Reintegro Importe Adicional');
            $table->decimal('reintegro_inc_gestion', 13, 2)->comment('Reintegro Inc Gestion');
            $table->decimal('descuento_aporte_muserpol', 13, 2)->comment('Descuento aporte muserpol');
            $table->decimal('descuento_covipol', 13, 2)->comment('Descuento covipol');
            $table->decimal('descuento_prestamo_musepol', 13, 2)->comment('Descuento prestamo muserpol');
            $table->string('carnet_tit')->nullable()->comment('Carnet numero titular');
            $table->string('num_com_tit')->nullable()->comment('complemento del numero de carnet titular');
            $table->string('pat_titular')->nullable()->comment('Apellido paterno titular');
            $table->string('mat_titular')->nullable()->comment('Apellido materno titular');
            $table->string('p_nom_titular')->nullable()->comment('Primer nombre titular');
            $table->string('s_nombre_titular')->nullable()->comment('Segundo nombre titular');
            $table->string('ap_casada_titular')->nullable()->comment('Ap casada titular');
            $table->date('fecha_nac_titular')->nullable()->comment('Fecha de nacimiento titular');
            $table->string('clase_renta_tit')->nullable()->comment('Clase de renta titular');
            $table->date('fec_fail_tit')->nullable()->comment('Fecha de fallecimiento titular');
            $table->boolean('is_validated')->default(false)->comment('Muestra si el registro fue validado');;// por defecto 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('db_aux')->dropIfExists('payroll_copy_senasirs');
    }
}