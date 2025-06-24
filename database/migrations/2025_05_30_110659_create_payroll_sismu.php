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
        Schema::create('payroll_sismu', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_id')->unsigned()->comment('Id del afiliado titular'); // Id del afiliado titular
            $table->foreign('affiliate_id')->references('id')->on('affiliates');
            $table->integer('id_padron')->comment('Identificador del padrón');
            $table->string('identity_card')->nullable()->comment('Cédula de identidad');
            $table->string('registration')->nullable()->comment('Matrícula');
            $table->string('registration_affiliate')->nullable()->comment('Matrícula del titular');
            $table->string('surname_husband')->nullable()->comment('Apellido de casada');
            $table->string('last_name')->nullable()->comment('Apellido paterno');
            $table->string('mothers_last_name')->nullable()->comment('Apellido materno');
            $table->string('first_name')->nullable()->comment('Primer nombre');
            $table->string('second_name')->nullable()->comment('Segundo nombre');
            $table->date('birth_date')->nullable()->comment('Fecha de nacimiento');
            $table->date('date_entry')->nullable()->comment('Fecha de ingreso');
            $table->date('date_death')->nullable()->comment('Fecha de fallecimiento');
            $table->string('gender')->nullable()->comment('Sexo');
            $table->string('civil_status')->nullable()->comment('Estado civil');
            $table->string('sector')->nullable()->comment('sector');
            $table->integer('global_contribution_year')->nullable()->comment('Gestión de aporte');
            $table->integer('global_contribution_month')->nullable()->comment('Mes de aporte');
            $table->decimal('global_contribution_amount', 13, 2)->nullable()->comment('Monto de aporte');
            $table->string('contribution_state')->nullable()->comment('Estado del aporte');
            $table->string('sector_code')->nullable()->comment('Código del sector');
            $table->integer('contribution_code')->nullable()->comment('Código de aporte');
            $table->integer('year')->nullable()->comment('Gestión');
            $table->integer('month')->nullable()->comment('Mes');
            $table->string('type')->nullable()->comment('Tipo de aporte');
            $table->decimal('base_wage', 13, 2)->nullable()->comment('Haber básico');
            $table->decimal('seniority_bonus', 13, 2)->nullable()->comment('Aporte antiguedad');
            $table->decimal('study_bonus', 13, 2)->nullable()->comment('Bono estudio');
            $table->decimal('position_bonus', 13, 2)->nullable()->comment('Bono cargo');
            $table->decimal('border_bonus', 13, 2)->nullable()->comment('Bono frontera');
            $table->decimal('east_bonus', 13, 2)->nullable()->comment('Bono oriente');
            $table->decimal('quotable', 13, 2)->nullable()->comment('Cotizable');
            $table->decimal('retirement_fund', 13, 2)->nullable()->comment('Fondo de retiro');
            $table->decimal('mortuary_quota', 13, 2)->nullable()->comment('Cuota Mortuoria');
            $table->decimal('total', 13, 2)->nullable()->comment('Valor del aporte');
            $table->timestamps();
            $table->softDeletes();  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_sismu');
    }
};
