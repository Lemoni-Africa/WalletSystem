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
        Schema::create('inflows', function (Blueprint $table) {
            $table->id();
            $table->string('customerId');
            $table->string('request_amount');
            $table->string('received_amount')->nullable();
            $table->string('accountNumber');
            $table->string('accountName');
            $table->string('bankName');
            $table->string('bankCode')->nullable();
            $table->string('reference');
            $table->string('status');
            $table->string('request_time');
            $table->string('provider');
            $table->string('action')->nullable();
            $table->string('fee')->nullable();
            $table->string('narration')->nullable();
            $table->string('srcAccountName')->nullable();
            $table->string('srcAccountNumber')->nullable();
            $table->string('srcBankCode')->nullable();
            $table->string('srcBankName')->nullable();
            $table->boolean('success')->nullable();
            $table->string('transactionId')->nullable();
            $table->string('time_of_verification')->nullable();
            $table->string('walletAccountNumber')->nullable();
            $table->timestamps();
        });
    }
    // 'action' => 'required',
    // 'amount' => 'required',
    // 'fee' => 'required',
    // 'narration' => 'required',
    // 'reference' => 'required',
    // 'srcAccountName' => 'required',
    // 'srcAccountNumber' => 'required',
    // 'srcBankCode' => 'required',
    // 'srcBankName' => 'required',
    // 'success' => 'required',
    // 'transactionId' => 'required',
    // 'walletAccountNumber' => 'required',

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inflows');
    }
};
