<?php

namespace Tests\Unit\Enums;

use App\Enums\PipelineStage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PipelineStageTest extends TestCase
{
    #[DataProvider('terminalStageProvider')]
    public function test_is_terminal_returns_true_for_won_and_lost(PipelineStage $stage): void
    {
        $this->assertTrue($stage->isTerminal());
    }

    /**
     * @return array<string, array{0: PipelineStage}>
     */
    public static function terminalStageProvider(): array
    {
        return [
            'won' => [PipelineStage::Won],
            'lost' => [PipelineStage::Lost],
        ];
    }

    #[DataProvider('openStageProvider')]
    public function test_is_terminal_returns_false_for_non_terminal_stages(PipelineStage $stage): void
    {
        $this->assertFalse($stage->isTerminal());
    }

    /**
     * @return array<string, array{0: PipelineStage}>
     */
    public static function openStageProvider(): array
    {
        return [
            'lead' => [PipelineStage::Lead],
            'qualification' => [PipelineStage::Qualification],
            'contact' => [PipelineStage::Contact],
            'proposal generation' => [PipelineStage::ProposalGeneration],
            'proposal analysis' => [PipelineStage::ProposalAnalysis],
            'proposal sent' => [PipelineStage::ProposalSent],
        ];
    }
}
