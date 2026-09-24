<x-layouts.app title="Login — ternis.link">
    <div class="mx-auto flex max-w-xl flex-col items-center py-16 text-center">
        <h1 class="font-display text-4xl font-bold tracking-tight">Sign in to ternis.link</h1>
        <p class="mt-3 max-w-md text-neutral-500 dark:text-neutral-400">Authenticate with your Ternis account to manage links, create custom short links, and view analytics.</p>

        @if (session('error'))
            <x-ui.alert tone="error" class="mt-6 w-full text-left">{{ session('error') }}</x-ui.alert>
        @endif

        <x-ui.button href="{{ url('/auth/redirect') }}" variant="primary" size="lg" class="mt-8">
            Login with Ternis Auth SSO
        </x-ui.button>

        @if (app()->environment('local', 'testing'))
            <x-ui.card class="mt-10 w-full border-dashed text-left">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-widest text-neutral-500 uppercase">Local dev demo login</span>
                    <span class="text-xs text-neutral-500">temporary</span>
                </div>
                <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">
                    Bypass live SSO locally to test role-based dashboards and capabilities:
                </p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button href="{{ url('/auth/demo?role=admin') }}" size="sm">Admin</x-ui.button>
                    <x-ui.button href="{{ url('/auth/demo?role=family') }}" size="sm">Family</x-ui.button>
                    <x-ui.button href="{{ url('/auth/demo?role=partner') }}" size="sm">Partner</x-ui.button>
                    <x-ui.button href="{{ url('/auth/demo?role=user') }}" size="sm">Standard User</x-ui.button>
                </div>
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
