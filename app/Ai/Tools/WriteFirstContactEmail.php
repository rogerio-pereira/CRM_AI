<?php

namespace App\Ai\Tools;

use App\Ai\Agents\WriteFirstContactEmailAgent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class WriteFirstContactEmail implements Tool
{
    public function name(): string
    {
        return 'write_first_contact_email';
    }

    public function description(): Stringable|string
    {
        return 'Write the example first-contact email (subject and body) for a lead. Always use this tool for contact_example. Do not draft the email yourself.';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
                'contact_name' => $schema->string()
                                        ->required(),
                'company_name' => $schema->string()
                                        ->required(),
                'line_of_business' => $schema->string()
                                            ->required(),
                'location' => $schema->string()
                                    ->required(),
                'service_angle' => $schema->string()
                                        ->required(),
                'observed_hook' => $schema->string()
                                        ->required(),
                'opportunity' => $schema->string()
                                        ->required(),
                'sample_insight' => $schema->string()
                                            ->required(),
            ];
    }

    public function handle(Request $request): Stringable|string
    {
        $brief = [
                'contact_name' => $request['contact_name'],
                'company_name' => $request['company_name'],
                'line_of_business' => $request['line_of_business'],
                'location' => $request['location'],
                'service_angle' => $request['service_angle'],
                'observed_hook' => $request['observed_hook'],
                'opportunity' => $request['opportunity'],
                'sample_insight' => $request['sample_insight'],
            ];
        $userPrompt = $this->buildUserPrompt($brief);
        $agent = app(WriteFirstContactEmailAgent::class);
        $response = $agent->prompt($userPrompt);

        if (! $response instanceof StructuredAgentResponse) {
            return $response->text;
        }

        $payload = $response->toArray();
        $encoded = json_encode($payload);

        if (! is_string($encoded)) {
            throw new RuntimeException('First contact email could not be encoded.');
        }

        return $encoded;
    }

    /**
     * @param  array<string, mixed>  $brief
     */
    private function buildUserPrompt(array $brief): string
    {
        $encodedBrief = json_encode($brief);

        if (! is_string($encodedBrief)) {
            throw new RuntimeException('First contact email brief could not be encoded.');
        }

        return "Write the example first contact email for this lead. Return structured JSON only.\n\n".$encodedBrief;
    }
}
