<?php

use App\Http\Controllers\user\User;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/create-account',[User::class,'create_account']);
Route::post('/otp-validation',[User::class,'otp_validation']);
Route::post('/resend-otp',[User::class,'resend_otp']);
Route::post('/change-password',[User::class,'new_password']);
Route::post('/login',[User::class,'login']);


Route::middleware('auth:sanctum','ability:U')->group(function(){
    Route::get('view-auth',[User::class,'getview']);

    Route::post('/logout',[User::class,'logout']);
});
