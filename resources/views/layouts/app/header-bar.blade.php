<header
    class="flex h-16 shrink-0 items-center gap-4 border-b border-border-subtle bg-app-sidebar px-6"
    data-test="app-header"
>
    <flux:heading size="lg" class="!mb-0 font-bold text-text-primary">
        {{ $title ?? config('app.name') }}
    </flux:heading>

    <flux:spacer />

    <div class="hidden max-w-sm flex-1 md:block">
        <flux:input
            type="search"
            placeholder="{{ __('Search CRM…') }}"
            class="w-full"
            disabled
            data-test="header-search"
        />
    </div>

    <div class="hidden lg:block">
        <x-desktop-user-menu :name="auth()->user()->name" />
    </div>
</header>
