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
        Schema::create('contribution_import_periods', function (Blueprint $table) {
            $table->id();
            $table->date('month_year')->comment('periodo de importación');
            $table->string('table')->comment('nombre de la tabla de importación');
            $table->string('type')->comment('tipo de importación, planilla o contribucion');
            $table->unique(['month_year', 'table']);
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
        Schema::dropIfExists('contribution_import_periods');
    }
};
