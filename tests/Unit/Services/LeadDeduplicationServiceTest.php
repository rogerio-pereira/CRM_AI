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

    public function test_detects_duplicate_by_company_name(): void
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
    }

    public function test_detects_duplicate_by_domain_email_and_phone(): void
    {
        Client::factory()->create([
            'company_name' => 'Alpha Co',
            'website' => 'https://www.greensprout.example/about',
            'contact_email' => 'a@example.com',
            'contact_phone' => '8135550000',
        ]);

        Client::factory()->create([
            'company_name' => 'Beta Co',
            'website' => 'https://beta.example',
            'contact_email' => 'Owner@Dup.example',
            'contact_phone' => '8135550001',
        ]);

        Client::factory()->create([
            'company_name' => 'Gamma Co',
            'website' => 'https://gamma.example',
            'contact_email' => 'c@example.com',
            'contact_phone' => '(813) 555-0199',
        ]);

        $byDomain = $this->service->findDuplicate([
            'company_name' => 'Different',
            'website' => 'https://greensprout.example',
            'email' => 'x@example.com',
        ]);

        $byEmail = $this->service->findDuplicate([
            'company_name' => 'Different',
            'website' => 'https://other.example',
            'email' => 'owner@dup.example',
        ]);

        $byPhone = $this->service->findDuplicate([
            'company_name' => 'Different',
            'website' => 'https://other2.example',
            'email' => 'y@example.com',
            'phone' => '813-555-0199',
        ]);

        $this->assertNotNull($byDomain);
        $this->assertNotNull($byEmail);
        $this->assertNotNull($byPhone);
    }
}
