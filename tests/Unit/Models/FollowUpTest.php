<?php

namespace Tests\Unit\Models;

use App\Enums\FollowUpReminderStatus;
use App\Models\FollowUp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_badge_classes_use_danger_when_overdue(): void
    {
        $followUp = FollowUp::factory()->overdue()->create();

        $this->assertStringContainsString('status-danger', $followUp->statusBadgeClasses());
    }

    public function test_status_badge_classes_use_reminder_status_when_not_overdue(): void
    {
        $followUp = FollowUp::factory()->create([
            'due_at' => now()->addDay(),
            'reminder_status' => FollowUpReminderStatus::Pending,
        ]);

        $this->assertSame(
            FollowUpReminderStatus::Pending->badgeClasses(),
            $followUp->statusBadgeClasses(),
        );
    }
}
