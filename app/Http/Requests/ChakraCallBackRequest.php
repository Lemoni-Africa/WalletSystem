<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChakraCallBackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'action' => 'required',
            'amount' => 'required',
            'fee' => 'required',
            'narration' => 'required',
            'reference' => 'required',
            'srcAccountName' => 'required',
            'srcAccountNumber' => 'required',
            'srcBankCode' => 'required',
            'srcBankName' => 'required',
            'success' => 'required',
            'transactionId' => 'required',
            'walletAccountNumber' => 'required',

        ];
    }
}

// {
//     // "action": "collection",
//     // "amount": "10000.00",
//     // "fee": "00.00000",
//     // "narration": "TGIF",
//     // "reference": "28c69884-fb0e-4717-98f6-a84727517a77",
//     // "srcAccountName": "Ray",
//     // "srcAccountNumber": "0000000000",
//     // "srcBankCode": "058",
//     // "srcBankName": "GTBank",
//     // "success": true,
//     "transactionId": "12345678901234567891212001234",
//     "walletAccountNumber": "0000000000"
// }
