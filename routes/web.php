<?php

use App\Http\Controllers\PayrollController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('payroll/document/{id}/{type}', [PayrollController::class, 'getDocument'])
    ->whereIn('type', ['preview', 'download'])
    ->name('payroll.document');
