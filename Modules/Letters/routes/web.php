<?php

use Illuminate\Support\Facades\Route;
use Modules\Letters\Http\Controllers\LettersController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('letters', LettersController::class)->names('letters');
});
