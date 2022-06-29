<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckIpMiddleware
{
    public $whiteIps;
    public $host;
    public $baseUrl;
    public $hostBaseUrl;
    public function __construct()
    {
        $this->baseUrl = env('WHITE_IPS');
        $this->hostBaseUrl = env('HOST');
    }
   
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->whiteIps = explode(',', $this->baseUrl);
        $this->host = explode(',', $this->hostBaseUrl);

        $getHost = request()->getHost();
        Log::info('Incoming IP  ' . $request->ip());
        Log::info('Incoming Host  ' . $request->getHost());
        if (!in_array($request->ip(), $this->whiteIps) && !in_array(request()->getHost(),$this->host)) {

            /*
                 You can redirect to any error page.
            */
            Log::info('Rejected IP ' . $request->ip());
            Log::info('Rejected HOST ' . $request->getHost());
            return response()->json(["You're not allowed to access this resource"], 403);
        }
        return $next($request);
    }
}
