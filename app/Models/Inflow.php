<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Providers;
use TransactionStatus;

class Inflow extends Model
{
    use HasFactory;
    protected $fillable = [
        "action",
        "amount",
        "fee",
        "narration",
        "reference",
        "srcAccountName",
        "srcAccountNumber",
        "srcBankCode",
        "srcBankName",
        "success",
        "transactionId",
        "walletAccountNumber",
        "customerId"
    ];

    public function saveInFlowRequest($data, $request) 
    {
        $this->customerId = $request->customerId;
        $this->request_amount = $request->amount;
        $this->callback_url = $request->callbackUrl;
        // $this->received_amount = $data['received_amount'];
        $this->accountNumber = $data['accountNumber'];
        $this->accountName = $data['accountName'];
        $this->bankName = $data['bankName'];
        $this->bankCode = $data['bankCode'];
        $this->reference = $data['reference'];
        $this->request_time = Carbon::now();
        $this->provider = Providers::CHAKRA->value;
        $this->status = TransactionStatus::PENDING->value;
        $this->save();

        return $this;
    }

    public function saveInFlowCrustRequest($data, $request) 
    {
        $this->customerId = $request->customerId;
        $this->request_amount = $request->amount;
        $this->callback_url = $request->callbackUrl;
        // $this->received_amount = $data['received_amount'];
        $this->accountNumber = $data['accountNumber'];
        $this->accountName = "";
        $this->bankName = $data['bankName'];
        $this->bankCode = '';
        $this->reference = $data['transactionNumber'];
        $this->request_time = Carbon::now();
        $this->provider = Providers::CRUST->value;
        $this->status = TransactionStatus::PENDING->value;
        $this->save();

        return $this;
    }

    public function updateFromCallBackForFailedTransaction($fromDb, $request) 
    {
        $fromDb->action = $request->action;
        $fromDb->received_amount = $request->creditAmount;
        $fromDb->fee = $request->fee;
        $fromDb->narration = $request->narration;
        $fromDb->srcAccountName = $request->srcAccountName;
        $fromDb->srcAccountNumber = $request->srcAccountNumber;
        $fromDb->srcBankCode = $request->srcBankCode;
        $fromDb->srcBankName = $request->srcBankName;
        $fromDb->success = $request->success;
        $fromDb->transactionId = $request->transactionId;
        $fromDb->walletAccountNumber = $request->walletAccountNumber;
        $fromDb->time_of_verification = Carbon::now();
        $fromDb->status = TransactionStatus::FAILED->value;
        $fromDb->save();
    }

    public function updateFromCallBackForSuccessfulTransaction($fromDb, $request)
    {
        $fromDb->action = $request->action;
        $fromDb->received_amount = $request->creditAmount;
        $fromDb->fee = $request->fee;
        $fromDb->narration = $request->narration;
        $fromDb->srcAccountName = $request->srcAccountName;
        $fromDb->srcAccountNumber = $request->srcAccountNumber;
        $fromDb->srcBankCode = $request->srcBankCode;
        $fromDb->srcBankName = $request->srcBankName;
        $fromDb->success = $request->success;
        $fromDb->transactionId = $request->transactionId;
        $fromDb->walletAccountNumber = $request->walletAccountNumber;
        $fromDb->time_of_verification = Carbon::now();
        $fromDb->status = TransactionStatus::COMPLETED->value;
        $fromDb->save();
    }

    public function updateFromCallBackForSuccessfulCrustTransaction($fromDb, $request)
    {
        $fromDb->received_amount = $request->amount;
        $fromDb->walletAccountNumber = $request->accountNumber;
        $fromDb->time_of_verification = Carbon::now();
        $fromDb->status = TransactionStatus::COMPLETED->value;
        $fromDb->save(); 
    }
    public function updateFromCallBackForFailedCrustTransaction($fromDb,$request) 
    {
        $fromDb->received_amount = $request->amount;
        $fromDb->walletAccountNumber = $request->accountNumber;
        $fromDb->time_of_verification = Carbon::now();
        $fromDb->status = TransactionStatus::FAILED->value;
        $fromDb->save();
    }

    public function saveResponse($response, $fromDb)
    {
        $fromDb->application_response = $response;
        $fromDb->save();
              
    }
}
