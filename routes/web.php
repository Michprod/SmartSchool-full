<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Features/Dashboard/Pages/DashboardHome');
})->middleware(['auth', 'verified'])->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Features/Dashboard/Pages/DashboardHome');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/students', function () {
        return Inertia::render('Features/Students/Pages/StudentManagement');
    })->middleware('permission:students:read')->name('students.page');

    Route::get('/finance', function () {
        return Inertia::render('Features/Finance/Pages/FinancialDashboard');
    })->middleware('permission:finance:read')->name('finance.index');

    Route::get('/communication', function () {
        return Inertia::render('Features/Communication/Pages/CommunicationCenter');
    })->middleware('permission:communication:read')->name('communication.index');

    Route::get('/events', function () {
        return Inertia::render('Features/Events/Pages/EventsPage');
    })->middleware('permission:events:read')->name('events.page');

    Route::get('/inventory', function () {
        return Inertia::render('Features/Inventory/Pages/InventoryPage');
    })->middleware('permission:inventory:read')->name('inventory.page');

    Route::get('/users', function () {
        return Inertia::render('Features/Users/Pages/UserManagement');
    })->middleware('permission:users:read')->name('users.page');

    Route::get('/admissions', function () {
        return Inertia::render('Features/Admissions/Pages/AdmissionManagement');
    })->middleware('permission:admissions:read')->name('admissions.page');

    Route::get('/grades', function () {
        return Inertia::render('Features/Grades/Pages/GradesPage');
    })->middleware('permission:grades:read')->name('grades.page');

    Route::get('/reports', function () {
        return Inertia::render('Features/Reports/Pages/ReportsPage');
    })->middleware('permission:reports:read')->name('reports.index');

    Route::get('/settings', function () {
        return Inertia::render('Features/Settings/Pages/SettingsPage');
    })->middleware('permission:settings:read')->name('settings.index');

    Route::get('/profile', function () {
        return Inertia::render('Features/Users/Pages/ProfilePage');
    })->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
