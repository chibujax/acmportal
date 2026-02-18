<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\CsvImportController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Member\DashboardController as MemberDashboard;
use App\Http\Controllers\Payment\ManualPaymentController;
use App\Http\Controllers\Payment\StripeController;
use App\Http\Controllers\Payment\PaystackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => redirect()->route('login'));

// Auth
Route::get('/login',   [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login',  [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Token-based registration (from invite link)
Route::get('/register/{token}',   [RegisterController::class, 'showForm'])->name('register.form');
Route::post('/register',          [RegisterController::class, 'register'])->name('register.post');

// Email verification
Route::get('/email/verify/{token}', [EmailVerificationController::class, 'verify'])->name('email.verify');

/*
|--------------------------------------------------------------------------
| Payment webhooks (exclude from CSRF)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/stripe',   [StripeController::class,   'webhook'])->name('webhook.stripe');
Route::post('/webhooks/paystack', [PaystackController::class, 'webhook'])->name('webhook.paystack');

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Email verification resend
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->name('email.resend');

    /*
    |----------------------------------------------------------------------
    | Admin routes
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {

        Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

        // Members
        Route::prefix('members')->name('members.')->group(function () {
            Route::get('/',           [MemberController::class, 'index'])->name('index');
            Route::post('/',          [MemberController::class, 'store'])->name('store');
            Route::get('/{member}',   [MemberController::class, 'show'])->name('show');
            Route::patch('/{member}/status', [MemberController::class, 'updateStatus'])->name('status');
            Route::patch('/{member}/role',   [MemberController::class, 'updateRole'])->name('role');
        });

        // CSV Import
        Route::get('/import',           [CsvImportController::class, 'showImportForm'])->name('members.import');
        Route::post('/import',          [CsvImportController::class, 'import'])->name('members.import.post');
        Route::post('/import/invites',  [CsvImportController::class, 'sendInvites'])->name('members.invites');
        Route::get('/pending',          [CsvImportController::class, 'pendingList'])->name('members.pending');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/',           [ReportController::class, 'index'])->name('index');
            Route::get('/financial',  [ReportController::class, 'financial'])->name('financial');
            Route::get('/arrears',    [ReportController::class, 'arrears'])->name('arrears');
            Route::get('/members',    [ReportController::class, 'memberSummary'])->name('members');
        });

        // Financial Secretary – manual payments
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/',            [ManualPaymentController::class, 'index'])->name('index');
            Route::get('/create',      [ManualPaymentController::class, 'create'])->name('create');
            Route::post('/',           [ManualPaymentController::class, 'store'])->name('store');
            Route::get('/{payment}',   [ManualPaymentController::class, 'show'])->name('show');
            Route::patch('/{payment}', [ManualPaymentController::class, 'update'])->name('update');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Member routes
    |----------------------------------------------------------------------
    */
    Route::prefix('member')->name('member.')->group(function () {
        Route::get('/dashboard',   [MemberDashboard::class, 'index'])->name('dashboard');
        Route::get('/profile',     [MemberDashboard::class, 'profile'])->name('profile');
        Route::post('/profile',    [MemberDashboard::class, 'updateProfile'])->name('profile.update');
        Route::get('/payments',    [MemberDashboard::class, 'paymentHistory'])->name('payments');
    });

    /*
    |----------------------------------------------------------------------
    | Online payment routes
    |----------------------------------------------------------------------
    */
    Route::prefix('pay')->name('payment.')->group(function () {

        // Stripe
        Route::get('/stripe/{cycle}',   [StripeController::class, 'checkout'])->name('stripe.checkout');
        Route::post('/stripe/intent',   [StripeController::class, 'createIntent'])->name('stripe.intent');
        Route::get('/stripe/success',   [StripeController::class, 'success'])->name('stripe.success');

        // Paystack
        Route::post('/paystack/initiate', [PaystackController::class, 'initiate'])->name('paystack.initiate');
        Route::get('/paystack/callback',  [PaystackController::class, 'callback'])->name('paystack.callback');
    });
});
