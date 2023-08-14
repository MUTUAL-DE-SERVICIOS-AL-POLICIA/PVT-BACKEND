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
        Schema::connection('db_aux')->create('payroll_copy_transcripts', function (Blueprint $table) {
            $table->id();
            $table->string('obs')->nullable()->comment('observacion');
            $table->string('uni')->nullable()->comment('Unidad');
            $table->integer('mes')->comment('Mes');
            $table->integer('a_o')->comment('Año');
            $table->string('car')->comment('Carnet');
            $table->string('pat')->nullable()->comment('Apellido paterno');
            $table->string('mat')->nullable()->comment('Apellido materno');
            $table->string('nom')->nullable()->comment('Primer nombre');
            $table->string('nom2')->nullable()->comment('Segundo nombre');
            $table->string('niv')->nullable()->comment('Nivel jerarquico');
            $table->string('gra')->nullable()->comment('Grado');
            $table->decimal('sue', 13, 2)->default(0)->comment('Sueldo');
            $table->decimal('cat', 13, 2)->default(0)->comment('Bono antiguedad');
            $table->decimal('gan', 13, 2)->default(0)->comment('Total ganado');
            $table->decimal('mus', 13, 2)->default(0)->comment('Aporte Muserpol');
            $table->decimal('est', 13, 2)->default(0)->comment('Bono estudio');
            $table->decimal('carg', 13, 2)->default(0)->comment('Bono cargo');
            $table->decimal('fro', 13, 2)->default(0)->comment('Bono frontera');
            $table->decimal('ori', 13, 2)->default(0)->comment('Bono oriente');
            $table->string('error_messaje')->nullable()->comment('Mensaje del error');
            $table->unsignedBigInteger('affiliate_id')->nullable()->comment('Id del afiliado titular');
            $table->enum('state', ['accomplished','unrealized'])->default('unrealized')->comment('Estado si fue encontrado o no encontrado');
            $table->string('criteria')->nullable()->comment('critetio de identificacion del afiliado');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('db_aux')->create('payroll_transcript_periods', function (Blueprint $table) {
            $table->id();
            $table->integer('month_p')->comment('Mes');
            $table->integer('year_p')->comment('Año');
            $table->integer('number_records')->comment('numero de registros ingresado por el usuario');
            $table->decimal('total_amount', 13, 2)->comment('monto total ingresado por el usuario');
            $table->string('img')->comment('ruta de la imagen de la planilla física');
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
        Schema::connection('db_aux')->dropIfExists('payroll_copy_transcripts');
        Schema::connection('db_aux')->dropIfExists('payroll_transcript_periods');
    }
};
