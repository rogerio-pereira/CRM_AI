<?php

namespace Database\Seeders;

use App\Enums\CommercialServiceCategory;
use App\Models\CommercialService;
use Illuminate\Database\Seeder;

class CommercialServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'category_slug' => CommercialServiceCategory::WebsiteDesignAndDevelopment,
                'name' => 'Domain registration',
                'description' => 'Registration and initial configuration of one domain.',
                'default_unit_price' => 35,
                'is_active' => true,
            ],
            [
                'category_slug' => CommercialServiceCategory::WebsiteDesignAndDevelopment,
                'name' => 'DNS setup',
                'description' => 'DNS record configuration and launch verification.',
                'default_unit_price' => 150,
                'is_active' => true,
            ],
            [
                'category_slug' => CommercialServiceCategory::CustomSoftwareDevelopment,
                'name' => 'Cloud environment setup',
                'description' => 'Application environment provisioning and deployment setup.',
                'default_unit_price' => 750,
                'is_active' => true,
            ],
            [
                'category_slug' => CommercialServiceCategory::CustomSoftwareDevelopment,
                'name' => 'Development hour',
                'description' => 'One hour of custom software design, development, or testing.',
                'default_unit_price' => 125,
                'is_active' => true,
            ],
            [
                'category_slug' => CommercialServiceCategory::BusinessAutomations,
                'name' => 'Automation workflow setup',
                'description' => 'Design, implementation, and testing of one automation workflow.',
                'default_unit_price' => 1200,
                'is_active' => true,
            ],
            [
                'category_slug' => CommercialServiceCategory::ContentCreation,
                'name' => 'Content production package',
                'description' => 'Planning and production of a monthly content package.',
                'default_unit_price' => 900,
                'is_active' => true,
            ],
            [
                'category_slug' => CommercialServiceCategory::EmailMarketing,
                'name' => 'Email campaign setup',
                'description' => 'Audience setup, campaign build, testing, and launch support.',
                'default_unit_price' => 650,
                'is_active' => true,
            ],
            [
                'category_slug' => CommercialServiceCategory::LeadGeneration,
                'name' => 'Lead generation campaign setup',
                'description' => 'Campaign targeting, channel setup, and initial launch.',
                'default_unit_price' => 1000,
                'is_active' => true,
            ],
        ];

        foreach ($services as $attributes) {
            $identity = [
                'name' => $attributes['name'],
            ];

            CommercialService::updateOrCreate($identity, $attributes);
        }
    }
}
