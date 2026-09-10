<?php

namespace Tests\Support;

use App\Ai\Agents\WriteFollowUpEmailAgent;

class FollowUpEmailFake
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function fake(?array $payload = null): void
    {
        $email = $payload ?? self::copywriterPayload();

        WriteFollowUpEmailAgent::fake([
            $email,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $payloads
     */
    public static function fakeSequence(array $payloads): void
    {
        WriteFollowUpEmailAgent::fake($payloads);
    }

    /**
     * @return array<string, mixed>
     */
    public static function copywriterPayload(): array
    {
        return [
            'channel' => 'email',
            'subject' => '🔁 A new way to turn local quotes into booked work',
            'body' => "Hi Sarah,\n\nHere is a new insight on the same problem.\n\nRoger Pereira\n[Front Porch Creative](https://frontporchcreative.io)",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function lastEmailPayload(): array
    {
        return [
            'channel' => 'email',
            'subject' => '📌 Last note on turning quotes into booked work',
            'body' => "Hi Sarah,\n\nThis is the last email I will send about this.\n\nRoger Pereira\n[Front Porch Creative](https://frontporchcreative.io)",
        ];
    }
}
