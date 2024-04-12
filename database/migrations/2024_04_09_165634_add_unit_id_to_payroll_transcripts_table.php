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
        Schema::table('payroll_transcripts', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->comment('Unidad');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payrroll_transcript', function (Blueprint $table) {
            $table->dropColumn('unit_id');
        });
    }
};
