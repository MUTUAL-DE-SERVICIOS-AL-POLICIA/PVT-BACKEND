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
        Schema::connection('db_aux')->create('payroll_copy_sismu', function (Blueprint $table) {
            $table->id();
            $table->integer('IdPadron')->comment('Identificador del padron');
            $table->string('PadCedulaIdentidad')->nullable()->comment('Cédula de identidad');
            $table->string('PadMatricula')->nullable()->comment('Matrícula');
            $table->string('PadMatriculaTit')->nullable()->comment('Matrícula del titular');
            $table->string('PadApellidoCasada')->nullable()->comment('Apellido de casada');
            $table->string('PadPaterno')->nullable()->comment('Apellido paterno');
            $table->string('PadMaterno')->nullable()->comment('Apellido materno');
            $table->string('PadNombres')->nullable()->comment('Primer nombre');
            $table->string('PadNombres2do')->nullable()->comment('Segundo nombre');
            $table->date('PadFecNac')->nullable()->comment('Fecha de nacimiento');
            $table->date('PadFecIng')->nullable()->comment('Fecha de ingreso');
            $table->date('PadFecFallec')->nullable()->comment('Fecha de fallecimiento');
            $table->string('PadSexo')->nullable()->comment('Sexo');
            $table->string('EstCivilCod')->nullable()->comment('Estado civil');
            $table->date('PadTipo')->nullable()->comment('Sector del afiliado');
            $table->integer('PrsAporGestion')->nullable()->comment('Gestión de aporte');
            $table->integer('PrsAporMes')->nullable()->comment('Mes de aporte');
            $table->decimal('PrsAporMonto', 13, 2)->nullable()->comment('Monto de aporte');
            $table->string('PrsAporSts')->nullable()->comment('Estado del aporte');
            $table->string('SectCod')->nullable()->comment('Código del sector');
            $table->integer('PrsCod')->nullable()->comment('Código de aporte');
            $table->integer('TasaGestion')->nullable()->comment('Gestión');
            $table->integer('MesCod')->nullable()->comment('Mes');
            $table->string('PrsAporMesTipo')->nullable()->comment('Tipo de aporte');
            $table->decimal('PrsAporMesHabBas', 13, 2)->nullable()->comment('Haber básico');
            $table->decimal('PrsAporMesAnt', 13, 2)->nullable()->comment('Aporte ant');
            $table->decimal('PrsAporMesBonEst', 13, 2)->nullable()->comment('Bono estudio');
            $table->decimal('PrsAporMesBonCargo', 13, 2)->nullable()->comment('Bono cargo');
            $table->decimal('PrsAporMesBonFron', 13, 2)->nullable()->comment('Bono frontera');
            $table->decimal('PrsAporMesBonOrie', 13, 2)->nullable()->comment('Bono oriente');
            $table->decimal('PrsAporMesCotiz', 13, 2)->nullable()->comment('Cotizable');
            $table->decimal('PrsAporMesFRP', 13, 2)->nullable()->comment('Fondo de retiro');
            $table->decimal('PrsAporMesCM', 13, 2)->nullable()->comment('Cuota Mortuoria');
            $table->decimal('PrsAporMesValAporte', 13, 2)->nullable()->comment('Valor del aporte');
            $table->string('error_messaje')->nullable()->comment('Mensaje del error');
            $table->unsignedBigInteger('affiliate_id')->nullable()->comment('Id del afiliado titular');
            $table->enum('state', ['accomplished','unrealized'])->default('unrealized')->comment('Estado si fue encontrado o no encontrado');
            $table->string('criteria')->nullable()->comment('criterio de identificacion del afiliado');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('db_aux')->dropIfExists('payroll_copy_sismu');
    }
};
