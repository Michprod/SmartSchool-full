<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Current authenticated user
    Route::get('/user', fn(Request $request) => $request->user());

    // ---- Étudiants & Classes ----
    Route::apiResource('students', \App\Http\Controllers\Api\StudentController::class);
    Route::apiResource('classes', \App\Http\Controllers\Api\SchoolClassController::class);

    // ---- Finance (Paiements) ----
    Route::apiResource('payments', \App\Http\Controllers\Api\PaymentController::class);

    // ---- Communication (Annonces) ----
    Route::apiResource('announcements', \App\Http\Controllers\Api\AnnouncementController::class);
    Route::post('announcements/{id}/read', [\App\Http\Controllers\Api\AnnouncementController::class, 'markRead']);

    // ---- Événements ----
    Route::apiResource('events', \App\Http\Controllers\Api\SchoolEventController::class);

    // ---- Inventaire ----
    Route::apiResource('inventory', \App\Http\Controllers\Api\InventoryItemController::class);

    // ---- Admissions ----
    Route::apiResource('admissions', \App\Http\Controllers\Api\AdmissionController::class);

    // ---- Gestion Utilisateurs (admin) ----
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);

    // ---- Rapports & Statistiques ----
    Route::get('reports/stats', [\App\Http\Controllers\Api\ReportController::class, 'stats']);
});
