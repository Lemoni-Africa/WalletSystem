<?php

namespace App\Http\Controllers;

use App\Contract\Responses\DefaultApiResponse;
use App\Http\Requests\ResolveBankNameRequest;
use App\Http\Resources\BankDetailsResource;
use App\Models\BankEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BankListController extends Controller
{
    private $baseCrustUrl;
    private $response;
    private $provider;
    private $baseNumeroUrl;
    private $bankEnv;
    private $baseUrlChakra;
    public function __construct()
    {
        $this->baseCrustUrl = env('CRUST_BASE_URL');
        $this->provider = env('BANK_PROVIDER');
        $this->baseNumeroUrl = env('NUMERO_BASE_URL');
        $this->bankEnv = env('BANK_ENV');
        $this->baseUrlChakra = env('BASE_URL');
        $this->response = new DefaultApiResponse();
    }
    public function getBankList()
    {
        switch ($this->provider) {
            case 'CRUST':
                try {
                    Log::info('********** Entering Crust GetBankList ***********');
                    $bankList = getBanks($this->baseCrustUrl);
                    Log::info('data gotten ' .$bankList);
                    if ($bankList['success']) {
                        $this->response->responseCode = '0';
                        $this->response->message = $bankList['message'];
                        $this->response->isSuccessful = true;
                        $this->response->data = $bankList['data'];
                        Log::info('response gotten ' .json_encode($this->response));
                        return response()->json($this->response, 200);
        
                    }
                    $this->response->responseCode = '2';
                    $this->response->message = $bankList['message'];
                    $this->response->isSuccessful = false;
                    Log::info('response gotten ' .json_encode($this->response));
                    return response()->json($this->response, 400);
                    
                    Log::info($bankList);
                } catch (\Exception $e) {
                    $this->response->message = 'Processing Failed, Contact Support';
                    Log::info(json_encode($e));
                    $this->response->error = $e->getMessage();
                    return response()->json($this->response, 500);
                }
                break;
            case 'NUMERO':
                try {
                    Log::info('********** Entering Numero GetBankList ***********');
                    $bankList = getBanksNumero($this->baseNumeroUrl);
                    Log::info('data gotten '. $bankList);
                    $decodedList = json_decode($bankList);
                    $this->response->responseCode = '0';
                    // $this->response->message = $bankList['message'];
                    $this->response->isSuccessful = true;
                    $this->response->data = $decodedList->banks;
                    Log::info('response gotten ' .json_encode($this->response));
                    return response()->json($this->response, 200);
        
                    // $this->response->responseCode = '2';
                    // $this->response->message = $bankList['message'];
                    // $this->response->isSuccessful = false;
                    // return response()->json($this->response, 400);
                    
                    Log::info($bankList);
                } catch (\Exception $e) {
                    $this->response->message = 'Processing Failed, Contact Support';
                    Log::info(json_encode($e));
                    $this->response->error = $e->getMessage();
                    return response()->json($this->response, 500);
                }
                break;
            case 'CHAKRA':
                try {
                    Log::info('********** Entering Chakra GetBankList ***********');
                    $bankList = getBanksChakra($this->baseUrlChakra);
                    Log::info('data gotten ' .$bankList);
                    if ($bankList['responseCode'] === "00") {
                        $bank = $bankList['data'];
                        $encodedJson = $bank; 
                        $data_array = [];
                        foreach($encodedJson as $service){
                            $bankName = $service['name'];
                            $bankCode = $service['code'];
                            $data = array('bankName' => $bankName, 'bankCode' => $bankCode);
                            array_push($data_array,$data);
                        }
                        $this->response->responseCode = '0';
                        $this->response->message = $bankList['responseMessage'];
                        $this->response->isSuccessful = true;
                        $this->response->data = $data_array;
                        Log::info('response gotten ' .json_encode($this->response));
                        return response()->json($this->response, 200);
                    }
                    $this->response->responseCode = '2';
                    $this->response->message = $bankList['responseMessage'] ?? "Failed";
                    $this->response->isSuccessful = false;
                    Log::info('response gotten ' .json_encode($this->response));
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

    public function getBalance()
    {
        try {
            $data = getBalance($this->baseCrustUrl);
            if ($data['success']) {
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
            
            Log::info($data);
        } catch (\Exception $e) {
            $this->response->message = 'Processing Failed, Contact Support';
            Log::info(json_encode($e));
            $this->response->error = $e->getMessage();
            return response()->json($this->response, 500);
        }
    }

    public function getAccounts()
    {
        try {
            $data = getAccounts($this->baseCrustUrl);
            if ($data['success']) {
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
    }

    
    public function getAccountName(ResolveBankNameRequest $request)
    {
        switch ($this->provider) {
            case 'CRUST':
                try {
                    Log::info('********** Entering Crust validateAccountNumber ***********');
                    if ($this->bankEnv === "TEST") {
                        $request->accountnumber ="0125594645";
                        $request->bankcode = "058";
                    }
                    Log::info($request->all());
                    $data = getAccountName($request, $this->baseCrustUrl);
                    Log::info('data gotten ' .$data);
                    if ($data['success']) {
                        $this->response->responseCode = '0';
                        $this->response->message = $data['message'];
                        $this->response->isSuccessful = true;
                        $this->response->data = $data['data'];
                        Log::info('response gotten ' .json_encode($this->response));
                        return response()->json($this->response, 200);
                    }
                    $this->response->responseCode = '1';
                    $this->response->message = $data['message'];
                    $this->response->isSuccessful = false;
                    Log::info('response gotten ' .json_encode($this->response));
                    return response()->json($this->response, 400);
                } catch (\Exception $e) {
                    $this->response->message = 'Processing Failed, Contact Support';
                    Log::info(json_encode($e));
                    $this->response->error = $e->getMessage();
                    return response()->json($this->response, 500);
                }
                break;
            case 'NUMERO':
                try {
                    Log::info('********** Entering Numero validateAccountNumber ***********');
                    $data = getAccountNameNumero($request, $this->baseNumeroUrl);
                    Log::info($request->all());
                    Log::info('data gotten ' .$data);
                    if ($data['status']) {
                        $this->response->responseCode = '0';
                        $this->response->message = $data['message'];
                        $this->response->isSuccessful = true;
                        $this->response->data = $data['data'];
                        Log::info('response gotten ' .json_encode($this->response));
                        return response()->json($this->response, 200);
                    }
                    $this->response->responseCode = '1';
                    $this->response->message = $data['message'];
                    $this->response->isSuccessful = false;
                    Log::info('response gotten ' .json_encode($this->response));
                    return response()->json($this->response, 400);
                } catch (\Exception $e) {
                    $this->response->message = 'Processing Failed, Contact Support';
                    Log::info(json_encode($e));
                    $this->response->error = $e->getMessage();
                    return response()->json($this->response, 500);
                }
                break;
            case 'CHAKRA':
                try {
                    Log::info('********** Entering Chakra validateAccountNumber ***********');
                    $data = accountEnquiryChakra($request, $this->baseUrlChakra);
                    Log::info($request->all());
                    Log::info('data gotten ' .$data);
                    $bankDetails = $data['data'];
                    $bankModel = new BankEnquiry();
                    $fromDb = $this->checkBankDetails($bankDetails['accountNumber'], $bankDetails['accountName']);
                    if (empty($fromDb)) {
                        if ($data['responseCode'] === "00") {
                            // $bankDetails = $data['data'];
                            // $encodedJson = $bank; 
                            // var_dump($bankDetails);
                            // $data_array = [];
                            // foreach($bankDetails as $service){
                            //     $account_name = $service['accountName'];
                            //     $account_number = $service->accountNumber;
                            //     $data = array('account_name' => $account_name, 'account_number' => $account_number);
                            //     array_push($data_array,$data);
                            // }
                            $savedModel = $bankModel->addAccountDetailsChakra($data['data']);
                            $this->response->responseCode = '0';
                            $this->response->message = $data['responseMessage'];
                            $this->response->isSuccessful = true;
                            $this->response->data = new BankDetailsResource($savedModel);
                            Log::info('response gotten ' .json_encode($this->response));
                            return response()->json($this->response, 200);
                        }
                        $this->response->responseCode = '1';
                        $this->response->message = $data['responseMessage'];
                        $this->response->isSuccessful = false;
                        Log::info('response gotten ' .json_encode($this->response));
                        return response()->json($this->response, 400);
                    }
                    $this->response->responseCode = '0';
                    $this->response->message = 'Successful';
                    $this->response->isSuccessful = true;
                    $this->response->data = $fromDb;
                    Log::info('response gotten ' .json_encode($this->response));
                    return response()->json($this->response, 200);
                   
                  
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

    public function checkBankDetails($accNumber, $accName)
    {
        return BankEnquiry::where('account_number', $accNumber)->where('account_name', $accName)->first();
    }
}
