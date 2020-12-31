<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\PackController;
use App\Http\Controllers\API\TransactionController;

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

Route::middleware('auth:sanctum')->group(function(){
    Route::get('user', [UserController::class, 'fetch']);
    Route::post('user', [UserController::class, 'updateProfile']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::post('checkout', [TransactionController::class, 'checkout']);
    Route::get('checkout_B', [TransactionController::class, 'checkout_B']);
    Route::get('transactions', [TransactionController::class, 'all']);
    Route::post('user/photo', [UserController::class, 'updatePhoto']);
    Route::get('transactions', [TransactionController::class, 'all']);
    Route::get('packs', [PackController::class, 'all']);
});

Route::post('login', [UserController::class, 'login']);
Route::post('register', [UserController::class, 'register']);