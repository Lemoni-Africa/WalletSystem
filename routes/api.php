<?php

use App\Http\Controllers\AgentWalletController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PayOutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(array('prefix' => 'auth' ), function () {
    Route::post('createToken', [AuthController::class, 'createToken']);
    Route::post('test', [AuthController::class, 'test']);
});


Route::group(array('prefix' => 'payout' ), function () {
    Route::post('quick', [PayOutController::class, 'quickPay']);
    Route::get('status/{transactionId}', [PayOutController::class, 'payOutStatus']);
    Route::post('bankEnquiry', [PayOutController::class, 'makeEnquiry']);
});

Route::group(array('prefix' => 'wallet' ), function () {
    Route::post('createWallet', [AgentWalletController::class, 'createWallet']);
    Route::post('getMerchantPeer', [AgentWalletController::class, 'getMerchantPeer']);
    Route::get('getMerchantBalance', [AgentWalletController::class, 'getMerchantBalance']);
    Route::post('fundingCallBack', [AgentWalletController::class, 'fundingCallBack']);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

