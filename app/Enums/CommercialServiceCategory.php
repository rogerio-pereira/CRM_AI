<?php

namespace App\Enums;

enum CommercialServiceCategory: string
{
    case BusinessAutomations = 'business-automations';
    case ContentCreation = 'content-creation';
    case CustomSoftwareDevelopment = 'custom-software-development';
    case EmailMarketing = 'email-marketing';
    case LeadGeneration = 'lead-generation';
    case WebsiteDesignAndDevelopment = 'website-design-and-development';

    public function label(): string
    {
        return match ($this) {
            self::BusinessAutomations => __('Business automations'),
            self::ContentCreation => __('Content creation'),
            self::CustomSoftwareDevelopment => __('Custom software development'),
            self::EmailMarketing => __('Email marketing'),
            self::LeadGeneration => __('Lead generation'),
            self::WebsiteDesignAndDevelopment => __('Website design and development'),
        };
    }
}
