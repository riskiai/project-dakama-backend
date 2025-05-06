<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\User\UsersController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ContactTypeController;

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

    Route::get('divisi', [DivisiController::class, 'index']);
    Route::get('divisiall', [DivisiController::class, 'divisiall']);
    Route::post('divisi-store', [DivisiController::class, 'store']);
    Route::get('divisi/{id}', [DivisiController::class, 'show']);
    Route::put('divisi-update/{id}', [DivisiController::class, 'update']);
    Route::delete('divisi-destroy/{id}', [DivisiController::class, 'destroy']);

    // Tax
    Route::get('tax', [TaxController::class, 'index']);
    Route::post('tax-store', [TaxController::class, 'store']);
    Route::get('tax/{id}', [TaxController::class, 'show']);
    Route::put('tax-update/{id}', [TaxController::class, 'update']);
    Route::delete('tax-destroy/{id}', [TaxController::class, 'destroy']);

    /* Contact */
    Route::get('contact', [ContactController::class, 'index']);
    Route::get('contact/name/all', [ContactController::class, 'companyAll']);
    Route::post('contact-store', [ContactController::class, 'store']);
    Route::post('contact-update/{id}', [ContactController::class, 'update']);
    Route::get('contact/{id}', [ContactController::class, 'show']);
    Route::get('contactall', [ContactController::class, 'contactall']);
    Route::get('contact-showtype', [ContactController::class, 'showByContactType']);
    Route::delete('contact-destroy/{id}', [ContactController::class, 'destroy']);

    // ContactType
        Route::prefix('contact-type')->group(function () {
           Route::get('/', [ContactTypeController::class, 'index']);
           Route::get('/{id}', [ContactTypeController::class, 'show']);
       });

});