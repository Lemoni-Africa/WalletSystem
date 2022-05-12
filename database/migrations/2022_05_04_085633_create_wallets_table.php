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
            $table->string('requestId');
            $table->string('merchantId');
            $table->string('firstName');
            $table->string('lastName');
            $table->string('middleName');
            $table->string('fullName');
            $table->string('email');
            $table->string('dob');
            $table->string('pin');
            $table->string('gender');
            $table->string('phoneNumber');
            $table->string('currency');
            $table->boolean('bvnVerified');
            $table->boolean('ninVerified');
            $table->string('bankName');
            $table->string('bankCode');
            $table->string('dateCreated');
            $table->string('lastActivityDate');
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
