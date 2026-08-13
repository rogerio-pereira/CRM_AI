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

    public function __construct(
        private readonly FetchPublicPage $fetchPublicPage,
        private readonly string $instructions,
    ) {}

    public function instructions(): string
    {
        return $this->instructions;
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
        $lead = $schema->object([
            'company_name' => $schema->string()->required(),
            'contact_name' => $schema->string()->nullable(),
            'email' => $schema->string()->required(),
            'phone' => $schema->string()->nullable(),
            'website' => $schema->string()->nullable(),
            'social_links' => $schema->array()->items($schema->string())->nullable(),
            'why_good_fit' => $schema->string()->nullable(),
            'observed_signals' => $schema->array()->items($schema->string())->nullable(),
        ]);

        $skipped = $schema->object([
            'name' => $schema->string()->required(),
            'reason' => $schema->string()->required(),
        ]);

        return [
            'leads' => $schema->array()->items($lead)->required(),
            'skipped' => $schema->array()->items($skipped)->required(),
        ];
    }
}
