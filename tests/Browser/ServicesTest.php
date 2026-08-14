<?php

use App\Enums\CommercialServiceCategory;
use App\Models\CommercialService;
use App\Models\User;

it('navigates to services and completes the catalog workflow', function () {
    $user = User::factory()
                ->create();

    $this->actingAs($user);

    $page = visit('/dashboard')
                ->assertNoSmoke()
                ->click('@nav-services')
                ->assertPathIs('/services')
                ->assertPresent('[data-test="services-page"]')
                ->click('@services-create-button')
                ->fill('@services-form-name', 'Browser DNS setup')
                ->select(
                    '@services-form-category',
                    CommercialServiceCategory::WebsiteDesignAndDevelopment->value
                )
                ->fill('@services-form-price', '225.00')
                ->fill('@services-form-description', 'DNS configuration for launch.')
                ->click('@services-form-submit')
                ->assertSee('Browser DNS setup');

    $service = CommercialService::where('name', 'Browser DNS setup')
                    ->firstOrFail();

    $page->click('@services-actions-'.$service->id)
        ->click('@services-edit-'.$service->id)
        ->fill('@services-form-price', '275.00')
        ->click('@services-form-submit')
        ->assertSee('275.00')
        ->click('@services-actions-'.$service->id)
        ->click('@services-toggle-active-'.$service->id)
        ->assertPresent('[data-test="services-status-'.$service->id.'"][data-active="false"]');

    $service->refresh();

    expect($service->default_unit_price)
        ->toBe('275.00');
    expect($service->is_active)
        ->toBeFalse();
});
