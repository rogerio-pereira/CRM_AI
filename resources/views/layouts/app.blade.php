<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="flex-1 bg-app p-6">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
