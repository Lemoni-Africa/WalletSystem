<?php

use App\Models\Inflow;
use App\Models\MerchantCred;
use App\Models\Payout;
use App\Models\Wallet;
use Carbon\Carbon;
use Faker\Factory;
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
    try {
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
    
    } catch (\Exception $e) {
        Log::info(json_encode($e));
        return response([
            'isSuccesful' => false,
            'message' => 'Processing Failed, Contact Support',
            // 'error' => $e->getMessage()
        ]);
    }
    
}

function getToken (){
    $baseUrl = env('BASE_URL');
    $url = "{$baseUrl}/credentials/get-token";
    $merchantId = env('MERCHANT_ID');
    $apiKey = getApiKeyChakra($merchantId);
    $body = [
        'merchantId' => $merchantId,
        'apiKey' => $apiKey,
    ];
    Log::info($body);
    $response = postJsonRequest($url, $body);
    $token = $response['data']['accessToken'];
    Cache::put('token', $token , now()->addMinutes(20));

    return $token;
}

function base64ChakraCred()
{
    $merchantId = env('MERCHANT_ID');
    $apiKey = getApiKeyChakra($merchantId);
    $base_string = base64_encode("{$merchantId}" . ":" . "{$apiKey}"); //for base64 encoding
    Log::info($base_string);
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
    Log::info($auth);
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
        // Log::info($data);
    return $data;
}
function chakraPayOut($request, $baseUrl)
{
    $base64Cred = base64ChakraCred();
    $header = createHeaders();
    $merchantRef = generateMerchantRef();
    $url = "{$baseUrl}/payout-default/quick-pay?chakra-credentials={$base64Cred}";
    //check if wallet is on db
    // $walletFromDb = Wallet::where('email', $request->sender)->first();
    $walletFromDb = Wallet::inRandomOrder()->first();
    Log::info('random email found'  .  $walletFromDb->email);
    if(!empty($walletFromDb)){
        $pin = decryptPin($walletFromDb['pin']);
    }
    else {
        $pin = '1234';
    }
    // get the password and decrpty
    $body = [
        'merchantRef' => $merchantRef,
        'sender' => $walletFromDb->email,
        'amount' => $request->amount,
        'narration' => "Transaction " . $merchantRef,
        'pin' => $pin,
        'beneficiaryBankCode' => $request->beneficiaryBankCode,
        'beneficiaryAccountNumber' => $request->beneficiaryAccountNumber,
    ];
    return httpPostRequest($url, $body, $header);
    
}

function numeroPayOut($request, $baseUrl)
{
    $merchantRef = generateMerchantRef();
    $url = "{$baseUrl}/transfer/initiateTransfer";
    $header = 'Bearer ' .env('AUTH_KEY_NUMERO');
    $validatedAccountNumber = numeroValidateAccount($request, $baseUrl);
    $body = [
        'amount' => $request->amount,
        'currency' => env('CURRENCY'),
        'narration' => "Transaction " . $merchantRef,
        'debitAccount' => env('DEBIT_ACCOUNT_NUMERO'),
        'transactionReference' => $merchantRef,
        'recipientBankCode' => $request->beneficiaryBankCode,
        'recipientAccountNumber' => $request->beneficiaryAccountNumber,
        // "accountName" => "Adebayo Taju",
        "accountName" => $validatedAccountNumber['data']['account_name'],
        "originatorName" => env('ORIGINATOR_NAME_NUMERO')
    ];
    Log::info($body);
    Log::info($validatedAccountNumber);
    return httpPostRequest2($url, $body, $header);
}

function numeroValidateAccount($request, $baseUrl)
{
    $url = "{$baseUrl}/transfer/validateAccountNumber";
    $header = 'Bearer ' .env('AUTH_KEY_NUMERO');
    // get the password and decrpty
    $body = [
        'accountNumber' => $request->beneficiaryAccountNumber,
        'bankCode' => $request->beneficiaryBankCode,
    ];
    // Log::info(httpPostRequest2($url, $body, $header));
    return httpPostRequest2($url, $body, $header);
}

