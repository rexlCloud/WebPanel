<div>
    <x-page-header title="Panel users" subtitle="Accounts that can log in to this panel">
        @if ($cloudUser?->can('CREATE_USER'))
            <x-slot:actions>
                <button type="button" wire:click="openCreate" class="btn btn-primary"><x-icon name="plus" class="size-4"/> New user</button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-load-error :message="$loadError"/>

    <div class="card divide-y divide-slate-800">
        @foreach ($users as $user)
            <div class="flex flex-wrap items-center justify-between gap-4 px-5 py-4" wire:key="user-{{ $user['username'] }}">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-700 text-xs font-semibold uppercase text-white">
                        {{ mb_substr($user['username'], 0, 2) }}
                    </span>
                    <div class="min-w-0">
                        <div class="font-medium text-white">
                            {{ $user['username'] }}
                            @if ($user['username'] === $cloudUser?->username)
                                <span class="ml-1 text-xs font-normal text-slate-500">(you)</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500">
                            {{ count($user['permissions']) === count($permissions) ? 'All permissions' : count($user['permissions']).' of '.count($permissions).' permissions' }}
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    @if ($cloudUser?->can('CHANGE_USER_PERMISSIONS'))
                        <button type="button" wire:click="edit(@js($user['username']), @js($user['permissions']))" class="btn btn-secondary btn-sm">
                            <x-icon name="key" class="size-4"/> Permissions
                        </button>
                    @endif
                    @if ($cloudUser?->can('DELETE_USER') && $user['username'] !== $cloudUser?->username)
                        <button type="button" wire:click="delete(@js($user['username']))"
                                wire:confirm="Delete the panel user {{ $user['username'] }}?"
                                class="btn btn-danger btn-sm"><x-icon name="trash" class="size-4"/> Delete</button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @php
        $describe = fn ($permission) => ucfirst(strtolower(str_replace('_', ' ', $permission)));
    @endphp

    @if ($creating)
        <x-modal title="New panel user" close="$set('creating', false)">
            <form wire:submit="create" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="new-username" class="label">Username</label>
                        <input id="new-username" wire:model="username" type="text" class="input" autocomplete="off" autofocus>
                        @error('username') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="new-password" class="label">Password</label>
                        <input id="new-password" wire:model="password" type="password" class="input" autocomplete="new-password">
                        @error('password') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                @if ($cloudUser?->can('CHANGE_USER_PERMISSIONS'))
                    <fieldset>
                        <legend class="label">Permissions</legend>
                        <div class="grid gap-1.5 sm:grid-cols-2">
                            @foreach ($permissions as $permission)
                                <label class="flex items-center gap-2 text-sm text-slate-300" @if (! $cloudUser->can($permission)) title="You do not have this permission yourself" @endif>
                                    <input type="checkbox" wire:model="newPermissions" value="{{ $permission }}" @disabled(! $cloudUser->can($permission))
                                           class="rounded border-slate-600 bg-slate-950 text-cyan-500 disabled:opacity-40">
                                    {{ $describe($permission) }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('creating', false)" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create user</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($editing !== null)
        <x-modal :title="'Permissions of '.$editing" close="$set('editing', null)">
            <form wire:submit="savePermissions" class="space-y-4">
                <p class="text-sm text-slate-400">You can only grant or revoke permissions you have yourself.</p>
                <div class="grid gap-1.5 sm:grid-cols-2">
                    @foreach ($permissions as $permission)
                        <label class="flex items-center gap-2 text-sm text-slate-300">
                            <input type="checkbox" wire:model="editPermissions" value="{{ $permission }}" @disabled(! $cloudUser?->can($permission))
                                   class="rounded border-slate-600 bg-slate-950 text-cyan-500 disabled:opacity-40">
                            {{ $describe($permission) }}
                        </label>
                    @endforeach
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('editing', null)" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save permissions</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
