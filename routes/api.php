<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPaymentsController;
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
    Route::post('/user/advanced', [UserController::class, 'create_advanced_user']);
    Route::put('/user', [UserController::class, 'update_user']);
    Route::put('/user/advanced/{document}', [UserController::class, 'update_advanced_user']);

    Route::post('/payments', [UserPaymentsController::class, 'store']);
    Route::get('/payments', [UserPaymentsController::class, 'index']);
    Route::get('/payments/{user_id}', [UserPaymentsController::class, 'get_associate_payments']);
});
