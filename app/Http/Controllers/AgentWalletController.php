<?php

namespace App\Http\Controllers;

use App\Contract\Responses\DefaultApiResponse;
use App\Http\Requests\AlertRequest;
use App\Http\Requests\ChakraCallBackRequest;
use App\Http\Requests\CreateWalletRequest;
use App\Http\Requests\CrustCallBackRequest;
use App\Http\Requests\MerchantPayRequest;
use App\Http\Requests\NumeroAccountCreationRequest;
use App\Models\Inflow;
use App\Models\MerchantBalance;
use App\Models\Wallet;
use Carbon\Carbon;
use gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TransactionStatus;

class AgentWalletController extends Controller
{
    private $baseUrl;
    private $response;
    private $baseNumeroUrl;
    private $callBackSecret;
    private $provider;
    private $baseCrustUrl;
    public function __construct()
    {
        $this->baseUrl = env('BASE_URL');
        $this->callBackSecret = env('CALL_BACK_SECRET');
        $this->baseNumeroUrl = env('NUMERO_BASE_URL');
        $this->provider = env('INFLOW_PROVIDER');
        $this->baseCrustUrl = env('CRUST_BASE_URL');
        $this->response = new DefaultApiResponse();
    }

    public function createWallet(CreateWalletRequest $request)
    {
        try {
            $pin = generateRandomString();
            $data = createChakraWallet($request, $this->baseUrl, $pin);
            // Log::info($data);
            if ($data['responseCode'] == "00") {
                $wallet = new Wallet();
                $encryptedPin = encryptPin($pin);
                $result = $wallet->AddWallet($data, $request, $encryptedPin);
                Log::info($result);

                $this->response->responseCode = '0';
                $this->response->message = $data['responseMessage'];
                $this->response->isSuccessful = true;
                $this->response->data = $data['data'];
                return response()->json($this->response, 200);

            }
            $this->response->responseCode = '2';
            $this->response->message = $data['responseMessage'];
            $this->response->isSuccessful = true;
            return response()->json($this->response, 200);
            
        } catch (\Exception $e) {
            $this->response->message = 'Processing Failed, Contact Support';
            Log::info(json_encode($e));
            $this->response->error = $e->getMessage();
            return response()->json($this->response, 500);
        }
    }

