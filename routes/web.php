<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConversionController;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/convert', [ConversionController::class, 'store'])->name('convert.store');
Route::get('/convert/{id}', [ConversionController::class, 'show'])->name('convert.show');// status
Route::get('/convert/{id}/download', [ConversionController::class, 'download'])->name('convert.download');