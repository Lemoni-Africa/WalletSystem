<?php

use App\Http\Controllers\AgentWalletController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankListController;
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


// Route::middleware(['ipChecker'])->group(function () {
    Route::group(array('prefix' => 'payout' ), function () {
        Route::post('quick', [PayOutController::class, 'quickPay']);
        Route::post('test', [PayOutController::class, 'test']);
        Route::post('quickCrust', [PayOutController::class, 'quickPayCrust']);

        Route::get('status/{transactionId}', [PayOutController::class, 'payOutStatus']);
        Route::post('bankEnquiry', [PayOutController::class, 'makeEnquiry']);
        Route::get('transactions', [PayOutController::class, 'getTransactionList']);
    });

    Route::group(array('prefix' => 'bank' ), function () {
        Route::get('bankList', [BankListController::class, 'getBankList']);
        Route::get('bankBalance', [BankListController::class, 'getBalance']);
        Route::get('bankAccounts', [BankListController::class, 'getAccounts']);
        Route::post('getAccountName', [BankListController::class, 'getAccountName']);
    });

    Route::group(array('prefix' => 'wallet' ), function () {
        Route::post('createWallet', [AgentWalletController::class, 'createWallet']);
        Route::post('getWallet', [AgentWalletController::class, 'getMerchantPeer']);
        Route::get('getMerchantBalance', [AgentWalletController::class, 'getMerchantBalance']);
        Route::post('credentialReset', [AgentWalletController::class, 'credential']);
        Route::post('fundingCallBack', [AgentWalletController::class, 'fundingCallBack']);
        Route::post('payoutCallBackChakra', [AgentWalletController::class, 'payoutChakraCallBack']);
        Route::post('callBackCrust', [AgentWalletController::class, 'fundingCrustCallBack']);
        Route::post('alert', [AgentWalletController::class, 'alertUrl']);
        Route::post('createNumeroAccount', [AgentWalletController::class, 'createVirtualAccountNumero']);
    });
// });





Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

