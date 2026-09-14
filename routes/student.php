<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Student\StudentDashboardController; // Assuming you have a dashboard controller
use App\Http\Controllers\Student\StudentProfileController;   // Assuming you have a profile controller
use App\Http\Controllers\Student\StudentPaymentController;  // The controller we created
// Add other student-specific controllers here as needed
// E.g., StudentCourseController, StudentResultController etc.

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application's
| student-facing area. These routes are typically loaded by the
| RouteServiceProvider within a group which
| is assigned the "web" middleware group, "auth:student" guard,
| and a "student" prefix.
|
*/

Route::prefix('student')->name('student.')->middleware(['auth:student', 'throttle:60,1'])->group(function () {

// Student Dashboard
Route::get('/dashboard', [StudentDashboardController::class, 'dashboard'])->name('dashboard');
// Student Profile
Route::prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [StudentProfileController::class, 'show'])->name('show'); // student.profile.show
    Route::get('/edit', [StudentProfileController::class, 'edit'])->name('edit'); // student.profile.edit
    Route::put('/', [StudentProfileController::class, 'update'])->name('update'); // student.profile.update
    Route::get('/change-password', [StudentProfileController::class, 'changePasswordForm'])->name('password.change'); // student.profile.password.change
    Route::post('/change-password', [StudentProfileController::class, 'updatePassword'])->name('password.update'); // student.profile.password.update
});

// Student Payments
Route::prefix('payments')->name('payments.')->group(function () {
    // Shows the student's payment history and fee status
    Route::get('/', [StudentPaymentController::class, 'myPayments'])->name('my'); // student.payments.my

    // Shows details of a specific payment
    // The {payment} parameter will be the ID of the Payment record
    Route::get('/{payment}', [StudentPaymentController::class, 'showPaymentDetails'])
        ->where('payment', '[0-9]+') // Ensure payment ID is numeric
        ->name('show'); // student.payments.show

    // Allows student to download a receipt for their payment
    Route::get('/{payment}/receipt', [StudentPaymentController::class, 'downloadReceipt'])
        ->where('payment', '[0-9]+') // Ensure payment ID is numeric
        ->name('receipt.download'); // student.payments.receipt.download

    // Placeholder for initiating a new payment (e.g., via a payment gateway)
    // Route::get('/make-payment', [StudentPaymentController::class, 'showMakePaymentForm'])->name('make');
    // Route::post('/process-payment', [StudentPaymentController::class, 'processOnlinePayment'])->name('process');
});

// Placeholder for Student Academic Information (e.g., courses, results)
// Route::prefix('academics')->name('academics.')->group(function () {
//     Route::get('/my-courses', [StudentCourseController::class, 'myCourses'])->name('courses');
//     Route::get('/my-results', [StudentResultController::class, 'myResults'])->name('results');
//     Route::get('/report-card/{term_id?}', [StudentResultController::class, 'viewReportCard'])->name('report_card');
// });

// Secure photo route for student's own photo (if applicable)
// Route::get('my-photo', [StudentProfileController::class, 'securePhoto'])->name('photo.mine');


// Example of how you might link to a specific module if students have access
// Route::prefix('learning-materials')->name('learning_materials.')->group(function () {
//     // Assuming a LearningMaterialController exists for students
//     Route::get('/', [App\Http\Controllers\Student\LearningMaterialController::class, 'index'])->name('index');
//     Route::get('/{material}', [App\Http\Controllers\Student\LearningMaterialController::class, 'show'])->name('show');
// });
});

