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
    public function __construct()
    {
        $this->baseUrl = env('BASE_URL');
        $this->baseCrustUrl = env('CRUST_BASE_URL');
        $this->callBackSecret = env('CALL_BACK_SECRET');
        $this->response = new DefaultApiResponse();
    }

    public function quickPay(QuickPayoutRequest $request)
    {
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


    public function getAccountName(ResolveBankNameRequest $request)
    {
        try {
            $response = [
                'isSuccess' =>  false,
                'responseCode' => null,
                'data'=> null,
                'message' => null,
            ];
            $data = getAccountName($request, $this->baseCrustUrl);
            Log::info($data);
            if ($data['success']) {
                $response['responseCode'] = '0';
                $response['message'] = $data['message'];
                $response['isSuccess'] = true;
                $response['data'] = $data['data'];

                return response()->json($response, 200);
            }
            $response['responseCode'] = '1';
            $response['message'] = $data['message'];
            $response['isSuccess'] = false;
            $response['data'] = $data['data'];

            return response()->json($response, 400);
        } catch (\Exception $e) {
            return response([
                'isSuccesful' => false,
                'message' => 'Processing Failed, Contact Support',
                'error' => $e->getMessage()
            ]);
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
    // public function payoutCallBack(ChakraPayoutCallBackRequest $request)
    // {
    //     try {
    //         Log::info($request->all());
    //         if ($request->hasHeader('x-payout-signature')) {
    //             $payoutSignature = $request->header('x-payout-signature');
    //             $request->headers->set('Content-Type', 'application/json');
    //             // $jsonEncodedPayload = json_encode($request->all());
    //             $hashedPayload = hash_hmac("sha512", json_encode($request->all()) , $this->callBackSecret);
    //             Log::info($hashedPayload);
    //             if($hashedPayload != $payoutSignature)
    //             {
    //                 $this->response->responseCode = '1';
    //                 $this->response->message = "Invalid Signature";                    
    //                 return response()->json($this->response, 401);
    //             }else{
                    
    //                 $inflow = new Inflow();
    //                 $fromDb = findInFlowbyReference($request->reference,$request->walletAccountNumber);
    //                 if (!empty($fromDb)) {
    //                     $response = postToIndians($request, $fromDb['customerId'], $fromDb['callback_url']);
    //                     Log::info('************response from application************' .  $response);
    //                     $inflow->saveResponse($request, $response);
    //                     if ($response->successful())
    //                     {
    //                     if ($request->success) {
    //                         //update DB to be successful
    //                         $inflow->updateFromCallBackForSuccessfulTransaction($request);
    //                         return  response([
    //                             'responseCode' => "00",
    //                             'responseMessage' => "Callback received"
    //                         ], 200);
    //                         } else {
    //                             $inflow->updateFromCallBackForFailedTransaction($request);
        
    //                             return  response([
    //                                 'responseCode' => "00",
    //                                 'responseMessage' => "Callback received"
    //                             ], 200);
    //                         }
    //                     }
                        
    //                 } else {
    //                     return  response([
    //                         'responseCode' => "1",
    //                         'responseMessage' => "Not Found"
    //                     ], 400);
    //                 } 
    //             }             
    //         }else{
    //             $this->response->responseCode = '1';
    //             $this->response->message = "Invalid Signature";                    
    //             return response()->json($this->response, 401);
    //         }
            
            
    //     } catch (\Exception $e) {
    //         $this->response->message = 'Processing Failed, Contact Support';
    //         Log::info(json_encode($e));
    //         $this->response->error = $e->getMessage();
    //         return response()->json($this->response, 500);
    //     }
    
    // }

}
