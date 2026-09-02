<?php

use App\Http\Controllers\ContaController;
use App\Http\Controllers\DespesaController;
use App\Http\Controllers\FormaPagamentoController;
use App\Http\Controllers\LogoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RendaController;
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

    Route::get('/logos/{nome}', [LogoController::class, 'show'])->name('logos.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::resource('contas', ContaController::class)->except(['create', 'edit', 'show']);
    Route::resource('formas-pagamento', FormaPagamentoController::class)
        ->except(['index', 'create', 'edit', 'show'])
        ->parameters(['formas-pagamento' => 'formaPagamento']);
    Route::resource('rendas', RendaController::class)->except(['create', 'edit', 'show']);

    Route::patch('despesas/{despesa}/marcar-como-paga', [DespesaController::class, 'marcarComoPaga'])
        ->name('despesas.marcar-como-paga');
    Route::patch('despesas/{despesa}/desfazer-pagamento', [DespesaController::class, 'desfazerPagamento'])
        ->name('despesas.desfazer-pagamento');
    Route::resource('despesas', DespesaController::class)->except(['create', 'edit', 'show']);
});

require __DIR__.'/auth.php';
