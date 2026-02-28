<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\MeetingController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\CsvImportController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\DuesCycleController;
use App\Http\Controllers\Admin\ChildrenController;
use App\Http\Controllers\Attendance\CheckInController;
use App\Http\Controllers\Member\AttendanceController as MemberAttendanceController;
use App\Http\Controllers\Member\DashboardController as MemberDashboard;
use App\Http\Controllers\Member\RelationshipController;
use App\Http\Controllers\Payment\ManualPaymentController;
use App\Http\Controllers\Payment\StripeController;
use App\Http\Controllers\Payment\PaystackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        return redirect()->route(
            $user->isAdmin() || $user->isFinancialSecretary() ? 'admin.dashboard' : 'member.dashboard'
        );
    }
    return redirect()->route('login');
});

// Auth
Route::get('/login',   [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login',  [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Token-based registration (from invite link)
Route::get('/register/{token}',   [RegisterController::class, 'showForm'])->name('register.form');
Route::post('/register',          [RegisterController::class, 'register'])->name('register.post');

// Email verification
Route::get('/email/verify/{token}', [EmailVerificationController::class, 'verify'])->name('email.verify');

// Password reset
Route::get('/forgot-password',             [PasswordResetController::class, 'showForgotForm'])->name('password.forgot');
Route::post('/forgot-password',            [PasswordResetController::class, 'sendReset'])->name('password.send');
Route::get('/verify-otp',                  [PasswordResetController::class, 'showOtpForm'])->name('password.otp.form');
Route::post('/verify-otp',                 [PasswordResetController::class, 'verifyOtp'])->name('password.otp.verify');
Route::get('/reset-password',              [PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password',             [PasswordResetController::class, 'updatePassword'])->name('password.update');

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
    | QR Attendance Check-In (authenticated members scan this URL)
    |----------------------------------------------------------------------
    */
    Route::get('/attend/{token}',          [CheckInController::class, 'show'])->name('attendance.checkin');
    Route::post('/attend/{token}/switch',  [CheckInController::class, 'switchUser'])->name('attendance.switch');
    Route::post('/attend/{token}/checkin', [CheckInController::class, 'checkin'])->name('attendance.checkin.post');

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
        Route::get('/import',                [CsvImportController::class, 'showImportForm'])->name('members.import');
        Route::post('/import',               [CsvImportController::class, 'import'])->name('members.import.post');
        Route::post('/import/invites',       [CsvImportController::class, 'sendInvites'])->name('members.invites');
        Route::post('/import/invite-single', [CsvImportController::class, 'inviteSingle'])->name('members.invite.single');
        Route::get('/pending',               [CsvImportController::class, 'pendingList'])->name('members.pending');

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

        // Meetings & Attendance
        Route::prefix('meetings')->name('meetings.')->group(function () {
            Route::get('/',                           [MeetingController::class, 'index'])->name('index');
            Route::get('/report',                     [MeetingController::class, 'report'])->name('report');
            Route::get('/report/export',              [MeetingController::class, 'exportReport'])->name('report.export');
            Route::get('/create',                     [MeetingController::class, 'create'])->name('create');
            Route::post('/',                          [MeetingController::class, 'store'])->name('store');
            Route::get('/{meeting}',                  [MeetingController::class, 'show'])->name('show');
            Route::get('/{meeting}/edit',             [MeetingController::class, 'edit'])->name('edit');
            Route::put('/{meeting}',                  [MeetingController::class, 'update'])->name('update');
            Route::patch('/{meeting}/activate',       [MeetingController::class, 'activate'])->name('activate');
            Route::patch('/{meeting}/close',          [MeetingController::class, 'close'])->name('close');
            Route::post('/{meeting}/manual-checkin',  [MeetingController::class, 'manualCheckIn'])->name('manual-checkin');
            Route::post('/{meeting}/mark-excused',    [MeetingController::class, 'markExcused'])->name('mark-excused');
            Route::get('/{meeting}/export',           [MeetingController::class, 'exportMeeting'])->name('export');
            Route::delete('/{meeting}/checkin/{record}', [MeetingController::class, 'removeCheckIn'])->name('remove-checkin');
        });

        // ── Phase 2: Dues Cycles ───────────────────────────────
        Route::prefix('dues-cycles')->name('dues-cycles.')->group(function () {
            Route::get('/',                    [DuesCycleController::class, 'index'])->name('index');
            Route::get('/create',              [DuesCycleController::class, 'create'])->name('create');
            Route::post('/',                   [DuesCycleController::class, 'store'])->name('store');
            Route::get('/{duesCycle}',         [DuesCycleController::class, 'show'])->name('show');
            Route::get('/{duesCycle}/edit',    [DuesCycleController::class, 'edit'])->name('edit');
            Route::put('/{duesCycle}',         [DuesCycleController::class, 'update'])->name('update');
            Route::get('/{duesCycle}/export',  [DuesCycleController::class, 'exportCsv'])->name('export');
        });

        // ── Phase 2: Children (admin overview) ────────────────
        Route::get('/children',              [ChildrenController::class, 'index'])->name('children.index');
        Route::delete('/children/{child}',   [ChildrenController::class, 'destroy'])->name('children.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Member routes
    |----------------------------------------------------------------------
    */
    Route::prefix('member')->name('member.')->group(function () {
        Route::get('/dashboard',    [MemberDashboard::class, 'index'])->name('dashboard');
        Route::get('/profile',      [MemberDashboard::class, 'profile'])->name('profile');
        Route::post('/profile',     [MemberDashboard::class, 'updateProfile'])->name('profile.update');
        Route::get('/payments',     [MemberDashboard::class, 'paymentHistory'])->name('payments');
        Route::get('/attendance',   [MemberAttendanceController::class, 'index'])->name('attendance');

        // ── Phase 2: Relationships ─────────────────────────────
        Route::get('/relationships',                              [RelationshipController::class, 'index'])->name('relationships');
        Route::get('/relationships/spouse/search',               [RelationshipController::class, 'searchSpouse'])->name('relationships.spouse.search');
        Route::post('/relationships/spouse',                     [RelationshipController::class, 'linkSpouse'])->name('relationships.spouse.link');
        Route::delete('/relationships/spouse',                   [RelationshipController::class, 'unlinkSpouse'])->name('relationships.spouse.unlink');
        Route::post('/relationships/children',                   [RelationshipController::class, 'addChild'])->name('relationships.children.add');
        Route::delete('/relationships/children/{child}',        [RelationshipController::class, 'removeChild'])->name('relationships.children.remove');
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
