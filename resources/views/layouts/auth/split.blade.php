<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-app antialiased">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="relative hidden h-full flex-col border-e border-border-subtle bg-app-sidebar p-10 text-text-primary lg:flex">
                <a href="{{ route('home') }}" class="relative z-20 flex items-center gap-3 text-lg font-bold" wire:navigate>
                    <span class="flex size-10 items-center justify-center rounded-md bg-primary">
                        <x-app-logo-icon class="size-6 fill-current text-white" />
                    </span>
                    {{ config('app.name') }}
                </a>

                @php
                    [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
                @endphp

                <div class="relative z-20 mt-auto">
                    <blockquote class="space-y-2">
                        <flux:heading size="lg" class="text-text-secondary">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
                        <footer><flux:heading class="text-text-muted">{{ trim($author) }}</flux:heading></footer>
                    </blockquote>
                </div>
            </div>
            <div class="w-full bg-app lg:p-8">
                <div class="mx-auto flex w-full max-w-sm flex-col justify-center space-y-6">
                    <a
                        href="{{ route('home') }}"
                        class="z-20 flex flex-col items-center gap-3 font-medium text-text-primary lg:hidden"
                        wire:navigate
                    >
                        <span class="flex size-10 items-center justify-center rounded-md bg-primary">
                            <x-app-logo-icon class="size-6 fill-current text-white" />
                        </span>
                        <flux:heading size="lg" class="!mb-0">{{ config('app.name') }}</flux:heading>
                    </a>

                    <div class="rounded-lg border border-border-subtle bg-app-surface p-6 md:p-8">
                        {{ $slot }}
                    </div>
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
