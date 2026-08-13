<?php

namespace App\Services;

use App\Models\Client;
use App\Support\UrlNormalizer;
use Illuminate\Support\Str;

class LeadDeduplicationService
{
    /**
     * Match by company name or website domain (primary), email or phone (secondary). ADR-015.
     *
     * @param  array{company_name?: ?string, website?: ?string, email?: ?string, phone?: ?string}  $candidate
     */
    public function findDuplicate(array $candidate): ?Client
    {
        $rawCompanyName = $candidate['company_name'] ?? null;
        $rawWebsite = $candidate['website'] ?? null;
        $rawEmail = $candidate['email'] ?? null;
        $rawPhone = $candidate['phone'] ?? null;

        $companyName = $this->normalizeCompanyName($rawCompanyName);
        $domain = $this->normalizeDomain($rawWebsite);
        $email = $this->normalizeEmail($rawEmail);
        $phone = $this->normalizePhone($rawPhone);

        $columns = [
                'id',
                'company_name',
                'website',
                'contact_email',
                'contact_phone',
            ];

        $clients = Client::query()
                        ->orderBy('id')
                        ->get($columns);

        foreach ($clients as $client) {
            $existingName = $this->normalizeCompanyName($client->company_name);
            $existingDomain = $this->normalizeDomain($client->website);
            $existingEmail = $this->normalizeEmail($client->contact_email);
            $existingPhone = $this->normalizePhone($client->contact_phone);

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

    private function normalizeCompanyName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $normalized = Str::lower(trim($name));
        $withoutExtraSpaces = preg_replace('/\s+/u', ' ', $normalized);

        if (! is_string($withoutExtraSpaces)) {
            $withoutExtraSpaces = '';
        }

        $withoutPunctuation = preg_replace('/[^\p{L}\p{N}\s]/u', '', $withoutExtraSpaces);

        if (! is_string($withoutPunctuation)) {
            $withoutPunctuation = '';
        }

        $normalized = trim($withoutPunctuation);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }

    private function normalizeDomain(?string $website): ?string
    {
        $url = UrlNormalizer::normalize($website);

        if ($url === null) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = Str::lower($host);

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        if ($host === '') {
            return null;
        }

        return $host;
    }

    private function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $trimmed = trim($email);

        if ($trimmed === '') {
            return null;
        }

        $validated = filter_var($trimmed, FILTER_VALIDATE_EMAIL);

        if ($validated === false) {
            return null;
        }

        return Str::lower($validated);
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (! is_string($digits)) {
            $digits = '';
        }

        if (strlen($digits) < 7) {
            return null;
        }

        return $digits;
    }
}
