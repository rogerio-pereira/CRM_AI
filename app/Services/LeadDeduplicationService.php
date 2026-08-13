<?php

namespace App\Services;

use App\Models\Client;
use App\Support\UrlNormalizer;

class LeadDeduplicationService
{
    /**
     * Match by company name or website domain (primary), email or phone (secondary). ADR-015.
     *
     * @param  array{company_name?: ?string, website?: ?string, email?: ?string, phone?: ?string}  $candidate
     */
    public function findDuplicate(array $candidate): ?Client
    {
        $companyName = $this->lowerText($candidate['company_name'] ?? null);
        $domain = $this->host($candidate['website'] ?? null);
        $email = $this->lowerText($candidate['email'] ?? null);
        $phone = $this->digits($candidate['phone'] ?? null);

        $clients = Client::query()
                        ->orderBy('id')
                        ->get([
                            'id',
                            'company_name',
                            'website',
                            'contact_email',
                            'contact_phone',
                        ]);

        foreach ($clients as $client) {
            $existingName = $this->lowerText($client->company_name);
            $existingDomain = $this->host($client->website);
            $existingEmail = $this->lowerText($client->contact_email);
            $existingPhone = $this->digits($client->contact_phone);

            if ($companyName !== null && $existingName === $companyName) {
                return $client;
            }

            if ($domain !== null && $existingDomain === $domain) {
                return $client;
            }

            if ($email !== null && $existingEmail === $email) {
                return $client;
            }

            if ($phone !== null && $existingPhone === $phone) {
                return $client;
            }
        }

        return null;
    }

    private function lowerText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        $text = strtolower($trimmed);

        if ($text === '') {
            return null;
        }

        return $text;
    }

    private function host(?string $website): ?string
    {
        $url = UrlNormalizer::normalize($website);

        if ($url === null) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host)) {
            return null;
        }

        $host = strtolower($host);

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        if ($host === '') {
            return null;
        }

        return $host;
    }

    private function digits(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = str_replace([' ', '-', '(', ')', '.', '+'], '', $phone);

        if (strlen($digits) < 7) {
            return null;
        }

        return $digits;
    }
}
