<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickPayoutRequest extends FormRequest
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
            'customerId' => 'required',
            'amount' => 'required',
            'beneficiaryBankCode' => 'required',
            'beneficiaryAccountNumber' => 'required',
            'reversalUrl' => 'required',
        ];
          
    }
}