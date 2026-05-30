<?php

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Current authenticated user
    Route::get('/user', fn (Request $request) => new UserResource($request->user()));

    // ---- Étudiants & Classes ----
    Route::get('students', [\App\Http\Controllers\Api\StudentController::class, 'index'])->middleware('permission:students:read');
    Route::get('students/{student}', [\App\Http\Controllers\Api\StudentController::class, 'show'])->middleware('permission:students:read');
    Route::post('students', [\App\Http\Controllers\Api\StudentController::class, 'store'])->middleware('permission:students:write');
    Route::put('students/{student}', [\App\Http\Controllers\Api\StudentController::class, 'update'])->middleware('permission:students:write');
    Route::patch('students/{student}', [\App\Http\Controllers\Api\StudentController::class, 'update'])->middleware('permission:students:write');
    Route::delete('students/{student}', [\App\Http\Controllers\Api\StudentController::class, 'destroy'])->middleware('permission:students:write');

    // ---- Dossier Élève (Présences/Documents) ----
    Route::get('students/{student}/attendance', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'index'])->middleware('permission:students:read');
    Route::get('students/{student}/attendance/summary', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'summary'])->middleware('permission:students:read');
    Route::post('students/{student}/attendance', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'store'])->middleware('permission:students:write');
    Route::put('students/{student}/attendance/{attendance}', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'update'])->middleware('permission:students:write');
    Route::delete('students/{student}/attendance/{attendance}', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'destroy'])->middleware('permission:students:write');

    Route::get('students/{student}/documents', [\App\Http\Controllers\Api\StudentDocumentController::class, 'index'])->middleware('permission:students:read');
    Route::get('students/{student}/documents/{document}/download', [\App\Http\Controllers\Api\StudentDocumentController::class, 'download'])->middleware('permission:students:read');
    Route::post('students/{student}/documents', [\App\Http\Controllers\Api\StudentDocumentController::class, 'store'])->middleware('permission:students:write');
    Route::delete('students/{student}/documents/{document}', [\App\Http\Controllers\Api\StudentDocumentController::class, 'destroy'])->middleware('permission:students:write');

    // ---- Géographie RDC (cascade adresses) ----
    Route::get('locations/provinces', [\App\Http\Controllers\Api\LocationController::class, 'provinces']);
    Route::get('locations/cities', [\App\Http\Controllers\Api\LocationController::class, 'cities']);
    Route::get('locations/communes', [\App\Http\Controllers\Api\LocationController::class, 'communes']);

    // ---- Classes scolaires (référentiel RDC) ----
    Route::get('classes/catalog', [\App\Http\Controllers\Api\SchoolClassController::class, 'catalog'])->middleware('permission:classes:read');
    Route::get('classes', [\App\Http\Controllers\Api\SchoolClassController::class, 'index'])->middleware('permission:classes:read');
    Route::post('classes', [\App\Http\Controllers\Api\SchoolClassController::class, 'store'])->middleware('permission:classes:write');
    Route::get('classes/{class}', [\App\Http\Controllers\Api\SchoolClassController::class, 'show'])->middleware('permission:classes:read');
    Route::put('classes/{class}', [\App\Http\Controllers\Api\SchoolClassController::class, 'update'])->middleware('permission:classes:write');
    Route::patch('classes/{class}', [\App\Http\Controllers\Api\SchoolClassController::class, 'update'])->middleware('permission:classes:write');
    Route::delete('classes/{class}', [\App\Http\Controllers\Api\SchoolClassController::class, 'destroy'])->middleware('permission:classes:write');
    Route::get('classes/{class}/students', [\App\Http\Controllers\Api\SchoolClassController::class, 'students'])->middleware('permission:classes:read');
    Route::apiResource('subjects', \App\Http\Controllers\Api\SubjectController::class);

    // ---- Finance (Paiements / Tranches / Configuration) ----
    Route::get('payments', [\App\Http\Controllers\Api\PaymentController::class, 'index'])->middleware('permission:finance:read');
    Route::get('payments/{payment}', [\App\Http\Controllers\Api\PaymentController::class, 'show'])->middleware('permission:finance:read');
    Route::post('payments', [\App\Http\Controllers\Api\PaymentController::class, 'store'])->middleware('permission:finance:write');
    Route::put('payments/{payment}', [\App\Http\Controllers\Api\PaymentController::class, 'update'])->middleware('permission:finance:write');
    Route::delete('payments/{payment}', [\App\Http\Controllers\Api\PaymentController::class, 'destroy'])->middleware('permission:finance:write');
    Route::get('payment-plans', [\App\Http\Controllers\Api\PaymentPlanController::class, 'index'])->middleware('permission:finance:read');
    Route::post('payment-plans', [\App\Http\Controllers\Api\PaymentPlanController::class, 'store'])->middleware('permission:finance:write');
    Route::post('payment-installments/{installmentId}/pay', [\App\Http\Controllers\Api\PaymentPlanController::class, 'payInstallment'])->middleware('permission:finance:write');
    Route::get('finance/config', [\App\Http\Controllers\Api\FinanceConfigController::class, 'index'])->middleware('permission:finance:read');
    Route::post('finance/config/fee-types', [\App\Http\Controllers\Api\FinanceConfigController::class, 'storeFeeType'])->middleware('permission:finance:write');
    Route::post('finance/config/installment-types', [\App\Http\Controllers\Api\FinanceConfigController::class, 'storeInstallmentType'])->middleware('permission:finance:write');
    Route::post('finance/config/fee-rates', [\App\Http\Controllers\Api\FinanceConfigController::class, 'storeFeeRate'])->middleware('permission:finance:write');

    // ---- Discipline ----
    Route::get('discipline/cases', [\App\Http\Controllers\Api\DisciplinaryController::class, 'index'])->middleware('permission:discipline:read');
    Route::post('discipline/cases', [\App\Http\Controllers\Api\DisciplinaryController::class, 'store'])->middleware('permission:discipline:write');
    Route::get('discipline/cases/{id}', [\App\Http\Controllers\Api\DisciplinaryController::class, 'show'])->middleware('permission:discipline:read');
    Route::put('discipline/cases/{id}', [\App\Http\Controllers\Api\DisciplinaryController::class, 'update'])->middleware('permission:discipline:write');
    Route::delete('discipline/cases/{id}', [\App\Http\Controllers\Api\DisciplinaryController::class, 'destroy'])->middleware('permission:discipline:write');
    Route::post('discipline/cases/{id}/actions', [\App\Http\Controllers\Api\DisciplinaryController::class, 'addAction'])->middleware('permission:discipline:write');
    Route::post('discipline/cases/{id}/notes', [\App\Http\Controllers\Api\DisciplinaryController::class, 'addNote'])->middleware('permission:discipline:write');

    // ---- Communication (Annonces) ----
    Route::apiResource('announcements', \App\Http\Controllers\Api\AnnouncementController::class);
    Route::post('announcements/{id}/read', [\App\Http\Controllers\Api\AnnouncementController::class, 'markRead']);

    // ---- Événements ----
    Route::apiResource('events', \App\Http\Controllers\Api\SchoolEventController::class);

    // ---- Inventaire ----
    Route::apiResource('inventory', \App\Http\Controllers\Api\InventoryItemController::class)->middleware('permission:inventory:read');

    // ---- Admissions ----
    Route::apiResource('admissions', \App\Http\Controllers\Api\AdmissionController::class)->middleware('permission:admissions:read');

    // ---- Gestion Utilisateurs (admin) ----
    Route::get('users', [\App\Http\Controllers\Api\UserController::class, 'index'])->middleware('permission:users:read');
    Route::get('users/{user}', [\App\Http\Controllers\Api\UserController::class, 'show'])->middleware('permission:users:read');
    Route::post('users', [\App\Http\Controllers\Api\UserController::class, 'store'])->middleware('permission:users:write');
    Route::put('users/{user}', [\App\Http\Controllers\Api\UserController::class, 'update'])->middleware('permission:users:write');
    Route::patch('users/{user}', [\App\Http\Controllers\Api\UserController::class, 'update'])->middleware('permission:users:write');
    Route::delete('users/{user}', [\App\Http\Controllers\Api\UserController::class, 'destroy'])->middleware('permission:users:write');

    Route::get('roles', [\App\Http\Controllers\Api\RoleController::class, 'index'])->middleware('permission:users:read');
    Route::get('roles/{role}', [\App\Http\Controllers\Api\RoleController::class, 'show'])->middleware('permission:users:read');
    Route::post('roles', [\App\Http\Controllers\Api\RoleController::class, 'store'])->middleware('permission:users:write');
    Route::put('roles/{role}', [\App\Http\Controllers\Api\RoleController::class, 'update'])->middleware('permission:users:write');
    Route::patch('roles/{role}', [\App\Http\Controllers\Api\RoleController::class, 'update'])->middleware('permission:users:write');
    Route::delete('roles/{role}', [\App\Http\Controllers\Api\RoleController::class, 'destroy'])->middleware('permission:users:write');

    // ---- Rapports & Statistiques ----
    Route::get('reports/stats', [\App\Http\Controllers\Api\ReportController::class, 'stats']);

    // ---- Paramètres ----
    Route::get('settings', [\App\Http\Controllers\SettingController::class, 'getSettings'])->middleware('permission:settings:read');
    Route::post('settings', [\App\Http\Controllers\SettingController::class, 'updateSettings'])->middleware('permission:settings:write');

    // ---- Portail parent (bulletins & évolution) ----
    Route::prefix('parent')->group(function () {
        Route::get('/children', [\App\Http\Controllers\Api\ParentAcademicController::class, 'children']);
        Route::get('/children/{studentId}/evolution', [\App\Http\Controllers\Api\ParentAcademicController::class, 'childEvolution']);
        Route::get('/children/{studentId}/academic-profile', [\App\Http\Controllers\Api\ParentAcademicController::class, 'childProfile']);
        Route::get('/children/{studentId}/report-card', [\App\Http\Controllers\Api\ParentAcademicController::class, 'childReportCard']);
    });

    // ---- SYSTÈME DE NOTES (GRADES) ----
    Route::prefix('grades')->group(function () {
        // Lecture (grades:read, grades:*, director…)
        Route::middleware('permission:grades:read')->group(function () {
            Route::get('/catalog', [\App\Http\Controllers\Api\AcademicController::class, 'catalog']);
            Route::get('/my-classes', [\App\Http\Controllers\Api\GradeController::class, 'myClasses']);
            Route::get('/classes/{classId}/students', [\App\Http\Controllers\Api\GradeController::class, 'classStudents']);
            Route::get('/', [\App\Http\Controllers\Api\GradeController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\GradeController::class, 'show']);
            Route::get('/students/{studentId}/averages', [\App\Http\Controllers\Api\GradeController::class, 'studentAverages']);
            Route::get('/students/{studentId}/evolution', [\App\Http\Controllers\Api\AcademicController::class, 'studentEvolution']);
            Route::get('/students/{studentId}/academic-profile', [\App\Http\Controllers\Api\AcademicController::class, 'studentProfile']);
            Route::get('/classes/{classId}/averages', [\App\Http\Controllers\Api\GradeController::class, 'classAverages']);
            Route::get('/classes/{classId}/bulletin', [\App\Http\Controllers\Api\AcademicController::class, 'classBulletin']);
            Route::get('/students/{studentId}/report-card', [\App\Http\Controllers\Api\GradeController::class, 'viewReportCard']);
        });

        // Écriture (grades:*)
        Route::middleware('permission:grades:*')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\GradeController::class, 'store']);
            Route::post('/bulk', [\App\Http\Controllers\Api\GradeController::class, 'bulkStore']);
            Route::put('/{id}', [\App\Http\Controllers\Api\GradeController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\GradeController::class, 'destroy']);
            Route::post('/{id}/publish', [\App\Http\Controllers\Api\GradeController::class, 'publish']);
            Route::post('/students/{studentId}/calculate', [\App\Http\Controllers\Api\GradeController::class, 'calculateStudentAverages']);
            Route::post('/classes/{classId}/calculate', [\App\Http\Controllers\Api\GradeController::class, 'calculateClassAverages']);
            Route::post('/students/{studentId}/report-card', [\App\Http\Controllers\Api\GradeController::class, 'generateReportCard']);
            Route::post('/students/{studentId}/report-card/publish', [\App\Http\Controllers\Api\GradeController::class, 'publishReportCard']);
        });
    });
});
