@props([
    'action',
    'label',
    'test',
])

<flux:tooltip :content="$label" position="left">
    <flux:button
        size="sm"
        variant="ghost"
        icon="check"
        wire:click="{{ $action }}"
        aria-label="{{ $label }}"
        class="border-0! bg-status-success/20! text-status-success! shadow-none hover:bg-status-success/30! hover:text-status-success!"
        data-test="{{ $test }}"
    ></flux:button>
</flux:tooltip>
