<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Providers;

class MerchantCred extends Model
{
    use HasFactory;
    public function AddMerchCred($data) 
    {  
        $this->merchantId = $data['clientId'];
        $this->apiKey = $data['apiKey'];
        $this->provider = Providers::CHAKRA->value;
        $this->save();

        return $this;
    }

    public function updateCred($fromDb, $data)
    {
        $fromDb->apiKey = $data['apiKey'];
        $fromDb->save();
    }
}
