<?php

use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserDependentsController;
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

Route::post('/payments/table', [UserPaymentsController::class, 'insert_table_payments']);
Route::put('/payments/table', [UserPaymentsController::class, 'update_bank_identifier']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/user', [UserController::class, 'user']);
    Route::get('/user/associates', [UserController::class, 'get_associates']);
    Route::post('/user/advanced', [UserController::class, 'create_advanced_user']);
    Route::put('/user', [UserController::class, 'update_user']);
    Route::put('/user/password', [UserController::class, 'update_password']);
    Route::put('/user/advanced/{document}', [UserController::class, 'update_advanced_user']);
    Route::delete('/user/associates/deactivate_user/{id}', [UserController::class, 'deactivate_user']);

    Route::post('/user/dependents', [UserDependentsController::class, 'store']);
    Route::get('/user/dependents', [UserDependentsController::class, 'index']);
    Route::delete('/user/dependents/{id}', [UserDependentsController::class, 'destroy']);

    Route::post('/payments', [UserPaymentsController::class, 'store']);
    Route::get('/payments', [UserPaymentsController::class, 'index']);
    Route::get('/payments/{user_id}', [UserPaymentsController::class, 'get_associate_payments']);
    Route::delete('/payments/{id}', [UserPaymentsController::class, 'delete']);

    Route::post('/reports', [ReportsController::class, 'store']);
    Route::get('/reports', [ReportsController::class, 'index']);
    Route::delete('/reports/{id}', [ReportsController::class, 'delete']);

    Route::post('/cash_flow', [CashFlowController::class, 'store']);
    Route::put('/cash_flow/{id}', [CashFlowController::class, 'update']);
    Route::post('/cash_flow/read_extract', [CashFlowController::class, 'read_extract']);
    Route::get('/cash_flow', [CashFlowController::class, 'index']);
});
