<?php

use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Backend\AchievementController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\ContentImageController;
use App\Http\Controllers\Backend\LeaderboardController;
use App\Http\Controllers\Backend\ModerationController;
use App\Http\Controllers\Backend\ObjectContentController;
use App\Http\Controllers\Backend\ObjectController;
use App\Http\Controllers\Backend\ProfileImageController;
use App\Http\Controllers\Backend\QuickDuelController;
use App\Http\Controllers\Backend\RankingController;
use App\Http\Controllers\Backend\SavedRankingController;
use App\Http\Controllers\Backend\SearchController;
use App\Http\Controllers\Backend\SocialController;
use App\Http\Controllers\Backend\WalletController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\CommunityController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Middleware\SetRequestLocale;
use Illuminate\Support\Facades\Route;

Route::permanentRedirect('/', '/id');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemaps/static.xml', [SitemapController::class, 'staticPages'])->name('sitemap.static');
Route::get('/sitemaps/{type}/{page}.xml', [SitemapController::class, 'content'])
    ->where(['type' => 'rankings|objects|articles', 'page' => '[1-9][0-9]*'])->name('sitemap.content');

Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback'])
    ->middleware(['guest', 'throttle:10,1'])->name('google.callback');

Route::get('/admin', [CommunityController::class, 'admin'])
    ->middleware(['auth', SetRequestLocale::class, 'can:moderate'])->name('community.admin');

Route::prefix('{locale}')->where(['locale' => 'id|en'])->middleware(SetRequestLocale::class)->name('community.')->group(function () {
    Route::get('/', [CommunityController::class, 'home'])->name('home');
    Route::get('/explore', [CommunityController::class, 'explore'])->name('explore');
    Route::get('/ranking/{post:slug}', [CommunityController::class, 'ranking'])->name('ranking');
    Route::get('/object/{object:slug}', [CommunityController::class, 'object'])->name('object');
    Route::get('/article/{article}', [CommunityController::class, 'article'])->name('article');
    Route::get('/profile/{user:username}', [CommunityController::class, 'profile'])->name('profile');
    Route::get('/login', [CommunityController::class, 'home'])->middleware('guest')->name('login');
    Route::get('/auth/google', [GoogleLoginController::class, 'redirect'])
        ->middleware(['guest', 'throttle:10,1'])->name('google.redirect');
    Route::get('/register', [CommunityController::class, 'home'])->middleware('guest')->name('register');
    Route::middleware('auth')->group(function () {
        Route::get('/create', [CommunityController::class, 'create'])->name('create');
        Route::get('/object/{object:slug}/write', [CommunityController::class, 'writeObjectArticle'])->name('object.write');
        Route::get('/object/{object:slug}/summary/edit', [CommunityController::class, 'editObjectSummary'])->name('object.summary.edit');
        Route::get('/object/{object:slug}/submission/edit', [CommunityController::class, 'editObjectSubmission'])->name('object.submission.edit');
        Route::get('/article/{article}/edit', [CommunityController::class, 'editObjectArticle'])->name('article.edit');
        Route::get('/wallet', [CommunityController::class, 'wallet'])->name('wallet');
        Route::get('/saved', [CommunityController::class, 'saved'])->name('saved');
    });
});

