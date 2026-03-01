<?php

namespace Tests\Browser\Reports;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Admin financial report page smoke test.
 * High-signal: catches broken report routes, queries, or view errors.
 */
class FinancialReportTest extends DuskTestCase
{
    public function test_financial_report_page_renders_for_finsec(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsFS($browser);

            $browser->visit('/admin/reports/financial')
                    ->assertSee('Financial Report')
                    ->assertSee('Total Collected');
        });
    }
}
