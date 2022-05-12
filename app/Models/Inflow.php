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

    public function updateFromCallBackForFailedTransaction($request) 
    {
        $updateDetails = [
            'action' => $request->action,
            'received_amount' => $request->amount,
            'fee' => $request->fee,
            'narration' => $request->narration,
            'srcAccountName' => $request->srcAccountName,
            'srcAccountNumber' => $request->srcAccountNumber,
            'srcBankCode' => $request->srcBankCode,
            'srcBankName' => $request->srcBankName,
            'success' => $request->success,
            'transactionId' => $request->transactionId,
            'walletAccountNumber' => $request->walletAccountNumber,
            'time_of_verification' => Carbon::now(),
            'status' => TransactionStatus::FAILED->value,
        ];
       DB::table('inflows')->where('reference', $request->reference)->update($updateDetails);
    }

    public function updateFromCallBackForSuccessfulTransaction($request)
    {
        $updateDetails = [
            'action' => $request->action,
            'received_amount' => $request->amount,
            'fee' => $request->fee,
            'narration' => $request->narration,
            'srcAccountName' => $request->srcAccountName,
            'srcAccountNumber' => $request->srcAccountNumber,
            'srcBankCode' => $request->srcBankCode,
            'srcBankName' => $request->srcBankName,
            'success' => $request->success,
            'transactionId' => $request->transactionId,
            'walletAccountNumber' => $request->walletAccountNumber,
            'time_of_verification' => Carbon::now(),
            'status' => TransactionStatus::COMPLETED->value,
        ];
       DB::table('inflows')->where('reference', $request->reference)->update($updateDetails);
              
    }
}
