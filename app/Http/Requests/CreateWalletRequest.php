<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWalletRequest extends FormRequest
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
            'firstName' => 'required',
            'lastName' => 'required',
            'middleName' => 'required',
            'phoneNumber' => 'required|string|unique:wallets,phoneNumber',
            // 'email' => 'required|string|unique:wallet,email',
            'email' => 'required|string|unique:wallets,email',
            'gender' => 'required',
            'dob' => 'required|date_format:Y-m-d',

        ];
    }
} 