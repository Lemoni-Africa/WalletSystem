<?php

use App\Models\Inflow;
use App\Models\Wallet;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

function createToken()
{

}

function postJsonRequest($url, $body)
{
    $headers = [
        'Content-Type' => 'application/json',
        'accept' => 'application/json'
        // 'x-api-key' => env('API_KEY'),
    ];
    $client = new Client([
        'headers' => $headers
    ]);
    $url = "{$url}";
    
    $response = $client->request('POST', $url, ['json' => $body] );
    $response = json_decode($response->getBody(), TRUE);
    // Log::info($response);
    return $response;
}

function curlCallRestApi($url, $headers, $jsonEncodedBody, $method){
    $curl = curl_init();
    if ($jsonEncodedBody == null){
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);
    }else{
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $jsonEncodedBody,
            CURLOPT_HTTPHEADER => $headers,
        ]);
    }
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function createHeaders(){
    $existingToken = Cache::get('token');
    if (empty($existingToken)) {
        $token = getToken();
        $headers = [];
        $headers[] = 'Bearer '.$token;
        return $headers;
    }
    $headers = [];
    $headers[] = 'Bearer '.$existingToken;
    return $headers;
}

function getToken (){
    $baseUrl = env('BASE_URL');
    $url = "{$baseUrl}/credentials/get-token";
    $merchantId = env('MERCHANT_ID');
    $apiKey = env('API_KEY');
    $body = [
        'merchantId' => $merchantId,
        'apiKey' => $apiKey,
    ];
    // $body2 = json_encode($body);
    Log::info($body);
    $response = postJsonRequest($url, $body);
    $token = $response['data']['accessToken'];
    Cache::put('token', $token , now()->addMinutes(20));

    return $token;
}

function base64ChakraCred()
{
    $merchantId = env('MERCHANT_ID');
    $apiKey = env('API_KEY');
    $base_string = base64_encode("{$merchantId}" . ":" . "{$apiKey}"); //for base64 encoding
    // Log::info($base_string);
    return $base_string;//for base64 decoding
}

function generateMerchantRef()
{
    return Str::uuid()->toString();
}


function postJsonRequest2($url, $body)
{
    $header = createHeaders();
    $headers = [
        'Content-Type' => 'application/json',
        'accept' => 'application/json',
        // 'x-api-key' => env('API_KEY'),
        'Authorization' => $header[0]
    ];
    $client = new Client([
        'headers' => $headers
    ]);
    $url = "{$url}";
    Log::info($headers);
    $response = $client->request('POST', $url, ['json' => $body] );
    $statusCode = $response->getStatusCode();
    // $data = json_decode($response->getBody(), TRUE);
    // Log::info($response);
    return $response;
}


function httpPostRequest($url, $body, $auth)
{
    $data = Http::withHeaders([
        'Content-Type' => 'application/json',
        'accept' => 'application/json',
        'Authorization' => $auth[0]
    ])->post($url, $body);

    return $data;
}

function httpPostRequest2($url, $body, $auth)
{
    $data = Http::withHeaders([
        'Content-Type' => 'application/json',
        'accept' => 'application/json',
        'Authorization' => $auth
    ])->post($url, $body);

    return $data;
}
function chakraPayOut($request, $baseUrl)
{
    $base64Cred = base64ChakraCred();
    $header = createHeaders();
    $merchantRef = generateMerchantRef();
    $url = "{$baseUrl}/payout-default/quick-pay?chakra-credentials={$base64Cred}";
    //check if wallet is on db
    $walletFromDb = Wallet::where('email', $request->sender)->first();
    if(!empty($walletFromDb)){
        $pin = decryptPin($walletFromDb['pin']);
    }
    else {
        $pin = '1234';
    }
    // get the password and decrpty
    $body = [
        'merchantRef' => $merchantRef,
        'sender' => $request->sender,
        'amount' => $request->amount,
        'narration' => "Sent " . $request->amount . " to " . $request->beneficiaryAccountNumber,
        'pin' => $pin,
        'beneficiaryBankCode' => $request->beneficiaryBankCode,
        'beneficiaryAccountNumber' => $request->beneficiaryAccountNumber,
    ];
    return httpPostRequest($url, $body, $header);
    
}


function crustPayout($request, $baseUrl)
{
    $header = env('CRUST_HEADER');
    $url = "{$baseUrl}/api/debit";
    $body = [
        'amount' => $request->amount,
        'narration' => $request->narration,
        'bankCode' => $request->beneficiaryBankCode,
        'accountNumber' => $request->beneficiaryAccountNumber,
    ];
    return httpPostRequest2($url, $body, $header);
}

