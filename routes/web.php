<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\fileController;
use App\Http\Controllers\BeatController;
use App\Http\Controllers\PartySaleController;
use App\Http\Controllers\CustomerController;

Route::get('/', function () {
    return view('welcome');
})->name('home');
Route::get('/file-upload', [App\Http\Controllers\fileController::CLASS, 'file_upload'])->name('fileUpload');
Route::post('/upload-excel', [fileController::class, 'uploadExcel'])->name('upload.excel');

Route::resource('beats', BeatController::class);

Route::post('bulk-update', [PartySaleController::class, 'bulkUpdate'])
    ->name('bulk-update');
Route::get('party-sales-download', [PartySaleController::class, 'download'])
    ->name('party-sales.download');
Route::resource('party-sales', PartySaleController::class);

Route::resource('customers', CustomerController::class);