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
use App\Http\Controllers\Admin\PledgeController;
use App\Http\Controllers\Admin\DonationItemController;
use App\Http\Controllers\Admin\SmsTemplateController;
use App\Http\Controllers\Admin\RoleController;
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
            $user->isSuperAdmin() ? 'admin.dashboard' : 'member.dashboard'
        );
    }
    return redirect()->route('login');
});

// Auth
Route::get('/login',   [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login',  [LoginController::class, 'login'])->name('login.post')->middleware('throttle:20,1');
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

        Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard')->middleware('superadmin');

        // ── Role Management (super admin only) ────────────────
        Route::middleware('superadmin')->prefix('roles')->name('roles.')->group(function () {
            Route::get('/',                 [RoleController::class, 'index'])->name('index');
            Route::get('/create',           [RoleController::class, 'create'])->name('create');
            Route::post('/',                [RoleController::class, 'store'])->name('store');
            Route::get('/{role}/edit',      [RoleController::class, 'edit'])->name('edit');
            Route::put('/{role}',           [RoleController::class, 'update'])->name('update');
            Route::delete('/{role}',        [RoleController::class, 'destroy'])->name('destroy');
            Route::get('/assign',           [RoleController::class, 'assign'])->name('assign');
            Route::post('/assign',          [RoleController::class, 'assignUpdate'])->name('assign.update');
        });

        // Members
        Route::middleware('page:members')->prefix('members')->name('members.')->group(function () {
            Route::get('/',           [MemberController::class, 'index'])->name('index');
            Route::post('/',          [MemberController::class, 'store'])->name('store');
            Route::get('/{member}',   [MemberController::class, 'show'])->name('show');
            Route::patch('/{member}/status', [MemberController::class, 'updateStatus'])->name('status');
            Route::patch('/{member}/role',   [MemberController::class, 'updateRole'])->name('role');
            Route::post('/{member}/sms',     [MemberController::class, 'sendSms'])->name('sms');
        });

        // CSV Import
        Route::middleware('page:import')->group(function () {
            Route::get('/import',                [CsvImportController::class, 'showImportForm'])->name('members.import');
            Route::post('/import',               [CsvImportController::class, 'import'])->name('members.import.post');
            Route::post('/import/invites',       [CsvImportController::class, 'sendInvites'])->name('members.invites');
            Route::post('/import/invite-single', [CsvImportController::class, 'inviteSingle'])->name('members.invite.single');
            Route::get('/pending',               [CsvImportController::class, 'pendingList'])->name('members.pending');
        });

        // Reports (financial + member summary)
        Route::middleware('page:reports')->prefix('reports')->name('reports.')->group(function () {
            Route::get('/',          [ReportController::class, 'index'])->name('index');
            Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
            Route::get('/members',   [ReportController::class, 'memberSummary'])->name('members');
        });

        // Arrears report (separate slug so it can be granted independently)
        Route::middleware('page:arrears')->group(function () {
            Route::get('/reports/arrears', [ReportController::class, 'arrears'])->name('reports.arrears');
        });

        // Payments & Dues Cycles
        Route::middleware('page:payments')->group(function () {
            Route::prefix('payments')->name('payments.')->group(function () {
                Route::get('/',            [ManualPaymentController::class, 'index'])->name('index');
                Route::get('/create',      [ManualPaymentController::class, 'create'])->name('create');
                Route::post('/',           [ManualPaymentController::class, 'store'])->name('store');
                Route::get('/{payment}',   [ManualPaymentController::class, 'show'])->name('show');
                Route::patch('/{payment}', [ManualPaymentController::class, 'update'])->name('update');
            });

            Route::prefix('dues-cycles')->name('dues-cycles.')->group(function () {
                Route::get('/',                              [DuesCycleController::class, 'index'])->name('index');
                Route::get('/create',                        [DuesCycleController::class, 'create'])->name('create');
                Route::post('/',                             [DuesCycleController::class, 'store'])->name('store');
                Route::get('/{duesCycle}',                   [DuesCycleController::class, 'show'])->name('show');
                Route::get('/{duesCycle}/edit',              [DuesCycleController::class, 'edit'])->name('edit');
                Route::put('/{duesCycle}',                   [DuesCycleController::class, 'update'])->name('update');
                Route::get('/{duesCycle}/export',            [DuesCycleController::class, 'exportCsv'])->name('export');
                Route::post('/{duesCycle}/send-reminders',   [DuesCycleController::class, 'sendReminders'])->name('send-reminders');
            });

            Route::get('/dues-cycles/{duesCycle}/pledges',  [PledgeController::class, 'index'])->name('pledges.index');
            Route::post('/dues-cycles/{duesCycle}/pledges', [PledgeController::class, 'store'])->name('pledges.store');
            Route::delete('/pledges/{pledge}',              [PledgeController::class, 'destroy'])->name('pledges.destroy');

            Route::get('/dues-cycles/{duesCycle}/items',        [DonationItemController::class, 'index'])->name('donation-items.index');
            Route::get('/dues-cycles/{duesCycle}/items/create', [DonationItemController::class, 'create'])->name('donation-items.create');
            Route::post('/dues-cycles/{duesCycle}/items',       [DonationItemController::class, 'store'])->name('donation-items.store');
            Route::delete('/donation-items/{donationItem}',     [DonationItemController::class, 'destroy'])->name('donation-items.destroy');
            Route::post('/donation-items/{donationItem}/fulfill', [DonationItemController::class, 'fulfill'])->name('donation-items.fulfill');
        });

        // Attendance reports – must be defined BEFORE meeting management so that
        // /report and /consecutive-absentees are not swallowed by the /{meeting} wildcard.
        // Attendance report – 'attendance' slug only (meetings slug does NOT grant this)
        Route::middleware('page:attendance')->prefix('meetings')->name('meetings.')->group(function () {
            Route::get('/report',        [MeetingController::class, 'report'])->name('report');
            Route::get('/report/export', [MeetingController::class, 'exportReport'])->name('report.export');
        });

        // Consecutive absentees – 'absentees' slug only (meetings slug does NOT grant this)
        Route::middleware('page:absentees')->prefix('meetings')->name('meetings.')->group(function () {
            Route::get('/consecutive-absentees', [MeetingController::class, 'consecutiveAbsentees'])->name('consecutive-absentees');
            Route::post('/send-consecutive-sms', [MeetingController::class, 'sendConsecutiveAbsenteeSms'])->name('send-consecutive-sms');
        });

        // Meeting management (wildcard /{meeting} routes must come AFTER specific paths above)
        Route::middleware('page:meetings')->prefix('meetings')->name('meetings.')->group(function () {
            Route::get('/',                              [MeetingController::class, 'index'])->name('index');
            Route::get('/create',                        [MeetingController::class, 'create'])->name('create');
            Route::post('/',                             [MeetingController::class, 'store'])->name('store');
            Route::post('/verify-address',               [MeetingController::class, 'verifyAddress'])->name('verify-address');
            Route::get('/{meeting}',                     [MeetingController::class, 'show'])->name('show');
            Route::get('/{meeting}/edit',                [MeetingController::class, 'edit'])->name('edit');
            Route::put('/{meeting}',                     [MeetingController::class, 'update'])->name('update');
            Route::patch('/{meeting}/activate',          [MeetingController::class, 'activate'])->name('activate');
            Route::patch('/{meeting}/close',             [MeetingController::class, 'close'])->name('close');
            Route::post('/{meeting}/manual-checkin',     [MeetingController::class, 'manualCheckIn'])->name('manual-checkin');
            Route::post('/{meeting}/mark-excused',       [MeetingController::class, 'markExcused'])->name('mark-excused');
            Route::get('/{meeting}/export',              [MeetingController::class, 'exportMeeting'])->name('export');
            Route::delete('/{meeting}/checkin/{record}', [MeetingController::class, 'removeCheckIn'])->name('remove-checkin');
            Route::post('/{meeting}/send-absent-sms',    [MeetingController::class, 'sendAbsenteeSms'])->name('send-absent-sms');
        });

        // SMS Templates
        Route::middleware('page:communications')->group(function () {
            Route::resource('sms-templates', SmsTemplateController::class)
                ->names('sms-templates');
        });

        // Children (admin overview)
        Route::middleware('page:children')->group(function () {
            Route::get('/children',                   [ChildrenController::class, 'index'])->name('children.index');
            Route::get('/children/create',            [ChildrenController::class, 'create'])->name('children.create');
            Route::post('/children',                  [ChildrenController::class, 'store'])->name('children.store');
            Route::get('/children/{child}/edit',      [ChildrenController::class, 'edit'])->name('children.edit');
            Route::put('/children/{child}',           [ChildrenController::class, 'update'])->name('children.update');
            Route::delete('/children/{child}',        [ChildrenController::class, 'destroy'])->name('children.destroy');
        });
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
