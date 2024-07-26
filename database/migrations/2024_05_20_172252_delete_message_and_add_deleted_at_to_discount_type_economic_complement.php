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
        Schema::table('discount_type_economic_complement', function (Blueprint $table) {
            $table->softDeletes(); // Agrega la columna deleted_at
            $table->dropColumn('message'); // Elimina la columna message
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('discount_type_economic_complement', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Elimina la columna deleted_at
            $table->string('message'); // Agrega de nuevo la columna message
        });
    }
};
