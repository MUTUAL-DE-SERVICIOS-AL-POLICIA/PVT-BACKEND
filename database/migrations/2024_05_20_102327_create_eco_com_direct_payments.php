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
        Schema::create('eco_com_direct_payments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->decimal('amount', 10, 2)->nullable(false)->comment('monto de dinero');
            $table->string('voucher')->comment('número de comprobante');
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
        Schema::dropIfExists('eco_com_direct_payments');
    }
};
