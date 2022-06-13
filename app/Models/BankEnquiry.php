<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Providers;

class BankEnquiry extends Model
{
    use HasFactory;

    public function addAccountDetailsChakra($data) 
    {
        $this->account_number = $data['amount'];
        $this->account_name = $data['amount'];
        $this->provider = Providers::CHAKRA->value;
        $this->save();
        return $this;
    }
}
