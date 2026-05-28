<x-layouts::app :title="__('Dashboard')">
    <div class="grid auto-rows-min gap-6 md:grid-cols-3" data-test="dashboard-page">
        @foreach (range(1, 3) as $card)
            <div class="relative aspect-video overflow-hidden rounded-lg border border-border-subtle bg-app-surface">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-text-muted/30" />
            </div>
        @endforeach
        <div class="relative col-span-full min-h-64 overflow-hidden rounded-lg border border-border-subtle bg-app-surface md:col-span-3">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-text-muted/30" />
        </div>
    </div>
</x-layouts::app>
