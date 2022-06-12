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
    public function AddPayOutNumero($data, $request, $bankDetails) 
    {
        $this->merchantReference = $data['data']['transaction_reference'];
        $this->paymentRef = $data['data']['transaction_reference'];
        $this->srcAccountName = env('ORIGINATOR_NAME_NUMERO');
        $this->srcAccountNumber = env('DEBIT_ACCOUNT_NUMERO');
        $this->beneficiaryBankCode = $request->beneficiaryBankCode;
        $this->beneficiaryAccountNumber = $request->beneficiaryAccountNumber;
        $this->beneficiaryAccountName = $bankDetails['data']['account_name'];
        $this->amount = $request->amount;
        $this->totalCharge = $data['data']['charge'];
        $this->narration = $request->narration;
        $this->provider = Providers::NUMERO->value;
        $this->transactionStatus = TransactionStatus::COMPLETED->value;
        $this->save();

        return $this;
    }

    public function AddPayOutCrust($data, $request, $bankDetails) 
    {
        $this->merchantReference = $data['data']['transactionNumber'];
        $this->paymentRef = $data['data']['transactionNumber'];
        $this->srcAccountName = $data['data']['recipientName'];
        $this->srcAccountNumber = env('DEBIT_ACCOUNT_CRUST');
        $this->beneficiaryBankCode = $request->beneficiaryBankCode;
        $this->beneficiaryAccountNumber = $request->beneficiaryAccountNumber;
        $this->beneficiaryAccountName = $bankDetails['data']['account_name'];
        $this->amount = $request->amount;
        $this->totalCharge = $data['data']['charges'];
        $this->transferStatus = $data['data']['status'];
        $this->narration = $data['data']['narration'];
        $this->provider = Providers::CRUST->value;
        $this->transactionStatus = TransactionStatus::COMPLETED->value;
        $this->save();

        return $this;
    }
    
    public function AddFailedPayOutCrust($data, $request, $bankDetails) 
    {
        $this->merchantReference = $data['data']['transactionNumber'];
        $this->paymentRef = $data['data']['transactionNumber'];
        $this->srcAccountName = $data['data']['recipientName'];
        $this->srcAccountNumber = env('DEBIT_ACCOUNT_CRUST');
        $this->beneficiaryBankCode = $request->beneficiaryBankCode;
        $this->beneficiaryAccountNumber = $request->beneficiaryAccountNumber;
        $this->beneficiaryAccountName = $bankDetails['data']['account_name'];
        $this->amount = $request->amount;
        $this->totalCharge = $data['data']['charges'];
        $this->transferStatus = $data['data']['status'];
        $this->narration = $data['data']['narration'];
        $this->provider = Providers::CRUST->value;
        $this->transactionStatus = TransactionStatus::FAILED->value;
        $this->save();

        return $this;
    }
    public function AddFailedPayOutNumero($data, $request, $bankDetails) 
    {
        $this->merchantReference = $data['data']['transaction_reference'];
        $this->paymentRef = $data['data']['transaction_reference'];
        $this->srcAccountName = env('ORIGINATOR_NAME_NUMERO');
        $this->srcAccountNumber = env('DEBIT_ACCOUNT_NUMERO');
        $this->beneficiaryBankCode = $request->beneficiaryBankCode;
        $this->beneficiaryAccountNumber = $request->beneficiaryAccountNumber;
        $this->beneficiaryAccountName = $bankDetails['data']['account_name'];
        $this->amount = $request->amount;
        $this->totalCharge = $data['data']['charge'];
        $this->narration = $request->narration;
        $this->provider = Providers::NUMERO->value;
        $this->transactionStatus = TransactionStatus::FAILED->value;
        $this->save();

        return $this;
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

    public function UpdateFailedPayOut($data) 
    {
        $this->creditProcessed = $data['data']['creditProcessed'];
        $this->transferStatus = $data['data']['transferStatus'];
        $this->markedForReversal = $data['data']['markedForReversal'];
        $this->reversed = $data['data']['reversed'];
        $this->transactionStatus = TransactionStatus::FAILED->value;
        $this->save();
        return $this;
    }
}
