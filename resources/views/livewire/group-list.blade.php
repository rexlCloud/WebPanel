<div @unless ($creating) wire:poll.10s @endunless>
    <x-page-header title="Groups" subtitle="Server groups and how many of their servers are running">
        @if ($cloudUser?->can('MANAGE_GROUPS'))
            <x-slot:actions>
                <button type="button" wire:click="openCreate" class="btn btn-primary"><x-icon name="plus" class="size-4"/> New group</button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-load-error :message="$loadError"/>

    @if ($groups->isEmpty())
        <x-empty icon="groups" title="No groups yet" text="Create a group to start servers from it."/>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($groups as $group)
                <div class="card flex flex-col p-5" wire:key="group-{{ $group['group_name'] }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('groups.show', $group['group_name']) }}" wire:navigate class="block truncate text-lg font-semibold text-white hover:text-cyan-300">
                                {{ $group['group_name'] }}
                            </a>
                            <div class="mt-1 flex flex-wrap gap-1.5 text-xs">
                                <span class="rounded bg-slate-800 px-1.5 py-0.5 text-slate-300">{{ $group['group_type'] }}</span>
                                @if ($group['group_static'])
                                    <span class="rounded bg-violet-500/15 px-1.5 py-0.5 text-violet-300">Static · {{ $group['group_wrapper'] }}</span>
                                @endif
                                @if ($group['group_fallback'])
                                    <span class="rounded bg-cyan-500/15 px-1.5 py-0.5 text-cyan-300">Fallback</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-semibold tabular-nums text-white">{{ count($group['servers']) }}</div>
                            <div class="text-xs text-slate-500">{{ Str::plural('server', count($group['servers'])) }}</div>
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-3 gap-2 text-xs">
                        <div><dt class="text-slate-500">Players</dt><dd class="tabular-nums text-slate-200">{{ $group['online_players'] }}</dd></div>
                        <div><dt class="text-slate-500">Min. servers</dt><dd class="tabular-nums text-slate-200">{{ $group['group_min_servers'] }}</dd></div>
                        <div><dt class="text-slate-500">Memory</dt><dd class="tabular-nums text-slate-200">{{ $group['group_memory'] }} MB</dd></div>
                    </dl>
                    @if ($cloudUser?->can('START_SERVER') && ! ($group['group_static'] && count($group['servers']) > 0))
                        <div class="mt-4 border-t border-slate-800 pt-4">
                            <button type="button" wire:click="startServers(@js($group['group_name']), 1)" class="btn btn-secondary btn-sm">
                                <x-icon name="play" class="size-4"/> Start server
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($creating)
        <x-modal title="New group" close="$set('creating', false)">
            <form wire:submit="create" class="space-y-4">
                <div>
                    <label for="group-name" class="label">Name</label>
                    <input id="group-name" wire:model="name" type="text" class="input" placeholder="Lobby" autofocus>
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="group-type" class="label">Type</label>
                        <select id="group-type" wire:model.live="type" class="input">
                            <option value="SERVER">Server</option>
                            <option value="PROXY">Proxy</option>
                        </select>
                    </div>
                    <div>
                        <label for="group-memory" class="label">Memory per server (MB)</label>
                        <input id="group-memory" wire:model="memory" type="number" min="256" step="256" class="input">
                        @error('memory') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label for="group-jar" class="label">Server software</label>
                    <select id="group-jar" wire:model.live="serverJar" class="input">
                        <option value="">Choose a version</option>
                        @foreach ($jarsForType as $jar)
                            @if ($jar['custom'] ?? false)
                                <option value="{{ $jar['id'] }}">Custom jar (your own server.jar, e.g. Minestom)</option>
                            @else
                                <option value="{{ $jar['id'] }}">{{ $jar['name'] }} {{ $jar['minecraft_version'] ?? $jar['version'] }}{{ ($jar['built'] ?? false) ? ' (built on first use)' : '' }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('serverJar') <p class="field-error">{{ $message }}</p> @enderror
                    @if ($selectedJarIsCustom)
                        <p class="mt-1 text-xs text-cyan-300">
                            Nothing is downloaded. Put your server.jar into the group's template
                            (templates/&lt;group&gt;/default/ on the master, or on the wrapper for static groups); servers
                            wait until it is there. The cloud adds no plugin and passes no start arguments: the server
                            reads its port and forwarding from cloud.json, e.g. with the MinestomBridge.
                        </p>
                    @endif
                    @if ($selectedJarIsBungeeCord)
                        <p class="mt-1 text-xs text-amber-300">
                            BungeeCord only supports legacy forwarding: set forwardingMode in the master's master.json to
                            LEGACY, otherwise players cannot join the servers through it.
                        </p>
                    @endif
                    @if ($selectedJarIsBuilt)
                        <p class="mt-1 text-xs text-amber-300">
                            Spigot cannot be downloaded, so the cloud builds it with SpigotMC's BuildTools. The first
                            group of a version takes a few minutes and needs git on the master (or, for static groups,
                            on the wrapper). Spigot has no Velocity modern forwarding: set forwardingMode to LEGACY.
                        </p>
                    @endif
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" wire:model.live="static" class="rounded border-slate-600 bg-slate-950 text-cyan-500">
                    Static: one server that keeps its files, on a fixed wrapper
                </label>
                @if ($static)
                    <div>
                        <label for="group-wrapper" class="label">Wrapper</label>
                        <select id="group-wrapper" wire:model="wrapper" class="input">
                            <option value="">Choose a connected wrapper</option>
                            @foreach ($wrapperNames as $wrapperName)
                                <option value="{{ $wrapperName }}">{{ $wrapperName }}</option>
                            @endforeach
                        </select>
                        @error('wrapper') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div>
                        <label for="group-min" class="label">Servers to keep online</label>
                        <input id="group-min" wire:model="minServers" type="number" min="0" class="input">
                        @error('minServers') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endif
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" wire:model="fallback" class="rounded border-slate-600 bg-slate-950 text-cyan-500">
                    Fallback: players join these servers when they connect
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('creating', false)" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Create group</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