function numeroCreateAccount($request, $baseUrl)
{
    if ($request->accountType == "One time") {
        $url = "{$baseUrl}/account/assignDynamicAccount";
        $header = 'Bearer ' .env('AUTH_KEY_NUMERO');
        // get the password and decrpty
        $body = [
            'accountName' => $request->accountName,
            'bvn' => $request->bvn,
            'accountUserNatureOfBusiness' => $request->accountUserNatureOfBusiness,
            'bankCode' => $request->bankCode,
            'accountType' => $request->accountType,
        ];
        // Log::info(httpPostRequest2($url, $body, $header));
        return httpPostRequest2($url, $body, $header);
    }
    if ($request->accountType == "Reserved") {
        $url = "{$baseUrl}/account/assignReservedAccount";
        $header = 'Bearer ' .env('AUTH_KEY_NUMERO');
        // get the password and decrpty
        $body = [
            'accountName' => $request->accountName,
            'bvn' => $request->bvn,
            'accountUserNatureOfBusiness' => $request->accountUserNatureOfBusiness,
            'bankCode' => $request->bankCode,
            'accountType' => $request->accountType,
        ];
        // Log::info(httpPostRequest2($url, $body, $header));
        return httpPostRequest2($url, $body, $header);
    }
    
}



function crustPayout($request, $baseUrl)
{
    $data = validateAccountName($request, $baseUrl);
    if ($data['success']) {
        $name = $data['data']['account_name'];
        $header = env('CRUST_HEADER');
        $url = "{$baseUrl}/api/debit";
        $body = [
            'accountName' => $name,
            'amount' => $request->amount,
            'narration' => "Sent " . $request->amount . " to " . $request->beneficiaryAccountNumber,
            'bankCode' => $request->beneficiaryBankCode,
            'accountNumber' => $request->beneficiaryAccountNumber,
        ];
        Log::info($body);
    }
   
    return httpPostRequest2($url, $body, $header);
}


function validateAccountName($request, $baseUrl)
{
    $header = env('CRUST_HEADER');
    $url = "{$baseUrl}/api/resolve-account-name";
    $body = [
        'accountnumber' => $request->beneficiaryAccountNumber,
        'bankcode' => $request->beneficiaryBankCode
    ];
    return httpPostRequest2($url, $body, $header);
}
// {
//     "accountName": "Greg Okenyi Omebije",
//     "accountNumber": "0125594645",
//     "amount": 25,
//     "bankCode": "058",
//     "narration": "Some payment"
//   }

//   {
//     "sender": "ray@lemoniafrica.com",
//     "amount": "10.5",
//     "narration": "Pay Gbedu",
//     "beneficiaryBankCode": "058",
//     "beneficiaryAccountNumber": "0125594645"
// }

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

