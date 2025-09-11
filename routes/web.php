<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Models\Cuestionario;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

// Rutas de gestión de usuarios (solo para superadmin)
Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::view('users', 'users')->name('users.index');
});

// Rutas de gestión de cuestionarios (para superadmin y admin)
Route::middleware(['auth', 'role:superadmin|admin'])->group(function () {
    Route::view('cuestionarios', 'cuestionarios')->name('cuestionarios.index');
    Route::get('cuestionarios/{cuestionario}/variables', function ($cuestionario) {
        return view('variables', ['cuestionarioId' => $cuestionario]);
    })->name('variables.index');
});

require __DIR__.'/auth.php';
