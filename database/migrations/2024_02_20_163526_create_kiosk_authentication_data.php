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
        Schema::connection('db_aux')->create('kiosk_authentication_data', function (Blueprint $table) {
            $table->id();
            $table->integer('affiliate_id')->comment('ID del afiliado');
            $table->string('identity_card');
            $table -> string('left_text')->comment('texto izquierdo');
            $table -> string('middle_text')->comment('texto izquierdo');
            $table -> string('right_text')->comment('texto izquierdo');
            $table -> boolean('ocr_state')->default(false)->comment('estado de ocr');
            $table -> boolean('facial_recognition')->default(false)->comment('estado de reconocimiento facial');
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
        Schema::connection('db_aux')->dropIfExists('kiosk_authentication_data');
    }
};
