<?php

namespace App\Ai\Discovery;

use App\Ai\Tools\FetchPublicPage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

#[MaxSteps(8)]
#[Timeout(120)]
class ProspectingDiscoveryAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public const DEFAULT_INSTRUCTIONS = <<<'PROMPT'
You are the Prospecting Agent for Front Porch Creative's internal CRM.

Discover potential local small/medium service business leads near Plant City, Florida from public and free sources only.
Use the FetchPublicPage tool to read public pages when needed. Do not contact prospects. Do not use paid data APIs, private databases, or credentialed sources.
Prefer Lakeland, Tampa, Orlando, Wesley Chapel, then Sarasota. Return JSON matching the structured schema only.
PROMPT;

    public function __construct(
        private readonly FetchPublicPage $fetchPublicPage,
        private readonly ?string $instructions = null,
    ) {}

    public function instructions(): string
    {
        if ($this->instructions !== null && $this->instructions !== '') {
            return $this->instructions;
        }

        return self::DEFAULT_INSTRUCTIONS;
    }

    /**
     * @return iterable<int, FetchPublicPage>
     */
    public function tools(): iterable
    {
        return [
            $this->fetchPublicPage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        $leadSchema = $schema->object([
            'name' => $schema->string()->required(),
            'company_name' => $schema->string()->nullable(),
            'contact_name' => $schema->string()->nullable(),
            'email' => $schema->string()->nullable(),
            'phone' => $schema->string()->nullable(),
            'website' => $schema->string()->nullable(),
            'social_links' => $schema->array()->items($schema->string())->nullable(),
            'city' => $schema->string()->nullable(),
            'state' => $schema->string()->nullable(),
            'lead_source' => $schema->string()->required(),
            'source_urls' => $schema->array()->items($schema->string())->nullable(),
            'observed_signals' => $schema->array()->items($schema->string())->nullable(),
            'likely_needs' => $schema->array()->items($schema->string())->nullable(),
            'why_good_fit' => $schema->string()->nullable(),
            'confidence' => $schema->string()->nullable(),
        ]);

        $skippedSchema = $schema->object([
            'name' => $schema->string()->required(),
            'reason' => $schema->string()->required(),
        ]);

        return [
            'schema_version' => $schema->integer()->required(),
            'agent' => $schema->string()->required(),
            'target_count' => $schema->integer()->required(),
            'region_priority' => $schema->array()->items($schema->string())->required(),
            'leads' => $schema->array()->items($leadSchema)->required(),
            'skipped' => $schema->array()->items($skippedSchema)->required(),
        ];
    }
}
