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
use App\Http\Controllers\Admin\AdminTwoFactorVerifyController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\DropRatesController;
use App\Http\Controllers\WikiController;
use App\Http\Controllers\Admin\WikiSectionController;
use App\Http\Controllers\Admin\WikiArticleController;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/robots.txt', function () {
    $content = view('robots')->render();
    return response($content, 200, ['Content-Type' => 'text/plain']);
});

Route::get('/', function () {
    return Inertia::render('HomeAlt', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::get('/home-classic', function () {
    return Inertia::render('Home', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home.classic');

// Downloads públicas
Route::get('/downloads', [DownloadController::class, 'index'])->name('downloads');
Route::get('/downloads/{download:slug}', [DownloadController::class, 'show'])->name('downloads.show');
Route::get('/downloads/{download:slug}/get', [DownloadController::class, 'download'])->name('downloads.get');
Route::get('/donations', fn() => Inertia::render('ComingSoon', ['title' => 'Donations']))->name('donations');

// Rutas de Información
Route::prefix('info')->name('info.')->group(function() {
    Route::get('/wiki', [WikiController::class, 'index'])->name('wiki');
    Route::get('/wiki/{section:slug}/{article:slug}', [WikiController::class, 'show'])->name('wiki.show');
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
    Route::get('/battleground', fn() => Inertia::render('ComingSoon', ['title' => 'Battlegrounds']))->name('battleground');
    Route::get('/woe-damage', fn() => Inertia::render('ComingSoon', ['title' => 'Guild vs Guild']))->name('woe-damage');
    Route::get('/woe', fn() => Inertia::render('ComingSoon', ['title' => 'War of Emperium']))->name('woe');
    Route::get('/mvps', fn() => Inertia::render('ComingSoon', ['title' => 'MvP']))->name('mvps');
    Route::get('/pvp', fn() => Inertia::render('ComingSoon', ['title' => 'PvP']))->name('pvp');
    Route::get('/cashpoints', fn() => Inertia::render('ComingSoon', ['title' => 'Cash']))->name('cashpoints');
    Route::get('/zeny', fn() => Inertia::render('ComingSoon', ['title' => 'Zeny']))->name('zeny');
    Route::get('/playtime', fn() => Inertia::render('ComingSoon', ['title' => 'Playtime']))->name('playtime');
    Route::get('/leveling', fn() => Inertia::render('ComingSoon', ['title' => 'Level']))->name('leveling');
});

// ==========================================
// RUTAS PROTEGIDAS (Solo usuarios logueados)
// ==========================================
Route::middleware(['auth', 'require2fa'])->group(function () {
    
    // 2. MODIFICAMOS ESTA RUTA PARA USAR EL CONTROLADOR
    // MASTER ACCOUNT: URL de rApanel, pero nombre interno 'dashboard' de Laravel Auth
    Route::get('/master-account', [DashboardController::class, 'index'])
        ->middleware(['verified'])
        ->name('dashboard');

    // NUEVAS RUTAS DEL MENÚ DE USUARIO
    Route::get('/transfer', fn() => Inertia::render('ComingSoon', ['title' => 'Transfer']))->name('account.transfer');
    Route::get('/vote', fn() => Inertia::render('ComingSoon', ['title' => 'Vote']))->name('vote');

    // PERFIL DE USUARIO
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/email', [ProfileController::class, 'changeEmail'])->middleware('throttle:5,5')->name('profile.email');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Crear cuenta de juego
    Route::post('/game-accounts', [GameAccountController::class, 'store'])->name('game-accounts.store');

    // Actualizar contraseña cuenta de juego
    Route::put('/game-accounts/{account_id}/password', [GameAccountController::class, 'changePassword'])->middleware('throttle:5,5')->name('game-accounts.password.update')->whereNumber('account_id');

    // Logs cuentas de juego
    Route::get('/game-accounts/{account_id}/logs', [GameAccountController::class, 'logs'])->name('game-accounts.logs')->whereNumber('account_id');

    // Eliminar cuenta de juego
    Route::delete('/game-accounts/{account_id}', [GameAccountController::class, 'destroy'])->middleware('throttle:5,10')->name('game-accounts.destroy')->whereNumber('account_id');

    // Ruta para generar/obtener el token de reclamación (debe ir ANTES del wildcard)
    Route::get('/game-accounts/claim-token', [DashboardController::class, 'getClaimToken'])->name('game-accounts.claim.token');

    // Detalle de cuenta de juego
    Route::get('/game-accounts/{account_id}', [GameAccountController::class, 'show'])->name('game-accounts.show')->whereNumber('account_id');

    // Cambio de género de cuenta de juego
    Route::put('/game-accounts/{account_id}/gender', [GameAccountController::class, 'changeGender'])->middleware('throttle:5,5')->name('game-accounts.gender')->whereNumber('account_id');

    // Acciones de personaje
    Route::put('/characters/{char_id}/reset-position', [CharacterController::class, 'resetPosition'])->name('characters.reset-position');
    Route::put('/characters/{char_id}/reset-look', [CharacterController::class, 'resetLook'])->name('characters.reset-look');
    Route::put('/characters/{char_id}/slot', [CharacterController::class, 'changeSlot'])->name('characters.change-slot');
    Route::put('/characters/{char_id}/preferences', [CharacterController::class, 'updatePreferences'])->name('characters.preferences');
});

// ==========================================
// RUTAS DE ADMINISTRACIÓN
// ==========================================

// Verificación 2FA para sesión admin (sin admin.2fa para poder acceder a confirmar)
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/verify-2fa',  [AdminTwoFactorVerifyController::class, 'create'])->name('admin.verify-2fa');
    Route::post('/verify-2fa', [AdminTwoFactorVerifyController::class, 'store'])->middleware('throttle:5,1')->name('admin.verify-2fa.store');
});

Route::middleware(['auth', 'admin', 'admin.2fa'])->prefix('admin')->name('admin.')->group(function () {
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
    Route::get('/console',                          [ConsoleController::class, 'index'])->name('console.index');
    Route::post('/console/token',                   [ConsoleController::class, 'token'])->name('console.token')->middleware('throttle:15,1');
    Route::get('/console/server/status',            [ConsoleController::class, 'serverStatus'])->name('console.server.status');
    Route::post('/console/server/{name}/start',     [ConsoleController::class, 'serverStart'])->name('console.server.start')->middleware('throttle:5,1');
    Route::post('/console/server/{name}/stop',      [ConsoleController::class, 'serverStop'])->name('console.server.stop')->middleware('throttle:5,1');
    Route::post('/console/server/{name}/restart',   [ConsoleController::class, 'serverRestart'])->name('console.server.restart')->middleware('throttle:5,1');
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
    Route::post('map-db/import-map-info',             [AdminMapDbController::class, 'importMapInfo'])->name('map-db.import-map-info');
    Route::delete('map-db/map-cache',                 [AdminMapDbController::class, 'destroyMapCache'])->name('map-db.destroy-map-cache');
    Route::delete('map-db/spawns',                    [AdminMapDbController::class, 'destroySpawns'])->name('map-db.destroy-spawns');
    Route::delete('map-db/{mapName}/spawns',          [AdminMapDbController::class, 'destroyMapSpawns'])->name('map-db.destroy-map-spawns')->where('mapName', '[a-zA-Z0-9_@]+');

    // Drop Rates admin
    Route::get('drop-rates',         [DropRatesController::class, 'index'])->name('drop-rates.index');
    Route::post('drop-rates/import', [DropRatesController::class, 'import'])->name('drop-rates.import');
    Route::post('drop-rates/update', [DropRatesController::class, 'update'])->name('drop-rates.update');
    Route::post('drop-rates/clear',  [DropRatesController::class, 'clear'])->name('drop-rates.clear');

    // Wiki admin
    Route::prefix('wiki')->name('wiki.')->group(function () {
        Route::resource('sections', WikiSectionController::class)->except(['show']);
        Route::resource('articles', WikiArticleController::class)->except(['show']);
    });

    // Site Settings admin
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/',                       [SiteSettingsController::class, 'index'])->name('index');
        Route::post('/general',               [SiteSettingsController::class, 'updateGeneral'])->name('general');
        Route::post('/home',                  [SiteSettingsController::class, 'updateHome'])->name('home');
        Route::post('/home-hero',             [SiteSettingsController::class, 'updateHomeHero'])->name('home-hero');
        Route::post('/home-sections',         [SiteSettingsController::class, 'updateHomeSections'])->name('home-sections');
        Route::post('/home-info-blocks',      [SiteSettingsController::class, 'updateHomeInfoBlocks'])->name('home-info-blocks');
        Route::post('/home-highlight-cards',  [SiteSettingsController::class, 'updateHomeHighlightCards'])->name('home-highlight-cards');
        Route::post('/seo',                   [SiteSettingsController::class, 'updateSeo'])->name('seo');
        Route::post('/social-networks',       [SiteSettingsController::class, 'updateSocialNetworks'])->name('social-networks');
        Route::post('/danger/clear-logs',     [SiteSettingsController::class, 'dangerClearLogs'])->name('danger.logs');
        Route::post('/danger/clear-cache',    [SiteSettingsController::class, 'dangerClearCache'])->name('danger.cache');
        Route::post('/danger/clear-sessions', [SiteSettingsController::class, 'dangerClearSessions'])->name('danger.sessions');
    });
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
