<?php

namespace Tests\Unit;

use App\Models\RecurringOrder;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringOrderDateTest extends TestCase
{
    public function test_weekly_repeat_advances_past_the_due_date(): void
    {
        $repeat = new RecurringOrder(['frequency' => 'weekly', 'next_order_date' => '2026-09-27']);

        $this->assertSame('2026-10-04', $repeat->nextDateAfter(Carbon::parse('2026-09-27'))->toDateString());
    }

    public function test_monthly_repeat_does_not_overflow_short_months(): void
    {
        $repeat = new RecurringOrder(['frequency' => 'monthly', 'next_order_date' => '2026-01-31']);

        $this->assertSame('2026-02-28', $repeat->nextDateAfter(Carbon::parse('2026-02-01'))->toDateString());
    }

    public function test_missed_monthly_occurrences_advance_to_the_next_future_date(): void
    {
        $repeat = new RecurringOrder(['frequency' => 'monthly', 'next_order_date' => '2026-01-31']);

        $this->assertSame('2026-03-28', $repeat->nextDateAfter(Carbon::parse('2026-03-01'))->toDateString());
    }
}
