@props([
    'series' => [],
    'testPrefix' => 'chart',
])

@php
    $values = collect($series)->pluck('value');
    $max = $values->max() ?? 0;

    if ($max <= 0) {
        $max = 1;
    }
@endphp

<div
    class="flex h-28 items-end gap-0.5 opacity-70"
    role="img"
    aria-hidden="true"
    data-test="dashboard-chart-{{ $testPrefix }}"
>
    @foreach ($series as $point)
        @php
            $heightPercent = max(4, (float) $point['value'] / (float) $max * 100);
        @endphp
        <div
            class="flex min-w-0 flex-1 flex-col justify-end"
            title="{{ $point['date'] }}: {{ $point['value'] }}"
        >
            <div
                class="w-full rounded-t bg-accent/50"
                style="height: {{ $heightPercent }}%"
                data-test="dashboard-chart-{{ $testPrefix }}-bar-{{ $point['date'] }}"
            ></div>
        </div>
    @endforeach
</div>