Route::get('/dashboard', fn () => redirect()->route('community.home', ['locale' => auth()->user()->locale ?? 'id']))
    ->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::get('objects/{object}/image', [ObjectController::class, 'image'])->name('objects.image');
    Route::get('users/{user}/{type}', [ProfileImageController::class, 'show'])->whereIn('type', ['avatar', 'cover'])->name('users.image');
    Route::get('rankings', [RankingController::class, 'index'])->name('rankings.index');
    Route::get('rankings/{post:slug}', [RankingController::class, 'show'])->name('rankings.show');
    Route::get('rankings/{post:slug}/comments', [SocialController::class, 'getComments'])->name('comments.index');
    Route::get('objects/{object:slug}/comments', [SocialController::class, 'getObjectComments'])->name('objects.comments.index');
    Route::get('articles/{article}/comments', [SocialController::class, 'getArticleComments'])->name('articles.comments.index');
    Route::get('comment-images/{image}', [SocialController::class, 'commentImage'])->name('comment-images.show');
    Route::get('content-images/{image}', [ContentImageController::class, 'show'])->name('content-images.show');
    Route::get('search', SearchController::class)->middleware('throttle:60,1')->name('search');
    Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
    Route::get('quick-duel', [QuickDuelController::class, 'show'])->name('quick-duel.show');
    Route::get('profile/{user:username}', [SocialController::class, 'profile'])->name('profile');

    Route::middleware(['auth', 'throttle:30,1'])->group(function () {
        Route::post('objects', [ObjectController::class, 'store'])->name('objects.store')->middleware('throttle:object-uploads');
        Route::patch('objects/{object}/submission', [ObjectController::class, 'resubmit'])->name('objects.resubmit')->middleware('throttle:object-uploads');
        Route::post('profile/{type}', [ProfileImageController::class, 'update'])->whereIn('type', ['avatar', 'cover'])->name('profile.image.update')->middleware('throttle:profile-uploads');
        Route::delete('profile/{type}', [ProfileImageController::class, 'destroy'])->whereIn('type', ['avatar', 'cover'])->name('profile.image.destroy');
        Route::post('objects/{object}/image', [ObjectController::class, 'proposeImage'])->name('objects.image.propose')->middleware('throttle:object-uploads');
        Route::post('content-images', [ContentImageController::class, 'store'])->name('content-images.store')->middleware('throttle:content-uploads');
        Route::post('objects/{object:slug}/summary-revisions', [ObjectContentController::class, 'storeSummary'])->name('objects.summary.store');
        Route::post('objects/{object:slug}/articles', [ObjectContentController::class, 'storeArticle'])->name('objects.articles.store');
        Route::patch('articles/{article}', [ObjectContentController::class, 'reviseArticle'])->name('articles.revise');
        Route::post('objects/{object:slug}/comments', [SocialController::class, 'objectComment'])->name('objects.comments.store')->middleware('throttle:comment-uploads');
        Route::post('articles/{article}/comments', [SocialController::class, 'articleComment'])->name('articles.comments.store')->middleware('throttle:comment-uploads');
        Route::post('rankings', [RankingController::class, 'store'])->name('rankings.store');
        Route::get('wallet', [WalletController::class, 'show'])->name('wallet.show');
        Route::post('wallet/claim', [WalletController::class, 'claim'])->name('wallet.claim');
        Route::put('achievements/featured', [AchievementController::class, 'feature'])->name('achievements.feature');
        Route::post('achievements/seen', [AchievementController::class, 'seen'])->name('achievements.seen');
        Route::post('support', [WalletController::class, 'support'])->name('support.store');
        Route::post('quick-duel/{duel}/vote', [QuickDuelController::class, 'vote'])->name('quick-duel.vote');
        Route::post('rankings/{post:slug}/comments', [SocialController::class, 'comment'])->name('comments.store')->middleware('throttle:comment-uploads');
        Route::post('comments/{comment}/like', [SocialController::class, 'toggleCommentLike'])->name('comments.like');
        Route::put('rankings/{post}/save', [SavedRankingController::class, 'store'])->name('rankings.save');
        Route::delete('rankings/{post}/save', [SavedRankingController::class, 'destroy'])->name('rankings.unsave');
        Route::post('saved-rankings/sync', [SavedRankingController::class, 'sync'])->name('saved-rankings.sync');
        Route::post('reports', [SocialController::class, 'report'])->name('reports.store');
    });
});

Route::prefix('admin/backend')->middleware(['auth', SetRequestLocale::class, 'can:moderate', 'throttle:30,1'])->name('admin.backend.')->group(function () {
    Route::get('queue', [ModerationController::class, 'queue'])->name('queue');
    Route::patch('objects/{object}', [ModerationController::class, 'reviewObject'])->name('objects.review');
    Route::patch('objects/{object}/image', [ModerationController::class, 'reviewImage'])->name('objects.image.review');
    Route::post('objects/{object}/aliases', [ModerationController::class, 'addAlias'])->name('objects.aliases.store');
    Route::put('objects/{object}/translations', [ModerationController::class, 'setTranslation'])->name('objects.translations.store');
    Route::patch('rankings/{post}/hide', [ModerationController::class, 'hidePost'])->name('rankings.hide');
    Route::patch('comments/{comment}/hide', [ModerationController::class, 'hideComment'])->name('comments.hide');
    Route::patch('content-revisions/{revision}', [ObjectContentController::class, 'review'])->name('content-revisions.review');
    Route::patch('articles/{article}/hide', [ObjectContentController::class, 'hideArticle'])->name('articles.hide');
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
