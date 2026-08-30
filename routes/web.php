<?php

use App\Http\Controllers\ContaController;
use App\Http\Controllers\FormaPagamentoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::resource('contas', ContaController::class)->except(['create', 'edit', 'show']);
    Route::resource('formas-pagamento', FormaPagamentoController::class)->except(['index', 'create', 'edit', 'show']);
});

require __DIR__.'/auth.php';
