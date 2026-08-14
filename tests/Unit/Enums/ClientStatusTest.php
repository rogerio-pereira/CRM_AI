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
        $statuses = ClientStatus::cases();
        $statusCount = count($statuses);

        $this->assertCount($statusCount, $options);

        foreach ($statuses as $status) {
            $statusValue = $status->value;
            $statusLabel = $status->label();

            $this->assertArrayHasKey($statusValue, $options);

            $optionLabel = $options[$statusValue];

            $this->assertSame($statusLabel, $optionLabel);
        }
    }

    public function test_color_token_and_badge_classes_are_defined_for_each_status(): void
    {
        foreach (ClientStatus::cases() as $status) {
            $this->assertNotSame('', $status->colorToken());
            $this->assertNotSame('', $status->badgeClasses());
        }
    }

    #[DataProvider('statusColorTokenProvider')]
    public function test_color_token_returns_expected_value(ClientStatus $status, string $expectedToken): void
    {
        $this->assertSame($expectedToken, $status->colorToken());
    }

    /**
     * @return array<string, array{0: ClientStatus, 1: string}>
     */
    public static function statusColorTokenProvider(): array
    {
        return [
            'active' => [ClientStatus::Active, 'success'],
            'contact intent' => [ClientStatus::ContactIntent, 'primary'],
            'ignored' => [ClientStatus::Ignored, 'warning'],
            'archived' => [ClientStatus::Archived, 'neutral'],
        ];
    }

    #[DataProvider('statusBadgeClassProvider')]
    public function test_badge_classes_use_expected_status_tokens(
        ClientStatus $status,
        string $expectedTokenFragment,
    ): void {
        $this->assertStringContainsString($expectedTokenFragment, $status->badgeClasses());
    }

    /**
     * @return array<string, array{0: ClientStatus, 1: string}>
     */
    public static function statusBadgeClassProvider(): array
    {
        return [
            'active' => [ClientStatus::Active, 'status-success'],
            'contact intent' => [ClientStatus::ContactIntent, 'primary-focus'],
            'ignored' => [ClientStatus::Ignored, 'amber-300'],
            'archived' => [ClientStatus::Archived, 'status-neutral'],
        ];
    }
}
