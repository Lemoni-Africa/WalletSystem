<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private $baseUrl;
    public function __construct()
    {
        $this->baseUrl = env('BASE_URL');
    }


    public function createToken(AuthRequest $request)
    {
        try {
            $response = [
                'isSuccess' =>  false,
                'message' => null,
                'data'=> null,
               
            ];
            // $url = "{$this->baseUrl}/credentials/get-token";
            // $body = [
            //     'merchantId' => $request->merchantId,
            //     'apiKey' => $request->apiKey,
            // ];
            $credentials = getToken();
            Log::info($credentials);
            $response['isSuccess'] = true;
            $response['data'] = $credentials;
            base64ChakraCred();
            // generateMerchantRef();
            $h = createHeaders();
            return $h;
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
        
    }

    public function test(Request $request)
    {
        // Log::info('*************' . $request);
        $token = Cache::get('token');
        $value = Cache::get('key');
        // $value = Cache::get('key', 'default');
        Log::info($token);
        return $token;
    }
}
