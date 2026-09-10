<?php

namespace Tests;

use App\Ai\Agents\WriteFollowUpEmailAgent;
use App\Ai\Discovery\ProspectingDiscoveryAgent;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use Tests\Support\QualificationFake;
use Tests\Support\RecommendationFake;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        QualificationFake::fakeSuccessful();
        RecommendationFake::fakeSuccessful();
        QualificationFake::fakeCopywriter();
        WriteFollowUpEmailAgent::fake([
            [
                'channel' => 'email',
                'subject' => '🔁 A new way to turn local quotes into booked work',
                'body' => "Hi Sarah,\n\nHere is a new insight on the same problem.\n\nRoger Pereira\n[Front Porch Creative](https://frontporchcreative.io)",
            ],
        ]);
        ProspectingDiscoveryAgent::fake([
            [
                'leads' => [],
                'skipped' => [],
            ],
        ]);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
