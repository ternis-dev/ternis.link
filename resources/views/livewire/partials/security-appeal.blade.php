@if ($blockedLinkId)
    <div class="mt-4 rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-neutral-900 dark:border-red-800 dark:bg-neutral-900 dark:text-neutral-100">
        @if ($appealSubmitted)
            <p role="status">{{ $appealLocale === 'de' ? 'Dein Einspruch wurde eingereicht und wird von einem Menschen überprüft.' : 'Your appeal has been submitted for human review.' }}</p>
        @else
            <form wire:submit="submitSecurityAppeal" class="space-y-3">
                <p class="font-semibold">{{ $appealLocale === 'de' ? 'Ein Fehler? Überprüfung anfragen' : 'Think this is a mistake? Appeal this decision' }}</p>
                <label class="block">
                    {{ $appealLocale === 'de' ? 'Deine E-Mail-Adresse' : 'Your email address' }}
                    <input type="email" wire:model="appealEmail" required maxlength="255" autocomplete="email" class="mt-1 block w-full rounded border border-neutral-300 bg-white p-2 text-neutral-900">
                </label>
                @error('appealEmail') <p role="alert" class="text-red-600">{{ $message }}</p> @enderror
                <label class="block">
                    {{ $appealLocale === 'de' ? 'Warum sollte die URL freigegeben werden?' : 'Why should this URL be unblocked?' }}
                    <textarea wire:model="appealReason" required minlength="10" maxlength="2000" rows="3" class="mt-1 block w-full rounded border border-neutral-300 bg-white p-2 text-neutral-900"></textarea>
                </label>
                @error('appealReason') <p role="alert" class="text-red-600">{{ $message }}</p> @enderror
                <button type="submit" wire:loading.attr="disabled" wire:target="submitSecurityAppeal" class="rounded bg-neutral-900 px-4 py-2 font-semibold text-white disabled:opacity-50">
                    {{ $appealLocale === 'de' ? 'Einspruch einreichen' : 'Submit appeal' }}
                </button>
            </form>
        @endif
    </div>
@endif
