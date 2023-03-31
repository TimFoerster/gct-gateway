<?php

use App\Http\Controllers\ChartApiController;
use App\Http\Controllers\SimulationApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('simulation')->controller(SimulationApiController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'getSimulation');
    Route::get('/{id}/log', 'simulationLog');

    Route::post('/start', 'start');
    Route::post('/{id}/log', 'log');
    Route::post('/{id}/log/file', 'logFile');
    Route::post('/{id}/log/import', 'importLogFile');
    Route::post('/{id}/accuracy', 'accuracy');
    Route::post('/{id}/accuracy/file', 'accuracyFile');
    Route::post('/{id}/end', 'end');
    Route::post('/{id}/send/file', 'sendFile');
    Route::post('/{id}/received/file', 'receivedFile');
    Route::post('/{id}/group/file', 'groupFile');
    Route::post('/{id}/screenshot', 'screenshot');
});

Route::prefix('chart')->controller(ChartApiController::class)->group(function () {
    Route::get('/scenarios', 'scenarios');
    Route::get('/seeds', 'seeds');
    Route::get('/appUpdates', 'appUpdates');
    Route::get('/algorithms', 'algorithms');

    Route::get('/simulations', 'simulations');
    Route::get('/device-groups', 'deviceGroups');

    Route::get('/data/series', 'dataSeries');

});
