<?php

namespace Tests\Unit\Enums;

use App\Enums\FollowUpPriority;
use App\Enums\TaskPriority;
use Tests\TestCase;

class PriorityBadgeTest extends TestCase
{
    public function test_follow_up_and_task_priorities_share_badge_classes(): void
    {
        $followUpLowClasses = FollowUpPriority::Low->badgeClasses();
        $taskLowClasses = TaskPriority::Low->badgeClasses();

        $followUpMediumClasses = FollowUpPriority::Medium->badgeClasses();
        $taskMediumClasses = TaskPriority::Medium->badgeClasses();

        $followUpHighClasses = FollowUpPriority::High->badgeClasses();
        $taskHighClasses = TaskPriority::High->badgeClasses();

        $this->assertSame($followUpLowClasses, $taskLowClasses);
        $this->assertSame($followUpMediumClasses, $taskMediumClasses);
        $this->assertSame($followUpHighClasses, $taskHighClasses);
    }

    public function test_high_priority_uses_danger_tokens(): void
    {
        $classes = TaskPriority::High->badgeClasses();

        $this->assertStringContainsString('status-danger', $classes);
    }

    public function test_medium_priority_uses_warning_tokens(): void
    {
        $classes = FollowUpPriority::Medium->badgeClasses();

        $this->assertStringContainsString('status-warning', $classes);
    }

    public function test_low_priority_uses_neutral_tokens(): void
    {
        $classes = TaskPriority::Low->badgeClasses();

        $this->assertStringContainsString('status-neutral', $classes);
    }
}
