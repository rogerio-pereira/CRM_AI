@props([
    'label',
    'classes',
    'status',
])

<span
    {{ $attributes->merge([
        'class' => 'inline-flex rounded-full border px-2 py-0.5 text-xs font-medium '.$classes,
    ]) }}
    data-status="{{ $status }}"
>
    {{ $label }}
</span>
