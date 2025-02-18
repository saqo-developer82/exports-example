<?php

use Exports\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('/export')->group(function () {
    Route::post('/{method?}', [ExportController::class, 'entityExport']);
});
