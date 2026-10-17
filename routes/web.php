<?php

use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\LeaderboardController;
use App\Http\Controllers\Backend\ModerationController;
use App\Http\Controllers\Backend\ObjectController;
use App\Http\Controllers\Backend\RankingController;
use App\Http\Controllers\Backend\SocialController;
use App\Http\Controllers\Backend\WalletController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\SetRequestLocale;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::prefix('backend')->middleware(SetRequestLocale::class)->name('backend.')->group(function () {
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('brands', [CategoryController::class, 'brands'])->name('brands.index');
    Route::get('objects', [ObjectController::class, 'index'])->name('objects.index');
    Route::get('objects/{object:slug}', [ObjectController::class, 'show'])->name('objects.show');
    Route::get('rankings', [RankingController::class, 'index'])->name('rankings.index');
    Route::get('rankings/{post:slug}', [RankingController::class, 'show'])->name('rankings.show');
    Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
    Route::get('profile/{user:username}', [SocialController::class, 'profile'])->name('profile');

    Route::middleware(['auth', 'throttle:30,1'])->group(function () {
        Route::post('objects', [ObjectController::class, 'store'])->name('objects.store');
        Route::post('rankings', [RankingController::class, 'store'])->name('rankings.store');
        Route::get('wallet', [WalletController::class, 'show'])->name('wallet.show');
        Route::post('wallet/claim', [WalletController::class, 'claim'])->name('wallet.claim');
        Route::post('support', [WalletController::class, 'support'])->name('support.store');
        Route::post('rankings/{post:slug}/comments', [SocialController::class, 'comment'])->name('comments.store');
        Route::post('reports', [SocialController::class, 'report'])->name('reports.store');
    });
});

Route::prefix('admin/backend')->middleware(['auth', SetRequestLocale::class, 'can:moderate', 'throttle:30,1'])->name('admin.backend.')->group(function () {
    Route::get('queue', [ModerationController::class, 'queue'])->name('queue');
    Route::patch('objects/{object}', [ModerationController::class, 'reviewObject'])->name('objects.review');
    Route::post('objects/{object}/aliases', [ModerationController::class, 'addAlias'])->name('objects.aliases.store');
    Route::put('objects/{object}/translations', [ModerationController::class, 'setTranslation'])->name('objects.translations.store');
    Route::patch('rankings/{post}/hide', [ModerationController::class, 'hidePost'])->name('rankings.hide');
    Route::patch('comments/{comment}/hide', [ModerationController::class, 'hideComment'])->name('comments.hide');
    Route::patch('reports/{report}', [ModerationController::class, 'resolveReport'])->name('reports.resolve');
});

Route::prefix('admin/backend')->middleware(['auth', SetRequestLocale::class, 'can:administer', 'throttle:30,1'])->name('admin.backend.')->group(function () {
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::post('brands', [CategoryController::class, 'storeBrand'])->name('brands.store');
    Route::get('audit-logs', [ModerationController::class, 'auditLogs'])->name('audit-logs.index');
    Route::get('ledger', [ModerationController::class, 'ledger'])->name('ledger.index');
    Route::post('objects/{object}/merge', [ModerationController::class, 'mergeObject'])->name('objects.merge');
    Route::post('objects/{object}/undo-merge', [ModerationController::class, 'undoMerge'])->name('objects.undo-merge');
    Route::post('support/{event}/reverse', [ModerationController::class, 'reverseSupport'])->name('support.reverse');
    Route::patch('users/{user}/restrict', [ModerationController::class, 'restrictUser'])->name('users.restrict');
});
