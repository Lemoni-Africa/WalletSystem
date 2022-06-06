<?php

namespace App\Http\Controllers;

use App\Contract\Responses\DefaultApiResponse;
use App\Http\Requests\ResolveBankNameRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BankListController extends Controller
{
    private $baseCrustUrl;
    private $response;
    private $provider;
    private $baseNumeroUrl;
    private $bankEnv;
    public function __construct()
    {
        $this->baseCrustUrl = env('CRUST_BASE_URL');
        $this->provider = env('BANK_PROVIDER');
        $this->baseNumeroUrl = env('NUMERO_BASE_URL');
        $this->bankEnv = env('BANK_ENV');
        $this->response = new DefaultApiResponse();
    }
    public function getBankList()
    {
        switch ($this->provider) {
            case 'CRUST':
                try {
                    $bankList = getBanks($this->baseCrustUrl);
                    if ($bankList['success']) {
                        $this->response->responseCode = '0';
                        $this->response->message = $bankList['message'];
                        $this->response->isSuccessful = true;
                        $this->response->data = $bankList['data'];
                        return response()->json($this->response, 200);
        
                    }
                    $this->response->responseCode = '2';
                    $this->response->message = $bankList['message'];
                    $this->response->isSuccessful = false;
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
                    $bankList = getBanksNumero($this->baseNumeroUrl);
                    $decodedList = json_decode($bankList);
                    $this->response->responseCode = '0';
                    // $this->response->message = $bankList['message'];
                    $this->response->isSuccessful = true;
                    $this->response->data = $decodedList->banks;
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
                    if ($this->bankEnv === "TEST") {
                        $request->accountnumber ="0125594645";
                        $request->bankcode = "058";
                    }
                    $data = getAccountName($request, $this->baseCrustUrl);
                    Log::info($data);
                    if ($data['success']) {
                        $this->response->responseCode = '0';
                        $this->response->message = $data['message'];
                        $this->response->isSuccessful = true;
                        $this->response->data = $data['data'];
                        return response()->json($this->response, 200);
                    }
                    $this->response->responseCode = '1';
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
            case 'NUMERO':
                try {
                    $data = getAccountNameNumero($request, $this->baseNumeroUrl);
                    if ($data['status']) {
                        $this->response->responseCode = '0';
                        $this->response->message = $data['message'];
                        $this->response->isSuccessful = true;
                        $this->response->data = $data['data'];
                        return response()->json($this->response, 200);
                    }
                    $this->response->responseCode = '1';
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
}
