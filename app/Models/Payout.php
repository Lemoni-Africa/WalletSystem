<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Providers;
use TransactionStatus;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        "merchantReference",
    ];
    
    public function __construct()
    {
        
    }

    public function AddPayOut($data) 
    {
        $this->requestId = $data['requestId'];
        $this->merchantReference = $data['data']['merchantReference'];
        $this->merchantId = $data['data']['merchantId'];
        $this->transactionId = $data['data']['transactionId'];
        $this->debitTransactionId = $data['data']['debitTransactionId'];
        $this->paymentRef = $data['data']['paymentRef'];
        $this->srcAccountName = $data['data']['srcAccountName'];
        $this->srcAccountNumber = $data['data']['srcAccountNumber'];
        $this->enquiryRef = $data['data']['enquiryRef'];
        $this->beneficiaryBankName = $data['data']['beneficiaryBankName'];
        $this->beneficiaryBankCode = $data['data']['beneficiaryBankCode'];
        $this->beneficiaryAccountNumber = $data['data']['beneficiaryAccountNumber'];
        $this->beneficiaryAccountName = $data['data']['beneficiaryAccountName'];
        $this->amount = $data['data']['amount'];
        $this->totalCharge = $data['data']['totalCharge'];
        $this->narration = $data['data']['narration'];
        $this->transferStatus = $data['data']['transferStatus'];
        $this->creditProcessed = $data['data']['creditProcessed'];
        $this->channel = $data['data']['channel'];
        $this->retryCount = $data['data']['retryCount'];
        $this->markedForReversal = $data['data']['markedForReversal'];
        $this->approvedForReversal = $data['data']['approvedForReversal'];
        $this->reversed = $data['data']['reversed'];
        $this->requestTime = $data['data']['requestTime'];
        $this->responseTime = $data['data']['responseTime'];
        $this->provider = Providers::CHAKRA->value;
        $this->transactionStatus = TransactionStatus::PENDING->value;
        $this->save();

        return $this;
    }


    public function UpdateSuccessfulPayOut($data) 
    {
        $this->creditProcessed = $data['data']['creditProcessed'];
        $this->transferStatus = $data['data']['transferStatus'];
        $this->transactionStatus = TransactionStatus::COMPLETED->value;
        $this->save();
        return $this;
    }
}
