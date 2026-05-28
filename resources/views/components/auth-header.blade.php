@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-1 text-center">
    <flux:heading size="xl" class="text-text-primary">{{ $title }}</flux:heading>
    <flux:subheading class="text-text-secondary">{{ $description }}</flux:subheading>
</div>
