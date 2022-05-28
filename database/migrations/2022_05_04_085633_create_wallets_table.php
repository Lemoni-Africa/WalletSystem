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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('requestId')->nullable();
            $table->string('merchantId')->nullable();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('middleName')->nullable();
            $table->string('fullName')->nullable();
            $table->string('email')->nullable();
            $table->string('dob')->nullable();
            $table->string('pin')->nullable();
            $table->string('gender')->nullable();
            $table->string('phoneNumber')->nullable();
            $table->string('currency')->nullable();
            $table->boolean('bvnVerified')->nullable();
            $table->boolean('ninVerified')->nullable();
            $table->string('bankName')->nullable();
            $table->string('bankCode')->nullable();
            $table->string('dateCreated')->nullable();
            $table->string('lastActivityDate')->nullable();
            $table->string('accountNumber')->nullable();
            $table->string('accountName')->nullable();
            $table->string('bvn')->nullable();
            $table->string('provider');
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
        Schema::dropIfExists('wallets');
    }
};
