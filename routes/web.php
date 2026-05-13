<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\GameAccountController;
use App\Http\Controllers\DashboardController; // <-- 1. AÑADIMOS ESTA LÍNEA

Route::get('/', function () {
    return Inertia::render('Home', [        
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

// RUTAS FALTANTES DEL MENÚ PÚBLICO
Route::get('/downloads', function() { return Inertia::render('Home'); })->name('downloads');
Route::get('/donations', function() { return Inertia::render('Home'); })->name('donations');

// Rutas de Información
Route::prefix('info')->name('info.')->group(function() {
    Route::get('/wiki', function() { return Inertia::render('Home'); })->name('wiki');
    Route::get('/who-sell', function() { return Inertia::render('Home'); })->name('who-sell');
    Route::get('/mvp-card', function() { return Inertia::render('Home'); })->name('mvp-card');
    Route::get('/item-db', function() { return Inertia::render('Home'); })->name('item-db');
});

// Rutas de Ranking
Route::prefix('rank')->name('rank.')->group(function() {
    Route::get('/battleground', function() { return Inertia::render('Home'); })->name('battleground');
    Route::get('/woe-damage', function() { return Inertia::render('Home'); })->name('woe-damage');
    Route::get('/woe', function() { return Inertia::render('Home'); })->name('woe');
    Route::get('/mvps', function() { return Inertia::render('Home'); })->name('mvps');
    Route::get('/pvp', function() { return Inertia::render('Home'); })->name('pvp');
    Route::get('/cashpoints', function() { return Inertia::render('Home'); })->name('cashpoints');
    Route::get('/zeny', function() { return Inertia::render('Home'); })->name('zeny');
    Route::get('/playtime', function() { return Inertia::render('Home'); })->name('playtime');
    Route::get('/leveling', function() { return Inertia::render('Home'); })->name('leveling');
});

// ==========================================
// RUTAS PROTEGIDAS (Solo usuarios logueados)
// ==========================================
Route::middleware('auth')->group(function () {
    
    // 2. MODIFICAMOS ESTA RUTA PARA USAR EL CONTROLADOR
    // MASTER ACCOUNT: URL de rApanel, pero nombre interno 'dashboard' de Laravel Auth
    Route::get('/master-account', [DashboardController::class, 'index'])
        ->middleware(['verified'])
        ->name('dashboard');

    // NUEVAS RUTAS DEL MENÚ DE USUARIO
    Route::get('/transfer', function() { return Inertia::render('Dashboard'); })->name('account.transfer');
    Route::get('/vote', function() { return Inertia::render('Dashboard'); })->name('vote');

    // PERFIL DE USUARIO
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Crear cuenta de juego
    Route::post('/game-accounts', [GameAccountController::class, 'store'])->name('game-accounts.store');

    // Actualizar contraseña cuenta de juego
    Route::put('/game-accounts/{account_id}/password', [GameAccountController::class, 'changePassword'])->name('game-accounts.password.update');

    // Logs cuentas de juego
    Route::get('/game-accounts/{account_id}/logs', [GameAccountController::class, 'logs'])->name('game-accounts.logs');

    // Eliminar cuenta de juego
    Route::delete('/game-accounts/{account_id}', [GameAccountController::class, 'destroy'])->name('game-accounts.destroy');

    // Ruta para generar/obtener el token de reclamación
    Route::get('/game-accounts/claim-token', [DashboardController::class, 'getClaimToken'])->name('game-accounts.claim.token');
});

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'es', 'pt', 'fr'])) {
        Session::put('locale', $locale);
        App::setLocale($locale);
    }
    return back();
})->name('setlang');

require __DIR__.'/auth.php';
