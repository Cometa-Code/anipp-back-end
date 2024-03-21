<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/user', [UserController::class, 'store']);
Route::post('/user/login', [UserController::class, 'login']);
Route::post('/user/recover-password', [UserController::class, 'generate_token']);
Route::get('/user/recover-password/verify-token/{token}', [UserController::class, 'verify_token']);
