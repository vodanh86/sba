<?php

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('customers', 'CustomerController@index');
Route::get('customer', 'CustomerController@find');
Route::get('invitation-letter', 'InvitationLetterController@find');
Route::get('contract', 'ContractController@find');
Route::get('notifications', 'NotificationController@index');
Route::get('notifications/get/{userId}', 'NotificationController@get');
Route::put('notifications/{userId}', 'NotificationController@check');