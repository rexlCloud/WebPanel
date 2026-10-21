<div @if ($selectedUuid === null) wire:poll.10s @endif>
    <x-page-header title="Players" :subtitle="$total.' '.Str::plural('player', $total).' online'"/>

    <x-load-error :message="$loadError"/>

    <div class="relative mb-4 max-w-md">
        <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 size-4 text-slate-500"/>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search by name, UUID or server" class="input pl-9" aria-label="Search players">
    </div>

    @if ($players->isEmpty())
        <x-empty icon="players" :title="$total === 0 ? 'Nobody is online' : 'No players match the search'"/>
    @else
        <div class="card overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800">
                <thead>
                <tr>
                    <th class="table-head">Player</th>
                    <th class="table-head">Server</th>
                    <th class="table-head">Proxy</th>
                    <th class="table-head">Online since</th>
                    <th class="table-head"><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                @foreach ($players as $player)
                    <tr wire:key="player-{{ $player['uuid'] }}" class="hover:bg-slate-800/40">
                        <td class="table-cell">
                            <div class="flex items-center gap-3">
                                <img src="https://mc-heads.net/avatar/{{ $player['uuid'] }}/32" alt="" class="size-8 rounded" loading="lazy">
                                <div>
                                    <div class="font-medium text-white">{{ $player['name'] }}</div>
                                    <div class="font-mono text-xs text-slate-500">{{ $player['uuid'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="table-cell text-slate-300">
                            {{ $player['server'] ?? '–' }}
                            @if ($player['server_group'])
                                <span class="text-xs text-slate-500">({{ $player['server_group'] }})</span>
                            @endif
                        </td>
                        <td class="table-cell text-slate-400">{{ $player['proxy'] ?? '–' }}</td>
                        <td class="table-cell text-slate-400" title="{{ \Illuminate\Support\Carbon::createFromTimestampMs($player['login'])->toDateTimeString() }}">
                            {{ \Illuminate\Support\Carbon::createFromTimestampMs($player['login'])->diffForHumans() }}
                        </td>
                        <td class="table-cell text-right">
                            @if ($cloudUser?->can('MANAGE_PLAYERS'))
                                <div class="flex justify-end gap-1">
                                    <button type="button" wire:click="open(@js($player['uuid']), @js($player['name']), 'message')" class="btn btn-ghost btn-sm" title="Send message">
                                        <x-icon name="chat" class="size-4"/><span class="sr-only">Message {{ $player['name'] }}</span>
                                    </button>
                                    <button type="button" wire:click="open(@js($player['uuid']), @js($player['name']), 'kick')" class="btn btn-ghost btn-sm text-red-400 hover:text-red-300" title="Kick">
                                        <x-icon name="kick" class="size-4"/><span class="sr-only">Kick {{ $player['name'] }}</span>
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($selectedUuid !== null)
        <x-modal :title="($mode === 'kick' ? 'Kick ' : 'Message ').$selectedName" close="close">
            <form wire:submit="submit" class="space-y-4">
                <div>
                    <label for="player-text" class="label">{{ $mode === 'kick' ? 'Reason (optional)' : 'Message' }}</label>
                    <input id="player-text" wire:model="text" type="text" maxlength="256" class="input" autofocus
                           placeholder="{{ $mode === 'kick' ? 'Shown on the disconnect screen' : 'Supports MiniMessage, e.g. <green>Hello' }}">
                    @error('text') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="close" class="btn btn-ghost">Cancel</button>
                    <button type="submit" @class(['btn', 'btn-danger' => $mode === 'kick', 'btn-primary' => $mode !== 'kick'])>
                        {{ $mode === 'kick' ? 'Kick player' : 'Send message' }}
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
