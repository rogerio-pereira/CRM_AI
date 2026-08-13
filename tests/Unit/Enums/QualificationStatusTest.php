<?php

namespace Tests\Unit\Enums;

use App\Enums\QualificationStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QualificationStatusTest extends TestCase
{
    #[DataProvider('statusLabelProvider')]
    public function test_label_returns_translated_string_for_each_status(
        QualificationStatus $status,
        string $expectedFragment,
    ): void {
        $this->assertStringContainsString($expectedFragment, $status->label());
    }

    /**
     * @return array<string, array{0: QualificationStatus, 1: string}>
     */
    public static function statusLabelProvider(): array
    {
        return [
            'pending' => [QualificationStatus::Pending, 'Pending'],
            'processing' => [QualificationStatus::Processing, 'Processing'],
            'qualified' => [QualificationStatus::Qualified, 'Qualified'],
            'failed' => [QualificationStatus::Failed, 'Failed'],
        ];
    }

    public function test_badge_classes_are_defined_for_each_status(): void
    {
        foreach (QualificationStatus::cases() as $status) {
            $this->assertNotSame('', $status->badgeClasses());
        }
    }

    #[DataProvider('statusBadgeClassProvider')]
    public function test_badge_classes_use_expected_tokens(
        QualificationStatus $status,
        string $expectedTokenFragment,
    ): void {
        $this->assertStringContainsString($expectedTokenFragment, $status->badgeClasses());
    }

    /**
     * @return array<string, array{0: QualificationStatus, 1: string}>
     */
    public static function statusBadgeClassProvider(): array
    {
        return [
            'pending' => [QualificationStatus::Pending, 'status-neutral'],
            'processing' => [QualificationStatus::Processing, 'text-ai'],
            'qualified' => [QualificationStatus::Qualified, 'status-success'],
            'failed' => [QualificationStatus::Failed, 'status-danger'],
        ];
    }
}
