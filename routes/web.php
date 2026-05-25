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
use App\Http\Controllers\MvpCardController;
use App\Http\Controllers\WhoSellController;
use App\Http\Controllers\Admin\MvpCardAdminController;
use App\Http\Controllers\NewsCommentController;
use App\Http\Controllers\NewsReactionController;
use App\Http\Controllers\Admin\NewsCommentController as AdminNewsCommentController;
use App\Http\Controllers\Admin\ItemDbController as AdminItemDbController;
use App\Http\Controllers\Admin\MobDbController as AdminMobDbController;
use App\Http\Controllers\Admin\MapDbController as AdminMapDbController;
use App\Http\Controllers\ItemDbController;
use App\Http\Controllers\MobDbController;
use App\Http\Controllers\MapDbController;
use App\Http\Controllers\SitemapController;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/robots.txt', function () {
    $content = view('robots')->render();
    return response($content, 200, ['Content-Type' => 'text/plain']);
});

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
    Route::get('/who-sell', [WhoSellController::class, 'index'])->name('who-sell');
    Route::get('/who-sell/shop/{vendingId}', [WhoSellController::class, 'show'])->name('who-sell.shop');
    Route::get('/mvp-card', [MvpCardController::class, 'index'])->name('mvp-card');
    Route::get('/mob-db', [MobDbController::class, 'index'])->name('mob-db');
    Route::get('/mob-db/{mobId}', [MobDbController::class, 'show'])->name('mob-db.show')->where('mobId', '[0-9]+');
    Route::get('/map-db', [MapDbController::class, 'index'])->name('map-db');
    Route::get('/map-db/{mapName}', [MapDbController::class, 'show'])->name('map-db.show')->where('mapName', '[a-zA-Z0-9_@]+');
    Route::get('/item-db', [ItemDbController::class, 'index'])->name('item-db');
    Route::get('/item-db/{itemId}', [ItemDbController::class, 'show'])->name('item-db.show')->where('itemId', '[0-9]+');
    Route::get('/item-db/{itemId}/monsters', [ItemDbController::class, 'monsters'])->name('item-db.monsters')->where('itemId', '[0-9]+');
    Route::get('/item-db/{itemId}/trade', [ItemDbController::class, 'trade'])->name('item-db.trade')->where('itemId', '[0-9]+');
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
    Route::put('/profile/email', [ProfileController::class, 'changeEmail'])->name('profile.email');
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
Route::middleware(['auth', 'admin', 'require2fa'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/',              [AdminController::class,         'dashboard'])->name('dashboard');
    Route::get('/users',                     [AdminUserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}',              [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}/mfa',       [AdminUserController::class, 'clearMfa'])->name('users.clear-mfa');
    Route::put('/users/{user}/role',         [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::put('/users/{user}/status',       [AdminUserController::class, 'updateStatus'])->name('users.status');
    Route::get('/game-accounts',                           [GameAccountAdminController::class, 'index'])->name('game-accounts.index');
    Route::post('/game-accounts/{accountId}/ban',          [GameAccountAdminController::class, 'ban'])->name('game-accounts.ban');
    Route::post('/game-accounts/{accountId}/unban',        [GameAccountAdminController::class, 'unban'])->name('game-accounts.unban');
    Route::patch('/game-accounts/{accountId}/group',       [GameAccountAdminController::class, 'setGroup'])->name('game-accounts.group');
    Route::put('/game-accounts/{accountId}',               [GameAccountAdminController::class, 'update'])->name('game-accounts.update');
    Route::get('/logs',          [LogAdminController::class,      'index'])->name('logs.index');
    Route::get('/characters',    [CharacterAdminController::class, 'index'])->name('characters.index');
    Route::get('/console',       [ConsoleController::class,        'index'])->name('console.index');
    Route::resource('news', AdminNewsController::class)->except(['show']);
    Route::delete('news-comments/{newsComment}', [AdminNewsCommentController::class, 'destroy'])->name('news-comments.destroy');
    Route::resource('downloads', AdminDownloadController::class)->except(['show']);
    Route::resource('download-categories', AdminDownloadCategoryController::class)->except(['show']);
    Route::get('mvp-cards', [MvpCardAdminController::class, 'index'])->name('mvp-cards.index');
    Route::patch('mvp-cards/{itemId}/toggle', [MvpCardAdminController::class, 'toggle'])->name('mvp-cards.toggle')->whereNumber('itemId');
    Route::get('mvp-cards/{itemId}/holders', [MvpCardAdminController::class, 'holders'])->name('mvp-cards.holders')->whereNumber('itemId');

    // Item DB admin
    Route::get('item-db',          [AdminItemDbController::class, 'index'])->name('item-db.index');
    Route::post('item-db/import',  [AdminItemDbController::class, 'import'])->name('item-db.import');
    Route::delete('item-db',       [AdminItemDbController::class, 'destroy'])->name('item-db.destroy');

    // Mob DB admin
    Route::get('mob-db',                [AdminMobDbController::class, 'index'])->name('mob-db.index');
    Route::post('mob-db/import',        [AdminMobDbController::class, 'import'])->name('mob-db.import');
    Route::post('mob-db/import-sql',    [AdminMobDbController::class, 'importSql'])->name('mob-db.import-sql');
    Route::delete('mob-db',             [AdminMobDbController::class, 'destroy'])->name('mob-db.destroy');

    // Map DB admin
    Route::get('map-db',                              [AdminMapDbController::class, 'index'])->name('map-db.index');
    Route::post('map-db/import-map-cache',            [AdminMapDbController::class, 'importMapCache'])->name('map-db.import-map-cache');
    Route::post('map-db/import-spawns',               [AdminMapDbController::class, 'importSpawns'])->name('map-db.import-spawns');
    Route::delete('map-db/map-cache',                 [AdminMapDbController::class, 'destroyMapCache'])->name('map-db.destroy-map-cache');
    Route::delete('map-db/spawns',                    [AdminMapDbController::class, 'destroySpawns'])->name('map-db.destroy-spawns');
    Route::delete('map-db/{mapName}/spawns',          [AdminMapDbController::class, 'destroyMapSpawns'])->name('map-db.destroy-map-spawns')->where('mapName', '[a-zA-Z0-9_@]+');
});

// Noticias públicas
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news:slug}', [NewsController::class, 'show'])->name('news.show');
Route::post('/news/{news:slug}/comments', [NewsCommentController::class, 'store'])->middleware('auth')->name('news.comments.store');
Route::post('/news/{news:slug}/react', [NewsReactionController::class, 'toggle'])->middleware('auth')->name('news.react');

// Emblema de guild (público, sin auth)
Route::get('/guild-emblem/{guild_id}', [GuildController::class, 'emblem'])
    ->where('guild_id', '[0-9]+')
    ->name('guild.emblem');

Route::get('profile/delete-confirm/{id}/{hash}', [ProfileController::class, 'confirmDeletion'])
    ->middleware(['signed', 'throttle:3,1'])
    ->name('profile.delete-confirm');

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'es', 'pt', 'fr'])) {
        Session::put('locale', $locale);
        App::setLocale($locale);
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
    }
    return back();
})->name('setlang');

require __DIR__.'/auth.php';
