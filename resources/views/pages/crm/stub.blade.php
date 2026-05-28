<x-layouts::app :title="$title">
    <div class="mx-auto max-w-3xl space-y-4" data-test="crm-stub-page">
        <flux:heading size="xl" class="font-bold text-text-primary">{{ $heading }}</flux:heading>
        <flux:text class="font-light text-text-secondary">{{ $message }}</flux:text>
    </div>
</x-layouts::app>
