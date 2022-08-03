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
        Schema::create('notification_carriers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id')->unsigned();
            $table->foreign('module_id')->references('id')->on('modules');
            $table->string('image')->nullable()->comment('Url de la imagen');
            $table->string('name')->comment('Nombre del medio de comunicación');
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
        Schema::dropIfExists('notification_carriers');
    }
};
