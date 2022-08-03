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
        Schema::create('notification_sends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('carrier_id')->unsigned();
            $table->foreign('carrier_id')->references('id')->on('notification_carriers');
            $table->unsignedBigInteger('number_id')->unsigned()->nullable();
            $table->foreign('number_id')->references('id')->on('notification_numbers');
            $table->morphs('sendable');
            $table->date('send_date')->comment('Fecha de envío del mensaje');
            $table->boolean('delivered')->default(false)->comment('Verificación de envío');// por defecto 
            $table->json('message')->comment('Mensaje enviado');
            $table->string('subject')->nullable()->comment('Asunto del mensaje');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_sends');
    }
};

