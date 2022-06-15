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
            $table->string('customerId');
            $table->string('callback_url')->nullable();
            $table->string('action')->nullable();           
            $table->string('narration_call_back')->nullable();
            $table->string('srcAccountName_call_back')->nullable();
            $table->string('srcAccountNumber_call_back')->nullable();
            $table->boolean('success')->nullable();
            $table->string('beneficiaryAccountName_call_back')->nullable();
            $table->string('beneficiaryAccountNumber_call_back')->nullable();
            $table->string('beneficiaryBankName_call_back')->nullable();
            $table->decimal('amount_call_back')->nullable();
            $table->json('application_response')->nullable();
            $table->timestamps();
            // {
            //     "action": "payout",
            //     "amount": 4744.62,
            //     "beneficiaryAccountName": "TAYO ADERIYE",
            //     "beneficiaryAccountNumber": "0000000000",
            //     "beneficiaryBankName": "Guaranty Trust Bank",
            //     "debitTransactionId": "099000000000055",
            //     "merchantId": "CHKTDP21100999606969",
            //     "narration": "Transfer",
            //     "paymentRef": "XXXXXXXXXXX",
            //     "srcAccountName": "lemon",
            //     "srcAccountNumber": "0000000000",
            //     "success": true
            // }
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
