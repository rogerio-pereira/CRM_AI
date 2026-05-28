<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-app antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <a
                    href="{{ route('home') }}"
                    class="flex flex-col items-center gap-3 font-medium text-text-primary"
                    wire:navigate
                >
                    <span class="flex size-10 items-center justify-center rounded-md bg-primary">
                        <x-app-logo-icon class="size-6 fill-current text-white" />
                    </span>
                    <flux:heading size="lg" class="!mb-0">{{ config('app.name') }}</flux:heading>
                </a>

                <div class="rounded-lg border border-border-subtle bg-app-surface shadow-sm">
                    <div class="px-8 py-8 md:px-10">{{ $slot }}</div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
