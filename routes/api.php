<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\User\UsersController;
use App\Http\Controllers\Auth\LogoutController;

Route::prefix('auth')->group(function () {
    Route::post('login', LoginController::class);

    Route::post('logout', LogoutController::class)
        ->middleware('auth:sanctum');
});


Route::middleware(['auth:sanctum'])->group(function () {
    // Users
    Route::prefix('user')->group(function () {
       Route::get('/', [UsersController::class, 'index']);
    //    Route::get('all', [UsersController::class, 'usersAll']);
    //    Route::get('me', function (Request $request) {
    //        // dd($request->user());
    //        return $request->user();
    //    });
    //    Route::get('/{id}', [UsersController::class, 'show']);
    //    Route::post('store', [UsersController::class, 'store']);
    //    Route::put('update/{id}', [UsersController::class, 'update']);
    //    Route::put('/reset-password/{id}', [UsersController::class, 'resetPassword']);
    //    Route::put('update-password', [UsersController::class, 'updatepassword']);
    //    Route::delete('destroy/{id}', [UsersController::class, 'destroy']);
    });


});