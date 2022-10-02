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
        Schema::create('affiliate_users', function (Blueprint $table) {
            $table->bigInteger('affiliate_token_id')->unique()->comment('id affiliate tokens');
            $table->foreign('affiliate_token_id')->references('id')->on('affiliate_tokens');
            $table->string('username')->comment('Usuario');
            $table->string('password')->comment('Contraseña');
            $table->enum('access_status', ['Activo', 'Inactivo', 'Pendiente'])->default('Pendiente')->comment('Estado del acceso'); 
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
        Schema::dropIfExists('affiliate_users');
    }
};
