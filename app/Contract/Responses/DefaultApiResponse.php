<?php

namespace App\Contract\Responses;

class DefaultApiResponse {
    public $isSuccess = false;
    public $responseCode;
    public $data;
    public $message;
    public $error;
}