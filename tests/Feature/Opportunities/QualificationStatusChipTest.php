<?php

namespace Tests\Feature\Opportunities;

use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Livewire\Opportunities\Index;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QualificationStatusChipTest extends TestCase
{
    use RefreshDatabase;

    public function test_kanban_renders_qualification_status_chips(): void
    {
        $user = User::factory()->create();
        $pending = Opportunity::factory()->qualificationPending()->create([
            'title' => 'Pending Chip Deal',
            'stage' => PipelineStage::Lead,
        ]);
        $processing = Opportunity::factory()->qualificationProcessing()->create([
            'title' => 'Processing Chip Deal',
            'stage' => PipelineStage::Qualification,
        ]);
        $qualified = Opportunity::factory()->qualificationQualified()->create([
            'title' => 'Qualified Chip Deal',
            'stage' => PipelineStage::Contact,
        ]);
        $failed = Opportunity::factory()->qualificationFailed()->create([
            'title' => 'Failed Chip Deal',
            'stage' => PipelineStage::Qualification,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSeeHtml('data-test="kanban-card-qualification-badge-'.$pending->id.'"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Pending->value.'"')
            ->assertSeeHtml('data-test="kanban-card-qualification-badge-'.$processing->id.'"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Processing->value.'"')
            ->assertSeeHtml('data-test="kanban-card-qualification-badge-'.$qualified->id.'"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Qualified->value.'"')
            ->assertSeeHtml('data-test="kanban-card-qualification-badge-'.$failed->id.'"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Failed->value.'"')
            ->assertSeeHtml($pending->qualification_status->badgeClasses())
            ->assertSeeHtml($processing->qualification_status->badgeClasses())
            ->assertSeeHtml($qualified->qualification_status->badgeClasses())
            ->assertSeeHtml($failed->qualification_status->badgeClasses());
    }

    public function test_opportunity_detail_renders_failed_qualification_error(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->qualificationFailed()->create([
            'title' => 'Failed Detail Deal',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $opportunity->id)
            ->assertSeeHtml('data-test="opportunities-detail-qualification-badge"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Failed->value.'"')
            ->assertSeeHtml('data-test="opportunities-detail-qualification-error"')
            ->assertSee('Qualification could not be completed. The team can try again later.');
    }

    public function test_opportunity_detail_renders_ai_insight_summary_when_qualified(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->qualificationQualified()->create([
            'title' => 'Qualified Detail Deal',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $opportunity->id)
            ->assertSeeHtml('data-test="opportunities-detail-qualification-badge"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Qualified->value.'"')
            ->assertSeeHtml('data-test="opportunities-detail-ai-insights-summary"')
            ->assertSee('Ready for a first conversation.')
            ->assertSee('AI-generated. Not a confirmed human decision.');
    }
}
