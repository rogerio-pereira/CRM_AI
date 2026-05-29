<?php

namespace Tests\Unit\Enums;

use App\Enums\FollowUpReminderStatus;
use App\Enums\TaskStatus;
use Tests\TestCase;

class StatusBadgeTest extends TestCase
{
    public function test_pending_status_badges_match_between_follow_up_and_task(): void
    {
        $this->assertSame(
            TaskStatus::Pending->badgeClasses(),
            FollowUpReminderStatus::Pending->badgeClasses(),
        );
    }

    public function test_completed_and_done_status_badges_match(): void
    {
        $this->assertSame(
            TaskStatus::Done->badgeClasses(),
            FollowUpReminderStatus::Completed->badgeClasses(),
        );
    }
}
