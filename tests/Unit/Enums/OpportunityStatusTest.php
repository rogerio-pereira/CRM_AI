<?php

namespace Tests\Unit\Enums;

use App\Enums\OpportunityStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OpportunityStatusTest extends TestCase
{
    #[DataProvider('statusLabelProvider')]
    public function test_label_returns_expected_value(OpportunityStatus $status, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $status->label());
    }

    /**
     * @return array<string, array{0: OpportunityStatus, 1: string}>
     */
    public static function statusLabelProvider(): array
    {
        return [
            'open' => [OpportunityStatus::Open, 'Open'],
            'won' => [OpportunityStatus::Won, 'Won'],
            'lost' => [OpportunityStatus::Lost, 'Lost'],
        ];
    }
}
