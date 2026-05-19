<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\GameAccountController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuildController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\GameAccountAdminController;
use App\Http\Controllers\Admin\LogAdminController;
use App\Http\Controllers\Admin\ConsoleController;
use App\Http\Controllers\Admin\CharacterAdminController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\Admin\DownloadController as AdminDownloadController;
use App\Http\Controllers\Admin\DownloadCategoryController as AdminDownloadCategoryController;

Route::get('/', function () {
    return Inertia::render('Home', [        
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

// Downloads públicas
Route::get('/downloads', [DownloadController::class, 'index'])->name('downloads');
Route::get('/downloads/{download:slug}', [DownloadController::class, 'show'])->name('downloads.show');
Route::get('/downloads/{download:slug}/get', [DownloadController::class, 'download'])->name('downloads.get');
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

    // Ruta para generar/obtener el token de reclamación (debe ir ANTES del wildcard)
    Route::get('/game-accounts/claim-token', [DashboardController::class, 'getClaimToken'])->name('game-accounts.claim.token');

    // Detalle de cuenta de juego
    Route::get('/game-accounts/{account_id}', [GameAccountController::class, 'show'])->name('game-accounts.show');

    // Cambio de género de cuenta de juego
    Route::put('/game-accounts/{account_id}/gender', [GameAccountController::class, 'changeGender'])->name('game-accounts.gender');

    // Acciones de personaje
    Route::put('/characters/{char_id}/reset-position', [CharacterController::class, 'resetPosition'])->name('characters.reset-position');
    Route::put('/characters/{char_id}/reset-look', [CharacterController::class, 'resetLook'])->name('characters.reset-look');
    Route::put('/characters/{char_id}/slot', [CharacterController::class, 'changeSlot'])->name('characters.change-slot');
    Route::put('/characters/{char_id}/preferences', [CharacterController::class, 'updatePreferences'])->name('characters.preferences');
});

// ==========================================
// RUTAS DE ADMINISTRACIÓN
// ==========================================
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/',              [AdminController::class,         'dashboard'])->name('dashboard');
    Route::get('/users',         [AdminUserController::class,     'index'])->name('users.index');
    Route::get('/users/{user}',  [AdminUserController::class,     'show'])->name('users.show');
    Route::put('/users/{user}/role',   [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::put('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
    Route::get('/game-accounts', [GameAccountAdminController::class, 'index'])->name('game-accounts.index');
    Route::get('/logs',          [LogAdminController::class,      'index'])->name('logs.index');
    Route::get('/characters',    [CharacterAdminController::class, 'index'])->name('characters.index');
    Route::get('/console',       [ConsoleController::class,        'index'])->name('console.index');
    Route::resource('news', AdminNewsController::class)->except(['show']);
    Route::resource('downloads', AdminDownloadController::class)->except(['show']);
    Route::resource('download-categories', AdminDownloadCategoryController::class)->except(['show']);
});

// Noticias públicas
Route::get('/news/{news:slug}', [NewsController::class, 'show'])->name('news.show');

// Emblema de guild (público, sin auth)
Route::get('/guild-emblem/{guild_id}', [GuildController::class, 'emblem'])
    ->where('guild_id', '[0-9]+')
    ->name('guild.emblem');

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'es', 'pt', 'fr'])) {
        Session::put('locale', $locale);
        App::setLocale($locale);
    }
    return back();
})->name('setlang');

require __DIR__.'/auth.php';
