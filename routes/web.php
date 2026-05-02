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
    })->name('students.page');

    Route::get('/finance', function () {
        return Inertia::render('Features/Finance/Pages/FinancialDashboard');
    })->name('finance.index');

    Route::get('/communication', function () {
        return Inertia::render('Features/Communication/Pages/CommunicationCenter');
    })->name('communication.index');

    Route::get('/events', function () {
        return Inertia::render('Features/Events/Pages/EventsPage');
    })->name('events.page');

    Route::get('/inventory', function () {
        return Inertia::render('Features/Inventory/Pages/InventoryPage');
    })->name('inventory.page');

    Route::get('/users', function () {
        return Inertia::render('Features/Users/Pages/UserManagement');
    })->name('users.page');

    Route::get('/admissions', function () {
        return Inertia::render('Features/Admissions/Pages/AdmissionManagement');
    })->name('admissions.page');

    Route::get('/reports', function () {
        return Inertia::render('Features/Reports/Pages/ReportsPage');
    })->name('reports.index');

    Route::get('/settings', function () {
        return Inertia::render('Features/Settings/Pages/SettingsPage');
    })->name('settings.index');

    Route::get('/profile', function () {
        return Inertia::render('Features/Users/Pages/ProfilePage');
    })->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
