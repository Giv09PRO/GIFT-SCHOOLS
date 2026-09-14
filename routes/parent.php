<?php

use App\Http\Controllers\Parent\ParentController;
use App\Http\Controllers\Parent\PaymentController;
use App\Http\Controllers\Parent\ResultController;
use App\Http\Controllers\Parent\ExamController;
use App\Http\Controllers\Parent\FeeController;
use App\Http\Controllers\Parent\StudentController;
use Illuminate\Support\Facades\Route;

// Parent Routes
Route::middleware(['auth:parents'])->group(function () {
    Route::get('/parent/dashboard', [ParentController::class, 'dashboard'])->name('parent.dashboard');


// Student Management Routes
    Route::prefix('parent/students')->name('parent.students.')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->name('index'); // Viewing students
        Route::get('{id}', [StudentController::class, 'show'])->name('show'); // Viewing individual student
    });

// Fees Management Routes
    Route::prefix('parent/fees')->name('parent.fees.')->group(function () {
        Route::get('/', [FeeController::class, 'feesIndex'])->name('index'); // Viewing fees
        Route::get('{id}', [FeeController::class, 'feesShow'])->name('show'); // Viewing individual fee
    });

// Payments Management Routes
    Route::prefix('parent/payments')->name('parent.payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'paymentsIndex'])->name('index'); // Viewing payments
        Route::get('{id}', [PaymentController::class, 'paymentsShow'])->name('show'); // Viewing individual payment
    });

// Exam Management Routes
    Route::prefix('parent/exams')->name('parent.exams.')->group(function () {
        Route::get('/', [ExamController::class, 'examsIndex'])->name('index'); // Viewing exams
        Route::get('{id}', [ExamController::class, 'examsShow'])->name('show'); // Viewing individual exam
    });

// Results Management Routes
    Route::prefix('parent/results')->name('parent.results.')->group(function () {
        Route::get('/', [ResultController::class, 'resultsIndex'])->name('index'); // Viewing results
        Route::get('{id}', [ResultController::class, 'resultsShow'])->name('show'); // Viewing individual result
    });
});
