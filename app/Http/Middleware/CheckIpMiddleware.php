<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckIpMiddleware
{
    public $whiteIps;
    public $baseUrl;
    public function __construct()
    {
        $this->baseUrl = env('WHITE_IPS');
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
        Log::info($this->whiteIps);
        if (!in_array($request->ip(), $this->whiteIps)) {

            /*
                 You can redirect to any error page.
            */
            return response()->json(['Your not allowed to access this resource'], 403);
        }
        return $next($request);
    }
}
