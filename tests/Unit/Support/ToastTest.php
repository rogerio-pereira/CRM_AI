<?php

namespace Tests\Unit\Support;

use App\Support\Toast;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class ToastTest extends TestCase
{
    public function test_show_dispatches_with_default_duration(): void
    {
        $component = Livewire::test(ToastProbe::class);

        $component->call('showDefault');

        $component->assertDispatched('toast-show', function (string $eventName, array $params): bool {
            $eventMatches = $eventName === 'toast-show';
            $durationMatches = $params['duration'] === 5000;
            $textMatches = $params['slots']['text'] === 'Saved.';
            $variantMatches = $params['dataset']['variant'] === 'success';

            return $eventMatches
                && $durationMatches
                && $textMatches
                && $variantMatches;
        });
    }

    public function test_show_dispatches_with_custom_duration(): void
    {
        $component = Livewire::test(ToastProbe::class);

        $component->call('showCustomDuration');

        $component->assertDispatched('toast-show', function (string $eventName, array $params): bool {
            $eventMatches = $eventName === 'toast-show';
            $durationMatches = $params['duration'] === 10000;
            $textMatches = $params['slots']['text'] === 'Failed.';
            $variantMatches = $params['dataset']['variant'] === 'warning';

            return $eventMatches
                && $durationMatches
                && $textMatches
                && $variantMatches;
        });
    }
}

class ToastProbe extends Component
{
    public function showDefault(): void
    {
        Toast::show(
            variant: 'success',
            text: 'Saved.',
        );
    }

    public function showCustomDuration(): void
    {
        Toast::show(
            variant: 'warning',
            text: 'Failed.',
            duration: 10000,
        );
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
