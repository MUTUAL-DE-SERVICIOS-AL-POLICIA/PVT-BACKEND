<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllowedMacDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('allowed_mac_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name')->unique()->comment('nombre del dispositivo');
            $table->string('password')->comment('contraseña del dispositivo');
            $table->string('api_token')->unique()->nullable()->default(null)->comment('token de acceso');
            $table->string('mac_address', 255)->unique()->comment('direccion mac');
            $table->boolean('is_enable')->default(false)->comment('Estado del dispositivo');
            $table->string('Description')->comment('Descripcion del dispositivo');
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
        Schema::connection('db_aux')->dropIfExists('allowed_mac_devices');
    }
}
