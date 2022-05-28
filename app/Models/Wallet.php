<?php

namespace App\Models;

use Carbon\Carbon;
use gender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Providers;

class Wallet extends Model
{
    use HasFactory;
    public function __construct()
    {

    }

    public function AddWallet($data, $request, $pin) 
    {
        $this->requestId = $data['requestId'];
        $this->merchantId = $data['data']['merchantId'];
        $this->firstName = $data['data']['firstName'];
        $this->lastName = $data['data']['lastName'];
        $this->middleName = $data['data']['middleName'];
        $this->fullName = $data['data']['fullName'];
        $this->email = $data['data']['email'];
        if ($request->gender == "1") {
            $this->gender = gender::MALE->value;
        }
        else {
            $this->gender = gender::FEMALE->value;
        }
        $this->dob = $request->dob;
        $this->pin = $pin;
        // $this->gender = $data['data']['gender'];
        $this->phoneNumber = $request->phoneNumber;
        $this->currency = $data['data']['currency'];
        $this->bvnVerified = $data['data']['bvnVerified'];
        $this->ninVerified = $data['data']['ninVerified'];
        $this->bankName = $data['data']['bankName'];
        $this->bankCode = $data['data']['bankCode'];
        $this->dateCreated = $data['data']['dateCreated'];
        $this->lastActivityDate = $data['data']['lastActivityDate'];
        $this->provider = Providers::CHAKRA->value;
        $this->save();
    }

    public function AddWalletNumero($data, $request) 
    {
        $this->bvn = $request->bvn;
        $this->currency = 'NGN';
        $this->bankCode = $request->bankCode;
        $this->dateCreated = Carbon::now();
        $this->provider = Providers::NUMERO->value;
        $this->accountNumber = $data['account_number'];
        $this->accountName = $data['account_name'];
        $this->save();
    }
}
