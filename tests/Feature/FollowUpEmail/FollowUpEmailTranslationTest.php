<?php

namespace Tests\Feature\FollowUpEmail;

use App\Enums\PipelineStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpEmailTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_follow_up_and_no_response_labels_exist_for_each_locale(): void
    {
        $locales = [
            config('app.locale'),
            config('app.fallback_locale'),
        ];
        $uniqueLocales = array_unique($locales);

        foreach ($uniqueLocales as $locale) {
            $this->app->setLocale($locale);

            $sendLabel = __('Send follow-up');
            $queuedLabel = __('Follow-up email queued.');
            $stageLabel = PipelineStage::NoResponse->label();

            $this->assertNotSame('', $sendLabel);
            $this->assertNotSame('', $queuedLabel);
            $this->assertNotSame('', $stageLabel);
            $this->assertSame('Send follow-up', $sendLabel);
            $this->assertSame('No Response', $stageLabel);
        }
    }
}
