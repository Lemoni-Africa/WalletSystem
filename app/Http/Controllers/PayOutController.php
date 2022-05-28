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
    private $baseNumeroUrl; 
    public function __construct()
    {
        $this->baseUrl = env('BASE_URL');
        $this->baseCrustUrl = env('CRUST_BASE_URL');
        $this->callBackSecret = env('CALL_BACK_SECRET');
        $this->provider = env('PROVIDER');
        $this->baseNumeroUrl = env('NUMERO_BASE_URL');
        $this->response = new DefaultApiResponse();
    }

    public function quickPay(QuickPayoutRequest $request)
    {
        switch ($this->provider) {
            case 'CHAKRA':
                try {
                    $response = [
                        'isSuccess' =>  false,
                        'responseCode' => null,
                        'data'=> null,
                        'message' => null,
                    ];
                    $data = chakraPayOut($request, $this->baseUrl);
                    if ($data['responseCode'] == "00") {
                       
        
                        $payout = new Payout();
                        $result = $payout->AddPayOut($data);
                        Log::info(json_encode($result));
                        sleep(30);
                        $getStatus = $this->checkStatus($result->transactionId);
                        Log::info($getStatus);
                        if ($getStatus['data']['transferStatus'] === "SUCCESSFUL") {
                            $payout->UpdateSuccessfulPayOut($getStatus);
                            $response['responseCode'] = '0';
                            $response['message'] = $getStatus['data']['creditProcessedStatus'];
                            $response['isSuccess'] = true;
                            $response['data'] = [
                                'transactionRef' => $data['data']['merchantReference']
                            ];
                            
                            return response()->json($response, 200);
                        }
                        Log::info($getStatus);
                        $payout->UpdateFailedPayOut($getStatus);
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
                break;
            case 'NUMERO':
                try {
                    $data = numeroPayOut($request, $this->baseNumeroUrl);
                    if ($data['status']) {
                        $details = numeroValidateAccount($request, $this->baseNumeroUrl);
                        $payout = new Payout();
                        $result = $payout->AddPayOutNumero($data, $request, $details);
                        $this->response->responseCode = '0';
                        $this->response->message = $data['message'];
                        $this->response->isSuccessful = true;
                        $this->response->data = $data['data'];
                        return response()->json($this->response, 200);
                    }
                } catch (\Exception $e) {
                    $this->response->message = 'Processing Failed, Contact Support';
                    Log::info(json_encode($e));
                    $this->response->error = $e->getMessage();
                    return response()->json($this->response, 500);
                }
                break;
            default:
                # code...
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
                $result = $payout->AddPayOut($data);
                Log::info(json_encode($result));
                sleep(25);
                $getStatus = $this->checkStatus($result->transactionId);
                if ($getStatus['data']['transferStatus'] === "SUCCESSFUL") {
                    $payout->UpdateSuccessfulPayOut($getStatus);
                    $response['responseCode'] = '0';
                    $response['message'] = $getStatus['data']['creditProcessedStatus'];
                    $response['isSuccess'] = true;
                    $response['data'] = [
                        'transactionRef' => $data['data']['merchantReference']
                    ];
                    
                    return response()->json($response, 200);
                }
                Log::info($getStatus);
                $payout->UpdateFailedPayOut($getStatus);
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

}
