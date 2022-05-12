<?php

namespace App\Http\Controllers;

use App\Contract\Responses\DefaultApiResponse;
use App\Http\Requests\ChakraCallBackRequest;
use App\Http\Requests\CreateWalletRequest;
use App\Http\Requests\MerchantPayRequest;
use App\Models\Inflow;
use App\Models\Wallet;
use gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgentWalletController extends Controller
{
    private $baseUrl;
    private $response;
    private $callBackSecret;
    public function __construct()
    {
        $this->baseUrl = env('BASE_URL');
        $this->callBackSecret = env('CALL_BACK_SECRET');
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
                $this->response->isSuccess = true;
                $this->response->data = $data['data'];
                return response()->json($this->response, 200);

            }
            $this->response->responseCode = '2';
            $this->response->message = $data['responseMessage'];
            $this->response->isSuccess = true;
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
        try {
            $walletGenerated = getMerchantPeer($this->baseUrl);
            $saveInflow = new Inflow();
            Log::info($walletGenerated);
            $saveInflow->saveInFlowRequest($walletGenerated, $request); 
            $this->response->responseCode = '0';
            $this->response->message = $walletGenerated['message'];
            $this->response->isSuccessful = true;
            $this->response->data = [
                "accountNumber" => $walletGenerated['accountNumber'],
                "accountName" => $walletGenerated['accountName'],
                "bankName" => $walletGenerated['bankName']
            ];
            return response()->json($this->response, 200);
        } catch (\Exception $e) {
            $this->response->message = 'Processing Failed, Contact Support';
            Log::info(json_encode($e));
            $this->response->error = $e->getMessage();
            return response()->json($this->response, 500);
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
            Log::info('sdsdsd' .$data);
            $response['responseCode'] = '0';
            $response['message'] = $data['responseMessage'];
            $response['isSuccess'] = true;
            $response['data'] = json_decode($data) ;
            return response()->json($response, 200);

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
            if ($request->hasHeader('x-payout-signature')) {
                $payoutSignature = $request->header('x-payout-signature');
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
                    $fromDb = findInFlowbyReference($request->reference);
                    if (!empty($fromDb)) {
                        if ($request->success) {
                        //update DB to be successful
                        $inflow->updateFromCallBackForSuccessfulTransaction($request);
                        return  response([
                            'responseCode' => "00",
                            'responseMessage' => "Callback received"
                        ], 200);
                        } else {
                            $inflow->updateFromCallBackForFailedTransaction($request);
    
                            return  response([
                                'responseCode' => "00",
                                'responseMessage' => "Callback received"
                            ], 200);
                        }
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


    
}
