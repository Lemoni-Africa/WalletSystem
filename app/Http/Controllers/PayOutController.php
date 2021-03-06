<?php

namespace App\Http\Controllers;

use App\Contract\Responses\DefaultApiResponse;
use App\Http\Requests\EnquiryRequest;
use App\Http\Requests\QuickPayoutRequest;
use App\Http\Requests\ResolveBankNameRequest;
use App\Http\Requests\TransactionListCrustRequest;
use App\Http\Resources\ChakraPayoutCallBackRequest;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Providers;
use TransactionStatus;

class PayOutController extends Controller
{
    private $baseUrl;
    private $baseCrustUrl;
    private $callBackSecret;
    private $response;
    private $provider;
    private $env;
    private $baseNumeroUrl;
    public function __construct()
    {
        $this->baseUrl = env('BASE_URL');
        $this->baseCrustUrl = env('CRUST_BASE_URL');
        $this->callBackSecret = env('CALL_BACK_SECRET');
        $this->provider = env('PROVIDER');
        $this->env = env('BANK_ENV');
        $this->baseNumeroUrl = env('NUMERO_BASE_URL');
        $this->response = new DefaultApiResponse();
    }

    public function quickPay(QuickPayoutRequest $request)
    {
        switch ($this->provider) {
            case 'CHAKRA':
                try {
                    // Log::info();
                    if ($this->env === 'TEST')
                    {
                        $request->beneficiaryBankCode = "058";
                        $request->beneficiaryAccountNumber = "0125594645";
                    }
                    $amount = (double)$request->amount;
                    $amount = round($amount, 2);
                    $request->amount = strval($amount);
                    Log::info('amount convert to double and rounded ' . $amount);
                    // $amountArray = explode('.',$amount);
                    // if ($amountArray[1] == '00') {
                    //     $amount = $amountArray[0];
                    //     Log::info($amount);
                    // }
                    Log::info('**********PayOut from Chakra service *************');
                    Log::info($request->all());

                    // $response = [
                    //     'isSuccess' =>  false,
                    //     'responseCode' => null,
                    //     'data'=> null,
                    //     'message' => null,
                    // ];
                    Log::info('Test data ' . $request->beneficiaryBankCode);
                    Log::info('Test data ' . $request->beneficiaryAccountNumber);
                    $data = chakraPayOut($request, $this->baseUrl);
                    Log::info('data gotten' .$data);
                    if ($data['responseCode'] == "00") {
                        $payout = new Payout();
                        $result = $payout->AddPayOut($data, $request);
                        Log::info(json_encode($result));
                        $this->response->responseCode = '0';
                        $this->response->message = "Transfer Successful";
                        $this->response->isSuccessful = true;
                        $this->response->data = [
                            'transactionRef' => $data['data']['merchantReference']
                        ];
                        Log::info('response gotten ' .json_encode($this->response));
                        return response()->json($this->response, 200);

                    }
                    $this->response->responseCode = '2';
                    $this->response->message = "Withrawal failed, try again later";
                    // $response['responseCode'] = '2';
                    // $response['message'] = "Withrawal failed, try again later";
                    // $response['isSuccess'] = false;
                    Log::info('response gotten ' .json_encode($this->response));
                    return response()->json($this->response, 400);

                } catch (\Exception $e) {
                    Log::info(json_encode($e));
                    $this->response->message = 'Processing Failed, Contact Support';
                    $this->response->error = $e->getMessage();
                    Log::info('response gotten ' .json_encode($this->response));
                    return response()->json($this->response, 500);
                }
                break;
            case 'NUMERO':
                try {
                    Log::info('**********PayOut from Numero service *************');
                    Log::info($request->all());
                    $data = numeroPayOut($request, $this->baseNumeroUrl);
                    $payout = new Payout();
                    Log::info('***** data gotten ****' .$data);
                    if ($data['status']) {
                        Log::info('Payout was successful');
                        $details = numeroValidateAccount($request, $this->baseNumeroUrl);
                        $result = $payout->AddPayOutNumero($data, $request, $details);
                        $this->response->responseCode = '0';
                        $this->response->message = $data['message'];
                        $this->response->isSuccessful = true;
                        $this->response->data = $data['data'];
                        Log::info('response gotten ' .json_encode($this->response));
                        return response()->json($this->response, 200);
                    }
                    Log::info('Payout was unsuccessful');
                    $details = numeroValidateAccount($request, $this->baseNumeroUrl);
                    $result = $payout->AddFailedPayOutNumero($data, $request, $details);
                    $this->response->responseCode = '1';
                    $this->response->message = $data['message'];
                    $this->response->isSuccessful = false;
                    $this->response->data = $data['data'];
                    Log::info('response gotten ' .json_encode($this->response));
                    return response()->json($this->response, 400);
                } catch (\Exception $e) {
                    $this->response->message = 'Processing Failed, Contact Support';
                    Log::info(json_encode($e));
                    $this->response->error = $e->getMessage();
                    Log::info('response gotten ' .json_encode($this->response));
                    return response()->json($this->response, 500);
                }
                break;
            case 'CRUST':
                try {
                    // if ($this->env === 'TEST')
                    // {
                    //     $request->beneficiaryBankCode = "058";
                    //     $request->beneficiaryAccountNumber = "0125594645";
                    // }
                    Log::info('**********PayOut from Crust service *************');
                    Log::info($request->all());
                    $data = crustPayout($request, $this->baseCrustUrl);
                    Log::info('response gotten ' . $data);
                    if ($data['message'] === "Invalid Bank")
                    {
                        $this->response->responseCode = '1';
                        $this->response->message = "Invalid Account Name";
                        $this->response->isSuccessful = false;
                        Log::info('response gotten ' .json_encode($this->response));
                        return response()->json($this->response, 400);
                    }
                    $payout = new Payout();
                    Log::info('***** data gotten ****' . $data);
                    if ($data['success'] && $data['data']['status']=="Successful") {
                        Log::info('Payout was successful');
                        $details = validateAccountName($request, $this->baseCrustUrl);
                        $result = $payout->AddPayOutCrust($data, $request, $details);
                        $this->response->responseCode = '0';
                        $this->response->message = "Transfer Successful";
                        $this->response->isSuccessful = true;
                        $this->response->data = [
                            'transactionRef' => $data['data']['transactionNumber']
                        ];
                        // $response['data'] = [
                        //     'transactionRef' => $data['data']['transactionNumber']
                        // ];
                        Log::info('response gotten ' .json_encode($this->response));
                        return response()->json($this->response, 200);
                    }
                    Log::info('Payout was unsuccessful');
                    $details = validateAccountName($request, $this->baseCrustUrl);
                    $result = $payout->AddFailedPayOutCrust($data, $request, $details);
                    $this->response->responseCode = '1';
                    $this->response->message = "Withrawal failed, try again later";
                    $this->response->isSuccessful = false;
                    // $this->response->data = $data['data'];
                    Log::info('response gotten ' .json_encode($this->response));
                    return response()->json($this->response, 400);
                } catch (\Exception $e)  {
                    $this->response->message = 'Processing Failed, Contact Support';
                    Log::info(json_encode($e));
                    $this->response->error = $e->getMessage();
                    Log::info('response gotten ' .json_encode($this->response));
                    return response()->json($this->response, 500);
                }
                break;
            default:
                break;
        }


    }


