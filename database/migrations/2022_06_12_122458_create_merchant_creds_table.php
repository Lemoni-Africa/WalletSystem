<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('merchant_creds', function (Blueprint $table) {
            $table->id();
            $table->string('merchantId');
            $table->string('apiKey');
            $table->string('provider');
            $table->timestamps();
            // $table->timestamps()->default('CURRENT_TIMESTAMP');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_creds');
    }
};
