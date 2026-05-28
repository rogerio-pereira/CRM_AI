<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-app antialiased">
        <flux:sidebar
            sticky
            collapsible
            class="border-e border-border-subtle bg-app-sidebar !w-60 data-flux-sidebar-collapsed-desktop:!w-[4.5rem]"
        >
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <div class="px-3 py-2 in-data-flux-sidebar-collapsed-desktop:hidden">
                    <div class="text-sm font-medium leading-none text-text-muted">{{ __('CRM') }}</div>
                </div>

                <flux:sidebar.item
                    icon="home"
                    :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')"
                    wire:navigate
                    data-test="nav-dashboard"
                >
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
                <flux:sidebar.item
                    icon="users"
                    :href="route('leads.index')"
                    :current="request()->routeIs('leads.*')"
                    wire:navigate
                    data-test="nav-leads"
                >
                    {{ __('Leads / Clients') }}
                </flux:sidebar.item>
                <flux:sidebar.item
                    icon="view-columns"
                    :href="route('opportunities.index')"
                    :current="request()->routeIs('opportunities.*')"
                    wire:navigate
                    data-test="nav-opportunities"
                >
                    {{ __('Opportunities') }}
                </flux:sidebar.item>
                <flux:sidebar.item
                    icon="calendar-days"
                    :href="route('follow-ups.index')"
                    :current="request()->routeIs('follow-ups.*')"
                    wire:navigate
                    data-test="nav-follow-ups"
                >
                    {{ __('Follow-ups') }}
                </flux:sidebar.item>
                <flux:sidebar.item
                    icon="clipboard-document-list"
                    :href="route('tasks.index')"
                    :current="request()->routeIs('tasks.*')"
                    wire:navigate
                    data-test="nav-tasks"
                >
                    {{ __('Tasks') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu :name="auth()->user()->name" />
        </flux:sidebar>

        <flux:header class="block! border-b border-border-subtle bg-app-sidebar" data-test="app-header">
            <flux:navbar class="w-full lg:hidden">
                <flux:heading size="lg" class="!mb-0 truncate font-bold text-text-primary">
                    {{ $title ?? config('app.name') }}
                </flux:heading>

                <flux:spacer />

                <flux:sidebar.toggle
                    class="lg:hidden"
                    icon="bars-2"
                    inset="right"
                    data-test="mobile-menu-toggle"
                />
            </flux:navbar>

            <div class="hidden h-16 w-full items-center gap-4 px-6 lg:flex">
                @include('layouts.app.header-bar', ['title' => $title ?? null])
            </div>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
