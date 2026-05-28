<?php

namespace Tests\Unit\Enums;

use App\Enums\ClientStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientStatusTest extends TestCase
{
    #[DataProvider('statusLabelProvider')]
    public function test_label_returns_translated_string_for_each_status(ClientStatus $status, string $expectedFragment): void
    {
        $this->assertStringContainsString($expectedFragment, $status->label());
    }

    /**
     * @return array<string, array{0: ClientStatus, 1: string}>
     */
    public static function statusLabelProvider(): array
    {
        return [
            'active' => [ClientStatus::Active, 'Active'],
            'contact intent' => [ClientStatus::ContactIntent, 'Contact intent'],
            'ignored' => [ClientStatus::Ignored, 'Ignored'],
            'archived' => [ClientStatus::Archived, 'Archived'],
        ];
    }

    public function test_options_returns_value_label_map_for_all_cases(): void
    {
        $options = ClientStatus::options();

        $this->assertCount(count(ClientStatus::cases()), $options);

        foreach (ClientStatus::cases() as $status) {
            $this->assertArrayHasKey($status->value, $options);
            $this->assertSame($status->label(), $options[$status->value]);
        }
    }
}
