<?php

namespace App\Models;

use gender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        $this->save();
    }
}
