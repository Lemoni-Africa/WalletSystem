<?php

namespace App\Contract\Responses;

class DefaultApiResponse {
    public $isSuccessful = false;
    public $responseCode;
    public $data;
    public $message;
    public $error;
}