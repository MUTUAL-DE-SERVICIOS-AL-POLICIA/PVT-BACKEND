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
        Schema::create('payroll_filemakers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_id')->unsigned()->comment('Id del afiliado titular'); // Id del afiliado titular
            $table->foreign('affiliate_id')->references('id')->on('affiliates');
            $table->integer('year_p')->comment('Año del periodo de aporte');
            $table->integer('month_p')->comment('Mes del periodo de aporte');
            $table->string('rent_class')->nullable()->comment('Clase de renta');
            $table->string('identity_card')->nullable()->comment('Carnet aportante');
            $table->string('registration')->nullable()->comment('Matricula aportante');
            $table->string('last_name')->nullable()->comment('Apellido paterno');
            $table->string('mothers_last_name')->nullable()->comment('Apellido materno');
            $table->string('first_name')->comment('Primer nombre');
            $table->string('second_name')->nullable()->comment('Segundo nombre');
            $table->string('surname_husband')->nullable()->comment('Apellido de casada');
            $table->string('deegre')->nullable()->comment('Grado');
            $table->date('payment_date')->nullable()->comment('Fecha de pago');
            $table->string('voucher')->nullable()->comment('Recibo de pago');
            $table->decimal('discount_contribution', 13, 2)->comment('Descuento aporte muserpol');
            $table->string('observation')->nullable()->comment('Observación');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_filemakers');
    }
};
