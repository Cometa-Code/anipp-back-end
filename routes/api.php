<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/
Route::post('/user', [UserController::class, 'store']);
Route::post('/user/login', [UserController::class, 'login']);

Route::post('/user/recover-password', [UserController::class, 'generate_token']);
Route::get('/user/recover-password/verify-token/{token}', [UserController::class, 'verify_token']);
Route::post('/user/recover-password/set-password', [UserController::class, 'recover_password']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/user', [UserController::class, 'user']);

    Route::get('/user/associates', [UserController::class, 'get_associates']);
});
