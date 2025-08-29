<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\User\UsersController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ContactTypeController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MutationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperationalController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Project\TaskController;
use App\Http\Controllers\Project\BudgetController;
use App\Http\Controllers\Project\LocationController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Project\SetUsersProjectController;
use App\Http\Controllers\Purchase\PurchaseController;
use App\Http\Controllers\Purchase\PurchaseStatusController;
use App\Http\Controllers\Purchase\PurchaseCategoryController;

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
        Route::get('me', [UsersController::class, 'me']);
        Route::get('/{id}', [UsersController::class, 'show']);
        Route::post('store', [UsersController::class, 'store']);
        Route::put('update/{id}', [UsersController::class, 'update']);
        Route::put('update-status-tidak-aktif/{id}', [UsersController::class, 'updateStatusTidakAkitf']);
        Route::put('update-status-aktif/{id}', [UsersController::class, 'updateStatusAkitf']);
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
        Route::prefix('assign-location')->group(function () {
            Route::get('/', [LocationController::class, 'index']);
            Route::get('/{id}', [LocationController::class, 'show']);
            Route::post('/', [LocationController::class, 'store']);
            Route::put('/{id}', [LocationController::class, 'update']);
            Route::delete('/{id}', [LocationController::class, 'destroy']);
        });

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

        // Set Users Dan Project Untuk Data Absen
        Route::get('setuser-project-absen', [SetUsersProjectController::class, 'index']);
        Route::get('setuser-project-absen-all', [SetUsersProjectController::class, 'indexAll']);
        Route::post('setuser-project-absen-create', [SetUsersProjectController::class, 'store']);
        Route::put('setuser-project-absen-update/{id}', [SetUsersProjectController::class, 'update']);
        Route::put('setuser-project-absen-bulk-update/{project}',
            [SetUsersProjectController::class, 'bulkUpdate']
        );
        Route::get('setuser-project-absen-show/{id}', [SetUsersProjectController::class, 'show']);
        Route::delete('setuser-project-absen-delete/{id}', [SetUsersProjectController::class, 'delete']);

        // Projects
        Route::get('/', [ProjectController::class, 'index']);
        Route::get('/all', [ProjectController::class, 'projectAll']);
        Route::get('/names', [ProjectController::class, 'indexAll']);
        Route::get('/allnames', [ProjectController::class, 'nameAll']);
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

    Route::prefix('purchase-category')->group(function () {
        Route::get('/', [PurchaseCategoryController::class, 'index']);
        Route::get('/{id}', [PurchaseCategoryController::class, 'show']);
    });

    // end point puchase status
    Route::prefix('purchase-status')->group(function () {
        Route::get('/', [PurchaseStatusController::class, 'index']);
        Route::get('/{id}', [PurchaseStatusController::class, 'show']);
    });

    Route::prefix('purchase')->group(function () {
        Route::get('/', [PurchaseController::class, 'index']);
        Route::get('/get-product-purchase', [PurchaseController::class, 'getDataProductPurchase']);
        Route::get('/all', [PurchaseController::class, 'indexAll']);
        Route::get('/counting-purchase', [PurchaseController::class, 'countingPurchase']);
        Route::get('/{id}', [PurchaseController::class, 'show']);
        Route::post('/create-purchase', [PurchaseController::class, 'createPurchase']);
        Route::put('/update-purchase/{id}', [PurchaseController::class, 'updatePurchase']);
        Route::put('/reject/{id}', [PurchaseController::class, 'rejectPurchase']);
        Route::put('/undo/{id}', [PurchaseController::class, 'undoPurchase']);
        Route::put('/activate/{id}', [PurchaseController::class, 'activatePurchase']);
        Route::put('/accept/{id}', [PurchaseController::class, 'acceptPurchase']);
        Route::put('/request/{id}', [PurchaseController::class, 'requestPurchase']);
        Route::put('/payment/{id}', [PurchaseController::class, 'paymentPurchase']);
        Route::put('/update-payment/{id}', [PurchaseController::class, 'updatePaymentPurchase']);
        Route::delete('/delete-purchase/{id}', [PurchaseController::class, 'destroy']);
        Route::delete('/delete-document/{id}', [PurchaseController::class, 'destroyDocument']);
    });

    Route::prefix('attendance')->group(function () {
        Route::get('/index', [AttendanceController::class, 'index']);
        Route::get('/show', [AttendanceController::class, 'show']);
        Route::get('/show-me', [AttendanceController::class, 'showMe']);
        Route::post('/store', [AttendanceController::class, 'store']);
        Route::post('/sync', [AttendanceController::class, 'sync']);
        Route::delete('/destroy-bulk', [AttendanceController::class, 'destroy']);

        Route::prefix('adjustment')->group(function () {
            Route::get('/index', [AttendanceController::class, 'adjustmentIndex']);
            Route::post('/store', [AttendanceController::class, 'adjustmentStore']);
            Route::put('/update/{id}', [AttendanceController::class, 'adjustmentUpdate']);
            Route::put('/approval', [AttendanceController::class, 'adjustmentApproval']);
            Route::delete('/destroy/{id}', [AttendanceController::class, 'adjustmentDestroy']);
        });
    });

    Route::prefix('overtime')->group(function () {
        Route::get('/index', [OvertimeController::class, 'index']);
        Route::post('/store', [OvertimeController::class, 'store']);
        Route::get('/show/current', [OvertimeController::class, 'showCurrent']);
        Route::get('/show/{id}', [OvertimeController::class, 'show']);
        Route::put('/update/{id}', [OvertimeController::class, 'update']);
        Route::put('/approval/{id}', [OvertimeController::class, 'approval']);
        Route::delete('/destroy/{id}', [OvertimeController::class, 'destroy']);
    });

    Route::prefix('loan')->group(function () {
        Route::get('/index', [LoanController::class, 'index']);
        Route::post('/store', [LoanController::class, 'store']);
        Route::get('/show/{id}', [LoanController::class, 'show']);
        Route::put('/update/{id}', [LoanController::class, 'update']);
        Route::put('/approval/{id}', [LoanController::class, 'approval']);
        Route::delete('/destroy/{id}', [LoanController::class, 'destroy']);
        Route::post('/payment/{id}', [LoanController::class, 'payment']);
    });

    Route::prefix('mutation')->group(function () {
        Route::get('loan', [MutationController::class, 'getLoan']);
    });

    Route::prefix('operational')->group(function () {
        Route::get('show', [OperationalController::class, 'show']);
        Route::post('save', [OperationalController::class, 'save']);
    });

    Route::prefix('payroll')->group(function () {
        Route::get('/index', [PayrollController::class, 'index']);
        Route::get('/counting', [PayrollController::class, 'counting']);
        Route::post('/store', [PayrollController::class, 'store']);
        Route::get('/show/{id}', [PayrollController::class, 'show']);
        Route::put('/approval/{id}', [PayrollController::class, 'approval']);
        Route::delete('/destroy/{id}', [PayrollController::class, 'destroy']);
    });

    Route::prefix('permission')->group(function () {
        Route::get('/index', [PermissionController::class, 'index']);
        Route::post('/store', [PermissionController::class, 'store']);
        Route::post('/assign', [PermissionController::class, 'assign']);
        Route::post('/unassign', [PermissionController::class, 'unassign']);
        Route::put('/update/{id}', [PermissionController::class, 'update']);
        Route::delete('/destroy/{id}', [PermissionController::class, 'destroy']);
    });

    Route::prefix('notification')->group(function () {
        Route::get('/index', [NotificationController::class, 'index']);
        Route::get('/show/{id}', [NotificationController::class, 'show']);
        Route::delete('/destroy-bulk', [NotificationController::class, 'destroy']);
    });
});
