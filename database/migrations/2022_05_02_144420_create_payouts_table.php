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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('requestId')->nullable();
            $table->string('merchantReference');
            $table->string('merchantId')->nullable();
            $table->string('transactionId')->nullable();
            $table->string('debitTransactionId')->nullable();
            $table->string('paymentRef'); //same as merchantRef
            $table->string('srcAccountNumber');
            $table->string('srcAccountName');
            $table->string('enquiryRef')->nullable();
            $table->string('beneficiaryBankName')->nullable();
            $table->string('beneficiaryBankCode')->nullable();
            $table->string('beneficiaryAccountNumber')->nullable();
            $table->string('beneficiaryAccountName')->nullable();
            $table->decimal('amount');
            $table->decimal('totalCharge');
            $table->string('narration');
            $table->string('transferStatus')->nullable();
            $table->boolean('creditProcessed')->nullable();
            $table->string('channel')->nullable();
            $table->integer('retryCount')->nullable();
            $table->boolean('markedForReversal')->nullable();
            $table->boolean('approvedForReversal')->nullable();
            $table->boolean('reversed')->nullable();
            $table->string('requestTime')->nullable();
            $table->string('responseTime')->nullable();
            $table->string('transactionStatus');
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
        Schema::dropIfExists('payouts');
    }
};
