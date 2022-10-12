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
            $table->bigIncrements('id')->comment('id affiliate tokens');
            $table->bigInteger('affiliate_id');
            $table->foreign('affiliate_id')->references('id')->on('affiliates')->comment('llave foranea de affiliates');
            $table->string('api_token')->unique()->nullable()->default(null)->comment('token para la aplicacion');
            $table->string('firebase_token')->nullable()->default(null)->comment('token generado en firebase');
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
        Schema::dropIfExists('affiliate_tokens');
    }
};