function getAccountNameNumero($request, $baseUrl)
{
    $url = "{$baseUrl}/transfer/validateAccountNumber";
    $header = 'Bearer ' .env('AUTH_KEY_NUMERO');
    $body = [
        'accountNumber' => $request->accountnumber,
        'bankCode' => $request->bankcode
    ];
    Log::info($body);
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

function walletCreationChakra($baseUrl, $pin)
{
    $base64Cred = base64ChakraCred();
    $header = createHeaders();
    $url = "{$baseUrl}/agent-wallet/create-wallet?chakra-credentials={$base64Cred}";
    $faker = Factory::create();
    
    $body = [
        'firstName' => "Rayasom",
        'lastName' => "Services",
        'middleName' => " ",
        'phoneNumber' => $faker->randomElement(['080', '081','090','070', '091', '071']) . $faker->numberBetween(62345678,97866898),
        'email' => $faker->email,
        'walletType' => "3",
        'channel' => "3",
        'gender' => $faker->randomElement(['1', '2']),
        'dob' =>  $faker->dateTimeBetween('1980-01-01', '2002-12-31')->format('Y-m-d'),
        'pin' => $pin,
        // 'date_of_birth' =>
    ];
    Log::info('body ' . json_encode($body));
    // storeWalletChakra()

    $result = httpPostRequest($url, $body, $header);
    if ($result['responseCode'] == "00") {
        Log::info('***********data from chakra ******  ' . $result);
        return array($body,$result);
    }
    
}

function storeWalletChakra($data, $request, $pin)
{
    // $pin = generateRandomString();
    $encryptedPin = encryptPin($pin);
    Log::info('************ save to database ***************');
    $wallet = new Wallet();
    Log::info('***********data******  '. $data);
    return $wallet->AddWalletChakra($data, $request, $encryptedPin);
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


function getBanksChakra($baseUrl)
{
    $base64Cred = base64ChakraCred();
    $header = createHeaders();
    Log::info($base64Cred);
    $url = "{$baseUrl}/payout-default/bank-list?chakra-credentials={$base64Cred}";

    
    return Http::withHeaders([
        'Authorization' => $header[0]
    ])->get($url);
}

function chakraCredReset($baseUrl, $request)
{
    $url = "{$baseUrl}/credentials/reset";
    $body = [
        'merchantId' => $request->merchantId,
    ];
    return Http::post($url, $body);
}


function accountEnquiryChakra($request, $baseUrl)
{
    $base64Cred = base64ChakraCred();
    $header = createHeaders();
    $url = "{$baseUrl}/payout-default/make-enquiry?chakra-credentials={$base64Cred}";
    $body = [
        'bankCode' => $request->bankcode,
        'accountNumber' => $request->accountnumber
    ];

    return httpPostRequest($url, $body, $header);
}

function getApiKeyChakra($merchId)
{
    $data = MerchantCred::where('merchantId', $merchId)->first();
    return $data->apiKey;
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

function findPayoutByReference($reference,$beneficaryAccount)
{
    return Payout::where('merchantReference', $reference)->where('beneficiaryAccountNumber', $beneficaryAccount)->where('transactionStatus', TransactionStatus::PENDING->value)->first();
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
        'amount' => $request->creditAmount,
        'success' => $request->success
    ];
    Log::info('****************** Indian Body ********************');
    Log::info(json_encode($body));
    // Log::info($body);
    $hashedPayload = hash_hmac("sha512", json_encode($body) , $callBackSecret);
    Log::info($hashedPayload);
    return httpPostRequestCallback($url, $body, $hashedPayload);
}

// function depositCallback(Request $request)
// {   
//     if ($request->hasHeader('x-funding')) {
//         $payload = json_encode($request->all());
//         $signature = $request->header('x-funding');
//         $secret = env('secret');
//         $hashedPayload = compute_hash($payload , $secret);
//         if($hashedPayload != $signature)
//         {
//             'error'
//         }
//     }else {
//         'error';
//     }
// }


// function compute_hash($payload , $secret)
// {
//     $hashedPayload = hash_hmac("sha512", $payload , $secret);
//     return $hashedPayload;
// }

// // function hash_is_valid($payload , $secret, $verify)
// // {
// //     $computed_hash = compute_hash($payload , $secret);
// //     return $computed_hash;
// // }
 



function postToIndiansPayout($request, $id, $url)
{
    
    $callBackSecret = env('INDIAN_SECRET');
    $body = [
        'customerId' => $id,
        'reference' => $request->paymentRef,
        'amount' => $request->amount,
        'success' => $request->success
    ];
    Log::info('****************** Indian Body ********************');
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

function getBanksNumero($baseUrl)
{
    // $url = "{$baseUrl}/transfer/getBanks";
    $header = 'Bearer ' .env('AUTH_KEY_NUMERO');
    // $header = env('CRUST_HEADER');
    $url = "{$baseUrl}/transfer/getBanks";
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

function getTransactionList($baseUrl, $pageNumber, $perPage)
{
    $header = env('CRUST_HEADER');
    $url = "{$baseUrl}/api/transactions?page={$pageNumber}&per_page={$perPage}";
    return Http::withHeaders([
        'Authorization' => $header
    ])->get($url);
}