function getAccountName($request, $baseUrl)
{
    $header = env('CRUST_HEADER');
    $url = "{$baseUrl}/api/resolve-account-name";
    $body = [
        'accountnumber' => $request->accountnumber,
        'bankcode' => $request->bankcode
    ];
    return httpPostRequest2($url, $body, $header);
}

function getAccountName2($bankCode, $accountNumber, $baseUrl)
{
    $header = env('CRUST_HEADER');
    $url = "{$baseUrl}/api/resolve-account-name";
    $body = [
        'accountnumber' => $accountNumber,
        'bankcode' => $bankCode
    ];
    return httpPostRequest2($url, $body, $header);
}

function getStatus($baseUrl, $transactionId)
{
    $base64Cred = base64ChakraCred();
    $header = createHeaders();
    $url = "{$baseUrl}/payout-default/get-payout-status?chakra-credentials={$base64Cred}&transactionId={$transactionId}";

    
    return Http::withHeaders([
        'Authorization' => $header[0]
    ])->get($url);
}



function createChakraWallet($request, $baseUrl, $pin)
{
    $base64Cred = base64ChakraCred();
    $header = createHeaders();
    $url = "{$baseUrl}/agent-wallet/create-wallet?chakra-credentials={$base64Cred}";
    $body = [
        'firstName' => $request->firstName,
        'lastName' => $request->lastName,
        'middleName' => $request->middleName,
        'phoneNumber' => $request->phoneNumber,
        'email' => $request->email,
        'walletType' => "3",
        'channel' => "3",
        'gender' => $request->gender,
        'dob' => $request->dob,
        'pin' => $pin,
    ];

    return httpPostRequest($url, $body, $header);
}

function generateRandomString($length = 4) {
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

function encryptPin($pin)
{
    // return Crypt::encryptString($pin);
    return encrypt($pin);
}

function decryptPin($pin)
{
    // return Crypt::encryptString($pin);
    return decrypt($pin);
}

function getMerchantPeer($baseUrl)
{
    $base64Cred = base64ChakraCred();
    $header = createHeaders();
    Log::info($base64Cred);
    $url = "{$baseUrl}/agent-wallet/get-merchant-peer?chakra-credentials={$base64Cred}";

    
    return Http::withHeaders([
        'Authorization' => $header[0]
    ])->get($url);
}

function getMerchantBalance($baseUrl)
{
    $base64Cred = base64ChakraCred();
    $header = createHeaders();
    $url = "{$baseUrl}/agent-wallet/get-merchant-balance?chakra-credentials={$base64Cred}";

    
    return Http::withHeaders([
        'Authorization' => $header[0]
    ])->get($url);
}



function findInFlowbyReference($reference,$walletNumber)
{
    return Inflow::where('reference', $reference)->where('accountNumber', $walletNumber)->where('status', TransactionStatus::PENDING->value)->first();
}

//find infiow here referece, wallter number, status pending

function findByRefernceAndCustomerId($reference, $customerId)
{
    return Inflow::where('reference', $reference)->where('customerId', $customerId)->first();
}


function httpPostRequestCallback($url, $body, $auth)
{
    $data = Http::withHeaders([
        'Content-Type' => 'application/json',
        'accept' => 'application/json',
        'x-funding' => $auth
    ])->post($url, $body);

    return $data;
}

function postToIndians($request, $id, $url)
{
    
    $callBackSecret = env('INDIAN_SECRET');
    $body = [
        'customerId' => $id,
        'reference' => $request->reference,
        'amount' => $request->amount,
        'success' => $request->success
    ];
    Log::info(json_encode($body));
    // Log::info($body);
    $hashedPayload = hash_hmac("sha512", json_encode($body) , $callBackSecret);
    Log::info($hashedPayload);
    return httpPostRequestCallback($url, $body, $hashedPayload);
}



function getBanks($baseUrl)
{
    $header = env('CRUST_HEADER');
    $url = "{$baseUrl}/api/bank-list";
    return Http::withHeaders([
        'Authorization' => $header
    ])->get($url);
}

function getBalance($baseUrl)
{
    $header = env('CRUST_HEADER');
    $url = "{$baseUrl}/api/balance";
    return Http::withHeaders([
        'Authorization' => $header
    ])->get($url);
}

function getAccounts($baseUrl)
{
    $header = env('CRUST_HEADER');
    $url = "{$baseUrl}/api/accounts";
    return Http::withHeaders([
        'Authorization' => $header
    ])->get($url);
}