    public function checkStatus($transactionId)
    {
        try {
            $base64Cred = base64ChakraCred();
            $header = createHeaders();
            $baseUrl = env('BASE_URL');
            $url = "{$this->baseUrl}/payout-default/get-payout-status?chakra-credentials={$base64Cred}&transactionId={$transactionId}";


            $data = Http::withHeaders([
                'Authorization' => $header[0]
            ])->get($url);
            if ($data['responseCode'] == "00") {
                // $response['responseCode'] = '0';
                // $response['message'] = $data['responseMessage'];
                // $response['isSuccess'] = true;
                // $response['data'] = $data['data'];

                // return response()->json($response, 200);
                return $data;
            }
            $response['responseCode'] = '2';
            $response['message'] = $data['responseMessage'];
            $response['isSuccess'] = true;
            return response()->json($response, 200);

        } catch (\Exception $e) {
            return response([
                'isSuccesful' => false,
                'message' => 'Processing Failed, Contact Support',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function payOutStatus($transactionId)
    {
        try {
            $response = [
                'isSuccess' =>  false,
                'responseCode' => null,
                'data'=> null,
                'message' => null,
            ];
            $data = getStatus($this->baseUrl, $transactionId);
            Log::info($data);
            if ($data['responseCode'] == "00") {
                $response['responseCode'] = '0';
                $response['message'] = $data['responseMessage'];
                $response['isSuccess'] = true;
                $response['data'] = $data['data'];

                return response()->json($response, 200);
            }
            $response['responseCode'] = '2';
            $response['message'] = $data['responseMessage'];
            $response['isSuccess'] = false;
            return response()->json($response, 400);

        } catch (\Exception $e) {
            return response([
                'isSuccesful' => false,
                'message' => 'Processing Failed, Contact Support',
                'error' => $e->getMessage()
            ]);
        }
    }





    public function quickPayCrust(QuickPayoutRequest $request)
    {
        try {
            $data = crustPayout($request, $this->baseCrustUrl);
            $accDetails = $this->getAccountDetails($request->beneficiaryBankCode, $request->beneficiaryAccountNumber);
            Log::info($data);
        } catch (\Exception $e) {
            return response([
                'isSuccesful' => false,
                'message' => 'Processing Failed, Contact Support',
                'error' => $e->getMessage()
            ], 500);
        }

    }



    private function getAccountDetails($bankCode, $accountNumber)
    {
        return $data = getAccountName2($bankCode, $accountNumber, $this->baseCrustUrl);
        Log::info($data);
        // return $data['account_name'];
    }

    public function getTransactionList(TransactionListCrustRequest $request)
    {
        try {
            $page = $request->query('page');
            $per_page = $request->query('per_page');

            $data = getTransactionList($this->baseCrustUrl, $page, $per_page);
            Log::info($data);
            if ($data['success']) {
                $this->response->responseCode = '0';
                $this->response->message = $data['message'];
                $this->response->isSuccessful = true;
                $this->response->data = $data['data'];
                return response()->json($this->response, 200);
            }
            $this->response->responseCode = '2';
            $this->response->message = $data['message'];
            $this->response->isSuccessful = true;
            return response()->json($this->response, 400);
        } catch (\Exception $e) {
            $this->response->message = 'Processing Failed, Contact Support';
            Log::info(json_encode($e));
            $this->response->error = $e->getMessage();
            return response()->json($this->response, 500);
        }

    }

    public function numeroPayOut(QuickPayoutRequest $request)
    {
        try {
            $response = [
                'isSuccess' =>  false,
                'responseCode' => null,
                'data'=> null,
                'message' => null,
            ];



            $data = numeroPayOut($request, $this->baseNumeroUrl);
            if ($data['status']) {
               return Log::info($data);

                $payout = new Payout();
                $result = $payout->AddPayOut($data, $request);
                return Log::info(json_encode($result));
                // sleep(25);
                $getStatus = $this->checkStatus($result->transactionId);
                if ($getStatus['data']['transferStatus'] === "SUCCESSFUL") {
                    // $payout->UpdateSuccessfulPayOut($getStatus);
                    $response['responseCode'] = '0';
                    $response['message'] = $getStatus['data']['creditProcessedStatus'];
                    $response['isSuccess'] = true;
                    $response['data'] = [
                        'transactionRef' => $data['data']['merchantReference']
                    ];

                    return response()->json($response, 200);
                }
                Log::info($getStatus);
                // $payout->UpdateFailedPayOut($getStatus);
                $response['responseCode'] = '1';
                $response['message'] =   $getStatus['data']["creditProcessedStatus"];
                $response['isSuccess'] = false;
                $response['data'] = [
                    'transactionRef' => $data['data']['merchantReference']
                ];
                return response()->json($response, 200);

            }
            $response['responseCode'] = '2';
            $response['message'] = $data['responseMessage'];
            $response['isSuccess'] = false;
            return response()->json($response, 400);

        } catch (\Exception $e) {
            return response([
                'isSuccesful' => false,
                'message' => 'Processing Failed, Contact Support',
                'error' => $e->getMessage()
            ]);
        }
    }


    public function test(QuickPayoutRequest $request)
    {
        // Log::info($auth);
        $token = env('token');
        $body = [
            "customerId" => $request->customerId,
            "amount" => $request->amount,
            "beneficiaryBankCode" => $request->beneficiaryBankCode,
            "beneficiaryAccountNumber" => $request->beneficiaryAccountNumber,
            "reversalUrl" => $request->reversalUrl,
        ];
        $data = Http::withHeaders([
            'Content-Type' => 'application/json',
            'accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post('https://apigateway.myapiservices.net/lemoni-txn/api/payout/quick', $body);
        Log::info($data);
        return $data;
    }
}
