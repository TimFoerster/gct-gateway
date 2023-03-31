<?php

use App\Http\Controllers\CalculationWebController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\DeviceWebController;
use App\Http\Controllers\SimulationWebController;
use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

Route::controller(WebController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/download', 'download');
    Route::post('/cleanup', 'cleanup');
    Route::post('/reset', 'reset');
});


Route::prefix('chart')->controller(ChartController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/radar', 'radar');
    Route::get('/series', 'series');
    Route::get('/series/data', 'seriesData');
    Route::get('/simulations', 'simulationData');
    Route::get('/devices', 'deviceData');
});


Route::prefix('calculation')->controller(CalculationWebController::class)->group(function () {
    Route::get('/{id}', 'detail');
    Route::get('/compare/{id}', 'compare');

});

Route::prefix('simulation')->controller(SimulationWebController::class)->group(function () {

    Route::get('/simulations.json', 'simulations');
    Route::get('/{id}', 'detail');
    Route::get('/{id}/{deviceId}/received', 'receivedMessages');
    Route::delete('/{id}/received', 'deleteReceived');
    Route::post('/{id}/reset', 'reset');
});

Route::prefix('device')->controller(DeviceWebController::class)->group(function () {
    Route::get('/{id}', 'detail');
    Route::get('/{id}/received.json', 'receivedMessages');
    Route::get('/{id}/send.json', 'sendMessages');
});

Route::prefix('compare')->controller(CompareController::class)->group(function () {
    Route::get('/device/{deviceId}', 'compareDevice');
});

Route::any('missing', function () {
    \App\Jobs\AddMissingDevicesJob::dispatchSync();
});

