@props([
    'priority',
])

<span
    {{ $attributes->class([
        'inline-flex rounded-full border px-2 py-0.5 text-xs font-medium',
        $priority->badgeClasses(),
    ]) }}
    data-priority="{{ $priority->value }}"
>
    {{ $priority->label() }}
</span>
