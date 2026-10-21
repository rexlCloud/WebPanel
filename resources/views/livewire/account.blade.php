<div>
    <x-page-header title="Account" :subtitle="'Logged in as '.$cloudUser?->username"/>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card p-6">
            <h2 class="text-base font-semibold text-white">Change password</h2>
            <p class="mt-1 text-sm text-slate-400">Other devices are logged out after the change.</p>
            <form wire:submit="changePassword" class="mt-5 space-y-4">
                <div>
                    <label for="current-password" class="label">Current password</label>
                    <input id="current-password" wire:model="currentPassword" type="password" class="input" autocomplete="current-password">
                    @error('currentPassword') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="new-password" class="label">New password</label>
                    <input id="new-password" wire:model="newPassword" type="password" class="input" autocomplete="new-password">
                    @error('newPassword') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="new-password-confirmation" class="label">Repeat new password</label>
                    <input id="new-password-confirmation" wire:model="newPassword_confirmation" type="password" class="input" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Change password</button>
            </form>
        </section>

        <section class="card p-6">
            <h2 class="text-base font-semibold text-white">Your permissions</h2>
            <ul class="mt-4 flex flex-wrap gap-2">
                @forelse ($cloudUser?->permissions ?? [] as $permission)
                    <li class="rounded-md bg-slate-800 px-2 py-1 font-mono text-xs text-slate-300">{{ $permission }}</li>
                @empty
                    <li class="text-sm text-slate-500">None</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
