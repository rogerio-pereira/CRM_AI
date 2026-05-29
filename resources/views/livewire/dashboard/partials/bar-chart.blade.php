@props([
    'series' => [],
    'testPrefix' => 'chart',
    'valueFormat' => 'integer',
])

@php
    use Illuminate\Support\Carbon;

    $values = collect($series)->pluck('value');
    $max = $values->max() ?? 0;
    $hasData = $values->sum() > 0;

    if ($max <= 0) {
        $max = 1;
    }
@endphp

<div class="space-y-2 overflow-visible" data-test="dashboard-chart-{{ $testPrefix }}-wrapper">
    <div
        class="flex h-24 items-end gap-0.5 overflow-visible pt-8"
        role="img"
        aria-label="{{ __('Chart for the last 30 days') }}"
        data-test="dashboard-chart-{{ $testPrefix }}"
    >
        @foreach ($series as $point)
            @php
                $barHeightPercent = 0;
                $numericValue = (float) $point['value'];

                if ($numericValue > 0) {
                    $barHeightPercent = max(8, $numericValue / (float) $max * 100);
                }

                if ($valueFormat === 'decimal') {
                    $displayValue = number_format($numericValue, 2);
                } else {
                    $displayValue = (string) (int) $numericValue;
                }

                $dateLabel = Carbon::parse($point['date'])->format('M j');
            @endphp
            <div
                class="group/bar relative flex h-full min-w-0 flex-1 cursor-default flex-col justify-end"
                data-test="dashboard-chart-{{ $testPrefix }}-point-{{ $point['date'] }}"
            >
                <div
                    class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-md border border-border-default bg-surface px-2 py-1 text-center text-xs shadow-lg opacity-0 transition-opacity group-hover/bar:opacity-100"
                    data-test="dashboard-chart-{{ $testPrefix }}-tooltip-{{ $point['date'] }}"
                    role="tooltip"
                >
                    <span class="block text-[10px] font-medium text-text-muted">{{ $dateLabel }}</span>
                    <span class="block font-semibold tabular-nums text-text-primary">{{ $displayValue }}</span>
                </div>

                <div
                    class="w-full rounded-t bg-accent/70 transition-[height] duration-150 group-hover/bar:bg-accent"
                    style="height: {{ $barHeightPercent }}%"
                    data-test="dashboard-chart-{{ $testPrefix }}-bar-{{ $point['date'] }}"
                ></div>
            </div>
        @endforeach
    </div>

    @if (! $hasData)
        <flux:text class="text-center text-xs text-text-muted" data-test="dashboard-chart-{{ $testPrefix }}-empty">
            {{ __('No data in the last 30 days.') }}
        </flux:text>
    @endif
</div>
