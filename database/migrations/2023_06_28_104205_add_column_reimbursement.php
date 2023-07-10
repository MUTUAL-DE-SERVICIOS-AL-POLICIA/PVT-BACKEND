<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnReimbursement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('db_aux')->table('payroll_copy_commands', function (Blueprint $table) {
            $table->boolean('reimbursement')->default(false)->comment('Verdadero es reintegro, falso es planilla');
        });
        Schema::table('payroll_commands', function(Blueprint $table){
            $table->boolean('reimbursement')->default(false)->comment('Verdadero es reintegro, falso es planilla');
        });
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->integer('category_id')->nullable();
            $table->string('contributionable_type')->nullable();
            $table->integer('contributionable_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('db_aux')->table('payroll_copy_commands', function (Blueprint $table) {
            $table->dropColumn('reimbursement');
        });
        Schema::table('payroll_commands', function(Blueprint $table){
            $table->dropColumn('reimbursement');
        });
        Schema::table('reimbursements', function(Blueprint $table){
            $table->dropColumn('category_id');
            $table->dropColumn('contributionable_type');
            $table->dropColumn('contributionable_id');
        });
    }
}