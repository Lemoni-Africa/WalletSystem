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
            Log::info('************ create wallet chakra ***************');
            Log::info($request->all());
            $pin = generateRandomString();
            $data = createChakraWallet($request, $this->baseUrl, $pin);
            Log::info('data gotten after creation of wallet' .$data);
            if ($data['responseCode'] == "00") {
                $wallet = new Wallet();
                $encryptedPin = encryptPin($pin);
                Log::info('************ save to database ***************');
                $result = $wallet->AddWallet($data, $request, $encryptedPin);

                $this->response->responseCode = '0';
                $this->response->message = $data['responseMessage'];
                $this->response->isSuccessful = true;
                $this->response->data = $data['data'];
                Log::info('response gotten after creation of wallet' .$this->response);
                return response()->json($this->response, 200);

            }
            $this->response->responseCode = '2';
            $this->response->message = $data['responseMessage'];
            $this->response->isSuccessful = true;
            Log::info('response gotten after creation of wallet' .$this->response);
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
        Log::info('************ get peer wallet endpoint ***************');
        switch ($this->provider) {
            case 'CHAKRA':
                try {
                    Log::info('**********Get Peer from Chakra service *************');
                    Log::info($request->all());
                    $walletGenerated = getMerchantPeer($this->baseUrl);
                    Log::info('data gotten ' .$walletGenerated);
                    if ($walletGenerated['success']){
                        $saveInflow = new Inflow();
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
                        Log::info('response gotten ' . json_encode($this->response));
                        return response()->json($this->response, 200);
                    }
                    $this->response->responseCode = '2';
                    $this->response->message = $walletGenerated['responseMessage'] || $walletGenerated['message'] ;
                    $this->response->isSuccessful = false;
                    Log::info('response gotten ' .$this->response);
                    return response()->json($this->response, 400);
                } catch (\Exception $e) {
                    $this->response->message = 'Processing Failed, Contact Support';
                    Log::info(json_encode($e));
                    // $this->response->error = $e->getMessage();
                    return response()->json($this->response, 500);
                }
                break;
            case 'CRUST':
                try {
                    Log::info('**********Get Peer from Crust service *************');
                    Log::info($request->all());
                    $data = getAccounts($this->baseCrustUrl);
                    Log::info('data gotten ' .$data);
                    if ($data['success']) {
                        $saveInflow = new Inflow();
                        // Log::info($data);
                        $saveInflow->saveInFlowCrustRequest($data['data'], $request);
                        $this->response->responseCode = '0';
                        $this->response->message = $data['message'];
                        $this->response->isSuccessful = true;
                        $this->response->data = $data['data'];
                        Log::info('response gotten ' .$this->response);
                        return response()->json($this->response, 200);
        
                    }
                    $this->response->responseCode = '2';
                    $this->response->message = $data['message'];
                    $this->response->isSuccessful = false;
                    Log::info('response gotten ' .$this->response);
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
            Log::info('**********Get Merchant Balance  *************');
            $data = getMerchantBalance($this->baseUrl);
            Log::info('data gotten ' .$data);
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
                Log::info('response gotten ' .$response);
                return response()->json($response, 200);
            }
            $response['responseCode'] = '2';
            $response['message'] = $data['responseMessage'];
            $response['isSuccess'] = false;
            $response['data'] = json_decode($data);
            Log::info('response gotten ' .$response);
            return response()->json($response, 400);

        } catch (\Exception $e) {
            Log::info(json_encode($e));
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
            Log::info('********** callback Chakra *****');
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
                    // Log::info('response gotten ' .$this->response);                   
                    return response()->json($this->response, 401);
                }else{
                    $inflow = new Inflow();
                    $fromDb = findInFlowbyReference($request->reference,$request->walletAccountNumber);
                    // Log::info('check if its on database ' .$fromDb);
                    if (!empty($fromDb)) {
                        if ($request->success) {
                            //update DB to be successful
                            Log::info('****Transaction was successful****');
                            $inflow->updateFromCallBackForSuccessfulTransaction($fromDb, $request);
                            $response = postToIndians($request, $fromDb['customerId'], $fromDb['callback_url']);
                            // Log::info('************response from application from indians ************' .  $response);
                            $inflow->saveResponse($response, $fromDb);
                            return  response([
                                'responseCode' => "00",
                                'responseMessage' => "Callback received"
                            ], 200);
                            } else {
                                Log::info('****Transaction failed ****');
                                $inflow->updateFromCallBackForFailedTransaction($fromDb, $request);
                                $response = postToIndians($request, $fromDb['customerId'], $fromDb['callback_url']);
                                // Log::info('************response from application from indians ************' .  $response);
                                $inflow->saveResponse($response, $fromDb);
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
                }             
            }else{
                $this->response->responseCode = '1';
                $this->response->message = "Invalid Signature";  
                Log::info('response gotten ' .$this->response);                   
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
            Log::info('********** callback Crust *****');
            Log::info($request->all());
            $inflow = new Inflow();
            $fromDb = findInFlowbyReference($request->transactionNumber,$request->accountNumber);
            Log::info('check if its on database ' .$fromDb);
            if (!empty($fromDb)) {
                if ($request->status === "SUCCESSFUL") {
                    //update DB to be successful
                    Log::info('****Transaction was successful****');
                    $inflow->updateFromCallBackForSuccessfulCrustTransaction($fromDb,$request);
                    $response = postToIndians($request, $fromDb['customerId'], $fromDb['callback_url']);
                    Log::info('************response from application from indians ************' .  $response);
                    $inflow->saveResponse($response, $fromDb);
                    return  response([
                        'responseCode' => "00",
                        'responseMessage' => "Callback received"
                    ], 200);
                    } else {
                        Log::info('****Transaction failed ****');
                        $inflow->updateFromCallBackForFailedCrustTransaction($fromDb, $request);
                        $response = postToIndians($request, $fromDb['customerId'], $fromDb['callback_url']);
                        Log::info('************response from application from indians ************' .  $response);
                        $inflow->saveResponse($response, $fromDb);
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
