<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnquiryRequest;
use App\Http\Requests\QuickPayoutRequest;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Providers;
use TransactionStatus;

class PayOutController extends Controller
{
    private $baseUrl;
    public function __construct()
    {
        $this->baseUrl = env('BASE_URL');
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
                    $response['message'] = $getStatus['data']['transferStatus'];
                    $response['isSuccess'] = true;
                    $response['data'] = [
                        'transactionRef' => $data['data']['merchantReference']
                    ];
                    
                    return response()->json($response, 200);
                }
                // Log::info($getStatus);
                $payout->UpdateFailedPayOut($getStatus);
                $response['responseCode'] = '1';
                $response['message'] =   $getStatus["responseMessage"];
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


}
