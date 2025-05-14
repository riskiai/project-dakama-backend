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
use App\Http\Controllers\Project\TaskController;
use App\Http\Controllers\Project\BudgetController;
use App\Http\Controllers\Project\ProjectController;

Route::prefix('auth')->group(function () {
    Route::post('login', LoginController::class);

    Route::post('logout', LogoutController::class)
        ->middleware('auth:sanctum');
});


/* Users Not Login */
Route::post('store-notlogin', [UsersController::class, 'storeNotLogin']);
Route::put('updatepassword-email', [UsersController::class, 'UpdatePasswordWithEmail']);
Route::put('updatepassword-emailtoken', [UsersController::class, 'UpdatePasswordWithEmailToken']);
Route::put('verify-token', [UsersController::class, 'verifyTokenAndUpdatePassword']);
Route::get('cektoken', [UsersController::class, 'cekToken']);

Route::middleware(['auth:sanctum'])->group(function () {
    // Users
    Route::prefix('user')->group(function () {
       Route::get('/', [UsersController::class, 'index']);
       Route::get('all', [UsersController::class, 'usersAll']);
       Route::get('me', function (Request $request) {
           // dd($request->user());
           return $request->user();
       });
       Route::get('/{id}', [UsersController::class, 'show']);
       Route::post('store', [UsersController::class, 'store']);
       Route::put('update/{id}', [UsersController::class, 'update']);
       Route::put('/reset-password/{id}', [UsersController::class, 'resetPassword']);
       Route::put('update-password', [UsersController::class, 'updatepassword']);
       Route::delete('destroy/{id}', [UsersController::class, 'destroy']);
    });

    // Divisi
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

    // Project
    Route::prefix('project')->group(function () {
        // Task Project
        Route::get('task', [TaskController::class, 'index']);
        Route::get('taskall', [TaskController::class, 'indexall']);
        Route::post('task-create', [TaskController::class, 'store']);
        Route::get('task/{id}', [TaskController::class, 'show']);
        Route::put('task-edit/{id}', [TaskController::class, 'update']);
        Route::delete('task-delete/{id}', [TaskController::class, 'destroy']);

        // Budget Project
        Route::get('budget', [BudgetController::class, 'index']);
        Route::get('budgetall', [BudgetController::class, 'indexall']);
        Route::post('budget-create', [BudgetController::class, 'store']);
        Route::get('budget/{id}', [BudgetController::class, 'show']);
        Route::put('budget-edit/{id}', [BudgetController::class, 'update']);
        Route::delete('budget-delete/{id}', [BudgetController::class, 'destroy']);

        // Projects
        Route::get('/', [ProjectController::class, 'index']);
        Route::get('/all', [ProjectController::class, 'projectAll']);
        Route::get('/names', [ProjectController::class, 'indexAll']);
        Route::get('/counting', [ProjectController::class, 'counting']);
        Route::get('/{id}', [ProjectController::class, 'show']);
        Route::post('/create-project', [ProjectController::class, 'createProject']);
        Route::put('/update/{id}', [ProjectController::class, 'update']);
        Route::put('/accept/{id}', [ProjectController::class, 'accept']);
        Route::put('/reject/{id}', [ProjectController::class, 'reject']);
        Route::put('/closed/{id}', [ProjectController::class, 'closed']);
        Route::put('/bonus/{id}', [ProjectController::class, 'bonus']);
        Route::put('/cancel/{id}', [ProjectController::class, 'cancel']);
        Route::delete('/delete/{id}', [ProjectController::class, 'destroy']);

        // Project Termin
        Route::post('/payment-termin/{id}', [ProjectController::class, 'paymentTermin']);
        Route::put('/update-termin/{id}', [ProjectController::class, 'updateTermin']);
        Route::delete('/delete-termin/{id}', [ProjectController::class, 'deleteTermin']);

     

    });

});