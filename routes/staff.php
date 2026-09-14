<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\{
    AdminSettingsController,
    ExamController,
    FeeController,
    FeeStructureAssignmentController, // Added new controller
    FinancialStatementController,
    GradeController,
    ParentController,
    PaymentController,
    ReportCardController,
    ResultController,
    RolePermissionController,
    RolloverController,
    SchoolController,
    StaffController,
    StudentController,
    SubjectController
};

Route::prefix('staff')->name('staff.')->middleware(['auth:staff', 'throttle:60,1'])->group(function () {

    // Dashboard & Base Redirect
    Route::get('/', [StaffController::class, 'dashboard'])->name('/'); // Common practice to name the base of a group 'index' or similar if it's a view
    Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');

    // Admin Settings
    Route::post('/admin/settings/set-viewing-school', [AdminSettingsController::class, 'setViewingSchool'])
        ->middleware(['can:manage global admin settings', 'throttle:10,1'])
        ->name('admin.settings.set_viewing_school');

    // --- Student Operations ---
    Route::prefix('students')->name('students.')->group(function () {
        Route::get('bulk-management', [StudentController::class, 'bulkManagement'])->name('bulk.management');
        Route::get('bulk-assign-usernames', [StudentController::class, 'showBulkAssignUsernamesForm'])->name('bulk.assign_usernames.form');
        Route::post('bulk-assign-usernames', [StudentController::class, 'processBulkAssignUsernames'])->middleware('throttle:10,1')->name('bulk.assign_usernames.process');

        Route::get('import', [StudentController::class, 'showImportForm'])->name('import.form');
        Route::post('import', [StudentController::class, 'processImport'])->middleware('throttle:5,1')->name('import.process');
        Route::get('import-template', [StudentController::class, 'downloadImportTemplate'])->name('import.template');
        Route::get('import/mapping', [StudentController::class, 'showImportMappingForm'])->name('import.mapping.form');
        Route::post('import/mapping', [StudentController::class, 'processMappedImport'])->middleware('throttle:5,1')->name('import.process_mapped');

        Route::get('bulk-enroll', [StudentController::class, 'showBulkEnrollForm'])->name('bulk_enroll.form');
        Route::post('bulk-enroll', [StudentController::class, 'processBulkEnroll'])->middleware('throttle:10,1')->name('bulk_enroll.process');
        Route::get('bulk-activate', [StudentController::class, 'showBulkActivateForm'])->name('bulk_activate.form');
        Route::post('bulk-activate', [StudentController::class, 'processBulkActivate'])->middleware('throttle:10,1')->name('bulk_activate.process');

        // Routes for AJAX calls in student forms (e.g., edit.blade.php)
        Route::get('getSchools', [StudentController::class, 'getSchools'])->name('getSchools'); // Generic: gets schools, might need syear
        Route::get('getGrades', [StudentController::class, 'getGrades'])->name('getGrades');     // Generic: gets grades, might need syear and school_id

        // Route for Select2 AJAX student search (used in fee creation, etc.)
        Route::get('search-json', [StudentController::class, 'searchJson'])->name('search_json');
        
        // ***** CORRECTED ROUTE FOR STUDENT FEES JSON *****
        Route::get('{student}/fees-json', [StudentController::class, 'getFeesJson'])
            ->name('fees.json') // This will be named staff.students.fees.json
            ->where('student', '[0-9]+');


        // More specific routes for AJAX calls (recommended for clarity in JS)
        Route::get('schools-by-syear', [StudentController::class, 'getSchoolsBySyear'])->name('schools_by_syear'); // Used by edit.blade.php JS
        Route::get('grades-by-school-syear', [StudentController::class, 'getGradesBySchoolAndSyear'])->name('grades_by_school_syear'); // Added for edit.blade.php JS
        Route::get('ajax-suggest-username', [StudentController::class, 'ajaxSuggestUsername'])->name('ajax.suggest_username');


        Route::get('export', [StudentController::class, 'export'])->name('export');
        Route::get('print', [StudentController::class, 'print'])->name('print');
        Route::post('{student}/reset-password', [StudentController::class, 'reset_pass'])->middleware('throttle:5,1')->name('reset_password');

        // Corrected naming to avoid staff.students.staff.students...
        Route::delete('{student}/enrollments/{enrollment}', [StudentController::class, 'destroyEnrollment'])
            ->name('enrollments.destroy') // This will correctly become staff.students.enrollments.destroy
            ->where(['student' => '[0-9]+', 'enrollment' => '[0-9]+']);

        Route::prefix('{student}/payments')->name('payments.')->group(function() {
            Route::get('/', [PaymentController::class, 'studentPayments'])->name('index');
            Route::post('/', [PaymentController::class, 'store'])->middleware('throttle:10,1')->name('store');
            Route::get('/create', [PaymentController::class, 'create'])->name('create');
        });

        Route::prefix('{student}/finance')->name('finance.')->group(function () {
            Route::get('statement/{syear?}', [FinancialStatementController::class, 'show'])->where('syear', '[0-9]{4}')->name('statement.show');
            Route::get('statement/pdf/{syear?}', [FinancialStatementController::class, 'generatePdf'])->where('syear', '[0-9]{4}')->name('statement.pdf');
        });

        Route::get('/data', [StudentController::class, 'data'])->name('data'); // For DataTables
    });

    // Student CRUD (ensure this is correctly placed if students prefix group handles specific student actions)
    // This will create routes like staff.students.index, staff.students.show, etc.
    // It uses the base '/students' path relative to '/staff'
    Route::resource('students', StudentController::class)->names('students');


    // Fees
    Route::prefix('fees')->name('fees.')->group(function() {
        // Routes for managing Fee Definitions (Fee Structures)
        Route::prefix('definitions')->name('definitions.')->group(function () {
            Route::get('/', [FeeController::class, 'indexDefinitions'])->name('index');
            Route::get('/create', [FeeController::class, 'createDefinition'])->name('create');
            Route::post('/', [FeeController::class, 'storeDefinition'])->middleware('throttle:10,1')->name('store');
            Route::get('/{definition}/edit', [FeeController::class, 'editDefinition'])->name('edit');
            Route::put('/{definition}', [FeeController::class, 'updateDefinition'])->middleware('throttle:10,1')->name('update');
            Route::delete('/{definition}', [FeeController::class, 'destroyDefinition'])->middleware('throttle:10,1')->name('destroy');
        });

        // Routes for assigning predefined multi-installment fee structures
        // Name: staff.fees.structure.assign_create
        Route::get('structure/assign', [FeeStructureAssignmentController::class, 'create'])->name('structure.assign_create');
        // Name: staff.fees.structure.assign.store
        Route::post('structure/assign', [FeeStructureAssignmentController::class, 'store'])->middleware('throttle:5,1')->name('structure.assign.store');

        // Routes for single fee assignments (bulk or individual) handled by FeeController
        Route::get('assign-bulk', [FeeController::class, 'showAssignBulkForm'])->name('assign_bulk.form');
        Route::post('assign-bulk', [FeeController::class, 'assignBulk'])->middleware('throttle:10,1')->name('assign_bulk.store');
        Route::get('get-pupil-count', [FeeController::class, 'getPupilCount'])->name('get_pupil_count'); // AJAX helper
        Route::get('report', [FeeController::class, 'showReportForm'])->name('report.form');
        Route::post('report', [FeeController::class, 'generateReport'])->name('report.generate');
        Route::post('{fee}/waive', [FeeController::class, 'waive'])->middleware('throttle:10,1')->name('waive'); // {fee} here refers to Fee model ID for an installment
        Route::get('{fee}/print-invoice', [FeeController::class, 'printInvoice'])->name('print_invoice');
        Route::get('print-filtered', [FeeController::class, 'printFilteredFeeNames'])->name('print_filtered_names');
        Route::post('{fee}/adjust-waiver', [FeeController::class, 'adjustWaiver'])->name('adjust_waiver');

        // FeeController resource routes for individual fee installments (billing_fees)
        // These are distinct from FeeDefinition routes.
        Route::get('/', [FeeController::class, 'index'])->name('index'); // Lists fee installments
        Route::get('/create', [FeeController::class, 'create'])->name('create'); // Form for single fee installment
        Route::post('/', [FeeController::class, 'store'])->middleware('throttle:10,1')->name('store'); // Stores single fee installment
        Route::get('/{fee}', [FeeController::class, 'show'])->name('show'); // Shows a specific fee installment
        Route::get('/{fee}/edit', [FeeController::class, 'edit'])->name('edit'); // Form to edit fee installment
        Route::put('/{fee}', [FeeController::class, 'update'])->middleware('throttle:10,1')->name('update'); // Updates fee installment
        Route::delete('/{fee}', [FeeController::class, 'destroy'])->middleware('throttle:10,1')->name('destroy'); // Deletes fee installment
    });


    // Payments
    // Note: {student}/payments routes are defined under the student prefix group.
    // These are general payment routes if not student-specific.
    Route::resource('payments', PaymentController::class)->except(['create', 'store'])->names('payments');
    Route::get('payments/allocate-unlinked/form', [PaymentController::class, 'showAllocateUnlinkedForm'])->name('payments.allocate_unlinked.form');
    Route::post('payments/allocate-unlinked/process', [PaymentController::class, 'processAllocateUnlinked'])->name('payments.allocate_unlinked.process');
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])->middleware('throttle:10,1')->name('payments.refund');
    Route::post('payments/{payment}/transfer', [PaymentController::class, 'transferPayment'])->middleware('throttle:10,1')->name('payments.transfer');
    Route::get('payments/{payment}/reallocate/form', [PaymentController::class, 'showReallocateForm'])->name('payments.reallocate.form');
    Route::post('payments/{payment}/reallocate', [PaymentController::class, 'processReallocate'])->middleware('throttle:10,1')->name('payments.reallocate.process');
    Route::get('payments/assign-bulk/form', [PaymentController::class, 'showAssignBulkForm'])->name('payments.assign_bulk.form');
    Route::post('payments/assign-bulk/process', [PaymentController::class, 'processAssignBulk'])->name('payments.assign_bulk.process');
    Route::get('payments/report/form', [PaymentController::class, 'showReportForm'])->name('payments.report.form');

    // Finance Statements
    Route::prefix('finance/statements')->name('finance.statements.')->group(function () {
        Route::get('/', [FinancialStatementController::class, 'index'])->name('index'); // staff.finance.statements.index
        Route::get('batch', [FinancialStatementController::class, 'showBatchForm'])->name('batch.form'); // staff.finance.statements.batch.form
        Route::post('batch', [FinancialStatementController::class, 'generateBatchStatements'])->name('batch.process'); // staff.finance.statements.batch.process
        Route::get('batch-status/{batchId}', [FinancialStatementController::class, 'checkBatchStatus'])->name('batch.status'); // staff.finance.statements.batch.status
        Route::get('batch-history', [FinancialStatementController::class, 'listBatches'])->name('batch.history'); // staff.finance.statements.batch.history
        Route::get('progress', [FinancialStatementController::class, 'getProgress'])->name('progress');


        // ***** THIS IS THE ADDED ROUTE *****
        Route::get('batch/{batchId}/download', [FinancialStatementController::class, 'downloadBatchZip'])
            ->name('batch.download'); // This generates the name: staff.finance.statements.batch.download
    });

    // Staff Management
    Route::post('manage/staff/{staff}/reset-password', [StaffController::class, 'reset_pass'])->middleware('throttle:5,1')->name('manage.staff.reset_password');
    Route::resource('manage/staff', StaffController::class)->names('manage.staff'); // Path: /staff/manage/staff

    // School
    Route::get('schools/{school}/settings', [SchoolController::class, 'editSettings'])->name('schools.settings.edit');
    Route::put('schools/{school}/settings', [SchoolController::class, 'updateSettings'])->middleware('throttle:10,1')->name('schools.settings.update');
    Route::resource('schools', SchoolController::class)->names('schools');

    // Grades
    Route::get('grades/unassigned-students', [GradeController::class, 'showStudentsWithoutGrade'])->name('grades.unassigned');
    Route::get('grades/{grade}/assign-students', [GradeController::class, 'showAssignStudentsForm'])->name('grades.assign.form');
    Route::post('grades/{grade}/assign-students', [GradeController::class, 'processAssignStudents'])->middleware('throttle:10,1')->name('grades.assign.process');

    // NEW: Route to view students enrolled in a specific grade
    Route::get('grades/{grade}/enrolled-students', [GradeController::class, 'viewEnrolledStudents'])->name('grades.enrolled_students');

    // NEW: Route to remove (soft delete) a student's enrollment from a grade
    // The {enrollment} parameter will be the ID of the StudentEnrollment record
    Route::delete('grades/enrollments/{enrollment}/remove', [GradeController::class, 'removeStudentEnrollment'])
        ->name('grades.enrollments.remove')
        ->where(['enrollment' => '[0-9]+']); // Ensure enrollment ID is numeric

    Route::resource('grades', GradeController::class)->except(['show'])->names('grades');


    // Parents Management
    Route::post('manage/parents/{parent}/reset-password', [ParentController::class, 'reset_pass'])->middleware('throttle:5,1')->name('manage.parents.reset_password');
    Route::resource('manage/parents', ParentController::class)->names('manage.parents'); // Path: /staff/manage/parents

    // Rollover
    // Note on throttle: '1,10' (1 attempt per 10 minutes) is very strict. Ensure this is intended.
    // Consider 'throttle:3,10' (3 attempts per 10 minutes) or 'throttle:10,1' (10 attempts per minute) if retries are common.
    Route::get('rollover', [RolloverController::class, 'showRolloverForm'])->name('rollover.form');
    Route::post('rollover', [RolloverController::class, 'processRollover'])->middleware(['can:perform school rollover', 'throttle:1,10'])->name('rollover.process');

    // Staff Profile (current authenticated staff)
    Route::get('profile', [StaffController::class, 'profile'])->name('profile.show');
    Route::get('profile/edit', [StaffController::class, 'editProfile'])->name('profile.edit');
    Route::put('profile', [StaffController::class, 'updateProfile'])->middleware('throttle:10,1')->name('profile.update');
    

    // Reset Password

    // Roles & Permissions
    Route::resource('roles', RolePermissionController::class)->middleware('can:manage roles and permissions')->names('roles');

    // Results, Exams, Report Cards
    Route::resource('results', ResultController::class)->names('results');
    Route::resource('exams', ExamController::class)->names('exams');

    // ReportCardController Routes:
    Route::resource('reports', ReportCardController::class)->names('reports'); // URI: /staff/reports
    Route::resource('reportcards', ReportCardController::class)->parameters(['reportcards' => 'reportcard'])->names('reportcards'); // URI: /staff/reportcards
    Route::patch('reportcards/{reportcard}/toggle-publish', [ReportCardController::class, 'togglePublish'])->name('reportcards.togglePublish');

    Route::resource('subjects', SubjectController::class)->names('subjects');
    // Modules (like Letters)
    Route::prefix('letters')->name('letters.')->group(function () { // Added name('letters.') for consistency if module routes use relative naming
        $lettersRoute = base_path('Modules/Letters/Routes/web.php');
        if (file_exists($lettersRoute)) {
            include $lettersRoute;
        }
    });

    // Secure photo routes
    Route::get('photo/{filename}', [StaffController::class, 'securePhoto'])->name('photo.secure'); // This is staff.photo.secure (using this one for clarity)

});
