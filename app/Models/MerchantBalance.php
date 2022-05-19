<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MerchantBalance extends Model
{
    use HasFactory;

    public function AddMerchBalance($data) 
    {
        $this->accountNumber = $data['accountNumber'];
        $this->bankName = $data['bankName'];
        $this->accountName = $data['accountName'];
        $this->previousAvailableBalance = strval($data['data']['previousAvailableBalance']);
        $this->availableBalance = strval($data['data']['availableBalance']);
        $this->bookedBalance = strval($data['data']['bookedBalance']);
        $this->save();

        return $this;
    }

    public function updateMerchBalance($data)
    {
        $updateDetails = [
            'previousAvailableBalance' => strval($data['data']['previousAvailableBalance']),
            'availableBalance' => strval($data['data']['availableBalance']),
            'bookedBalance' => strval($data['data']['bookedBalance']),
            'updated_at' => Carbon::now()
        ];
       DB::table('merchant_balances')->where('accountNumber', $data['accountNumber'])->update($updateDetails);
              
    }
}