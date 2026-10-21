<div wire:poll.5s>
    <x-page-header title="Servers" :subtitle="$total.' '.Str::plural('server', $total).' running'"/>

    <x-load-error :message="$loadError"/>

    <div class="mb-4 flex flex-wrap gap-3">
        <div class="relative min-w-56 flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-500"/>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search servers" class="input pl-9" aria-label="Search servers">
        </div>
        <select wire:model.live="group" class="input w-auto" aria-label="Filter by group">
            <option value="">All groups</option>
            @foreach ($groups as $groupName)
                <option value="{{ $groupName }}">{{ $groupName }}</option>
            @endforeach
        </select>
        <select wire:model.live="state" class="input w-auto" aria-label="Filter by state">
            <option value="">All states</option>
            <option value="online">Online</option>
            <option value="started">Starting</option>
            <option value="waiting">Waiting</option>
            <option value="stopping">Stopping</option>
            <option value="starting">Queued</option>
        </select>
    </div>

    @if ($servers->isEmpty())
        <x-empty icon="server" :title="$total === 0 ? 'No servers are running' : 'No servers match the filters'"/>
    @else
        <div class="card overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800">
                <thead>
                <tr>
                    <th class="table-head">Name</th>
                    <th class="table-head">State</th>
                    <th class="table-head">Group</th>
                    <th class="table-head">Players</th>
                    <th class="table-head">Wrapper</th>
                    <th class="table-head">Port</th>
                    <th class="table-head"><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                @foreach ($servers as $server)
                    <tr wire:key="server-{{ $server['server_name'] }}" class="hover:bg-slate-800/40">
                        <td class="table-cell">
                            <a href="{{ route('servers.show', $server['server_name']) }}" wire:navigate class="font-medium text-white hover:text-cyan-300">
                                {{ $server['server_name'] }}
                            </a>
                            @if ($server['server_state'])
                                <span class="ml-2 text-xs text-slate-500">{{ $server['server_state'] }}</span>
                            @endif
                        </td>
                        <td class="table-cell"><x-state-badge :state="$server['online_state']"/></td>
                        <td class="table-cell text-slate-300">
                            @if ($cloudUser?->can('SEE_ALL_GROUPS'))
                                <a href="{{ route('groups.show', $server['server_group']) }}" wire:navigate class="hover:text-white">{{ $server['server_group'] }}</a>
                            @else
                                {{ $server['server_group'] }}
                            @endif
                        </td>
                        <td class="table-cell tabular-nums text-slate-300">{{ $server['online_players'] }} / {{ $server['max_players'] }}</td>
                        <td class="table-cell text-slate-400">{{ $server['wrapper'] ?? '–' }}</td>
                        <td class="table-cell tabular-nums text-slate-400">{{ $server['server_port'] }}</td>
                        <td class="table-cell text-right">
                            <div class="flex justify-end gap-1">
                                @if ($cloudUser?->can('STOP_SERVER', 'START_SERVER'))
                                    <button type="button" wire:click="restart(@js($server['server_name']))" class="btn btn-ghost btn-sm" title="Restart">
                                        <x-icon name="restart" class="size-4"/><span class="sr-only">Restart</span>
                                    </button>
                                @endif
                                @if ($cloudUser?->can('STOP_SERVER'))
                                    <button type="button" wire:click="stop(@js($server['server_name']))" class="btn btn-ghost btn-sm" title="Stop">
                                        <x-icon name="stop" class="size-4"/><span class="sr-only">Stop</span>
                                    </button>
                                    <button type="button" wire:click="kill(@js($server['server_name']))"
                                            wire:confirm="Kill {{ $server['server_name'] }}? The process is terminated immediately and unsaved data is lost."
                                            class="btn btn-ghost btn-sm text-red-400 hover:text-red-300" title="Kill">
                                        <x-icon name="bolt" class="size-4"/><span class="sr-only">Kill</span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
