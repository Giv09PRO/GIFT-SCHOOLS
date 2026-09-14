<?php

use Illuminate\Support\Facades\Route;
use Modules\Letters\Http\Controllers\LettersController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('letters', LettersController::class)->names('letters');
});
