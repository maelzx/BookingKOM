<?php

use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Volt::route('dashboard', 'dashboard')->name('dashboard');
    Volt::route('calendar', 'calendar')->name('calendar');

    Volt::route('resources', 'resources.index')->name('resources.index');
    Volt::route('resources/create', 'resources.form')
        ->middleware('can:manage-resources')
        ->name('resources.create');
    Volt::route('resources/{resource}/edit', 'resources.form')
        ->middleware('can:manage-resources')
        ->name('resources.edit');
    Volt::route('resources/{resource}', 'resources.show')->name('resources.show');

    Volt::route('bookings', 'bookings.index')->name('bookings.index');
    Volt::route('bookings/create', 'bookings.form')->name('bookings.create');
    Volt::route('bookings/{booking}/edit', 'bookings.form')->name('bookings.edit');
    Volt::route('bookings/{booking}', 'bookings.show')->name('bookings.show');

    Volt::route('approvals', 'approvals.index')
        ->middleware('can:approve-bookings')
        ->name('approvals.index');

    Volt::route('notifications', 'notifications.index')->name('notifications.index');

    Route::get('attachments/{attachment}', AttachmentDownloadController::class)
        ->middleware('throttle:60,1')
        ->name('attachments.download');
});

Volt::route('reports', 'reports.index')
    ->middleware(['auth', 'verified', 'can:view-reports'])
    ->name('reports.index');

Route::middleware(['auth', 'verified', 'can:view-reports', 'throttle:30,1'])->group(function (): void {
    Route::get('exports/bookings', [ExportController::class, 'bookings'])->name('exports.bookings');
    Route::get('exports/resources', [ExportController::class, 'resources'])->name('exports.resources');
    Route::get('exports/utilisation', [ExportController::class, 'utilisation'])->name('exports.utilisation');
});

Volt::route('resource-types', 'resource-types.index')
    ->middleware(['auth', 'verified', 'can:manage-catalog'])
    ->name('resource-types.index');

Volt::route('users', 'users.index')
    ->middleware(['auth', 'verified', 'can:manage-users'])
    ->name('users.index');

Volt::route('settings', 'settings.index')
    ->middleware(['auth', 'verified', 'can:manage-settings'])
    ->name('settings');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Public mobile page opened when a resource QR code is scanned.
Volt::route('r/{resource:code}', 'scan.resource')
    ->middleware('throttle:60,1')
    ->name('scan.show');

require __DIR__.'/auth.php';
