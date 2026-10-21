<div>
    <x-page-header :title="$name" :back="route('groups')"
                   :subtitle="$group ? $group['group_type'].($group['group_static'] ? ' · static on '.$group['group_wrapper'] : '').' · '.$group['online_players'].' players' : null">
        @if ($group && $cloudUser?->can('MANAGE_GROUPS'))
            <x-slot:actions>
                @unless ($group['group_static'])
                    <button type="button" wire:click="syncTemplate" class="btn btn-secondary"><x-icon name="sync" class="size-4"/> Sync template</button>
                @endunless
                <button type="button" wire:click="delete"
                        wire:confirm="Delete the group {{ $name }}? All of its servers are stopped."
                        class="btn btn-danger"><x-icon name="trash" class="size-4"/> Delete</button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-load-error :message="$loadError"/>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2" wire:poll.5s>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-sm font-medium text-slate-300">Servers ({{ $servers->count() }})</h2>
                @if ($group && $cloudUser?->can('START_SERVER') && ! ($group['group_static'] && $servers->isNotEmpty()))
                    <form wire:submit="startServers" class="flex items-center gap-2">
                        <label for="start-amount" class="sr-only">Number of servers</label>
                        <input id="start-amount" wire:model="startAmount" type="number" min="1" max="50" class="input w-20 py-1.5">
                        <button type="submit" class="btn btn-secondary btn-sm"><x-icon name="play" class="size-4"/> Start</button>
                    </form>
                @endif
            </div>
            @error('startAmount') <p class="field-error mb-2">{{ $message }}</p> @enderror

            @if ($servers->isEmpty())
                <x-empty icon="server" title="No servers running" text="Start one, or raise the minimum number of servers."/>
            @else
                <div class="card divide-y divide-slate-800">
                    @foreach ($servers as $server)
                        <div class="flex items-center justify-between gap-3 px-4 py-3" wire:key="gs-{{ $server['server_name'] }}">
                            <div class="flex min-w-0 items-center gap-3">
                                <a href="{{ route('servers.show', $server['server_name']) }}" wire:navigate class="truncate font-medium text-white hover:text-cyan-300">{{ $server['server_name'] }}</a>
                                <x-state-badge :state="$server['online_state']"/>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-slate-400">
                                <span class="tabular-nums">{{ $server['online_players'] }} / {{ $server['max_players'] }}</span>
                                <span class="hidden sm:inline">{{ $server['wrapper'] ?? '–' }}</span>
                                @if ($cloudUser?->can('STOP_SERVER'))
                                    <button type="button" wire:click="stopServer(@js($server['server_name']))" class="btn btn-ghost btn-sm" title="Stop">
                                        <x-icon name="stop" class="size-4"/><span class="sr-only">Stop {{ $server['server_name'] }}</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section>
            <h2 class="mb-3 text-sm font-medium text-slate-300">Settings</h2>
            <form wire:submit="save" class="card space-y-4 p-5">
                @php($readonly = ! $cloudUser?->can('MANAGE_GROUPS'))
                <div>
                    <label for="memory" class="label">Memory per server (MB)</label>
                    <input id="memory" wire:model="memory" type="number" min="101" class="input" @disabled($readonly)>
                    @error('memory') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="max-players" class="label">Max. players per server</label>
                    <input id="max-players" wire:model="maxPlayers" type="number" min="0" class="input" @disabled($readonly)>
                    @error('maxPlayers') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                @unless ($group['group_static'] ?? false)
                    <div>
                        <label for="min-servers" class="label">Servers to keep online</label>
                        <input id="min-servers" wire:model="minServers" type="number" min="0" class="input" @disabled($readonly)>
                        @error('minServers') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endunless
                <div>
                    <div class="flex items-center justify-between">
                        <label for="java-runtime" class="label">Java runtime</label>
                        @unless ($readonly)
                            <button type="button" wire:click="refreshJavaRuntimes" class="text-xs text-cyan-400 hover:text-cyan-300">Refresh</button>
                        @endunless
                    </div>
                    <select id="java-runtime" wire:model.live="javaCommand" class="input" @disabled($readonly)>
                        <option value="java">Default: java on the wrapper's PATH</option>
                        @foreach ($javaRuntimes as $runtime)
                            <option value="{{ $runtime['path'] }}" wire:key="java-{{ $runtime['path'] }}">{{ $runtime['name'] }} · {{ implode(', ', $runtime['wrappers']) }} · {{ $runtime['path'] }}</option>
                        @endforeach
                        @unless ($knownRuntime)
                            <option value="{{ $javaCommand }}">{{ $javaCommand }} (no connected wrapper reports it)</option>
                        @endunless
                    </select>
                    @error('javaCommand') <p class="field-error">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-slate-500">
                        Each wrapper reports the Java installations it finds. Servers of this group only start on
                        wrappers that have the selected one.
                    </p>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" wire:model="fallback" class="rounded border-slate-600 bg-slate-950 text-cyan-500" @disabled($readonly)>
                    Fallback group
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" wire:model="removeOldLogs" class="rounded border-slate-600 bg-slate-950 text-cyan-500" @disabled($readonly)>
                    Delete old logs when a server starts
                </label>
                @unless ($readonly)
                    <button type="submit" class="btn btn-primary w-full justify-center" wire:loading.attr="disabled">Save settings</button>
                @endunless
            </form>
        </section>
    </div>
</div>
