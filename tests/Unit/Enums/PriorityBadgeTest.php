<?php

namespace Tests\Unit\Enums;

use App\Enums\FollowUpPriority;
use App\Enums\TaskPriority;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PriorityBadgeTest extends TestCase
{
    #[DataProvider('priorityProvider')]
    public function test_task_and_follow_up_priorities_expose_badge_classes(string $expectedFragment, TaskPriority $taskPriority, FollowUpPriority $followUpPriority): void
    {
        $this->assertStringContainsString($expectedFragment, $taskPriority->badgeClasses());
        $this->assertStringContainsString($expectedFragment, $followUpPriority->badgeClasses());
    }

    /**
     * @return array<string, array{0: string, 1: TaskPriority, 2: FollowUpPriority}>
     */
    public static function priorityProvider(): array
    {
        return [
            'low' => ['status-neutral', TaskPriority::Low, FollowUpPriority::Low],
            'medium' => ['status-warning', TaskPriority::Medium, FollowUpPriority::Medium],
            'high' => ['status-danger', TaskPriority::High, FollowUpPriority::High],
        ];
    }
}
