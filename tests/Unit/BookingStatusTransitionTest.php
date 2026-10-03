<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Services\BookingStatusTransition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BookingStatusTransitionTest extends TestCase
{
    public function test_pending_can_transition_to_confirmed(): void
    {
        $this->assertTrue(app(BookingStatusTransition::class)->canTransition(BookingStatus::Pending, BookingStatus::Confirmed));
    }

    public function test_completed_cannot_transition_again(): void
    {
        $this->assertFalse(app(BookingStatusTransition::class)->canTransition(BookingStatus::Completed, BookingStatus::Confirmed));
    }

    public function test_assert_throws_for_illegal_transition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(BookingStatusTransition::class)->assertCanTransition(BookingStatus::Cancelled, BookingStatus::Confirmed);
    }
}
