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
        Schema::create('affiliate_tokens', function (Blueprint $table) {
            $table->id()->comment('id affiliate tokens');
            $table->timestamps();
            $table->bigInteger('affiliate_id');
            $table->foreign('affiliate_id')->references('id')->on('affiliates')->comment('llave foranea de affiliates');
            $table->string('api_token', 80)->unique()->nullable()->default(null)->comment('token para la aplicacion');
            $table->string('device_id')->unique()->nullable()->comment('Id del dispositivo vinculado');
            $table->string('firebase_token', 80)->unique()->nullable()->default(null)->comment('token generado en firebase');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('affiliate_tokens');
    }
};
