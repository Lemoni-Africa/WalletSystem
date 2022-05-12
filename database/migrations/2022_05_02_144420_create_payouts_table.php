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
            $table->string('requestId');
            $table->string('merchantReference');
            $table->string('merchantId');
            $table->string('transactionId');
            $table->string('debitTransactionId');
            $table->string('paymentRef');
            $table->string('srcAccountNumber');
            $table->string('srcAccountName');
            $table->string('enquiryRef');
            $table->string('beneficiaryBankName');
            $table->string('beneficiaryBankCode');
            $table->string('beneficiaryAccountNumber');
            $table->string('beneficiaryAccountName');
            $table->decimal('amount');
            $table->decimal('totalCharge');
            $table->string('narration');
            $table->string('transferStatus');
            $table->boolean('creditProcessed');
            $table->string('channel');
            $table->integer('retryCount');
            $table->boolean('markedForReversal');
            $table->boolean('approvedForReversal');
            $table->boolean('reversed');
            $table->string('requestTime');
            $table->string('responseTime');
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
