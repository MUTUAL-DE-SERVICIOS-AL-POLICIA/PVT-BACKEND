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
        Schema::table('contribution_passives', function (Blueprint $table) {
            $table->decimal('aps_total_cc', 13, 2)->default(0)->comment('Fracción de Compensación de Cotización');
            $table->decimal('aps_total_fsa', 13, 2)->default(0)->comment('Fracción de Saldo Acumulado');
            $table->decimal('aps_total_fs', 13, 2)->default(0)->comment('Fracción Solidaria de Vejez');
            $table->decimal('aps_total_death', 13,2)->default(0)->comment('Renta por Muerte');
            $table->decimal('aps_disability', 13, 2)->default(0)->comment('Renta invalides');
            $table->decimal('aps_reimbursement', 13, 2)->default(0)->comment('Reintegro');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contribution_passives', function (Blueprint $table) {
            //
        });
    }
};
