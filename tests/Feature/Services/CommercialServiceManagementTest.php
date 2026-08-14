<?php

namespace Tests\Feature\Services;

use App\Enums\CommercialServiceCategory;
use App\Livewire\Services\Index;
use App\Models\CommercialService;
use App\Models\User;
use Database\Seeders\CommercialServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommercialServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_index_requires_authentication(): void
    {
        $this->get(route('services.index'))
            ->assertRedirect(route('login'));
    }

    public function test_services_index_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user)
            ->get(route('services.index'))
            ->assertOk();
    }

    public function test_factory_creates_a_service_with_catalog_casts(): void
    {
        $service = CommercialService::factory()
                        ->create([
                            'category_slug' => CommercialServiceCategory::BusinessAutomations,
                            'default_unit_price' => '125.50',
                        ]);

        $this->assertSame(CommercialServiceCategory::BusinessAutomations, $service->category_slug);
        $this->assertSame('125.50', $service->default_unit_price);
        $this->assertTrue($service->is_active);
    }

    public function test_user_can_create_a_commercial_service(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('name', 'DNS migration')
            ->set('description', 'Move DNS records to the managed provider.')
            ->set('category_slug', CommercialServiceCategory::WebsiteDesignAndDevelopment->value)
            ->set('default_unit_price', '250.00')
            ->call('saveService')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('commercial_services', [
            'name' => 'DNS migration',
            'category_slug' => CommercialServiceCategory::WebsiteDesignAndDevelopment->value,
            'default_unit_price' => 250,
            'is_active' => true,
        ]);
    }

    public function test_name_price_and_category_are_validated(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('name', '')
            ->set('default_unit_price', '-1.555')
            ->set('category_slug', 'unknown-category')
            ->call('saveService')
            ->assertHasErrors([
                'name',
                'default_unit_price',
                'category_slug',
            ]);
    }

    public function test_user_can_update_and_deactivate_a_service(): void
    {
        $user = User::factory()
                    ->create();
        $service = CommercialService::factory()
                        ->create([
                            'name' => 'Original service',
                            'default_unit_price' => '100.00',
                        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openEditModal', $service->id)
            ->set('name', 'Updated service')
            ->set('default_unit_price', '175.00')
            ->call('saveService')
            ->call('toggleActive', $service->id)
            ->assertHasNoErrors();

        $service->refresh();

        $this->assertSame('Updated service', $service->name);
        $this->assertSame('175.00', $service->default_unit_price);
        $this->assertFalse($service->is_active);
    }

    public function test_list_filters_by_search_category_and_active_status(): void
    {
        $user = User::factory()
                    ->create();
        $matchingService = CommercialService::factory()
                                ->create([
                                    'name' => 'Matching automation',
                                    'category_slug' => CommercialServiceCategory::BusinessAutomations,
                                    'is_active' => true,
                                ]);
        $otherService = CommercialService::factory()
                            ->inactive()
                            ->create([
                                'name' => 'Other website service',
                                'category_slug' => CommercialServiceCategory::WebsiteDesignAndDevelopment,
                            ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('search', 'Matching')
            ->set('categoryFilter', CommercialServiceCategory::BusinessAutomations->value)
            ->set('activeFilter', 'active')
            ->assertSee($matchingService->name)
            ->assertDontSee($otherService->name);
    }

    public function test_baseline_seeder_is_idempotent(): void
    {
        $this->seed(CommercialServiceSeeder::class);
        $firstCount = CommercialService::count();

        $this->seed(CommercialServiceSeeder::class);
        $secondCount = CommercialService::count();

        $this->assertSame(8, $firstCount);
        $this->assertSame($firstCount, $secondCount);
    }
}
