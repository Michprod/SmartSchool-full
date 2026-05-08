<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;Route::get('/fix-admin', function () {
    $user = \App\Models\User::firstOrCreate(
        ['email' => 'admin@example.com'],
        [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => 'admin',
            'is_active' => true,
        ]
    );
    $user->password = \Illuminate\Support\Facades\Hash::make('password');
    $user->save();
    return response()->json(['message' => 'Admin user reset/created.', 'email' => $user->email]);
});

Route::middleware('auth:sanctum')->group(function () {
    // Current authenticated user
    Route::get('/user', fn(Request $request) => $request->user());

    // ---- Étudiants & Classes ----
    Route::apiResource('students', \App\Http\Controllers\Api\StudentController::class)->middleware('permission:students:read');
    Route::apiResource('classes', \App\Http\Controllers\Api\SchoolClassController::class)->middleware('permission:students:read');
    Route::apiResource('subjects', \App\Http\Controllers\Api\SubjectController::class);

    // ---- Finance (Paiements) ----
    Route::apiResource('payments', \App\Http\Controllers\Api\PaymentController::class)->middleware('permission:finance:read');

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
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);
    Route::apiResource('roles', \App\Http\Controllers\Api\RoleController::class)->middleware('permission:users:read');

    // ---- Rapports & Statistiques ----
    Route::get('reports/stats', [\App\Http\Controllers\Api\ReportController::class, 'stats']);

    // ---- Paramètres ----
    Route::get('settings', [\App\Http\Controllers\SettingController::class, 'getSettings'])->middleware('permission:settings:read');
    Route::post('settings', [\App\Http\Controllers\SettingController::class, 'updateSettings'])->middleware('permission:settings:write');

    // ---- SYSTÈME DE NOTES (GRADES) ----
    Route::prefix('grades')->middleware('permission:grades:*')->group(function () {
        // Classes et élèves du professeur
        Route::get('/my-classes', [\App\Http\Controllers\Api\GradeController::class, 'myClasses']);
        Route::get('/classes/{classId}/students', [\App\Http\Controllers\Api\GradeController::class, 'classStudents']);

        // Évaluations (notes individuelles)
        Route::get('/', [\App\Http\Controllers\Api\GradeController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\GradeController::class, 'store']);
        Route::post('/bulk', [\App\Http\Controllers\Api\GradeController::class, 'bulkStore']);
        Route::get('/{id}', [\App\Http\Controllers\Api\GradeController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\GradeController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\GradeController::class, 'destroy']);
        Route::post('/{id}/publish', [\App\Http\Controllers\Api\GradeController::class, 'publish']);

        // Calcul des moyennes
        Route::post('/students/{studentId}/calculate', [\App\Http\Controllers\Api\GradeController::class, 'calculateStudentAverages']);
        Route::post('/classes/{classId}/calculate', [\App\Http\Controllers\Api\GradeController::class, 'calculateClassAverages']);

        // Résultats et moyennes
        Route::get('/students/{studentId}/averages', [\App\Http\Controllers\Api\GradeController::class, 'studentAverages']);
        Route::get('/classes/{classId}/averages', [\App\Http\Controllers\Api\GradeController::class, 'classAverages']);

        // Bulletins
        Route::post('/students/{studentId}/report-card', [\App\Http\Controllers\Api\GradeController::class, 'generateReportCard']);
        Route::get('/students/{studentId}/report-card', [\App\Http\Controllers\Api\GradeController::class, 'viewReportCard']);
    });
});
