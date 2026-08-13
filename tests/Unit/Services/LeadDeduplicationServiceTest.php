<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Services\LeadDeduplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadDeduplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeadDeduplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LeadDeduplicationService::class);
    }

    public function test_detects_duplicate_by_normalized_company_name(): void
    {
        Client::factory()->create([
            'company_name' => 'Green Sprout Lawn Care',
            'website' => 'https://other.example',
            'contact_email' => 'a@example.com',
        ]);

        $duplicate = $this->service->findDuplicate([
            'company_name' => 'green sprout lawn care!',
            'website' => 'https://different.example',
            'email' => 'b@example.com',
        ]);

        $this->assertNotNull($duplicate);
        $this->assertSame('Green Sprout Lawn Care', $duplicate->company_name);
    }

    public function test_detects_duplicate_by_website_domain(): void
    {
        Client::factory()->create([
            'company_name' => 'Alpha Co',
            'website' => 'https://www.greensprout.example/about',
            'contact_email' => 'a@example.com',
        ]);

        $duplicate = $this->service->findDuplicate([
            'company_name' => 'Totally Different Name',
            'website' => 'https://greensprout.example',
            'email' => 'b@example.com',
        ]);

        $this->assertNotNull($duplicate);
    }

    public function test_detects_duplicate_by_email(): void
    {
        Client::factory()->create([
            'company_name' => 'Alpha Co',
            'website' => 'https://alpha.example',
            'contact_email' => 'Owner@GreenSprout.example',
        ]);

        $duplicate = $this->service->findDuplicate([
            'company_name' => 'Beta Co',
            'website' => 'https://beta.example',
            'email' => 'owner@greensprout.example',
        ]);

        $this->assertNotNull($duplicate);
    }

    public function test_detects_duplicate_by_phone_digits(): void
    {
        Client::factory()->create([
            'company_name' => 'Alpha Co',
            'website' => 'https://alpha.example',
            'contact_email' => 'a@example.com',
            'contact_phone' => '(813) 555-0199',
        ]);

        $duplicate = $this->service->findDuplicate([
            'company_name' => 'Beta Co',
            'website' => 'https://beta.example',
            'email' => 'b@example.com',
            'phone' => '813-555-0199',
        ]);

        $this->assertNotNull($duplicate);
    }

    public function test_returns_null_when_no_match(): void
    {
        Client::factory()->create([
            'company_name' => 'Alpha Co',
            'website' => 'https://alpha.example',
            'contact_email' => 'a@example.com',
            'contact_phone' => '8135550100',
        ]);

        $duplicate = $this->service->findDuplicate([
            'company_name' => 'Beta Co',
            'website' => 'https://beta.example',
            'email' => 'b@example.com',
            'phone' => '8135550199',
        ]);

        $this->assertNull($duplicate);
    }
}
