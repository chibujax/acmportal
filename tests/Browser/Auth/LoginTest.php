<?php

namespace Tests\Browser\Auth;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Tests the login form for admin, financial secretary, and member roles.
 * Also verifies that wrong credentials produce an error.
 */
class LoginTest extends DuskTestCase
{
    public function test_member_cannot_access_admin_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsMember($browser, 'member.a@test.com');

            $browser->visit('/admin/dashboard')
                    ->waitForLocation('/admin/dashboard')
                    ->assertSee('403')
                    ->assertSee('Access Denied');
        });
    }

    public function test_admin_can_login_and_see_admin_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('input[name="login"]', 'admin@test.com')
                    ->type('input[name="password"]', 'Admin@1234')
                    ->click('button[type="submit"]')
                    ->waitForLocation('/admin/dashboard')
                    ->assertSee('ACM');
        });
    }

    public function test_admin_can_login_and_see_admin_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('input[name="login"]', 'finsec@test.com')
                    ->type('input[name="password"]', 'FinSec@1234')
                    ->click('button[type="submit"]')
                    ->waitForLocation('/admin/dashboard');
        });
    }

    public function test_member_can_login_and_see_member_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('input[name="login"]', 'member.a@test.com')
                    ->type('input[name="password"]', 'Member@1234')
                    ->click('button[type="submit"]')
                    ->waitForLocation('/member/dashboard')
                    ->assertSee('Welcome back');
        });
    }

    public function test_member_can_login_using_phone_number(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('input[name="login"]', '07900000003')
                    ->type('input[name="password"]', 'Member@1234')
                    ->click('button[type="submit"]')
                    ->waitForLocation('/member/dashboard');
        });
    }

    public function test_wrong_password_stays_on_login_with_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('input[name="login"]', 'admin@test.com')
                    ->type('input[name="password"]', 'WrongPassword!')
                    ->click('button[type="submit"]')
                    ->waitForText('credentials')
                    ->assertPathIs('/login');
        });
    }

    protected function tearDown(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout();
        });
        parent::tearDown();
    }

}
