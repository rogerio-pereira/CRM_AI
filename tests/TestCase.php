<?php

namespace Tests;

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
