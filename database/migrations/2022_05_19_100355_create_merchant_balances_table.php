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
        Schema::create('merchant_balances', function (Blueprint $table) {
            $table->id();
            $table->string('accountNumber');
            $table->string('bankName');
            $table->string('accountName');
            $table->decimal('previousAvailableBalance');
            $table->decimal('availableBalance');
            $table->decimal('bookedBalance');
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
        Schema::dropIfExists('merchant_balances');
    }
};
