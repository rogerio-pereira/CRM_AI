@props([
    'label',
    'value',
    'icon',
    'test',
    'accentBorder' => 'border-l-accent',
    'iconBg' => 'bg-gradient-to-br from-accent/25 to-accent/5 text-accent',
    'glowClass' => 'bg-accent/25',
    'ringClass' => 'ring-accent/20',
])

<div
    {{ $attributes->class([
        'group relative min-h-[7.5rem] overflow-hidden rounded-xl border border-border-default/80 bg-gradient-to-br from-surface via-app-elevated/30 to-app/50 p-4 shadow-sm ring-1 ring-inset ring-white/[0.04] transition duration-200 hover:-translate-y-0.5 hover:border-border-strong hover:shadow-lg hover:shadow-black/20 lg:col-span-1',
        'border-l-[3px]' => true,
        $accentBorder,
    ]) }}
    data-test="{{ $test }}"
>
    <div @class(['pointer-events-none absolute -end-10 -top-10 size-28 rounded-full opacity-70 blur-3xl transition group-hover:opacity-90', $glowClass])></div>
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-transparent via-transparent to-white/[0.03]"></div>

    <div class="relative flex h-full items-center justify-between gap-3">
        <div class="min-w-0 flex-1">
            <flux:text class="text-[10px] font-semibold uppercase leading-tight tracking-widest text-text-muted">
                {{ $label }}
            </flux:text>
            <p class="mt-2 text-4xl font-bold tabular-nums leading-none tracking-tight text-text-primary">
                {{ $value }}
            </p>
        </div>

        <div
            @class([
                'flex size-14 shrink-0 items-center justify-center rounded-xl shadow-md ring-1 ring-inset',
                $iconBg,
                $ringClass,
            ])
            aria-hidden="true"
        >
            @switch ($icon)
                @case('user-plus')
                    <flux:icon.user-plus class="size-7" />
                    @break
                @case('briefcase')
                    <flux:icon.briefcase class="size-7" />
                    @break
                @default
                    <flux:icon.chart-bar class="size-7" />
            @endswitch
        </div>
    </div>
</div>
