<?php

use App\Http\Controllers\QrCodeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::fallback(function () {
    //return view('404');
    return redirect(url('/admin'));
});

Route::get('/qr/{encoded_id}', [QrCodeController::class, 'show']);
Route::post('/qr/{encoded_id}', [QrCodeController::class, 'show']);
