<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\fileController;
use App\Http\Controllers\BeatController;
use App\Http\Controllers\PartySaleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SalesmanController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RouteMasterController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register.show');
Route::post('/register', [AuthController::class, 'register'])->name('register.perform');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/file-upload', [App\Http\Controllers\fileController::class, 'file_upload'])->name('fileUpload');
});

Route::middleware(['auth', 'role:admin,super_admin'])->group(function () {
    Route::get('/salesman-report', [App\Http\Controllers\SalesmanController::class, 'report_table'])->name('reportTable');
    Route::post('/upload-excel', [fileController::class, 'uploadExcel'])->name('upload.excel');

    Route::resource('beats', BeatController::class);
    Route::resource('routes', RouteMasterController::class);

    Route::post('bulk-update', [PartySaleController::class, 'bulkUpdate'])->name('bulk-update');
    Route::post('bulk-sale-update', [SalesmanController::class, 'bulkSaleUpdate'])->name('bulk-sale-update');
    Route::get('party-sales-download', [PartySaleController::class, 'download'])->name('party-sales.download');
    Route::resource('party-sales', PartySaleController::class);

    Route::resource('customers', CustomerController::class);
    Route::resource('products', ProductController::class);

    Route::get('/customers/{customer}/transactions', [CustomerController::class, 'transactions'])
        ->name('customers.transactions');


    // Show salesman table page
    Route::get('sales-man', [SalesmanController::class, 'index'])->name('salesman');
    Route::get('/sales-report/download', [SalesmanController::class, 'downloadReport'])->name('sales-report.download');

    // Show payment entries for a salesman (POST request from button)
    Route::post('sales-man', [SalesmanController::class, 'salesManDetails'])->name('salesman.reports');

    Route::get('/trip-sheet', [fileController::CLASS, 'trip_sheet_report'])->name('trip.report');
    Route::get('/credit-details-popup', [fileController::class, 'credit_popup']);
    Route::post('/trip-sheet/save', [fileController::class, 'save_trip'])->name('trip.save');
    Route::get('/trip-details', [fileController::class, 'trip_details'])->name('trip.details');
    Route::post('/trip-details/update', [fileController::class, 'trip_details_update'])->name('trip.details.update');
    Route::delete('/trip-details/delete', [fileController::class, 'delete_trip_sheet'])->name('trip.details.delete');
    Route::get('/trip-details-routes', [fileController::class, 'trip_details_routes'])->name('trip.details.routes');

    Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::get('/collections/download', [CollectionController::class, 'download'])->name('collections.download');
    Route::get('/manual-stocks', [ProductController::class, 'manual_stock_report'])->name('manualStocks');
});
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::resource('users', UserController::class);
});

