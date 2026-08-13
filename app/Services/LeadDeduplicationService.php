<?php

namespace App\Services;

use App\Models\Client;
use App\Support\UrlNormalizer;
use Illuminate\Support\Str;

class LeadDeduplicationService
{
    /**
     * Find an existing lead that matches company name, website domain, email, or phone (ADR-015).
     *
     * @param  array{
     *     company_name?: ?string,
     *     website?: ?string,
     *     email?: ?string,
     *     contact_email?: ?string,
     *     phone?: ?string,
     *     contact_phone?: ?string,
     * }  $candidate
     */
    public function findDuplicate(array $candidate): ?Client
    {
        $companyName = $this->normalizeCompanyName($candidate['company_name'] ?? null);
        $domain = $this->normalizeDomain($candidate['website'] ?? null);
        $email = $this->normalizeEmail($candidate['email'] ?? $candidate['contact_email'] ?? null);
        $phone = $this->normalizePhone($candidate['phone'] ?? $candidate['contact_phone'] ?? null);

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
            if ($this->matchesPrimary($client, $companyName, $domain)) {
                return $client;
            }

            if ($this->matchesSecondary($client, $email, $phone)) {
                return $client;
            }
        }

        return null;
    }

    public function isDuplicate(array $candidate): bool
    {
        return $this->findDuplicate($candidate) !== null;
    }

    public function normalizeCompanyName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $normalized = Str::lower(trim($name));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? '';
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalized) ?? '';
        $normalized = trim($normalized);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }

    public function normalizeDomain(?string $website): ?string
    {
        $normalizedUrl = UrlNormalizer::normalize($website);

        if ($normalizedUrl === null) {
            return null;
        }

        $host = parse_url($normalizedUrl, PHP_URL_HOST);

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

    public function normalizeEmail(?string $email): ?string
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

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) < 7) {
            return null;
        }

        return $digits;
    }

    protected function matchesPrimary(Client $client, ?string $companyName, ?string $domain): bool
    {
        if ($companyName !== null) {
            $existingName = $this->normalizeCompanyName($client->company_name);

            if ($existingName !== null && $existingName === $companyName) {
                return true;
            }
        }

        if ($domain !== null) {
            $existingDomain = $this->normalizeDomain($client->website);

            if ($existingDomain !== null && $existingDomain === $domain) {
                return true;
            }
        }

        return false;
    }

    protected function matchesSecondary(Client $client, ?string $email, ?string $phone): bool
    {
        if ($email !== null) {
            $existingEmail = $this->normalizeEmail($client->contact_email);

            if ($existingEmail !== null && $existingEmail === $email) {
                return true;
            }
        }

        if ($phone !== null) {
            $existingPhone = $this->normalizePhone($client->contact_phone);

            if ($existingPhone !== null && $existingPhone === $phone) {
                return true;
            }
        }

        return false;
    }
}
