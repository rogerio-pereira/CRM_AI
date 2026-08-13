<?php

namespace Tests\Feature\Leads;

use App\Enums\QualificationStatus;
use App\Livewire\Leads\Index;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QualificationStatusChipTest extends TestCase
{
    use RefreshDatabase;

    public function test_leads_index_renders_qualification_status_chips(): void
    {
        $user = User::factory()->create();
        $pending = Client::factory()->qualificationPending()->create([
            'company_name' => 'Pending Chip Co',
        ]);
        $processing = Client::factory()->qualificationProcessing()->create([
            'company_name' => 'Processing Chip Co',
        ]);
        $qualified = Client::factory()->qualificationQualified()->create([
            'company_name' => 'Qualified Chip Co',
        ]);
        $failed = Client::factory()->qualificationFailed()->create([
            'company_name' => 'Failed Chip Co',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSeeHtml('data-test="leads-qualification-badge-'.$pending->id.'"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Pending->value.'"')
            ->assertSeeHtml('data-test="leads-qualification-badge-'.$processing->id.'"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Processing->value.'"')
            ->assertSeeHtml('data-test="leads-qualification-badge-'.$qualified->id.'"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Qualified->value.'"')
            ->assertSeeHtml('data-test="leads-qualification-badge-'.$failed->id.'"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Failed->value.'"')
            ->assertSeeHtml($pending->qualification_status->badgeClasses())
            ->assertSeeHtml($processing->qualification_status->badgeClasses())
            ->assertSeeHtml($qualified->qualification_status->badgeClasses())
            ->assertSeeHtml($failed->qualification_status->badgeClasses());
    }

    public function test_lead_detail_renders_failed_qualification_error(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->qualificationFailed()->create([
            'company_name' => 'Failed Detail Co',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSeeHtml('data-test="leads-detail-qualification-badge"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Failed->value.'"')
            ->assertSeeHtml('data-test="leads-detail-qualification-error"')
            ->assertSee('Qualification could not be completed. The team can try again later.');
    }

    public function test_lead_detail_renders_ai_insight_summary_when_qualified(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->qualificationQualified()->create([
            'company_name' => 'Qualified Detail Co',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSeeHtml('data-test="leads-detail-qualification-badge"')
            ->assertSeeHtml('data-status="'.QualificationStatus::Qualified->value.'"')
            ->assertSeeHtml('data-test="leads-detail-ai-insights-summary"')
            ->assertSee('Ready for a first conversation.')
            ->assertSee('AI-generated. Not a confirmed human decision.');
    }
}
