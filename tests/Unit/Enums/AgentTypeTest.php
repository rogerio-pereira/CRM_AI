<?php

namespace Tests\Unit\Enums;

use App\Enums\AgentType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AgentTypeTest extends TestCase
{
    #[DataProvider('labelProvider')]
    public function test_label_returns_translated_string_for_each_type(AgentType $type, string $expectedFragment): void
    {
        $this->assertStringContainsString($expectedFragment, $type->label());
    }

    /**
     * @return array<string, array{0: AgentType, 1: string}>
     */
    public static function labelProvider(): array
    {
        return [
            'prospecting' => [AgentType::Prospecting, 'Prospecting'],
            'qualification' => [AgentType::Qualification, 'Qualification'],
            'recommendation' => [AgentType::Recommendation, 'Recommendation'],
            'proposal assistant' => [AgentType::ProposalAssistant, 'Proposal assistant'],
        ];
    }
}
