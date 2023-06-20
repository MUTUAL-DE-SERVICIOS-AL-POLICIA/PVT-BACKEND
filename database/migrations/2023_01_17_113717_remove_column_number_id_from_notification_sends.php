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
        Schema::table('notification_sends', function (Blueprint $table) {
            $table->unsignedBigInteger('sender_number')->unsigned()->nullable();
            $table->foreign('sender_number')->references('id')->on('notification_numbers');
            $table->dropForeign('notification_sends_number_id_foreign');
            $table->dropColumn('number_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notification_sends', function (Blueprint $table) {
            //
        });
    }
};
