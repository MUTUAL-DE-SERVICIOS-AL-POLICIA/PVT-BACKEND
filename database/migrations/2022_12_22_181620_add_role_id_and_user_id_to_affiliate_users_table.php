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
        Schema::table('affiliate_users', function (Blueprint $table) {
           $table->integer('role_id')->comment('Role del Usuario que genera las credenciales');
           $table->integer('user_id')->comment('usuario que genera las credenciales');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('affiliate_user', function (Blueprint $table) {
            //
        });
    }
};
