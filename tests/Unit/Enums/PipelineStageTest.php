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

    public function test_ordered_returns_eight_stages_in_pipeline_order(): void
    {
        $ordered = PipelineStage::ordered();

        $this->assertCount(8, $ordered);
        $this->assertSame(PipelineStage::Lead, $ordered[0]);
        $this->assertSame(PipelineStage::Lost, $ordered[7]);
    }

    public function test_label_and_color_token_are_defined_for_each_stage(): void
    {
        foreach (PipelineStage::ordered() as $stage) {
            $this->assertNotSame('', $stage->label());
            $this->assertNotSame('', $stage->colorToken());
            $this->assertNotSame('', $stage->slug());
            $this->assertNotSame('', $stage->badgeClasses());
            $this->assertNotSame('', $stage->columnClasses());
            $this->assertNotSame('', $stage->columnHeadingClasses());
        }
    }

    #[DataProvider('userActionStageProvider')]
    public function test_requires_user_action_returns_true_for_human_driven_stages(PipelineStage $stage): void
    {
        $this->assertTrue($stage->requiresUserAction());
        $this->assertStringContainsString('primary', $stage->columnClasses());
        $this->assertStringContainsString('primary', $stage->badgeClasses());
    }

    /**
     * @return array<string, array{0: PipelineStage}>
     */
    public static function userActionStageProvider(): array
    {
        return [
            'contact' => [PipelineStage::Contact],
            'proposal analysis' => [PipelineStage::ProposalAnalysis],
        ];
    }

    #[DataProvider('automatedOrAwaitingStageProvider')]
    public function test_requires_user_action_returns_false_for_non_human_stages(PipelineStage $stage): void
    {
        $this->assertFalse($stage->requiresUserAction());
        $this->assertStringContainsString('border-border', $stage->columnClasses());
    }

    /**
     * @return array<string, array{0: PipelineStage}>
     */
    public static function automatedOrAwaitingStageProvider(): array
    {
        return [
            'lead' => [PipelineStage::Lead],
            'qualification' => [PipelineStage::Qualification],
            'proposal generation' => [PipelineStage::ProposalGeneration],
            'proposal sent' => [PipelineStage::ProposalSent],
            'won' => [PipelineStage::Won],
            'lost' => [PipelineStage::Lost],
        ];
    }
}