    public function getMerchantPeer(MerchantPayRequest $request)
    {
        switch ($this->provider) {
            case 'CHAKRA':
                try {
                    Log::info('**********Wallet Generated *************');
                    Log::info($request->all());
                    $walletGenerated = getMerchantPeer($this->baseUrl);
                    Log::info($walletGenerated);
                    if ($walletGenerated['success']){
                        $saveInflow = new Inflow();
                        Log::info($walletGenerated);
                        $saveInflow->saveInFlowRequest($walletGenerated, $request); 
                        $this->response->responseCode = '0';
                        $this->response->message = $walletGenerated['message'];
                        $this->response->isSuccessful = true;
                        $this->response->data = [
                            "accountNumber" => $walletGenerated['accountNumber'],
                            "accountName" => $walletGenerated['accountName'],
                            "reference" => $walletGenerated['reference'],
                            "bankName" => $walletGenerated['bankName'],
                            "bankCode" => $walletGenerated['bankCode']
                        ];
                        return response()->json($this->response, 200);
                    }
                    $this->response->responseCode = '2';
                    $this->response->message = $walletGenerated['responseMessage'] || $walletGenerated['message'] ;
                    $this->response->isSuccessful = false;
                    return response()->json($this->response, 400);
                } catch (\Exception $e) {
                    $this->response->message = 'Processing Failed, Contact Support';
                    Log::info(json_encode($e));
                    $this->response->error = $e->getMessage();
                    return response()->json($this->response, 500);
                }
                break;
            case 'CRUST':
                try {
                    $data = getAccounts($this->baseCrustUrl);
                    if ($data['success']) {
                        $saveInflow = new Inflow();
                        Log::info($data);
                        $saveInflow->saveInFlowCrustRequest($data['data'], $request);
                        $this->response->responseCode = '0';
                        $this->response->message = $data['message'];
                        $this->response->isSuccessful = true;
                        $this->response->data = $data['data'];
                        return response()->json($this->response, 200);
        
                    }
                    $this->response->responseCode = '2';
                    $this->response->message = $data['message'];
                    $this->response->isSuccessful = false;
                    return response()->json($this->response, 400);
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


    public function getMerchantBalance()
    {
        try {
            $response = [
                'isSuccess' =>  false,
                'responseCode' => null,
                'data'=> null,
                'message' => null,
            ];
            $data = getMerchantBalance($this->baseUrl);
            $merchBalance = new MerchantBalance();
            if ($data['responseCode'] == "00"){
                $accountFromDb = $this->checkAccountNumber($data['accountNumber']);
                if (empty($accountFromDb)) {
                    $merchBalance->AddMerchBalance($data);      
                }   
                $merchBalance->updateMerchBalance($data);
                $response['responseCode'] = '0';
                $response['message'] = $data['responseMessage'];
                $response['isSuccess'] = true;
                $response['data'] = json_decode($data) ;
                return response()->json($response, 200);
            }
            $response['responseCode'] = '2';
            $response['message'] = $data['responseMessage'];
            $response['isSuccess'] = false;
            $response['data'] = json_decode($data) ;
            return response()->json($response, 400);

        } catch (\Exception $e) {
            return response([
                'isSuccesful' => false,
                'message' => 'Processing Failed, Contact Support',
                'error' => $e->getMessage()
            ]);
        }
    }



    public function fundingCallBack(ChakraCallBackRequest $request)
    {
        try {
            Log::info('**********');
            Log::info($request->all());
            if ($request->hasHeader('x-payout-signature')) {
                $payoutSignature = $request->header('x-payout-signature');
                $request->headers->set('Content-Type', 'application/json');
                // $jsonEncodedPayload = json_encode($request->all());
                $hashedPayload = hash_hmac("sha512", json_encode($request->all()) , $this->callBackSecret);
                Log::info($hashedPayload);
                if($hashedPayload != $payoutSignature)
                {
                    $this->response->responseCode = '1';
                    $this->response->message = "Invalid Signature";                    
                    return response()->json($this->response, 401);
                }else{
                    
                    $inflow = new Inflow();
                    $fromDb = findInFlowbyReference($request->reference,$request->walletAccountNumber);
                    if (!empty($fromDb)) {
                        $response = postToIndians($request, $fromDb['customerId'], $fromDb['callback_url']);
                        Log::info('************response from application from indians ************' .  $response);
                        $inflow->saveResponse($response, $fromDb);
                        if ($response->successful())
                        {
                        if ($request->success) {
                            //update DB to be successful
                            $inflow->updateFromCallBackForSuccessfulTransaction($fromDb, $request);
                            return  response([
                                'responseCode' => "00",
                                'responseMessage' => "Callback received"
                            ], 200);
                            } else {
                                $inflow->updateFromCallBackForFailedTransaction($fromDb, $request);
        
                                return  response([
                                    'responseCode' => "00",
                                    'responseMessage' => "Callback received"
                                ], 200);
                            }
                        }
                        
                    } else {
                        return  response([
                            'responseCode' => "1",
                            'responseMessage' => "Not Found"
                        ], 400);
                    } 
                }             
            }else{
                $this->response->responseCode = '1';
                $this->response->message = "Invalid Signature";                    
                return response()->json($this->response, 401);
            }
            
            
        } catch (\Exception $e) {
            $this->response->message = 'Processing Failed, Contact Support';
            Log::info(json_encode($e));
            $this->response->error = $e->getMessage();
            return response()->json($this->response, 500);
        }
    }


    public function fundingCrustCallBack(CrustCallBackRequest $request)
    {
        try {
            Log::info($request->all());
            $inflow = new Inflow();
            $fromDb = findInFlowbyReference($request->transactionNumber,$request->accountNumber);
            if (!empty($fromDb)) {
                // $response = postToIndians($request, $fromDb['customerId'], $fromDb['callback_url']);
                // Log::info('************response from application************' .  $response);
                // $inflow->saveResponse($request, $response);
                // if ($response->successful())
                // {
                if ($request->status === "SUCCESSFUL") {
                    //update DB to be successful
                    $inflow->updateFromCallBackForSuccessfulCrustTransaction($fromDb,$request);
                    return  response([
                        'responseCode' => "00",
                        'responseMessage' => "Callback received"
                    ], 200);
                    } else {
                        $inflow->updateFromCallBackForFailedCrustTransaction($fromDb,$request);

                        return  response([
                            'responseCode' => "00",
                            'responseMessage' => "Callback received"
                        ], 200);
                    }
                
            } else {
                return  response([
                    'responseCode' => "1",
                    'responseMessage' => "Not Found"
                ], 400);
            } 
        
            
            
        } catch (\Exception $e) {
            $this->response->message = 'Processing Failed, Contact Support';
            Log::info(json_encode($e));
            $this->response->error = $e->getMessage();
            return response()->json($this->response, 500);
        }
    }

    public function alertUrl(AlertRequest $request)
    {
        try {
            $findByReference = findByRefernceAndCustomerId($request->reference, $request->customerId);
            Log::info($findByReference);
            if (!empty($findByReference)) {
                $this->response->responseCode = '0';
                $this->response->message = 'Details retrieved';
                $this->response->isSuccessful = true;
                $this->response->data = [
                    "customerId" => $findByReference['customerId'],
                    "amountReceived" => $findByReference['received_amount'],
                    "status" => $findByReference['status'],
                    "reference" => $findByReference['reference'],
                ];
                return response()->json($this->response, 200);
            }
            $this->response->responseCode = '1';
            $this->response->message = 'Invalid Details';
            $this->response->isSuccessful = false;
            return response()->json($this->response, 400);
        } catch (\Exception $e) {
            $this->response->message = 'Processing Failed, Contact Support';
            Log::info(json_encode($e));
            $this->response->error = $e->getMessage();
            return response()->json($this->response, 500);
        }
    }


    public function createVirtualAccountNumero(NumeroAccountCreationRequest $request)
    {
        try {
            $data = numeroCreateAccount($request, $this->baseNumeroUrl);
            $wallet = new Wallet();
            $result = $wallet->AddWalletNumero($data, $request);
            $decodeData = json_decode($data);
            $this->response->responseCode = '0';
            $this->response->isSuccessful = true;
            $this->response->data = $decodeData;
            return response()->json($this->response, 200);
        } catch (\Exception $e) {
            $this->response->message = 'Processing Failed, Contact Support';
            Log::info(json_encode($e));
            $this->response->error = $e->getMessage();
            return response()->json($this->response, 500);
        }
        


    }


    public static function checkAccountNumber($accountNumber)
    {
        return MerchantBalance::where('accountNumber', $accountNumber)->first();
    }
    
}
